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
			case "iqtest":
			case "iqself":
			case "iqsurv":
				$ok = 1;
				$moduleName = "quiz";
				$moodleActivity = quizMigration($olatChapter);
				break;
			case "sp":
			case "st":
				switch ($olatChapter->getSubType()) {
					case "page":
						$ok = 1;
						$moduleName = "page";
						$moodleActivity = new ActivityPage(moodleFixHTML($olatChapter->getChapterPage(), $olatChapter->getLongTitle(), "page"), $olatChapter->getContentFile());
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
			$moodleActivity->setOlatType($ctype);
			$moodleActivity->setName($olatChapter->getShortTitle());
			$moodleActivity->setIndent($olatChapter->getIndentation());
			$moodleActivity->setBook(false);
			$moodleSection->setActivity($moodleActivity);
		}
		foreach ($olatChapter->getSubject() as $olatSubject) {
			$stype = $olatSubject->getSubjectType();
			$ok = 0;
			switch ($stype) {
				case "iqtest":
				case "iqself":
				case "iqsurv":
					$ok = 1;
					$moduleName = "quiz";
					$moodleActivity = quizMigration($olatSubject);
					break;
				case "sp":
				case "st":
					switch ($olatSubject->getSubjectSubType()) {
						case "page":
							$ok = 1;
							$moduleName = "page";
							$moodleActivity = new ActivityPage(moodleFixHTML($olatSubject->getSubjectPage(), $olatSubject->getLongTitle(), "page"), $olatSubject->getSubjectContentFile());
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
				$moodleActivity->setOlatType($stype);
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
			case "iqtest":
			case "iqself":
			case "iqsurv":
				$ok = 1;
				$moduleName = "quiz";
				$moodleActivity = quizMigration($sub);
				break;
			case "sp":
			case "st":
				switch ($sub->getSubjectSubType()) {
					case "page":
						$ok = 1;
						$moduleName = "page";
						$moodleActivity = new ActivityPage(moodleFixHTML($sub->getSubjectPage(), $sub->getLongTitle(), "page"), $sub->getSubjectContentFile());
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
			$moodleActivity->setOlatType($type);
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

// Reads out the quizzes and parses them to the Moodle object
//
// PARAMETERS
// -> $olatObject = The OLAT Object
function quizMigration($olatObject) {
	$act = new ActivityQuiz(
				$olatObject->getDescription(),
				$olatObject->getDuration(),
				$olatObject->getPassingScore()
	);
	foreach ($olatObject->getQuizSections() as $qs) {
		$id = substr($qs->getId(), 7);
		if (strpos($id, "_") !== false) {
			$id = substr($id, strrpos($id, "_") + 1);
		}
		$quizPage = new QuizPage(
					$id,
					$qs->getTitle(),
					htmlspecialchars($qs->getDescription(), ENT_QUOTES, "UTF-8"),
					$qs->getOrdering(),
					$qs->getAmount()
		);
		foreach ($qs->getItems() as $qsi) {
			$quotation = ($qsi->getType() != "SCQ" ? $qsi->getQuotation() : NULL);
			$media = ($qsi->getType() == "FIB" ? $qsi->getMedia() : NULL);
			$question = ($qsi->getType() == "FIB" ? $qsi->getContent() : $qsi->getQuestion());
			$question = htmlspecialchars(html_entity_decode($question), ENT_QUOTES, "UTF-8");
			$quizQuestion = new QuizQuestion(
						(string) substr($qsi->getId(), strrpos($qsi->getId(), ":") + 1),
						$qsi->getTitle(),
						$qsi->getType(),
						$quotation,
						$qsi->getScore(),
						htmlspecialchars(html_entity_decode($qsi->getDescription()), ENT_QUOTES, "UTF-8"),
						moodleFixHTML($question, $olatObject->getLongTitle(), "quiz"),
						$qsi->getHint(),
						$qsi->getSolutionFeedback(),
						$qsi->getMax_attempts(),
						$media
			);
			
			if ($qsi->getType() == "FIB") {
				foreach ($qsi->getFeedback() as $qsif) {
					$quizFeedback = new QuizFeedback($qsif->getId(), htmlspecialchars($qsif->getFeedback(), ENT_QUOTES, "UTF-8"));
					$quizQuestion->setQFeedback($quizFeedback);
				}
			}
			
			foreach ($qsi->getPossibilities() as $qsip) {
				$feedback = null;
				foreach ($qsi->getFeedback() as $qsif) {
					if ($qsip->getId() == $qsif->getId()) {
						$feedback = $qsif->getFeedback();
					}
				}
				// Strips the FIB answers for only the actual answer, not the layout
				if ($qsi->getType() == "FIB") {
					$pos1 = strpos($qsip->getAnswer(), '"');
					$pos2 = strpos($qsip->getAnswer(), '"', $pos1 + 1);
					$pos3 = strpos($qsip->getAnswer(), '"', $pos2 + 1);
					$pos4 = strpos($qsip->getAnswer(), '"', $pos3 + 1);
					$answer = substr($qsip->getAnswer(), $pos3 + 1, $pos4 - $pos3 - 1);
				}
				else {
					$answer = $qsip->getAnswer();
				}
				$quizPossibility = new QuizPossibility(
					(string) $qsip->getId(),
					htmlspecialchars(html_entity_decode($answer), ENT_QUOTES, "UTF-8"),
					$qsip->getIs_correct(),
					htmlspecialchars($feedback, ENT_QUOTES, "UTF-8")
				);
				$quizQuestion->setQPossibility($quizPossibility);
			}
			$quizQuestion = quizMediaFiles($quizQuestion);
			$quizPage->setPageQuestion($quizQuestion);
		}
		$act->setQuizPage($quizPage);
	}
	return $act;
}

// This cleans up the HTML files given by OLAT to make them work
// in Moodle.
//
// PARAMETERS
// -> $html = The HTML file (as string)
//    $page = The title of the activity that contains the HTML file
//    $type = Type of HTML to fix (page or quiz)
function moodleFixHTML($html, $title, $type) {
	// Removes everything before <body> and after </body>
	$patternRemoveStart = '/^.+?&lt;body&gt;/ism';
	$replaceRemoveStart = '';
	$fixhtmlRemoveStart = preg_replace($patternRemoveStart, $replaceRemoveStart, $html);
	
	$patternRemoveEnd = '/&lt;\/body&gt;.+$/ism';
	$replaceRemoveEnd = '';
	$fixhtmlRemoveEnd = preg_replace($patternRemoveEnd , $replaceRemoveEnd, $fixhtmlRemoveStart);
	
	$mediaReplace = '&lt;a href=&quot;@@PLUGINFILE@@/$1&quot;&gt;$1&lt;/a&gt;';
	
	// Media files (Object)
	if ($type == "page") {
		$patternMedia = '/&lt;object.*?file\=(.+?)&quot;.*?&lt;\/object&gt;/ism';
	}
	else {
		$patternMedia = '/&lt;object.*?file\=media\/(.+?)&quot;.*?&lt;\/object&gt;/ism';
	}
	$replaceMedia = $mediaReplace;
	$fixhtmlMedia = preg_replace($patternMedia, $replaceMedia, $fixhtmlRemoveEnd);
	
	// Media files (BPlayer)
	if ($type == "page") {
		$patternMedia2 = '/&lt;script&gt;.?BPlayer\.insertPlayer\(&quot;(.+?)&quot;.+?&lt;\/script&gt;/ism';
	}
	else {
		$patternMedia2 = '/&lt;script&gt;.?BPlayer\.insertPlayer\(&quot;media\/(.+?)&quot;.+?&lt;\/script&gt;/ism';
	}
	$replaceMedia2 = $mediaReplace;
	$fixhtmlMedia2 = preg_replace($patternMedia2, $replaceMedia2, $fixhtmlMedia);
	
	// Multiple FIB textboxes fix to Cloze (Moodle)
	if ($type == "quiz") {
		$patternFIB = "/:text(.+?)box:/ism";
		$replaceFIB = " {#$1} ";
		$fixhtmlFIB = preg_replace($patternFIB, $replaceFIB, $fixhtmlMedia2);
	}
	else {
		$fixhtmlFIB = $fixhtmlMedia;
	}
	
	$dom = new DOMDocument;
	$errorState = libxml_use_internal_errors(TRUE);
	$dom->loadHTML('<?xml encoding="UTF-8">' . htmlspecialchars_decode($fixhtmlFIB, ENT_QUOTES));
	$errors = libxml_get_errors();
	if (!empty($errors)) {
		echo "<p style='color:darkorange;'>WARNING - HTML errors found in '" . utf8_decode($title) . "', this could cause some strange results or parts that won't show up in Moodle!<ul style='color:darkorange;'>";
		foreach ($errors as $error) {
			echo "<li>" . $error->message . " on line " . $error->line . "</li>";
		}
		echo "</ul></p>";
	}
	
	// Fix the references in <img> and <a> tags that don't lead to an external page
	foreach ($dom->getElementsByTagName('img') as $inode) {
		$srcValue = $inode->getAttribute('src');
		// Removed the media/ before the file if it exists (for quizzes)
		if (substr($srcValue, 0, 6) == "media/" && $type == "quiz") {
			$srcValue = substr($srcValue, 6);
		}
		if (substr($srcValue, 0, 7)  !== "http://" 
		 && substr($srcValue, 0, 8)  !== "https://") {
			$inode->setAttribute('src', '@@PLUGINFILE@@/' . $srcValue);
		}
	}
	
	foreach ($dom->getElementsByTagName('a') as $anode) {
		if ($anode->hasAttribute('href')) {
			$hrefValue = $anode->getAttribute('href');
			if (substr($hrefValue, 0, 7)  !== "http://" 
			 && substr($hrefValue, 0, 8)  !== "https://" 
			 && substr($hrefValue, 0, 11) !== "javascript:"
			 && substr($hrefValue, 0, 15) !== "@@PLUGINFILE@@/") {
				$anode->setAttribute('href', '@@PLUGINFILE@@/' . $hrefValue);
			}
		}
	}
	
	// Spaces in filenames
	foreach ($dom->getElementsByTagName('a') as $anode) {
		if ($anode->hasAttribute('href')) {
			$hrefValue = $anode->getAttribute('href');
			$anode->setAttribute('href', str_replace(" ", "%20", $hrefValue));
		}
	}
	
	foreach ($dom->getElementsByTagName('img') as $inode) {
		$srcValue = $inode->getAttribute('src');
		$inode->setAttribute('src', str_replace(" ", "%20", $srcValue));
	}
	
	// Strips all the <a> tags for everything except 'href' (except if 'href' doesn't exist)
	// This is to not break the regex later on for Moodle references
	foreach ($dom->getElementsByTagName('a') as $anode) {
		if ($anode->hasAttribute('href')) {
			foreach ($anode->attributes as $anodea) {
				if ($anodea->name !== 'href') {
					$anode->removeAttribute($anodea->name);
				}
			}
		}
	}
	
	libxml_use_internal_errors($errorState);
	$fixedHTML = $dom->saveHTML();
	libxml_clear_errors();
	
	// Since DOM re-adds the useless tags (html, body, DOCTYPE), we remove them again
	$htmlSpec = htmlspecialchars($fixedHTML, ENT_QUOTES, "UTF-8");
	$fixEnd1 = preg_replace($patternRemoveStart, $replaceRemoveStart, $htmlSpec);
	$fixEnd2 = preg_replace($patternRemoveEnd , $replaceRemoveEnd, $fixEnd1);

	return $fixEnd2;
}

// Media files don't show up in the question, they are just referenced to.
// This function will check if there are media files, and adds them to the question.
// so that they show up in Moodle.
//
// PARAMETERS
// -> $quizQuestion = the QuizQuestion object
function quizMediaFiles($quizQuestion) {
	$qq = $quizQuestion;
	if (!empty($qq->getQMedia())) {
		$dom = new DOMDocument;
		$errorState = libxml_use_internal_errors(TRUE);
		$dom->loadHTML('<?xml encoding="UTF-8">' . htmlspecialchars_decode($quizQuestion->getQQuestion(), ENT_QUOTES));
		
		$body = $dom->getElementsByTagName('body')->item(0);
		foreach ($qq->getQMedia() as $qqm) {
			$img = $dom->createElement('img');
			$img->setAttribute("src", "@@PLUGINFILE@@/" . substr($qqm, 6));
			$img->setAttribute("alt", "@@PLUGINFILE@@/" . substr($qqm, 6));
			$body->insertBefore($img, $body->firstChild);
		}
		
		libxml_use_internal_errors($errorState);
		$fixedQuestion = $dom->saveHTML();
		libxml_clear_errors();
		$qq->setQQuestion(htmlspecialchars($fixedQuestion, ENT_QUOTES, "UTF-8"));
	}
	return $qq;
}

// Checks if there are scenarios with two or more pages in a row,
// and adds a 'books' boolean (T/F) to it in the object for said page.
// If the indentation is one more than the previous activity, it will
// also get a 'subchapter' boolean assigned.
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
			// But this can mess with the indentation of following pages, so this the first one will be ignored (except when it's a page itself).
			if (!$firstActivity || $activity->getOlatType() == "sp") {
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
				
				// Converts the <a> tags to a page in the current course (if present)
				foreach ($olatFiles as $olatFile) {
					$htmlPattern = '/&lt;a href=&quot;(?:@@PLUGINFILE\/|.*?)' . preg_quote($olatFile, '/') . '(.*?)&quot;(.*?)&gt;/ism';
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
						$javaPattern = '/&lt;a href=&quot;javascript:parent\.gotonode\(' . $jactivity->getActivityID() . '\)&quot;(.*?)&gt;(.*?)&lt;\/a&gt;/ism';
						$javaPattern2 = '/&lt;a href=&quot;javascript:parent\.gotonode\(' . (string) ($jactivity->getActivityID() + 50000000000000) . '\)&quot;(.*?)&gt;(.*?)&lt;\/a&gt;/ism';
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
