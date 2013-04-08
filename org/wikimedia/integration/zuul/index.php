<?php
require_once( __DIR__ . '/../../../../shared/IntegrationPage.php' );

$p = IntegrationPage::newFromPageName( 'Zuul Status' );
$p->setDir( __DIR__ );
$p->setRootDir( dirname( __DIR__ ) );

$p->embedCSS('
/**
 * Zuul status
 */
.zuul-container {
	transition-property: opacity, background-color;
	transition-duration: 1s;
	transition-timing-function: ease-in-out;
	clear: both;
	opacity: 0;
	cursor: progress;
	min-height: 200px;
	background-color: #f8ffaa;
}

.zuul-container-ready {
	opacity: 1;
	cursor: auto;
	background-color: #fff;
}

.zuul-spinner,
.zuul-spinner:hover /* override bootstrap .btn:hover */ {
	opacity: 0;
	transition: opacity 3s ease-out;
	cursor: default;
	pointer-events: none;
}

.zuul-spinner-on,
.zuul-spinner-on:hover {
	opacity: 1;
	transition-duration: 0.4s;
	cursor: progress;
}

.zuul-change-arrow {
	text-align: center;
	font-size: 16pt;
	line-height: 1.0;
}

.zuul-change-id {
	text-transform: none;
	float: right;
}

.zuul-change-job a {
	overflow: auto;
}

.zuul-result {
	text-shadow: none;
	font-weight: normal;
	float: right;
	background-color: #E9E9E9;
	color: #555;
}

.zuul-result.label-success {
	background-color: #CDF0CD;
	color: #468847;
}

.zuul-result.label-important {
	background-color: #F1DBDA;
	color: #B94A48;
}

.zuul-result.label-warning {
	background-color: #F3E6D4;
	color: #F89406;
}

.zuul-msg-wrap {
	max-height: 150px;
	overflow: hidden;
}

.zuul-container-ready .zuul-msg-wrap {
	transition: max-height 1s ease-in;
}

.zuul-msg-wrap-off {
	max-height: 0;
}

.zuul-msg p {
	margin: 0;
}
');

$p->addHtmlContent('
<p>Real-time status monitor of Zuul, the pipeline manager between Gerrit and Jenkins. <a href="https://www.mediawiki.org/wiki/Continuous_integration/Zuul">more info &raquo;</a></p>

<div class="zuul-container" id="zuul-container">
	<p>Queue lengths: <span id="zuul-eventqueue-length">..</span> events, <span id="zuul-resulteventqueue-length">..</span> results.</p>
	<div id="zuul-pipelines" class="row"></div>
	<!-- TODO: add graphite
	<h2>Job statistics</h2>
	-->
</div>
');

$p->enableFooter();
$p->addScript( 'status.js' );

$p->flush();
