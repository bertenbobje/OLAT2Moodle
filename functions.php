<?php
  /**********************************************************
  /* Functions used in olat2moodle.php
	/**********************************************************
	/* Bert Truyens
	/*********************************************************/

	function imgfix($htmldoc, $path) {
		$doc = new DOMDocument();
		$doc->loadHTML($htmldoc);
		$tags = $doc->getElementsByTagName('img');
		foreach ($tags as $tag) {
			$old_src = $tag->getAttribute('src');
			$new_src = $path . $old_src;
			$tag->setAttribute('src', $new_src);
    }
		return $doc->saveHTML();
	}
?>