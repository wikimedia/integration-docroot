<?php
require_once( __DIR__ . '/../../../../shared/IntegrationPage.php' );

$p = IntegrationPage::newFromPageName( 'Zuul status page' );
$p->setDir( __DIR__ );
$p->setRootDir( dirname( __DIR__ ) );

$p->embedCSS('
/**
 * Zuul status
 */
.change {
	border: 1px solid #95c7db;
	margin-top: 10px;
	padding: 2px;
}

.change > .header {
	background: #E2ECEF;
	color: black;
	margin: -2px -2px 2px -2px;
	padding: 4px;
}

.change > .header > .changeid {
	float: right;
}

.job {
	display: block;
}

.pipeline {
	float: left;
	width: 25em;
	padding: 4px;
}

.pipeline > .header {
	background: #0000cc;
	color: white;
}

.arrow {
	text-align: center;
	font-size: 16pt;
	line-height: 1.0;
}

.result_success {
	color: #007f00;
}

.result_failure {
	color: #cf2f19;
}

.result_unstable {
	color: #e39f00;
}

a:link {
	color: #204A87;
}

#message p {
	margin: 0;
}

.alertbox {
	border: 1px solid #e5574d;
	background: #ffaba5;
	color: black;
	padding: 1em;
	font-size: 12pt;
	margin: 0pt;
}
');

$p->addHtmlContent('
<p>This is the status page for the Zuul daemon on Wikimedia infrastructure.</p>

<p>Queue lengths: <span id="trigger_event_queue_length"></span> events,
<span id="result_event_queue_length"></span> results.</p>

<div class="container">
	<div id="message"></div>
</div>

<div id="pipeline-container"></div>

<!-- TODO: add graphite
<div class="container" id="graph-container">
	<h2>Job statistics</h2>
</div>
-->
');

$p->addScript( 'status.js' );

$p->flush();