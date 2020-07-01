<?php

class DocPage extends TwbsPageBase {
	protected $site = 'Documentation';

	protected function getNavItems() {
		return [
			'/index/' => 'Documentation index',
			'/cover/' => 'Test coverage',
			'https://gerrit.wikimedia.org/r/' => 'Gerrit Code-Review',
			'https://integration.wikimedia.org/' => 'Continuous integration',
		];
	}
}
