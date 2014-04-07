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
function olatObjectToMoodleObject($olatObject) {
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
				case "sp":
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
			case "sp":
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
			
			if (is_object($sub)) {
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
	// Removes everything before <body> and after </body>
	$patternRemoveStart = '/^.+&lt;body&gt;/ism';
	$replaceRemoveStart = '';
	$fixhtmlRemoveStart = preg_replace($patternRemoveStart, $replaceRemoveStart, $html);
	
	$patternRemoveEnd = '/&lt;\/body&gt;.+$/ism';
	$replaceRemoveEnd = '';
	$fixhtmlRemoveEnd = preg_replace($patternRemoveEnd , $replaceRemoveEnd, $fixhtmlRemoveStart);
	
	// References
	$patternReferences = '/&lt;a href=&quot;((?!http:\/\/)(?!javascript:).+?)&quot;(.*?)&lt;\/a&gt;/ism';
	$replaceReferences = '&lt;a href=&quot;@@PLUGINFILE@@/$1&quot;$2&lt;/a&gt;';
	$fixhtmlReferences = preg_replace($patternReferences, $replaceReferences, $fixhtmlRemoveStart);
	
	// Un-reference the HTML files
	$patternUnreference = '/&lt;a href=&quot;@@PLUGINFILE@@\/(.+?\.html?.*?)&quot;(.*?)&lt;\/a&gt;/ism';
	$replaceUnreference = '&lt;a href=&quot;$1&quot;$2&lt;/a&gt;';
	$fixhtmlUnreference = preg_replace($patternUnreference, $replaceUnreference, $fixhtmlReferences);
	
	$mediaReplace = '&lt;a href=&quot;@@PLUGINFILE@@/$1&quot;&gt;$1&lt;/a&gt;';
	
	// Media files (Object)
	$patternMedia = '/&lt;object.*?file\=(.+?)&quot;.*?&lt;\/object&gt;/ism';
	$replaceMedia = $mediaReplace;
	$fixhtmlMedia = preg_replace($patternMedia, $replaceMedia, $fixhtmlUnreference);
	
	// Media files (BPlayer)
	$patternMedia2 = '/&lt;script.+?Bplayer.insertPlayer\(&quot;(.+?)&quot;.+?&lt;\/script&gt;/ism';
	$replaceMedia2 = $mediaReplace;
	$fixhtmlMedia2 = preg_replace($patternMedia2, $replaceMedia2, $fixhtmlMedia);
	
	// Images
	$patternImages = '/src=&quot;(?!http:\/\/)(?!javascript:)(.+?)&quot;/ism';
	$replaceImages = 'src=&quot;@@PLUGINFILE@@/$1&quot;';
	$fixhtmlImages = preg_replace($patternImages, $replaceImages, $fixhtmlMedia2);
	
	// Spaces in filenames
	$patternSpaces = '/(?:&lt;a href=&quot;@@PLUGINFILE@@\/|\G)\S*\K (?=(?:(?!&quot;|&gt;).)*?&quot;)/ism';
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
	foreach ($object->getSection() as $section) {
		$pageSequence = 0;
		$previousActivity = null;
		$subChapter = false;
		$firstActivity = true;
		foreach ($section->getActivity() as $activity) {
			// The first activity will always be the first indent, and could never become a book.
			// But this can mess with the indentation of following pages, so this the first one will be ignored.
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
						if ($activity->getIndent() == $previousIndent || $activity->getIndent() == $previousIndent + 1) {
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
								if ($activity->getIndent() == $previousIndent + 1) {
									$activity->setBookSubChapter(true);
									$subChapter = true;
								}
								else if ($activity->getIndent() == $previousIndent) {
									$activity->setBookSubChapter(false);
									$subChapter = false;
								}
							}
						}
						else {
							$pageSequence = 0;
							$subChapter = false;
						}
					}
					else {
						$pageSequence++;
					}
				}
				else {
					$pageSequence = 0;
					$previousActivity = null;
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
				$olatFiles = listFolderFiles($olatFilesPath);
				$htmlString = $activity->getContent();
				foreach ($olatFiles as $olatFile) {
					$htmlPattern = '/&lt;a href=&quot;' . preg_quote($olatFile, '/') . '(.*?)&quot;(.*?)&gt;/ism';
					preg_match($htmlPattern, $htmlString, $matches);
					if (!empty($matches)) {
						foreach ($object->getSection() as $msection) {
							foreach ($msection->getActivity() as $mactivity) {
								if ($mactivity->getModuleName() == "page") {
									if ($mactivity->getContentFile() == $olatFile) {
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
				
				// Converts the javascript: nodes to matching Moodle activities (if they are present)
				foreach ($object->getSection() as $jsection) {
					foreach ($jsection->getActivity() as $jactivity) {
						$javaPattern = '/&lt;a href=&quot;javascript:.*?gotonode\(' . $jactivity->getActivityID() . '\)&quot;(.*?)&gt;(.*?)&lt;\/a&gt;/ism';
						$javaPattern2 = '/&lt;a href=&quot;javascript:.*?gotonode\(' . (string) ($jactivity->getActivityID() + 50000000000000) . '\)&quot;(.*?)&gt;(.*?)&lt;\/a&gt;/ism';
						preg_match($javaPattern, $htmlString, $javaMatches);
						preg_match($javaPattern2, $htmlString, $javaMatches2);
						if (!empty($javaMatches) || !empty($javaMatches2)) {
							if ($books) {
								if ($jactivity->getBook()) {
									$javaReplace = '&lt;a href=&quot;$@BOOKVIEWBYIDCH*' . (string) ($jactivity->getBookContextID() - 1) . '*' . $jactivity->getChapterID() . '@$&quot;$1&gt;$2&lt;/a&gt;';
								}
								else {
									$javaReplace = '&lt;a href=&quot;$@' . strtoupper($jactivity->getModuleName()) . 'VIEWBYID*' . $jactivity->getModuleID() . '@$&quot;$1&gt;$2&lt;/a&gt;';
								}
							}
							else {
								$javaReplace = '&lt;a href=&quot;$@' . strtoupper($jactivity->getModuleName()) . 'VIEWBYID*' . $jactivity->getModuleID() . '@$&quot;$1&gt;$2&lt;/a&gt;';
							}
							$contentJava = $activity->getContent();
							if (!empty($javaMatches)) {
								$activityContentJava = preg_replace($javaPattern, $javaReplace, $contentJava);
							}
							else if (!empty($javaMatches2)) {
								$activityContentJava = preg_replace($javaPattern2, $javaReplace, $contentJava);
							}
							$activity->setContent($activityContentJava);
						}
					}
				}
			}
		}
	}
	return $object;
}

?>
