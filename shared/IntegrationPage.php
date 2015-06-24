<?php
require_once __DIR__ . '/Page.php';

class IntegrationPage extends Page {
	protected $site = 'Integration';

	protected function getNavItems() {
		return array(
			'/cover/' => 'Coverage',
			'https://tools.wmflabs.org/nagf/?project=integration' => 'Monitor',
			'/zuul/' => 'Zuul',
			'https://gerrit.wikimedia.org/r/' => 'Gerrit',
			'https://doc.wikimedia.org/' => 'Documentation',
		);
	}
}
