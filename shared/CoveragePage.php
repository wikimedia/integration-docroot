<?php
/**
 * Copyright (C) 2017-2018 Kunal Mehta <legoktm@member.fsf.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

/**
 * Show a dashboard of code coverage results on the main index page
 *
 * @internal
 */
class CoveragePage extends DocPage {

	private $coverageDir;

	/**
	 * Defaults from phpunit/src/Util/Configuration.php
	 */
	private const COVERAGE_LOW = 50;
	private const COVERAGE_HIGH = 90;

	/**
	 * @param string $path Path relative to WMF_DOC_PATH env variable
	 */
	public function setCoverageDir( $path ) {
		if ( getenv( 'WMF_DOC_PATH' ) === false ) {
			self::error( '$WMF_DOC_PATH must be properly set' );
		}
		$coverageDir = self::resolvePath( getenv( 'WMF_DOC_PATH' ), $path );
		if ( !$coverageDir ) {
			self::error( 'Coverage path not found' );
		}
		$this->coverageDir = $coverageDir;
	}

	/**
	 * Lists directory similar to dir index, but
	 * includes a progress bar
	 */
	public function handleCoverageIndex() {
		// Get a list of coverage report directories
		$cloverFiles = glob( $this->coverageDir . '/*/clover.xml' );
		$lcovFiles = glob( $this->coverageDir . '/*/lcov.info' );

		$css = file_get_contents( __DIR__ . '/cover.css' );
		if ( $css ) {
			$this->embedCSS( $css );
		}

		$intro = <<<HTML
<blockquote>
<p>
Test coverage refers to measuring how much a software program has been
exercised by tests. Coverage is a means of determining the rigour with
which the question underlying the test has been answered.
</p>
– <a href="https://en.wikipedia.org/w/index.php?title=Fault_coverage&oldid=675795947">Wikipedia</a>
</blockquote>
HTML;
		$this->addHtmlContent( $intro );

		$crumbs = [
			[
				'name' => 'Coverage home',
				'href' => '/cover/',
			],
			[
				'name' => 'MediaWiki extensions',
				'href' => '/cover-extensions/',
			],
			[
				'name' => 'MediaWiki skins',
				'href' => '/cover-skins/',
			],
		];

		$breadcrumbs = <<<HTML
<ul class="wm-nav cover-nav">
HTML;
		foreach ( $crumbs as $crumb ) {
			$isActive = (
				basename( $this->coverageDir ) === basename( $crumb['href'] )
			);
			$class = '';
			if ( $isActive ) {
				$class = ' class="wm-nav-item-active"';
			}
			$breadcrumbs .= <<<HTML
	<li><a href="{$crumb['href']}"{$class}>{$crumb['name']}</a></li>
HTML;
		}

		$breadcrumbs .= <<<HTML
</ul>
HTML;

		$this->addHtmlContent( $breadcrumbs );

		$buttons = [
			'name' => [
				'text' => 'Sort by name',
				'class' => [ 'wm-btn' ],
				'href' => "./?sort=name"
			],
			'percent' => [
				'text' => 'Sort by coverage percentage',
				'class' => [ 'wm-btn' ],
				'href' => "./?sort=percent"
			],
			'mtime' => [
				'text' => 'Sort by modified time',
				'class' => [ 'wm-btn' ],
				'href' => "./?sort=mtime"
			],
		];

		$sortKey =
			array_key_exists(
				(string)( $_GET['sort'] ?? '' ), $buttons
			)
			? $_GET['sort']
			: 'name';

		// Sort in the alternative direction next time
		if ( ( $_GET['dir'] ?? 'asc' ) === 'asc' ) {
			$sortDirButton = 'desc';
			$sortAsc = true;
		} else {
			$sortDirButton = 'asc';
			$sortAsc = false;
		}

		$buttons[$sortKey]['class'][] = 'wm-btn-active';
		$buttons[$sortKey]['href'] .= '&dir=' . $sortDirButton;

		$sortNav = '';
		foreach ( $buttons as $button ) {
			$class = implode( ' ', $button['class'] );
			$sortNav .= <<<HTML
		<a role="button" class="{$class}" href="{$button['href']}">{$button['text']}</a>
HTML;
		}

		$this->addHtmlContent( "<hr>$sortNav" );
		$this->addHtmlContent( '<table class="cover-list">' );
		$html = '';

		// Combined list of all coverage file formats
		// Keyed by sub directory name (which is expected to serve the HTML report)
		// so that we don't list the same project multiple times,
		// if it publishes multiple formats.
		$reports = [];

		foreach ( $cloverFiles as $file ) {
			$data = @file_get_contents( $file );
			if ( !$data ) {
				// Race condition?
				continue;
			}
			$subDir = basename( dirname( $file ) );
			$reports["./$subDir/"] = [
				'percent' => $this->parseClover( $data ),
				'name' => $subDir,
				'mtime' => stat( $file )['mtime'],
				'file' => "./$subDir/" . basename( $file ),
			];
		}
		foreach ( $lcovFiles as $file ) {
			$data = @file_get_contents( $file );
			if ( !$data ) {
				// Race condition?
				continue;
			}
			$subDir = basename( dirname( $file ) );
			$reports["./$subDir/"] = [
				'percent' => $this->parseLcov( $data ),
				'name' => $subDir,
				'mtime' => stat( $file )['mtime'],
				'file' => "./$subDir/" . basename( $file ),
			];
		}

		uasort(
			$reports,
			static function ( $a, $b ) use ( $sortKey, $sortAsc ) {
				if ( $a[$sortKey] === $b[$sortKey] ) {
					return 0;
				}

				return $sortAsc
					? ( ( $a[$sortKey] < $b[$sortKey] ) ? -1 : 1 )
					: ( ( $a[$sortKey] > $b[$sortKey] ) ? -1 : 1 );
			}
		);

		$lowThreshold = self::COVERAGE_LOW;
		$highThreshold = self::COVERAGE_HIGH;
		foreach ( $reports as $subDir => $info ) {
			$modifiedTime = date( 'Y-m-d H:i \\G\\M\\T', $info['mtime'] );

			$dirLinkHtml = htmlspecialchars( $subDir );
			$pcHtml = htmlspecialchars( (string)$info['percent'] );
			$dirNameHtml = htmlspecialchars( $info['name'] );
			$fileLinkHtml = htmlspecialchars( $info['file'] );
			$fileNameHtml = htmlspecialchars( basename( $info['file'] ) );
			$html .= <<<HTML
<tr>
	<td class="cover-item-meter"><a href="$dirLinkHtml">
		<meter min="0" max="100" low="$lowThreshold" high="$highThreshold" optimum="99" value="$pcHtml">$pcHtml%</meter>
		<span class="cover-item-meter-pc">$pcHtml%</span>
	</a></td>
	<td class="cover-item-name"><a href="$dirLinkHtml">$dirNameHtml</a></td>
	<td class="cover-item-mtime">$modifiedTime</td>
	<td class="cover-item-extra">(<a href="$fileLinkHtml">$fileNameHtml</a>)</td>
</tr>
HTML;
		}
		$this->addHtmlContent( "$html</table>" );
	}

