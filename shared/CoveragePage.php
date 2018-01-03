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
		$this->addHtmlContent( '<ul class="nav nav-pills nav-stacked cover-list">' );
		$html = '';
		foreach ( $results as $clover ) {
			$info = $this->parseClover( $clover );
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

		$xml = new SimpleXMLElement( $contents );
		$metrics = $xml->project->metrics;
		$total = (int)$metrics['methods'] +
			(int)$metrics['conditionals'] +
			(int)$metrics['statements'] +
			(int)$metrics['elements'];
		if ( $total === 0 ) {
			// Avoid division by 0 warnings, and treat 0/0 as 100%
			// to match the PHPUnit behavior
			$percent = 1;
		} else {
			$percent = (
					(int)$metrics['coveredmethods'] +
					(int)$metrics['coveredconditionals'] +
					(int)$metrics['coveredstatements'] +
					(int)$metrics['coveredelements']
				) / $total;
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
