<?php
require_once __DIR__ . '/Page.php';

class IntegrationPage extends Page {
	protected $site = 'Integration';

	protected function getNavHtml() {
		return <<<HTML
<ul class="nav">
	<li class="divider-vertical"></li>
	<li><a href="{{ROOT}}/cover/">Coverage</a></li>
	<li><a href="{{ROOT}}/monitoring/">Monitoring</a></li>
	<li><a href="{{ROOT}}/nightly/">Nightly</a></li>
	<li><a href="{{ROOT}}/zuul/">Zuul</a></li>
	<li class="divider-vertical"></li>
	<li><a href="https://gerrit.wikimedia.org/r/">Gerrit</a></li>
	<li><a href="https://doc.wikimedia.org/">Documentation</a></li>
</ul>
HTML;
	}
}
