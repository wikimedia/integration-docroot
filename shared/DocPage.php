<?php
require_once __DIR__ . '/Page.php';

class DocPage extends Page {
	protected $site = 'Documentation';

	protected function getNavHtml() {
		return <<<HTML
<ul class="nav">
	<li class="divider-vertical"></li>
	<li><a href="https://gerrit.wikimedia.org/r/">Gerrit</a></li>
	<li><a href="https://integration.wikimedia.org/">Integration</a></li>
</ul>
HTML;
	}
}
