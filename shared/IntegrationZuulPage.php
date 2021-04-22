<?php

class IntegrationZuulPage extends TwbsPageBase {
	protected $site = 'Integration';

	protected function getNavItems() {
		return [
			'/zuul/' => 'Zuul status',
			'https://integration.wikimedia.org/ci/' => 'Jenkins',
			'https://doc.wikimedia.org/cover/' => 'Test coverage',
			'https://doc.wikimedia.org/' => 'Documentation',
			'https://gerrit.wikimedia.org/r/' => 'Gerrit Code Review',
		];
	}
}
