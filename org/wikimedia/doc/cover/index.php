<?php
/**
 * Copyright (C) 2017 Kunal Mehta <legoktm@member.fsf.org>
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

require_once __DIR__ . '/../../../../shared/DocPage.php';

/**
 * Show a dashboard of code coverage results on the main index page
 */
class CoveragePage extends DocPage {

	/**
	 * Defaults from phpunit/src/Util/Configuration.php
	 */
	const COVERAGE_LOW = 50;
	const COVERAGE_HIGH = 90;

	/**
	 * Lists directory similar to dir index, but
	 * includes a progress bar
	 */
	public function handleCoverageIndex() {
		// Get list of directories with clover.xml
		$results = glob( __DIR__ . '/*/clover.xml' );
		$this->embedCSS( file_get_contents( __DIR__ . '/cover.css' ) );
		$this->addHtmlContent( '<ul class="nav nav-pills nav-stacked">' );
		$html = '';
		foreach ( $results as $clover ) {
			$info = $this->parseClover( $clover );
			$dirName = htmlspecialchars( basename( dirname( $clover ) ) );
			$percent = sprintf( '%.2f', $info['percent'] );
			$color = $this->getLevelColor( $info['percent'] );
			$html .= <<<HTML
<li>
	<a href="./$dirName/">
		<div class="progress-name">$dirName</div>
		<div class="progress">
			<div class="progress-bar progress-bar-$color" role="progressbar" aria-valuenow="$percent" 
				aria-valuemin="0" aria-valuemax="100" style="width: $percent%">
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
		$percent = (
			(int)$metrics['coveredmethods'] +
			(int)$metrics['coveredconditionals'] +
			(int)$metrics['coveredstatements'] +
			(int)$metrics['coveredelements']
		) / (
			(int)$metrics['methods'] +
			(int)$metrics['conditionals'] +
			(int)$metrics['statements'] +
			(int)$metrics['elements']
		);
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

/** @var CoveragePage $p */
$p = CoveragePage::newDirIndex( 'Coverage' );
$p->setRootDir( dirname( __DIR__ ) );
$p->handleCoverageIndex();
$p->handleDirIndex();
$p->flush();
