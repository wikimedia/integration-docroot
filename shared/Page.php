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

	/** @return string[] */
	protected static function getRequestPath() {
		if ( isset( $_SERVER['REDIRECT_URL'] ) ) {
			// Rewritten by Apache to e.g. dir.php
			$path = $_SERVER['REDIRECT_URL'];
		} elseif ( isset( $_SERVER['SCRIPT_NAME'] ) ) {
			// Direct inclusion from e.g. cover/index.php
			$path = dirname( $_SERVER['SCRIPT_NAME'] ) . '/';
		}
		if ( !$path || !isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
			self::error( 'Invalid context.' );
			return;
		}
		// Use realpath() to prevent escalation through e.g. "../"
		// Note: realpath() also normalises paths to have no trailing slash
		$realPath = realpath( $_SERVER['DOCUMENT_ROOT'] . $path );
		if ( !$realPath || strpos( $realPath, $_SERVER['DOCUMENT_ROOT'] ) !== 0 ) {
			// Path escalation. Should be impossible as Apache normalises this.
			self::error( 'Invalid context.' );
			return;
		}
		if ( substr( $path, -1 ) === '/' ) {
			$realPath .= '/';
		}
		$urlPath = substr( $realPath, strlen( rtrim( $_SERVER['DOCUMENT_ROOT'], '/' ) ) );

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

		$content = @file_get_contents( $file );
		if ( $content === false ) {
			throw new RuntimeException( 'Unreadable file ' . basename( $file ) );
		}

		$this->content .= trim( $content );
	}

	protected function getNavItems() {
		return [
			'https://gerrit.wikimedia.org/r/' => 'Gerrit',
			'https://integration.wikimedia.org/' => 'Integration',
			'https://doc.wikimedia.org/' => 'Documentation',
		];
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

	protected function getDirIndexDirectories() {
		return glob( $this->getDir() . "/*", GLOB_ONLYDIR );
	}

	public function handleDirIndex() {
		if ( $this->flags & self::INDEX_PREFIX ) {
			if ( $this->flags & self::INDEX_PARENT_PREFIX && $this->getUrlPath() !== '/' ) {
				$this->pageName .= basename( dirname( $this->getDir() ) ) . ': ' . basename( $this->getDir() );
			} else {
				$this->pageName .= basename( $this->getDir() );
			}
		}

		$subDirPaths = $this->getDirIndexDirectories();
		if ( $this->flags & self::INDEX_ALLOW_SKIP ) {
			if ( count( $subDirPaths ) === 1 ) {
				header( 'Location: ./' . basename( $subDirPaths[0] ) . '/' );
				exit;
			}
		}

		if ( count( $subDirPaths ) === 0 ) {
			$this->addHtmlContent( '<div class="alert alert-warning" role="alert"><strong>Empty directory!</strong></div>' );
		} else {
			$this->addHtmlContent( '<ul class="nav nav-pills nav-stacked">' );
			foreach ( $subDirPaths as $path ) {
				$dirName = basename( $path );
				$this->addHtmlContent( '<li><a href="./' . htmlspecialchars( $dirName ) . '/">'
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
