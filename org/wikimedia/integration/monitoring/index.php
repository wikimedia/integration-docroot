<?php
require_once __DIR__ . '/../../../../shared/IntegrationPage.php';

$p = IntegrationPage::newFromPageName( 'Monitoring' );
$p->setDir( __DIR__ );
$p->setRootDir( dirname( __DIR__ ) );
$p->enableFooter();

$hosts = array(
	// Graphite supports wildcard target, but we want separate graphs in this case
	'integration-slave1001',
	'integration-slave1002',
	'integration-slave1003',
	'integration-slave1006',
	'integration-slave1007',
	'integration-slave1008',
	'integration-slave1009',
	'integration-puppetmaster'
);
$targets = array(
	'cpu' => array(
		'title' => 'CPU - idle',
		'query' => '.cpu.total.idle.value'
	),
	'mem' => array(
		'title' => 'Memory - free',
		'query' => '.memory.MemFree.value'
	),
	'disk' => array(
		'title' => 'Disk space - available',
		'query' => '.diskspace.*.byte_avail.value'
	),
);
$sections = array();
$menu = array();
$content = '<div id="contents"></div>';
foreach ( $targets as $targetId => $props ) {
	$hostTargets = array();

	foreach ( $hosts as $host ) {
		$hostTargets[] = 'integration.' . $host . $props['query'];

		$id = $host . $targetId;
		$title = "$host: {$props['title']}";
		$sections[ $id ] = array(
			'title' => $title,
			'graph' => array(
				'title' => $title,
				'target' => 'integration.' . $host . $props['query'],
			),
		);
	}

	$id = 'all' . $targetId;
	$title = "overview: {$props['title']}";
	$sections[ $id ] = array(
		'title' => $title,
		'graph' => array(
			'title' => $title,
			'target' => 'alias(sum(' . join( ',', $hostTargets ) . '),"' . $props['query'] . '")',
		),
	);
}

ksort( $sections );
foreach ( $sections as $sectionId => $section ) {
	$content .= '<h4 id="h-' . $sectionId . '">' . htmlspecialchars( $section['title'] ) . '</h4>';
	$menu[] = array( 'id' => $sectionId, 'label' => $section['title'] );
	$graph = $section['graph'];
	$content .= '<img width="800" height="250" src="//graphite.wmflabs.org/render/?'
		. htmlspecialchars(http_build_query(array(
			'title' => $graph['title'] . ' (24h)',
			'width' => 800,
			'height' => 250,
			'from' => '-24h',
			'target' => $graph['target'],
		)))
		. '">';
	$content .= '<br><img width="400" height="250" src="//graphite.wmflabs.org/render/?'
		. htmlspecialchars(http_build_query(array(
			'title' => $graph['title'] . ' (1week)',
			'width' => 400,
			'height' => 250,
			'from' => '-1week',
			'target' => $graph['target'],
		)))
		. '">';
	$content .= '<img width="400" height="250" src="//graphite.wmflabs.org/render/?'
		. htmlspecialchars(http_build_query(array(
			'title' => $graph['title'] . ' (1month)',
			'width' => 400,
			'height' => 250,
			'from' => '-1month',
			'target' => $graph['target'],
		)))
		. '">';
}

$menuExport = json_encode( $menu );
$content .= <<<HTML
<script>
var Wikimedia = {
	monitoringMenu: $menuExport
};
</script>
HTML;

$p->addHtmlContent( $content );
$p->addScript( 'monitoring.js' );
$p->flush();
