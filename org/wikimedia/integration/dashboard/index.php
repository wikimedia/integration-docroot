<?php
require_once( __DIR__ . '/../../../../shared/IntegrationPage.php' );

$p = IntegrationPage::newFromPageName( 'CI Dashboard' );
$p->setDir( __DIR__ );
$p->setRootDir( dirname( __DIR__ ) );
$p->enableFooter();

# Configuration
$build_status = array(
	# section => Jenkins ob name => job title
	'Beta cluster (eqiad)' => array(
		'beta-code-update-eqiad' => 'MW code',
		'beta-mediawiki-config-update-eqiad' => 'MW conf',
		'beta-update-databases-eqiad' => 'DB update',
		'beta-parsoid-update-eqiad' => 'Parsoid update',
		'beta-recompile-math-texvc' => 'texvc math',
	),
	'Beta cluster (pmtpa)' => array(
		'beta-code-update-pmtpa' => 'MW code',
		'beta-mediawiki-config-update-pmtpa' => 'MW conf',
		'beta-update-databases-pmtpa' => 'DB update',
		'beta-parsoid-update-pmtpa' => 'Parsoid update',
		'beta-recompile-math-texvc' => 'texvc math',
	),
	'MediaWiki' => array(
		'mediawiki-core-regression-master' => 'master',
		'mediawiki-core-regression-REL1_22' => 'REL1_22',
		'mediawiki-core-regression-REL1_21' => 'REL1_21',
		'mediawiki-core-regression-REL1_20' => 'REL1_20',
		'mediawiki-core-regression-REL1_19' => 'REL1_19',
		'mediawiki-core-doxygen-publish' => 'doxygen',
		'mediawiki-core-jsduck-publish' => 'jsduck',
	),
	'Misc' => array(
		'operations-puppet-doc' => 'puppet doc',
	),
);
$jenkins_url = 'https://integration.wikimedia.org/ci';

$content = '';
foreach( $build_status as $section => $status) {
	$content .= "\n<h3>$section</h3>\n<ul class=\"unstyled\">";
	foreach( $status as $jobname => $title ) {
		$content .= <<<HTML
<li><a href="$jenkins_url/job/$jobname/">
	<img width="108" src="$jenkins_url/buildStatus/icon?job=$jobname"></a>
	&#160;
	<a href="$jenkins_url/job/$jobname/">$title</a>
</a></li>
HTML;
	}
	$content .= "\n</ul>\n";

}



$p->addHtmlContent( $content );
$p->flush();
