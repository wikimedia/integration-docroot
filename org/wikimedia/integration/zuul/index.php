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
	opacity: 0.4;
	transition: opacity 1.4s ease-out;
	min-width: 6em;
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
}

.zuul-change-progress {
	width: 4em;
	float: right;
	margin-top:0.30em;
}

progress,
progress[role] {
	appearance: none;
	-moz-appearance: none;
	-webkit-appearance: none;
	border: none;
	background-image: none;
}

/** IE10 */
progress {
	color: #069;
}
/** Firefox, maybe IE10 as well  */
progress {
	background: #C0C0C0;
}

/** Webkit */
progress::-webkit-progress-value {
	background: #069;
}
progress::-webkit-progress-bar {
	background: #C0C0C0;
}

/** Firefox */
progress::-moz-progress-bar {
	background: #069;
}

.zuul-result {
	text-shadow: none;
	font-weight: normal;
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

/**
 * Zuul status (bootstrap layout)
 */

.zuul-change-id {
	float: right;
}

.zuul-change-job-link {
	overflow: auto;
	display: block;
}

.zuul-result {
	float: right;
}
');

$p->addHtmlFile( 'default.html' );
$p->enableFooter();
$p->addScript( 'status.js' );
$p->flush();
