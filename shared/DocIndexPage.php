<?php

class DocIndexPage extends DocPage {

	public function getDir() {
		return dirname( self::getRequestPath()['fileDir'] );
	}

	/**
	 * Exclude internal directories
	 *
	 * @param string|null $parent
	 * @return string[]
	 */
	protected function getDirIndexDirectories( $parent = null ) {
		$exclude = [
			'cover',
			'cover-extensions',
			'index',
			'lib',
		];

		$dirs = [];
		foreach ( parent::getDirIndexDirectories( $parent ) as $dir ) {
			if ( !in_array( basename( $dir ), $exclude, true ) ) {
				$dirs[] = $dir;
			}
		}
		return $dirs;
	}
}
