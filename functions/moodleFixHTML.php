<?php

// Removes the DOCTYPE and the <html>, <head> and <body> tags, including end tags.
// Also fixes the <img src=""> tags to be Moodle-specific and makes the
// .mp3, .flv and .wav references Moodle-specific by turning them into <a> tags.
//
// PARAMETERS
// -> $html = The HTML file (as string)
function moodleFixHTML($html) {
	$patternRemoveStart = '/^.+&lt;body&gt;/ism';
	$replaceRemoveStart = '';
	$fixhtmlRemoveStart = preg_replace($patternRemoveStart, $replaceRemoveStart, $html);
	
	$patternRemoveEnd = '/&lt;\/body&gt;.+$/ism';
	$replaceRemoveEnd = '';
	$fixhtmlRemoveEnd = preg_replace($patternRemoveEnd , $replaceRemoveEnd, $fixhtmlRemoveStart);

	// Media files
	$patternMedia = '/^&lt;object.*file\=(.+?)&quot;.*&lt;\/object&gt;/ism';
	$replaceMedia = '&lt;a href=&quot;@@PLUGINFILE@@/$1&quot;&gt;$1&lt;/a&gt;';
	$fixhtmlMedia = preg_replace($patternMedia, $replaceMedia, $fixhtmlRemoveEnd);
	
	// Images
	$patternImages = '/src=&quot;(.+?)&quot;/i';
	$replaceImages = 'src=&quot;@@PLUGINFILE@@/$1&quot;';
	$fixhtmlImages = preg_replace($patternImages, $replaceImages, $fixhtmlMedia);
	
	return $fixhtmlImages;
}

?>
