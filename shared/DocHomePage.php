<?php

class DocHomePage extends WmuiPageBase {
	protected $site = 'Documentation';

	protected function getNavItems() {
		return [
			'/cover/' => 'Code coverage',
			'https://gerrit.wikimedia.org/r/' => 'Gerrit Code-Review',
			'https://integration.wikimedia.org/' => 'Continuous integration',
		];
	}
}
