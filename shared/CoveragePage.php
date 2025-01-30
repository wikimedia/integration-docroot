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
		// Get a list of directories with clover.xml
		$cloverFiles = glob( $this->coverageDir . '/*/clover.xml' );
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
			'Test coverage' => [
				'name' => 'Coverage home',
				'href' => '/cover/',
				'active' => false,
			],
			'MediaWiki extension test coverage' => [
				'name' => 'MediaWiki extensions',
				'href' => '/cover-extensions/',
				'active' => false,
			],
			'MediaWiki skin test coverage' => [
				'name' => 'MediaWiki skins',
				'href' => '/cover-skins/',
				'active' => false,
			],
		];

		$crumbs[$this->pageName]['active'] = true;
		$crumbs[$this->pageName]['href'] = '#';

		$breadcrumbs = <<<HTML
<ul class="wm-nav cover-nav">
HTML;
		foreach ( $crumbs as $crumb ) {

			$class = '';
			if ( $crumb['active'] ) {
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
		$this->addHtmlContent( '<ul class="wm-nav cover-list">' );
		$html = '';
		$clovers = [];
		foreach ( $cloverFiles as $cloverFile ) {
			$clover = file_get_contents( $cloverFile );
			if ( !$clover ) {
				// Race condition?
				continue;
			}
			$clovers[$cloverFile] = $this->parseClover( $clover ) + [
				'name' => basename( dirname( $cloverFile ) ),
				'mtime' => stat( $cloverFile )['mtime'],
			];
		}
		uasort(
			$clovers,
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
		foreach ( $clovers as $info ) {
			$dirName = htmlspecialchars( $info['name'] );
			$modifiedTime = date( DateTimeInterface::ATOM, $info['mtime'] );
			$percent = (string)round( $info['percent'] );

			$html .= <<<HTML
<li>
	<a class="cover-item" href="./$dirName/">
		<span class="cover-item-meter">
			<meter min="0" max="100" low="$lowThreshold" high="$highThreshold" optimum="99" value="$percent">$percent%</meter><span> $percent%</span>
		</span>
		<span>$dirName</span>
		<span class="cover-mtime">$modifiedTime</span>
	</a>
	<span class="cover-extra">(<a href="./$dirName/clover.xml">xml</a>)</span>
</li>
HTML;
		}
		$this->addHtmlContent( "$html</ul>" );
	}

	/**
	 * Get data out of the clover.xml file
	 *
	 * @param string $contents Contents of a clover.xml file
	 * @return array
	 */
	protected function parseClover( $contents ) {
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
		// TODO: Figure out how to get a more friendly name
		return [
			'percent' => round( $percent * 100, 2 )
		];
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
