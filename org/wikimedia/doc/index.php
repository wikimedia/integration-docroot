<?php
require_once( __DIR__ . '/../../../shared/IntegrationPage.php' );

$p = IntegrationPage::newFromPageName( 'Documentation' );

$p->addHtmlContent('
<ul>
	<li><a href="/mediawiki-core/master/php/html/">MediaWiki core</a> (<a href="/mediawiki-core/master/php/html/">PHP</a> &bull; <a href="/mediawiki-core/master/js/">JS</a>)</li>
	<li><a href="/VisualEditor/master/">VisualEditor</a></li>
	<li><a href="/puppet/">Puppet</a> (<a href="/puppetsource/">source</a>)</li>
</ul>
');

$p->flush();
