<?php

class DocPage extends Page {
	protected $site = 'Documentation';

	protected function getNavItems() {
		return [
			'/cover/' => 'Coverage',
			'https://gerrit.wikimedia.org/r/' => 'Gerrit',
			'https://integration.wikimedia.org/' => 'Integration',
		];
	}
}
