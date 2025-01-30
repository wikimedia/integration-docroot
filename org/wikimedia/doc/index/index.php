<?php
require_once __DIR__ . '/../../../../shared/autoload.php';

$p = DocIndexPage::newDirIndex( 'Documentation index' );
if ( getenv( 'WMF_DOC_PATH' ) === false ) {
	DocIndexPage::error( '$WMF_DOC_PATH must be properly set' );
} else {
	$indexDir = DocIndexPage::resolvePath( getenv( 'WMF_DOC_PATH' ), '/' );
	if ( !$indexDir ) {
		DocIndexPage::error( '$WMF_DOC_PATH path is invalid' );
	} else {
		// @phan-suppress-next-line PhanPossiblyFalseTypeArgument
		$p->handleDirIndex( getenv( 'WMF_DOC_PATH' ), '/' );
		$p->flush();
	}
}
