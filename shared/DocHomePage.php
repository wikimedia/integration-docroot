<?php

class DocHomePage extends WmuiPageBase {
	protected $site = 'Open Source';
	protected $caption = 'Free and open-source software from Wikimedia.';
	private $data = [];

	public static function newFromProjects( $file ) {
		$yaml = file_get_contents( $file );
		if ( !$yaml ) {
			throw new InvalidArgumentException( 'Unreadable file' );
		}
		$data = Utils::parseRmsYaml( $yaml );

		$p = static::newIndex();
		$p->data = $data;
		$p->addStylesheet( '/lib/wmui-osproject.css' );
		return $p;
	}

	public function renderContent() {
		echo '<nav class="wm-osproject-nav"><ul>';
		foreach ( $this->data as $section => $_ ) {
			echo '<li><a href="#' . htmlspecialchars( $this->anchor( $section ) ) . '">' . htmlspecialchars( $section ) . '</a></li>';
		}
		echo '</ul></nav>';
		foreach ( $this->data as $section => $projects ) {
			echo '<h2 id="' . htmlspecialchars( $this->anchor( $section ) ) . '">' . htmlspecialchars( $section ) . '</h2>';
			echo '<div class="wm-osproject-grid">';
			uksort( $projects, 'strnatcasecmp' );
			foreach ( $projects as $title => $project ) {
				$this->renderTile( $title, $project );
			}
			echo '</div>';
		}
	}

	private function renderTile( $title, array $data ) {
		$titleHtml = htmlspecialchars( $title );
		$langHtml = htmlspecialchars( @$data['lang'] ?: '' );
		$taglineHtml = htmlspecialchars( @$data['tagline'] ?: '' );
		$homepageHtml = htmlspecialchars( @$data['homepage'] ?: '' );
?>
		<div class="wm-osproject-tile" tabindex="0">
		<h3 class="wm-osproject-tile-title">
			<?= $titleHtml ?><small><?= $langHtml ?></small>
		</h3>
		<p class="wm-osproject-tile-tagline"><?= $taglineHtml ?></p>
		<ul class="wm-osproject-tile-links">
			<?php
			if ( $homepageHtml ) {
				echo '<li><a href="' . $homepageHtml . '">Project homepage</a></li>';
			}
			foreach ( @$data['links'] ?: [] as $text => $url ) {
				echo '<li><a href="' . htmlspecialchars( $url ) . '">' . htmlspecialchars( $text ) . '</a></li>';
			}
		?></ul>
		</div>
<?php
	}

	private function anchor( $title ) {
		$title = trim( $title );
		$title = strtolower( $title );
		$title = preg_replace( '/[^a-z]+/', '-', $title );
		return $title;
	}

	protected function getNavItems() {
		return [
			'/index/' => 'Documentation index',
			'/cover/' => 'Test coverage',
			'https://gerrit.wikimedia.org/r/' => 'Gerrit Code-Review',
			'https://integration.wikimedia.org/' => 'Continuous integration',
		];
	}
}
