<?php

require_once("classes/olatclasses.php");
require_once("classes/moodleclasses.php");

// Creates an as good as possible Moodle object from
// the given object parameter (OLAT backup object).
//
// PARAMETERS
// -> $olatObject = OLAT Object
function olatObjectToMoodleObject($olatObject) {
	$number = 0;
	$moodleCourse = new MoodleCourse(
							$olatObject->getID(),
							$olatObject->getShortTitle(),
							$olatObject->getLongTitle(),
							1);
	
	foreach ($olatObject->getChapter() as $olatChapter) {
		$indent = 0;
		$moodleSection = new Section($olatChapter->getChapterID(), $olatChapter->getShortTitle(), $number);
		$type = $olatChapter->getType();
		if ($type != "st") {
			$ok = 0;
			switch ($type) {
				case "sp":
					$ok = 1;
					$moduleName = "page";
					$moodleActivity = new ActivityPage(moodleFixHTML($olatChapter->getChapterPage()));
					break;
				case "bc":
					$ok = 1;
					$moduleName = "folder";
					$moodleActivity = new ActivityFolder($olatChapter->getChapterFolders());
					break;
				case "tu":
					$ok = 1;
					$moduleName = "url";
					$moodleActivity = new ActivityURL($olatChapter->getURL());
					break;
			}
			if ($ok == 1) {
				$moodleActivity->setActivityID((string) ($olatChapter->getChapterID() / 2));
				$moodleActivity->setSectionID($olatChapter->getChapterID());
				$moodleActivity->setModuleName($moduleName);
				$moodleActivity->setName($olatChapter->getShortTitle());
				$moodleActivity->setIndent($indent);
				$moodleSection->setActivity(isset($moodleActivity) ? $moodleActivity : null);
			}
			$indent++;
		}
		foreach ($olatChapter->getSubject() as $olatSubject) {
			$type = $olatSubject->getSubjectType();
			$ok = 0;
			switch ($type) {
				case "sp":
					$ok = 1;
					$moduleName = "page";
					$moodleActivity = new ActivityPage(moodleFixHTML($olatSubject->getSubjectPage()));
					break;
				case "bc":
					$ok = 1;
					$moduleName = "folder";
					$moodleActivity = new ActivityFolder($olatSubject->getSubjectFolders());
					break;
				case "tu":
					$ok = 1;
					$moduleName = "url";
					$moodleActivity = new ActivityURL($olatSubject->getSubjectURL());
					break;
			}
			if ($ok == 1) {
				$moodleActivity->setActivityID($olatSubject->getSubjectID());
				$moodleActivity->setSectionID($olatChapter->getChapterID());
				$moodleActivity->setModuleName($moduleName);
				$moodleActivity->setName($olatSubject->getSubjectShortTitle());
				$moodleActivity->setIndent($indent);
				$moodleSection->setActivity(isset($moodleActivity) ? $moodleActivity : null);
			}
		}	
		$moodleCourse->setSection($moodleSection);
		$number++;
	}
	return $moodleCourse;
}

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
