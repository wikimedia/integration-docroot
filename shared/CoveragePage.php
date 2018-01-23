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

require_once __DIR__ . '/DocPage.php';

/**
 * Show a dashboard of code coverage results on the main index page
 */
class CoveragePage extends DocPage {

	private $coverageDir;

	/**
	 * Defaults from phpunit/src/Util/Configuration.php
	 */
	const COVERAGE_LOW = 50;
	const COVERAGE_HIGH = 90;

	public function setCoverageDir( $path ) {
		$this->coverageDir = $path;
		$this->setRootDir( dirname( $path ) );
	}

	/**
	 * Lists directory similar to dir index, but
	 * includes a progress bar
	 */
	public function handleCoverageIndex() {
		// Get list of directories with clover.xml
		$results = glob( $this->coverageDir . '/*/clover.xml' );
		$this->embedCSS( file_get_contents( __DIR__ . '/cover.css' ) );

		$sort = isset( $_GET['sort'] ) ? (string)$_GET['sort'] : null;

		if ( $this->pageName === 'Test coverage' ) {
			$href = $this->fixNavUrl( '/cover-extensions/' );
			$breadcrumbs = <<<HTML
<ul class="nav nav-tabs">
	<li class="active"><a href="#">Coverage home</a></li>
	<li><a href="$href">MediaWiki extensions</a></li>
</ul>
HTML;
		} else {
			$href = $this->fixNavUrl( '/cover/' );
			$breadcrumbs = <<<HTML
<ul class="nav nav-tabs">
	<li><a href="$href">Coverage home</a></li>
	<li class="active"><a href="#">MediaWiki extensions</a></li>
</ul>
HTML;
		}
		$this->addHtmlContent( $breadcrumbs );

		$intro = <<<HTML
<blockquote>
<p>
Test coverage refers to measuring how much a software program has been
exercised by tests. Coverage is a means of determining the rigour with
which the question underlying the test has been answered.
</p>
<footer>
<a href="https://en.wikipedia.org/w/index.php?title=Fault_coverage&oldid=675795947">Wikipedia</a>
</footer>
</blockquote>
HTML;
		$this->addHtmlContent( $intro );

		if ( $sort === 'cov' ) {
			$sortNav = <<<HTML
<div class="btn-group btn-group-sm">
		<a class="btn btn-default" href="./">Sort by name</a>
		<button type="button" class="btn btn-default active">Sort by coverage percentage</button>
</div>
HTML;
		} else {
			$sortNav = <<<HTML
<div class="btn-group btn-group-sm">
		<button type="button" class="btn btn-default active">Sort by name</button>
		<a class="btn btn-default" href="./?sort=cov">Sort by coverage percentage</a>
</div>
HTML;
		}

		$this->addHtmlContent( $sortNav );
		$this->addHtmlContent( '<ul class="nav nav-pills nav-stacked cover-list">' );
		$html = '';
		$clovers = [];
		foreach ( $results as $clover ) {
			$clovers[$clover] = $this->parseClover( $clover );
		}
		if ( isset( $_GET['sort'] ) && $_GET['sort'] === 'cov' ) {
			// Order by coverage, ascending
			uasort( $clovers, function ( $a, $b ) {
				if ( $a['percent'] === $b['percent'] ) {
					return 0;
				}
				return ( $a['percent'] < $b['percent'] ) ? -1 : 1;
			} );
		}
		foreach ( $clovers as $clover => $info ) {
			$dirName = htmlspecialchars( basename( dirname( $clover ) ) );
			$percent = (string)round( $info['percent'] );
			$color = $this->getLevelColor( $info['percent'] );
			$minWidth = $percent >= 10 ? '3em' : '2em';
			$html .= <<<HTML
<li>
	<a class="cover-item" href="./$dirName/">
		<span>$dirName</span>
		<div class="progress">
			<div class="progress-bar progress-bar-$color" role="progressbar" aria-valuenow="$percent" 
				aria-valuemin="0" aria-valuemax="100" style="min-width: $minWidth; width: $percent%">
				$percent%
			</div>
		</div>
	</a>
</li>
HTML;
		}
		$this->addHtmlContent( "$html</ul>" );
	}

	/**
	 * Get data out of the clover.xml file
	 *
	 * @param string $fname
	 * @return array|bool false on failure
	 */
	protected function parseClover( $fname ) {
		$contents = file_get_contents( $fname );
		if ( !$contents ) {
			// Race condition?
			return false;
		}

		$types = [ 'methods', 'conditionals', 'statements', 'elements' ];
		$total = 0;
		$xml = new SimpleXMLElement( $contents );
		$metrics = $xml->project->metrics;
		// A proper clover.xml file will have all of the four types, but
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
			'percent' => $percent * 100,
		];
	}

	/**
	 * Get the CSS class for the progress bar,
	 * based on code in PHP_CodeCoverage
	 *
	 * @param float $percent
	 * @return string
	 */
	protected function getLevelColor( $percent ) {
		if ( $percent <= self::COVERAGE_LOW ) {
			return 'danger';
		} elseif ( $percent >= self::COVERAGE_HIGH ) {
			return 'success';
		} else {
			// In the middle
			return 'warning';
		}
	}

	/**
	 * Exclude directories that already have been listed
	 *
	 * @return string[]
	 */
	protected function getDirIndexDirectories() {
		$dirs = parent::getDirIndexDirectories();
		$noClover = [];
		foreach ( $dirs as $dir ) {
			if ( !file_exists( "$dir/clover.xml" ) ) {
				$noClover[] = $dir;
			}
		}

		return $noClover;
	}

	/**
	 * Only show the directory index if there are actually
	 * directories left over that didn't have clover.xml files.
	 */
	public function handleDirIndex() {
		if ( $this->getDirIndexDirectories() ) {
			parent::handleDirIndex();
		}
	}
}
