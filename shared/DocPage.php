<?php

class DocPage extends WikimediaUiThemePageBase {
	protected $site = 'Documentation';

	protected function getNavItems() {
		return [
			'/' => 'Home',
			'/index/' => 'Doc index',
			'/cover/' => 'Test coverage',
			'/metrics/' => 'Code metrics',
		];
	}

	protected function getFooterItems() {
		return [
			'https://integration.wikimedia.org/zuul/' => 'Continuous integration status',
			'https://gerrit.wikimedia.org/r/' => 'Gerrit Code Review',
			'https://wikitech.wikimedia.org/wiki/Doc.wikimedia.org' => 'doc.wikimedia.org documentation',
		];
	}
}
