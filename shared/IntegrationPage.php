<?php

class IntegrationPage extends TwbsPageBase {
	protected $site = 'Integration';

	protected function getNavItems() {
		return [
			'/zuul/' => 'Zuul status',
			'https://doc.wikimedia.org/' => 'Documentation',
			'https://doc.wikimedia.org/cover/' => 'Test coverage',
			'https://gerrit.wikimedia.org/r/' => 'Gerrit Code Review',
		];
	}
}
