<?php

require_once("classes/olatclasses.php");
require_once("classes/moodleclasses.php");

// Creates an as good as possible Moodle object from
// the given object parameter (OLAT backup object).
//
// Bert Truyens
//
// PARAMETERS
// -> $olatObject = the OLAT Object
//         $books = Check if the book checkbox was marked
function olatObjectToMoodleObject($olatObject, $books) {
	$number = 0;
	$moodleCourse = new MoodleCourse(
							$olatObject->getID(),
							$olatObject->getShortTitle(),
							$olatObject->getLongTitle(),
							1);
	
	foreach ($olatObject->getChapter() as $olatChapter) {
		$moodleSection = new Section($olatChapter->getChapterID(), $olatChapter->getShortTitle(), $number);
		$ctype = $olatChapter->getType();
		$ok = 0;
		switch ($ctype) {
			case "sp":
			case "st":
				switch ($olatChapter->getSubType()) {
					case "page":
						$ok = 1;
						$moduleName = "page";
						$moodleActivity = new ActivityPage(moodleFixHTML($olatChapter->getChapterPage()), $olatChapter->getContentFile());
						break;
					case "emptypage":
						$ok = 1;
						$moduleName = "label";
						$moodleActivity = new ActivityLabel();
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
			case "wiki":
				$ok = 1;
				$moduleName = "wiki";
				$moodleActivity = new ActivityWiki();
				break;
		}
		if ($ok == 1) {
			$moodleActivity->setActivityID((string) ($olatChapter->getChapterID() - 50000000000000));
			$moodleActivity->setSectionID($olatChapter->getChapterID());
			$moodleActivity->setModuleName($moduleName);
			$moodleActivity->setName($olatChapter->getShortTitle());
			$moodleActivity->setIndent($olatChapter->getIndentation());
			$moodleActivity->setBook(false);
			$moodleSection->setActivity($moodleActivity);
		}
		foreach ($olatChapter->getSubject() as $olatSubject) {
			$stype = $olatSubject->getSubjectType();
			$ok = 0;
			switch ($stype) {
				case "st":
					switch ($olatSubject->getSubjectSubType()) {
						case "page":
							$ok = 1;
							$moduleName = "page";
							$moodleActivity = new ActivityPage(moodleFixHTML($olatSubject->getSubjectPage()), $olatSubject->getSubjectContentFile());
							break;
						case "emptypage":
							$ok = 1;
							$moduleName = "label";
							$moodleActivity = new ActivityLabel();
							break;
						case "resource":
							$ok = 1;
							$moduleName = "resource";
							$moodleActivity = new ActivityResource($olatSubject->getSubjectResource());
							break;
					}
					moodleGetActivities($moodleSection, $olatSubject->getSubject(), $olatChapter);
					break;
				case "sp":
					switch ($olatSubject->getSubjectSubType()) {
						case "page":
							$ok = 1;
							$moduleName = "page";
							$moodleActivity = new ActivityPage(moodleFixHTML($olatSubject->getSubjectPage()), $olatSubject->getSubjectContentFile());
							break;
						case "emptypage":
							$ok = 1;
							$moduleName = "label";
							$moodleActivity = new ActivityLabel();
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
				case "wiki":
					$ok = 1;
					$moduleName = "wiki";
					$moodleActivity = new ActivityWiki();
					break;
			}
			if ($ok == 1) {
				$moodleActivity->setActivityID($olatSubject->getSubjectID());
				$moodleActivity->setSectionID($olatChapter->getChapterID());
				$moodleActivity->setModuleName($moduleName);
				$moodleActivity->setName($olatSubject->getSubjectShortTitle());
				$moodleActivity->setIndent($olatSubject->getSubjectIndentation());
				$moodleActivity->setBook(false);
				$moodleSection->setActivity($moodleActivity);
				
				moodleGetActivities($moodleSection, $olatSubject->getSubject(), $olatChapter);
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
function moodleGetActivities(&$mSec, $oSub, $olatChapter) {
	foreach ($oSub as $sub) {
		$type = $sub->getSubjectType();
		$ok = 0;
		switch ($type) {
			case "st":
				switch ($sub->getSubjectSubType()) {
					case "page":
						$ok = 1;
						$moduleName = "page";
						$moodleActivity = new ActivityPage(moodleFixHTML($sub->getSubjectPage()), $sub->getSubjectContentFile());
						break;
					case "emptypage":
						$ok = 1;
						$moduleName = "label";
						$moodleActivity = new ActivityLabel();
						break;
					case "resource":
						$ok = 1;
						$moduleName = "resource";
						$moodleActivity = new ActivityResource($sub->getSubjectResource());
						break;
				}
				moodleGetActivities($mSec, $sub->getSubject(), $olatChapter);
				break;
			case "sp":
				switch ($sub->getSubjectSubType()) {
					case "page":
						$ok = 1;
						$moduleName = "page";
						$moodleActivity = new ActivityPage(moodleFixHTML($sub->getSubjectPage()), $sub->getSubjectContentFile());
						break;
					case "emptypage":
						$ok = 1;
						$moduleName = "label";
						$moodleActivity = new ActivityLabel();
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
			case "wiki":
				$ok = 1;
				$moduleName = "wiki";
				$moodleActivity = new ActivityWiki();
				break;
		}
		if ($ok == 1) {
			$moodleActivity->setActivityID($sub->getSubjectID());
			$moodleActivity->setSectionID($olatChapter->getChapterID());
			$moodleActivity->setModuleName($moduleName);
			$moodleActivity->setName($sub->getSubjectShortTitle());
			$moodleActivity->setIndent($sub->getSubjectIndentation());
			$moodleActivity->setBook(false);
			
			$mSec->setActivity($moodleActivity);
			
			if(is_object($sub)) {
				moodleGetActivities($mSec, $sub->getSubject(), $olatChapter);
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

	$mediaReplace = '&lt;a href=&quot;@@PLUGINFILE@@/$1&quot;&gt;$1&lt;/a&gt;';
	
	// Media files (Object)
	$patternMedia = '/&lt;object.*file\=(.+?)&quot;.*&lt;\/object&gt;/ism';
	$replaceMedia = $mediaReplace;
	$fixhtmlMedia = preg_replace($patternMedia, $replaceMedia, $fixhtmlRemoveEnd);
	
	// Media files (BPlayer)
	$patternMedia2 = '/^&lt;script.+Bplayer.insertPlayer\(&quot;(.+?)&quot;.+&lt;\/script&gt;/ism';
	$replaceMedia2 = $mediaReplace;
	$fixhtmlMedia2 = preg_replace($patternMedia2, $replaceMedia2, $fixhtmlMedia);
	
	// Images
	$patternImages = '/src=&quot;(.+?)&quot;/i';
	$replaceImages = 'src=&quot;@@PLUGINFILE@@/$1&quot;';
	$fixhtmlImages = preg_replace($patternImages, $replaceImages, $fixhtmlMedia2);
	
	// Spaces in filenames
	//$patternSpaces = '/&lt;a href=&quot;@@PLUGINFILE@@\/(.*?)([ *])(.*?)&quot;&gt;(.+?)&lt;\/a&gt;/i';
	//$replaceSpaces = '&lt;a href=&quot;@@PLUGINFILE@@/$1%20$3&quot;&gt;$4&lt;/a&gt;';
	$patternSpaces = '/(?:&lt;a href=&quot;@@PLUGINFILE@@\/|\G)\S*\K (?=(?:(?!&quot;|&gt;).)*?&quot;)/i';
	$replaceSpaces = '%20';
	$fixhtmlSpaces = preg_replace($patternSpaces, $replaceSpaces, $fixhtmlImages);
	
	return $fixhtmlSpaces;
}

// Checks if there are scenarios with two or more pages in a row,
// and adds a 'books' boolean (T/F) to it in the object for said page.
// NOTE: This only happens if the books checkbox was checked at the start.
//
// PARAMETERS
// -> $moodleObject = the Moodle object
function checkForBooks($moodleObject) {
	$object = $moodleObject;
	foreach($object->getSection() as $section) {
		$pageSequence = 1;
		$previousActivity = null;
		$subChapter = false;
		$firstActivity = true;
		foreach($section->getActivity() as $activity) {
			// The first activity will always be the first indent, and could never become a book.
			// But this can mess with the code, so the first activity is ignored.
			if (!$firstActivity) {
				$moduleName = $activity->getModuleName();
				if ($moduleName == "page") {
					if (isset($previousActivity)) {
						if ($subChapter) {
							$previousIndent = $previousActivity->getIndent() - 1;
						}
						else {
							$previousIndent = $previousActivity->getIndent();
						}
						if ($activity->getIndent() == $previousIndent) {
							$pageSequence++;
							if ($pageSequence >= 2) {
								if ($pageSequence == 2) {
									$bookContextID = $previousActivity->getContextID();
									$previousActivity->setBook(true);
									$previousActivity->setBookContextID($bookContextID);
									$previousActivity->setBookSubChapter(false);
								}
								$activity->setBook(true);
								$activity->setBookContextID($bookContextID);
								$activity->setBookSubChapter(false);
							}
							$subChapter = false;
						}
						else if ($activity->getIndent() == $previousIndent + 1) {
							$pageSequence++;
							if ($pageSequence >= 2) {
								if ($pageSequence == 2) {
									$bookContextID = $previousActivity->getContextID();
									$previousActivity->setBook(true);
									$previousActivity->setBookContextID($bookContextID);
									$previousActivity->setBookSubChapter(false);
								}
								$activity->setBook(true);
								$activity->setBookContextID($bookContextID);
								$activity->setBookSubChapter(true);
							}
							$subChapter = true;
						}
						else {
							$pageSequence = 1;
							$subChapter = false;
						}
					}
				}
				else {
					$pageSequence = 1;
					$subChapter = false;
				}
				$previousActivity = $activity;
			}
			else {
				$firstActivity = false;
			}
		}
	}
	
	// Chapter IDs, this is for books later on.
	$chapterID = 1;
	foreach ($object->getSection() as $section) {
		foreach ($section->getActivity() as $activity) {
			if ($activity->getBook()) {
				$activity->setChapterID($chapterID);
				$chapterID++;
			}
		}
	}
	
	return $object;
}

// This function fixes HTML references to the course itself in Moodle activities
//
// PARAMETERS
// -> $moodleObject = the Moodle object
//      $olatObject = the OLAT object
//           $books = Check if the book checkbox was marked
function fixHTMLReferences($moodleObject, $olatObject, $books) {
	$object = $moodleObject;
	foreach ($object->getSection() as $section) {
		foreach ($section->getActivity() as $activity) {
			$moduleName = $activity->getModuleName();
			if ($moduleName == "page") {
				$olatFilesPath = $olatObject->getRootdir() . "/coursefolder";	
				$olatFiles = getDirectoryList($olatFilesPath);
				foreach ($olatFiles as $olatFile) {
					$htmlString = $activity->getContent();
					$htmlPattern = '/&lt;a href=&quot;' . preg_quote($olatFile) . '(.*?)&quot;(.*?)&gt;/ism';
					preg_match($htmlPattern, $htmlString, $matches);
					if (!empty($matches)) {
						foreach ($object->getSection() as $msection) {
							foreach ($msection->getActivity() as $mactivity) {
								if (method_exists($mactivity, "getContentFile") && $mactivity->getContentFile() == $olatFile) {
									if ($books) {
										if ($mactivity->getBook()) {
											$htmlReplace = '&lt;a href=&quot;$@BOOKVIEWBYIDCH*' . (string) ($mactivity->getBookContextID() - 1) . '*' . $mactivity->getChapterID() . '@$$1&quot;$2&gt;';
										}
										else {
											$htmlReplace = '&lt;a href=&quot;$@' . strtoupper($mactivity->getModuleName()) . 'VIEWBYID*' . $mactivity->getModuleID() . '@$$1&quot;$2&gt;';
										}
									}
									else {
										$htmlReplace = '&lt;a href=&quot;$@' . strtoupper($mactivity->getModuleName()) . 'VIEWBYID*' . $mactivity->getModuleID() . '@$$1&quot;$2&gt;';
									}
									$content = $activity->getContent();
									$activityContent = preg_replace($htmlPattern, $htmlReplace, $content);
									$activity->setContent($activityContent);
								}
							}
						}
					}
				}
			}
		}
	}
	return $object;
}

?>
