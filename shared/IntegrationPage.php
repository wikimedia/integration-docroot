<?php

class IntegrationPage extends WmuiPageBase {
	protected $site = 'Integration';

	protected function getNavItems() {
		return [
			'/' => 'Home',
			'/zuul/' => 'Zuul status',
			'https://integration.wikimedia.org/ci/' => 'Jenkins',
			'https://doc.wikimedia.org/cover/' => 'Test coverage',
		];
	}

	protected function getFooterItems() {
		return [
			'https://doc.wikimedia.org/' => 'Documentation',
			'https://gerrit.wikimedia.org/r/' => 'Gerrit Code Review',
		];
	}
}
