<?php
require_once __DIR__ . '/../../../../shared/IntegrationPage.php';

$p = IntegrationPage::newFromPageName( 'Monitoring' );
$p->setDir( __DIR__ );
$p->setRootDir( dirname( __DIR__ ) );
$p->enableFooter();

$hosts = array(
	// Graphite supports wildcard target, but we want separate graphs in this case
	'overview',
	'integration-slave1001',
	'integration-slave1002',
	'integration-slave1003',
	'integration-slave1006',
	'integration-slave1007',
	'integration-slave1008',
	'integration-slave1009',
	'integration-puppetmaster',
);
$graphConfigs = array(
	'cpu' => array(
		'title' => 'CPU',
		'targets' => array(
			'alias(color(stacked(HOST.cpu.total.user.value),"#3333bb"),"User")',
			'alias(color(stacked(HOST.cpu.total.nice.value),"#ffea00"),"Nice")',
			'alias(color(stacked(HOST.cpu.total.system.value),"#dd0000"),"System")',
			'alias(color(stacked(HOST.cpu.total.iowait.value),"#ff8a60"),"Wait I/O")',
			'alias(alpha(color(stacked(HOST.cpu.total.idle.value),"#e2e2f2"),0.4),"Idle")',
		),
	),
	'memory' => array(
		'title' => 'Memory',
		'targets' => array(
			'alias(color(stacked(HOST.memory.Inactive.value),"#5555cc"),"Inactive")',
			'alias(color(stacked(HOST.memory.Cached.value),"#33cc33"),"Cached")',
			'alias(color(stacked(HOST.memory.Buffers.value),"#99ff33"),"Buffers")',
			'alias(alpha(color(stacked(HOST.memory.MemFree.value),"#f0ffc0"),0.4),"Free")',
			'alias(color(stacked(HOST.memory.SwapCached.value),"#9900CC"),"Swap")',
			'alias(color(HOST.memory.MemTotal.value,"red"),"Total")',
		),
		'overview' => array(
			'alias(color(stacked(sum(HOST.memory.Inactive.value)),"#5555cc"),"Inactive")',
			'alias(color(stacked(sum(HOST.memory.Cached.value)),"#33cc33"),"Cached")',
			'alias(color(stacked(sum(HOST.memory.Buffers.value)),"#99ff33"),"Buffers")',
			'alias(alpha(color(stacked(sum(HOST.memory.MemFree.value)),"#f0ffc0"),0.4),"Free")',
			'alias(color(stacked(sum(HOST.memory.SwapCached.value)),"#9900CC"),"Swap")',
			'alias(color(sum(HOST.memory.MemTotal.value),"red"),"Total")',
		),
	),
	'disk' => array(
		'title' => 'Disk space',
		'targets' => array(
			'aliasByNode(HOST.diskspace.*.byte_avail.value,-3,-2)',
		),
		'overview' => array(
			'alias(stacked(sum(HOST.diskspace.*.byte_avail.value)),"byte_avail")',
		),
	),
);
$sections = array();
$menu = array();
$content = '<div id="contents"></div>';
foreach ( $hosts as $hostName ) {
	$host = $hostName === 'overview' ? '*' : $hostName;
	$content .= '<h3 id="h-' . htmlspecialchars( $host ) . '">' . htmlspecialchars( $hostName ) . '</h3>';
	$menu[] = array( 'value' => "h-$host", 'label' => $hostName );
	foreach ( $graphConfigs as $graphID => $graph ) {
		$content .= '<h4 id="h-' . htmlspecialchars( "$host-$graphID" ) . '">' . htmlspecialchars( "$hostName: {$graph['title']}" ) . '</h4>';
		$menu[] = array( 'value' => "h-$host-$graphID", 'label' => "$hostName: {$graph['title']}" );
		$targetQuery = '';

		if ( $hostName !== 'overview' ) {
			$targets = $graph['targets'];
		} elseif ( isset( $graph['overview'] ) ) {
			$targets = $graph['overview'];
		} else {
			// Default overview: sum() the source values
			$targets = array_map( function ( $target ) {
				return preg_replace( '/HOST([^\)]+)/', 'sum(HOST$1)', $target );
			}, $graph['targets'] );
		}

		foreach ( $targets as $target ) {
			$targetQuery .= '&target=' . urlencode( str_replace( 'HOST', "integration.$host", $target ) );
		}

		$content .= '<img width="800" height="250" src="//graphite.wmflabs.org/render/?'
			. htmlspecialchars(http_build_query(array(
				'title' => $graph['title'] . ' last day',
				'width' => 800,
				'height' => 250,
				'from' => '-24h',
				'hideLegend' => 'false',
				'uniqueLegend' => 'true',
			)) . $targetQuery )
			. '">';
		$content .= '<br><img width="400" height="250" src="//graphite.wmflabs.org/render/?'
			. htmlspecialchars(http_build_query(array(
				'title' => $graph['title'] . ' last week',
				'width' => 400,
				'height' => 250,
				'from' => '-1week',
				'hideLegend' => 'false',
				'uniqueLegend' => 'true',
			)) . $targetQuery )
			. '">';
		$content .= '<img width="400" height="250" src="//graphite.wmflabs.org/render/?'
			. htmlspecialchars(http_build_query(array(
				'title' => $graph['title'] . ' last month',
				'width' => 400,
				'height' => 250,
				'from' => '-1month',
				'hideLegend' => 'false',
				'uniqueLegend' => 'true',
			)) . $targetQuery )
			. '">';
	}
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
