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
	protected function getDirIndexContents( $parent = null ) {
		$exclude = [
			'cover',
			'cover-extensions',
			'cover-skins',
			'index',
			'lib',
		];

		$entries = [];
		foreach ( parent::getDirIndexContents( $parent ) as $entry ) {
			if ( !in_array( basename( $entry ), $exclude, true ) ) {
				$entries[] = $entry;
			}
		}
		return $entries;
	}
}
