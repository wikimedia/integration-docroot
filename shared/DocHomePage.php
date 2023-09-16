<?php

class DocHomePage extends DocPage {
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

	protected function getSubnavItems() {
		$items = [];
		foreach ( $this->data as $section => $_ ) {
			$items[ '#' . $this->anchor( $section ) ] = $section;
		}
		return $items;
	}

	public function renderContent() {
		foreach ( $this->data as $section => $projects ) {
			echo '<h2 class="wm-osproject-heading" id="' . htmlspecialchars( $this->anchor( $section ) ) . '">' . htmlspecialchars( $section ) . '</h2>';
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
		$lang = $data['lang'] ?? '';
		$lang = explode( ',', $lang );
		$lang = implode( ', ', array_map( 'trim', $lang ) );
		$langHtml = htmlspecialchars( $lang );
		$taglineHtml = htmlspecialchars( $data['tagline'] ?? '' );
		$homepageHtml = htmlspecialchars( $data['homepage'] ?? '' );
?>
		<div class="wm-osproject-tile" tabindex="0" id="<?php echo $titleHtml ?>">
		<h3 class="wm-osproject-tile-title"><?php
		if ( isset( $data['logo'] ) ) {
			$logoUrl = '/logos/' . basename( $data['logo'] );
			?><img loading="lazy" class="wm-osproject-tile-logo" src="<?php echo htmlspecialchars( $logoUrl ) ?>"><?php
		}
		echo $titleHtml;
		?> <small><?php echo $langHtml ?></small>
		</h3>
		<p class="wm-osproject-tile-tagline"><?php echo $taglineHtml ?></p>
		<ul class="wm-osproject-tile-links">
			<?php
			if ( $homepageHtml ) {
				echo '<li><a href="' . $homepageHtml . '">Project homepage</a></li>';
			}
			foreach ( $data['links'] ?? [] as $text => $url ) {
				echo '<li><a href="' . htmlspecialchars( $url ) . '">' . htmlspecialchars( $text ) . '</a></li>';
			}
		?></ul>
		</div>
<?php
	}

	private function anchor( $title ) {
		$title = trim( $title );
		$title = strtolower( $title );
		return preg_replace( '/[^a-z]+/', '-', $title );
	}
}
