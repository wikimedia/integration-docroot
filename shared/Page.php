<?php

class Page {
	/** If the directory only has one subdirectory, redirect to it. */
	const INDEX_ALLOW_SKIP = 1;

	/** Append the directory name to the page name. */
	const INDEX_PREFIX = 2;

	/** Append the parent directory to the page name (if INDEX_PREFIX is on). */
	const INDEX_PARENT_PREFIX = 4;

	protected $site = 'Wikimedia';
	protected $pageName = false;
	protected $flags = 0;
	protected $stylesheets = [];
	protected $embeddedCSS = [];
	protected $content = '';
	protected $scripts = [];

	/**
	 * @param string $pageName
	 */
	public static function newFromPageName( $pageName ) {
		$p = new static();
		$p->pageName = $pageName;
		return $p;
	}

	public static function newIndex() {
		$p = new static();
		$p->pageName = false;
		return $p;
	}

	/**
	 * @param string $pageName
	 * @param int $flags
	 */
	public static function newDirIndex( $pageName, $flags = 0 ) {
		$p = new static();
		$p->pageName = $pageName;
		$p->flags = $flags;
		return $p;
	}

	/**
	 * Resolve $path relatively to $base and ensure it is container in $base
	 *
	 * Use realpath() to prevent escalation through e.g. "../"
	 * Note: realpath() also normalises paths to have no trailing slash.
	 */
	public static function resolvePath( $base, $path ) {
		$realPath = realpath( $base . $path );
		if ( !$realPath || strpos( $realPath, realpath( $base ) ) !== 0 ) {
			return false;
		}
		return $realPath;
	}

	/** @return string[] */
	protected static function getRequestPath() {
		if ( isset( $_SERVER['REDIRECT_URL'] ) ) {
			// Rewritten by Apache to e.g. dir.php
			$path = $_SERVER['REDIRECT_URL'];
		} elseif ( isset( $_SERVER['SCRIPT_NAME'] ) ) {
			// Direct inclusion from e.g. cover/index.php
			$path = dirname( $_SERVER['SCRIPT_NAME'] ) . '/';
		}

		$realPath = self::resolvePath( $_SERVER['DOCUMENT_ROOT'], $path );
		if ( $realPath ) {
			$realBase = realpath( $_SERVER['DOCUMENT_ROOT'] );
		} elseif ( getenv( 'WMF_DOC_PATH' ) !== false ) {
			// Fall back to CI published files
			$realPath = self::resolvePath( getenv( 'WMF_DOC_PATH' ), $path );
			$realBase = realpath( getenv( 'WMF_DOC_PATH' ) );
		}

		if ( !$realPath || !$realBase ) {
			// Path escalation. Should be impossible as Apache normalises this.
			self::error( 'Invalid context path.' );
			return;
		}

		if ( substr( $realPath, -1 ) !== '/' ) {
			$realPath .= '/';
		}
		$urlPath = substr( $realPath, strlen( $realBase ) );

		return [
			'urlPath' => $urlPath,
			'fileDir' => $realPath,
		];
	}

	/**
	 * @return string URL Path from document root to current url
	 */
	public function getUrlPath() {
		return self::getRequestPath()['urlPath'];
	}

	public function getDir() {
		return self::getRequestPath()['fileDir'];
	}

	/**
	 * @param string $cssText
	 */
	public function embedCSS( $cssText ) {
		$this->embeddedCSS[] = trim( $cssText, "\n" );
	}

	/**
	 * @param string $src Path to script (may be relative)
	 */
	public function addScript( $src ) {
		$this->scripts[] = $src;
	}

	/**
	 * @param string $src Path to script (may be relative)
	 */
	public function addStylesheet( $src ) {
		$this->stylesheets[] = $src;
	}

	/**
	 * @param string $html
	 */
	public function addHtmlContent( $html ) {
		$this->content .= trim( $html );
	}

	/**
	 * @param string $file Absolute path
	 */
	public function addHtmlFile( $file ) {
		if ( substr( $file, 0, 1 ) !== '/' ) {
			throw new InvalidArgumentException( 'Illegal path' );
		}

		$content = file_get_contents( $file );
		if ( $content === false ) {
			throw new RuntimeException( 'Unreadable file ' . basename( $file ) );
		}

		$this->content .= trim( $content );
	}

	protected function isNavActive( $href ) {
		return $this->getUrlPath() === $href;
	}

	protected function getNavItems() {
		// Stub
		return [];
	}

	/**
	 * Get submenu items for the current page.
	 *
	 * @return array
	 */
	protected function getSubnavItems() {
		// Stub
		return [];
	}

	public function flush() {
?><!DOCTYPE html>
<html dir="ltr" lang="en-US">
<meta charset="utf-8">
<a role="banner" href="/" title="Navigate to home of <?php echo htmlentities( $this->site ); ?>"><?php echo htmlentities( $this->site ); ?></a>
<main role="main">
<?php
	if ( $this->pageName ) {
		echo '<h1>' . htmlentities( $this->pageName ) . '</h1>';
	}
	echo '<article>' . $this->content . '</article>';
?>
</main>
</html>
<?php
	}

	protected function getDirIndexDirectories( $dir = null ) {
		$dir = $dir !== null ? $dir : $this->getDir();
		return glob( $dir . "/*", GLOB_ONLYDIR );
	}

	/**
	 *
	 * To simply list the directories for the current request url:
	 *
	 *     $p->handleDirIndex( $p->getDir(), $p->getUrlPath() );
	 *
	 * @param string $dir Full path to directory on disk
	 * @param string $urlPath URL path prefix, to that same directory
	 */
	public function handleDirIndex( $dir, $urlPath ) {
		if ( $this->flags & self::INDEX_PREFIX ) {
			if ( $this->flags & self::INDEX_PARENT_PREFIX && $urlPath !== '/' ) {
				$this->pageName .= basename( dirname( $dir ) ) . ': ' . basename( $dir );
			} else {
				$this->pageName .= basename( $dir );
			}
		}

		$subDirPaths = $this->getDirIndexDirectories( $dir );
		if ( $this->flags & self::INDEX_ALLOW_SKIP ) {
			if ( count( $subDirPaths ) === 1 ) {
				header( "Location: {$urlPath}" . basename( $subDirPaths[0] ) . '/' );
				exit;
			}
		}

		if ( count( $subDirPaths ) === 0 ) {
			$this->addHtmlContent( '<div class="wm-alert wm-alert-error" role="alert"><strong>Empty directory!</strong></div>' );
		} else {
			$this->addHtmlContent( '<ul class="wm-nav">' );
			foreach ( $subDirPaths as $path ) {
				$dirName = basename( $path );
				$this->addHtmlContent( '<li><a href="' . htmlspecialchars( "{$urlPath}{$dirName}/" ) . '">'
					. htmlspecialchars( $dirName )
					. '</a>'
				);
			}
			$this->addHtmlContent( '</ul>' );
		}
	}

	public static function error( $msg, $statusCode = 500 ) {
		$statusCode = (int)$statusCode;
		http_response_code( $statusCode );
		echo "<!DOCTYPE html><title>Error $statusCode</title><p>"
			. htmlspecialchars( $msg )
			. '</p>';
		exit;
	}

	private function __construct() {
	}
}
