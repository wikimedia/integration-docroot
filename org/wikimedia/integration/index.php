<?php
require_once( __DIR__ . '/../../../shared/IntegrationPage.php' );

$p = IntegrationPage::newFromPageName( 'Continuous integration' );
$p->enableFooter();

$p->addHtmlContent('
<p>This is the <a href="//www.mediawiki.org/wiki/Continuous_integration">continous integration</a> server for <a href="//www.wikimedia.org/">Wikimedia Foundation</a> projects.</p>
<h4>Applications</h4>
<table class="table table-bordered table-hover">
	<tr><td><a href="https://gerrit.wikimedia.org/r/">Gerrit</a> (code review)</td></tr>
	<tr><td><a href="ci/">Jenkins</a></td></tr>
	<tr><td class="muted"><s>TestSwarm (QUnit/Javascript)</s>, disabled</td></tr>
	<tr><td><a href="zuul/">Zuul status</a> (Gerrit/Jenkins gateway)</td></tr>
</table>

<h4>Nightly builds</h4>
<table class="table table-bordered table-hover">
	<tr><td><a href="nightly/mediawiki/core/">MediaWiki core</a> (<a href="/nightly/mediawiki/core/mediawiki-latest.zip">latest</a>)</td></tr>
	<tr><td><a href="nightly/mobile/android-commons/">Commons</a> Android application
	(github: <a href="https://github.com/wikimedia/android-commons">android-commons</a>)
	</td></tr>
	<tr><td>
	<a href="WikipediaMobile/nightly/">Wikipedia</a> Android application
	(github: <a href="https://github.com/wikimedia/WikipediaMobile">WikipediaMobile</a>)
	</td></tr>
	<tr><td>
	<a href="WiktionaryMobile/nightly/">Wiktionary</a> Android application
	(github: <a href="https://github.com/wikimedia/WiktionaryMobile">WiktionaryMobile</a>)
	</td></tr>
	<tr><td>
	<a href="WLMMobile/nightly/">Wikimedia Loves Monument</a> Android application
	(github: <a href="https://github.com/wikimedia/WLMMobile">WLMMobile</a>)
	</td></tr>
</table>
');

$p->flush();
