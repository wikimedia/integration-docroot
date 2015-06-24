<?php
require_once __DIR__ . '/Page.php';

class DocPage extends Page {
	protected $site = 'Documentation';

	protected function getNavItems() {
		return array(
			'https://gerrit.wikimedia.org/r/' => 'Gerrit',
			'https://integration.wikimedia.org/' => 'Integration',
		);
	}
}
