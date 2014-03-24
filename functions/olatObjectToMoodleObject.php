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
		$ctype = $olatChapter->getType();
		if ($ctype != "st") {
			$ok = 0;
			switch ($ctype) {
				case "sp":
					switch ($olatChapter->getSubType()) {
						case "page":
							$ok = 1;
							$moduleName = "page";
							$moodleActivity = new ActivityPage(moodleFixHTML($olatChapter->getChapterPage()));
							break;
						case "resource":
							$ok = 1;
							$moduleName = "resource";
							$moodleActivity = new ActivityResource($olatChapter->getChapterResource());
							break;
					}
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
				$moodleActivity->setActivityID((string) ($olatChapter->getChapterID() - 50000000000000));
				$moodleActivity->setSectionID($olatChapter->getChapterID());
				$moodleActivity->setModuleName($moduleName);
				$moodleActivity->setName($olatChapter->getShortTitle());
				$moodleActivity->setIndent($indent);
				$moodleSection->setActivity($moodleActivity);
			}
			$indent++;
		}
		foreach ($olatChapter->getSubject() as $olatSubject) {
			$stype = $olatSubject->getSubjectType();
			$ok = 0;
			switch ($stype) {
				case "sp":
					switch ($olatSubject->getSubjectSubType()) {
						case "page":
							$ok = 1;
							$moduleName = "page";
							$moodleActivity = new ActivityPage(moodleFixHTML($olatSubject->getSubjectPage()));
							break;
						case "resource":
							$ok = 1;
							$moduleName = "resource";
							$moodleActivity = new ActivityResource($olatSubject->getSubjectResource());
							break;
					}
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
				case "st":
					$ok = 0;
					moodleGetActivities($moodleSection, $olatSubject->getSubject(), $olatChapter, $indent);
					break;
			}
			if ($ok == 1) {
				$moodleActivity->setActivityID($olatSubject->getSubjectID());
				$moodleActivity->setSectionID($olatChapter->getChapterID());
				$moodleActivity->setModuleName($moduleName);
				$moodleActivity->setName($olatSubject->getSubjectShortTitle());
				$moodleActivity->setIndent($indent);
				$moodleSection->setActivity($moodleActivity);
				
				moodleGetActivities($moodleSection, $olatSubject->getSubject(), $olatChapter, $indent);
			}
		}
		$moodleCourse->setSection($moodleSection);
		$number++;
	}
	return $moodleCourse;
}

// Reads out every deep OLAT child and saves it as an Activity in
// Moodle with the correct indentation.
//
// PARAMETERS
// ->       &$mSec : The Moodle Section
//           $oSub : The OLAT Subject
//    $olatChapter : The OLAT Chapter (for the ID)
//              $i : The current indentation.
function moodleGetActivities(&$mSec, $oSub, $olatChapter, &$i) {
	foreach ($oSub as $sub) {
		$type = $sub->getSubjectType();
		$ok = 0;
		switch ($type) {
			case "sp":
				switch ($sub->getSubjectSubType()) {
					case "page":
						$ok = 1;
						$moduleName = "page";
						$moodleActivity = new ActivityPage(moodleFixHTML($sub->getSubjectPage()));
						break;
					case "resource":
						$ok = 1;
						$moduleName = "resource";
						$moodleActivity = new ActivityResource($sub->getSubjectResource());
						break;
				}
				break;
			case "bc":
				$ok = 1;
				$moduleName = "folder";
				$moodleActivity = new ActivityFolder($sub->getSubjectFolders());
				break;
			case "tu":
				$ok = 1;
				$moduleName = "url";
				$moodleActivity = new ActivityURL($sub->getSubjectURL());
				break;
			case "st":
				$ok = 0;
				moodleGetActivities($mSec, $sub->getSubject(), $olatChapter, $i);
				break;
		}
		if ($ok == 1) {
			$moodleActivity->setActivityID($sub->getSubjectID());
			$moodleActivity->setSectionID($olatChapter->getChapterID());
			$moodleActivity->setModuleName($moduleName);
			$moodleActivity->setName($sub->getSubjectShortTitle());
			$moodleActivity->setIndent($i);
			
			$mSec->setActivity($moodleActivity);
			
			if(is_object($sub)) {
				moodleGetActivities($mSec, $sub->getSubject(), $olatChapter, $i);
			}
		}
	}
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