	/**
	 * Extract data from a clover.xml file
	 *
	 * @param string $contents
	 * @return float Overall line coverage percentage
	 */
	public function parseClover( string $contents ): float {
		$types = [ 'methods', 'conditionals', 'statements', 'elements' ];
		$total = 0;
		$xml = new SimpleXMLElement( $contents );
		$metrics = $xml->project->metrics;
		// A proper clover.xml file will have all the four types, but
		// we're also converting other types into clover.xml, that don't
		// have all the keys we expect. Using isset() should make this safe.
		foreach ( $types as $type ) {
			if ( isset( $metrics[$type] ) ) {
				$total += (int)$metrics[$type];
			}
		}
		if ( $total === 0 ) {
			// Avoid division by 0 warnings, and treat 0/0 as 100%
			// to match the PHPUnit behavior
			$percent = 1;
		} else {
			$covered = 0;
			foreach ( $types as $type ) {
				if ( isset( $metrics["covered$type"] ) ) {
					$covered += (int)$metrics["covered$type"];
				}

			}
			$percent = $covered / $total;
		}
		return round( $percent * 100 );
	}

	/**
	 * Extract data from an lcov.info file
	 *
	 * @param string $contents
	 * @return float Overall line coverage percentage
	 */
	public function parseLcov( string $contents ): float {
		$total = 0;
		$covered = 0;
		foreach ( explode( "\n", $contents ) as $line ) {
			// Check for "LF" (lines found) and "LH" (lines hit)
			// https://manpages.debian.org/bullseye/lcov/geninfo.1.en.html#FILES
			if ( str_starts_with( $line, 'LF:' ) ) {
				$total += (int)substr( $line, 3 );
			} elseif ( str_starts_with( $line, 'LH:' ) ) {
				$covered += (int)substr( $line, 3 );
			}
		}

		return $total > 0
			? round( ( $covered / $total ) * 100 )
			: 0;
	}

	/**
	 * Exclude directories that already have been listed by handleCoverageIndex()
	 *
	 * @param string|null $parent
	 * @return string[]
	 */
	protected function getDirIndexContents( $parent = null ) {
		$entries = parent::getDirIndexContents( $parent );
		$noClover = [];
		foreach ( $entries as $entry ) {
			if ( !is_dir( $entry ) || !file_exists( "$entry/clover.xml" ) ) {
				$noClover[] = $entry;
			}
		}

		return $noClover;
	}

	/**
	 * Only show the directory index if there are actually
	 * directories left over that didn't have clover.xml files.
	 */
	public function handleDirIndex( $dir, $urlPath ) {
		if ( $this->getDirIndexContents( $dir ) ) {
			parent::handleDirIndex( $dir, $urlPath );
		}
	}

	protected function isNavActive( $href ) {
		// Also mark /cover/ as active when on /cover-extensions/
		return $this->getUrlPath() === $href || $href === '/cover/';
	}
}
