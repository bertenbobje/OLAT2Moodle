<?php

require_once("classes/generalclasses.php");
require_once("classes/olatclasses.php");
require_once("classes/moodleclasses.php");

// IDs for every quiz part
$pageID = 1;
$questionID = 1;
$answerID = 1;

// Creates an as good as possible Moodle object from
// the given object parameter (OLAT backup object).
//
// Bert Truyens
//
// PARAMETERS
// -> $olatObject = the OLAT Object
//         $error = The error handler
function olatObjectToMoodleObject($olatObject, &$error) {
	global $pageID;
	global $questionID;
	global $answerID;
	
	$number = 0;
	$moodleCourse = new MoodleCourse(
							$olatObject->getID(),
							str_replace(' & ', ' &amp; ', $olatObject->getShortTitle()),
							str_replace(' & ', ' &amp; ', $olatObject->getLongTitle()),
							1);
	
	foreach ($olatObject->getChapter() as $olatChapter) {
		$moodleSection = new Section(
								$olatChapter->getChapterID(), 
								str_replace(' & ', ' &amp; ', $olatChapter->getShortTitle()), 
								$number);
		$ctype = $olatChapter->getType();
		$ok = 0;
		switch ($ctype) {
			case "iqtest":
			case "iqself":
			case "iqsurv":
				$ok = 1;
				$moduleName = "quiz";
				$moodleActivity = quizMigration($olatChapter, $pageID, $questionID, $answerID, $error);
				break;
			case "sp":
			case "st":
				switch ($olatChapter->getSubType()) {
					case "page":
						$ok = 1;
						$moduleName = "page";
						$moodleActivity = new ActivityPage(moodleFixHTML($olatChapter->getChapterPage(), $olatChapter->getLongTitle(), "page", $error), $olatChapter->getContentFile());
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
			case "ta":
				$ok = 1;
				$moduleName = "assign";
				$moodleActivity = new ActivityAssignment($olatChapter->getTaskText());
				break;
		}
		if ($ok == 1) {
			$moodleActivity->setActivityID((string) ($olatChapter->getChapterID() - 50000000000000));
			$moodleActivity->setSectionID($olatChapter->getChapterID());
			$moodleActivity->setModuleName($moduleName);
			$moodleActivity->setOlatType($ctype);
			$moodleActivity->setName(str_replace(' & ', ' &amp; ', $olatChapter->getShortTitle()));
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
					$moodleActivity = quizMigration($olatSubject, $pageID, $questionID, $answerID, $error);
					break;
				case "sp":
				case "st":
					switch ($olatSubject->getSubjectSubType()) {
						case "page":
							$ok = 1;
							$moduleName = "page";
							$moodleActivity = new ActivityPage(moodleFixHTML($olatSubject->getSubjectPage(), $olatSubject->getLongTitle(), "page", $error), $olatSubject->getSubjectContentFile());
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
				case "ta":
					$ok = 1;
					$moduleName = "assign";
					$moodleActivity = new ActivityAssignment($olatSubject->getSubjectTaskText());
					break;
			}
			if ($ok == 1) {
				$moodleActivity->setActivityID($olatSubject->getSubjectID());
				$moodleActivity->setSectionID($olatChapter->getChapterID());
				$moodleActivity->setModuleName($moduleName);
				$moodleActivity->setOlatType($stype);
				$moodleActivity->setName(str_replace(' & ', ' &amp; ', $olatSubject->getSubjectShortTitle()));
				$moodleActivity->setIndent($olatSubject->getSubjectIndentation());
				$moodleActivity->setBook(false);
				$moodleSection->setActivity($moodleActivity);
				
				moodleGetActivities($moodleSection, $olatSubject->getSubject(), $olatChapter, $error);
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
// ->        $mSec = The Moodle Section
//           $oSub = The OLAT Subject
//    $olatChapter = The OLAT Chapter (for the ID)
//          $error = The error handler
function moodleGetActivities(&$mSec, $oSub, $olatChapter, &$error) {
	global $pageID;
	global $questionID;
	global $answerID;

	foreach ($oSub as $sub) {
		$type = $sub->getSubjectType();
		$ok = 0;
		switch ($type) {
			case "iqtest":
			case "iqself":
			case "iqsurv":
				$ok = 1;
				$moduleName = "quiz";
				$moodleActivity = quizMigration($sub, $pageID, $questionID, $answerID, $error);
				break;
			case "sp":
			case "st":
				switch ($sub->getSubjectSubType()) {
					case "page":
						$ok = 1;
						$moduleName = "page";
						$moodleActivity = new ActivityPage(moodleFixHTML($sub->getSubjectPage(), $sub->getLongTitle(), "page", $error), $sub->getSubjectContentFile());
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
			case "ta":
				$ok = 1;
				$moduleName = "assign";
				$moodleActivity = new ActivityAssignment($sub->getSubjectTaskText());
				break;
		}
		if ($ok == 1) {
			$moodleActivity->setActivityID($sub->getSubjectID());
			$moodleActivity->setSectionID($olatChapter->getChapterID());
			$moodleActivity->setModuleName($moduleName);
			$moodleActivity->setOlatType($type);
			$moodleActivity->setName(str_replace(' & ', ' &amp; ', $sub->getSubjectShortTitle()));
			$moodleActivity->setIndent($sub->getSubjectIndentation());
			$moodleActivity->setBook(false);
			
			$mSec->setActivity($moodleActivity);
			
			if (is_object($sub)) {
				moodleGetActivities($mSec, $sub->getSubject(), $olatChapter, $error);
			}
		}
	}
}

// Reads out the quizzes and parses them to the Moodle object
//
// PARAMETERS
// -> $olatObject = The OLAT Object
//        $pageID = The current page ID
//    $questionID = The current question ID
//      $answerID = The current answer ID
//         $error = The error handler
function quizMigration($olatObject, &$pageID, &$questionID, &$answerID, &$error) {
	$act = new ActivityQuiz(
				str_replace(' & ', ' &amp; ', htmlspecialchars($olatObject->getDescription(),  ENT_QUOTES, "UTF-8")),
				$olatObject->getDuration(),
				$olatObject->getPassingScore(),
				$olatObject->getClustering()
	);
	foreach ($olatObject->getQuizSections() as $qs) {
		$id = substr($qs->getId(), 7);
		if (strpos($id, "_") !== false) {
			$id = substr($id, strrpos($id, "_") + 1);
		}
		$quizPage = new QuizPage(
					(string) $pageID,
					$qs->getTitle(),
					str_replace(' & ', ' &amp; ', htmlspecialchars($qs->getDescription(), ENT_QUOTES, "UTF-8")),
					$qs->getOrdering(),
					$qs->getAmount()
		);
		$pageID++;
		if ($qs->getDescription() != "") {
			$quizQuestion = new QuizQuestion(
					(string) $questionID,
					"Inleiding",
					"description",
					NULL,
					0,
					"Inleiding",
					str_replace(' & ', ' &amp; ', htmlspecialchars(html_entity_decode($qs->getDescription()), ENT_QUOTES, "UTF-8")),
					NULL,
					NULL,
					NULL,
					NULL,
					NULL,
					NULL
			);
			$questionID++;
			$quizPage->setPageQuestion($quizQuestion);
			$quizPage->setRandomQuestionID((string) $questionID);
			$quizPage->setPageDescriptionElement(true);
		}
		foreach ($qs->getItems() as $qsi) {
			$qid = (string) substr($qsi->getId(), strrpos($qsi->getId(), ":") + 1);
			$question = ($qsi->getType() == "FIB" ? $qsi->getContent() : $qsi->getQuestion());
			$question = str_replace(' & ', ' &amp; ', htmlspecialchars(html_entity_decode($question), ENT_QUOTES, "UTF-8"));
			$quizQuestion = new QuizQuestion(
						(string) $questionID,
						str_replace(' & ', ' &amp; ', $qsi->getTitle()),
						$qsi->getType(),
						($qsi->getType() != "SCQ" && $qsi->getType() != "ESSAY" ? $qsi->getQuotation() : NULL),
						($qsi->getType() != "ESSAY" ? $qsi->getOriginalScore() : NULL),
						str_replace(' & ', ' &amp; ', htmlspecialchars(html_entity_decode($qsi->getDescription()), ENT_QUOTES, "UTF-8")),
						moodleFixHTML($question, $olatObject->getLongTitle(), "quiz", $error),
						($qsi->getType() == "SCQ" || $qsi->getType() == "MCQ" ? $qsi->getRandomOrder() : NULL),
						($qsi->getType() != "ESSAY" ? str_replace(' & ', ' &amp; ', htmlspecialchars(html_entity_decode($qsi->getHint()), ENT_QUOTES, "UTF-8")) : NULL),
						($qsi->getType() != "ESSAY" ? str_replace(' & ', ' &amp; ', htmlspecialchars(html_entity_decode($qsi->getSolutionFeedback()), ENT_QUOTES, "UTF-8")) : NULL),
						($qsi->getType() != "ESSAY" ? $qsi->getMax_attempts() : NULL),
						($qsi->getType() == "FIB" ? $qsi->getMedia() : NULL),
						($qsi->getType() == "ESSAY" ? $qsi->getEssayRows() : NULL)
			);
			$questionID++;
			if ($qsi->getType() == "FIB") {
				foreach ($qsi->getFeedback() as $qsif) {
					$quizFeedback = new QuizFeedback(
											$qsif->getId(), 
											str_replace(' & ', ' &amp; ', htmlspecialchars($qsif->getFeedback(), ENT_QUOTES, "UTF-8")));
					$quizQuestion->setQFeedback($quizFeedback);
				}
			}
			$pos = $qsi->getPossibilities();
			if (empty($pos) && $qsi->getType() != "ESSAY") {
				$quizQuestion->setQType("SCQ");
				$quizPossibility = new QuizPossibility(
					(string) $answerID,
					"NO ANSWER",
					null,
					null,
					true,
					""
				);
				$answerID++;
				$error->setError(new Error("WARNING", 2, "Question found with no answers (" . $quizQuestion->getQTitle() . "), the question will be added with one radio button saying \"NO ANSWER\"", 0));
				$quizQuestion->setQPossibility($quizPossibility);
			}
			else {
				foreach ($pos as $qsip) {
					$feedback = null;
					foreach ($qsi->getFeedback() as $qsif) {
						if ($qsip->getId() == $qsif->getId()) {
							$feedback = str_replace(' & ', ' &amp; ', $qsif->getFeedback());
						}
					}
					if (is_array($qsip->getAnswer())) {
						$answer = array();
						foreach ($qsip->getAnswer() as $qpqpa) {			
							array_push($answer, str_replace(' & ', ' &amp; ', htmlspecialchars(html_entity_decode($qpqpa), ENT_QUOTES, "UTF-8")));
						}
					}
					else {
						$answer = str_replace(' & ', ' &amp; ', htmlspecialchars(html_entity_decode($qsip->getAnswer())));
					}
					$quizPossibility = new QuizPossibility(
						(string) $answerID,
						$answer,
						$qsip->getScore(),
						$qsip->getCase(),
						$qsip->getIs_correct(),
						htmlspecialchars($feedback, ENT_QUOTES, "UTF-8")
					);
					$answerID++;
					$quizQuestion->setQPossibility($quizPossibility);
				}
			}
			$quizQuestion = quizMediaFiles($quizQuestion);
			$quizPage->setPageQuestion($quizQuestion);
		}
		if ($qs->getOrdering() == "Random") {
			if ($qs->getAmount() == "" || $qs->getAmount() == 0) {
				$amount = count($qs->getItems());
			}
			else {
				$amount = $qs->getAmount();
			}
			for ($i = 0; $i < $amount; $i++) {
				$quizPage->setRandomQuestionID((string) $questionID);
				$questionID++;
			}
		}
		$act->setQuizPage($quizPage);
	}
	return $act;
}

// This cleans up the HTML files given by OLAT to make them work
// in Moodle.
//
// PARAMETERS
// ->  $html = The HTML file (as string)
//     $page = The title of the activity that contains the HTML file
//     $type = Type of HTML to fix (page or quiz)
//    $error = The error handler
function moodleFixHTML($html, $title, $type, &$error) {
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
		$patternMedia = '/&lt;object.*?&quot;file\=(.+?)&quot;.*?&lt;\/object&gt;/ism';
	}
	else {
		$patternMedia = '/&lt;object.*?&quot;file\=media\/(.+?)&quot;.*?&lt;\/object&gt;/ism';
	}
	$fixhtmlMedia = preg_replace($patternMedia, $mediaReplace, $fixhtmlRemoveEnd);
	
	// Media files (BPlayer)
	if ($type == "page") {
		$patternMedia2 = '/&lt;script.*?BPlayer\.insertPlayer\(&quot;(.+?)&quot;.*?&lt;\/script&gt;/ism';
		//$patternMedia2 = '/&lt;span id=&quot;olatFlashMovieViewer0&quot; class=&quot;olatFlashMovieViewer&quot;.*?&lt;script.*?BPlayer\.insertPlayer\(&quot;(.+?)&quot;.*?&lt;\/script&gt;.*?&lt;\/span&gt;/ism';
	}
	else {
		$patternMedia2 = '/&lt;script.*?BPlayer\.insertPlayer\(&quot;media\/(.+?)&quot;.*?&lt;\/script&gt;/ism';
		//$patternMedia2 = '/&lt;span id=&quot;olatFlashMovieViewer0&quot; class=&quot;olatFlashMovieViewer&quot;.*?&lt;script.*?BPlayer\.insertPlayer\(&quot;media\/(.+?)&quot;.*?&lt;\/script&gt;.*?&lt;\/span&gt;/ism';
	}
	$fixhtmlMedia2 = preg_replace($patternMedia2, $mediaReplace, $fixhtmlMedia);
	
	// Multiple FIB textboxes fix to Cloze (Moodle)
	if ($type == "quiz") {
		$fixhtmlFIB = $fixhtmlMedia2;
		$qcounter = 1;
		$patternFIB = "/:text.+?box:/ism";
		preg_match_all($patternFIB, $fixhtmlMedia2, $matches);
		foreach ($matches as $m) {
			foreach ($m as $patternFIB)	{
				$fixhtmlFIB = preg_replace("/" . $patternFIB . "/ism", " {#" . $qcounter . "} ", $fixhtmlFIB);
				$qcounter++;
			}
		}
	}
	else {
		$fixhtmlFIB = $fixhtmlMedia2;
	}
	
	$dom = new DOMDocument;
	$errorState = libxml_use_internal_errors(TRUE);
	$dom->loadHTML('<?xml encoding="UTF-8">' . str_replace(' & ', ' &amp; ', htmlspecialchars_decode($fixhtmlFIB, ENT_QUOTES)));
	$htmlErrors = libxml_get_errors();
	if (!empty($htmlErrors)) {
		$error->setError(new Error("WARNING", 1, "HTML validation errors found in '" . $title . "'", 0));
		foreach ($htmlErrors as $e) {
			$error->setError(new Error("WARNING", 1, "- " . $e->message . " on line <strong>" . $e->line . "</strong> (starting from &lt;body&gt;)", 1));
		}
	}
	
	// Fix the references in <img> and <a> tags that don't lead to an external page
	foreach ($dom->getElementsByTagName('img') as $inode) {
		$srcValue = $inode->getAttribute('src');
		// Removed the media/ before the file if it exists (for quizzes)
		if (substr($srcValue, 0, 6) == "media/" && $type == "quiz") {
			$srcValue = substr($srcValue, 6);
		}
		if (substr($srcValue, 0, 7) !== "http://" 
		 && substr($srcValue, 0, 8) !== "https://"
		 && substr($srcValue, 0, 5) !== "data:") {
			$inode->setAttribute('src', '@@PLUGINFILE@@/' . $srcValue);
		}
	}
	
	foreach ($dom->getElementsByTagName('a') as $anode) {
		if ($anode->hasAttribute('href')) {
			$hrefValue = $anode->getAttribute('href');
			if (substr($hrefValue, 0, 7)  !== "http://" 
			 && substr($hrefValue, 0, 8)  !== "https://" 
			 && substr($hrefValue, 0, 5)  !== "data:"
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
			$anode->setAttribute('href', str_replace(' ', '%20', $hrefValue));
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
	$qqm = $qq->getQMedia();
	if (!empty($qqm)) {
		$dom = new DOMDocument;
		$errorState = libxml_use_internal_errors(TRUE);
		$dom->loadHTML('<?xml encoding="UTF-8">' . htmlspecialchars_decode($quizQuestion->getQQuestion(), ENT_QUOTES));
		
		$body = $dom->getElementsByTagName('body')->item(0);
		foreach ($qq->getQMedia() as $qqm) {
			$img = $dom->createElement('img');
			$img->setAttribute("src", "@@PLUGINFILE@@/" . substr($qqm, 6));
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
					$htmlPattern = '/&lt;a href=&quot;@@PLUGINFILE@@\/' . preg_quote($olatFile, '/') . '(.*?)&quot;.*?&gt;/ism';
					preg_match($htmlPattern, $htmlString, $matches);
					if (!empty($matches)) {
						foreach ($object->getSection() as $msection) {
							foreach ($msection->getActivity() as $mactivity) {
								if ($mactivity->getModuleName() == "page") {
									if ($mactivity->getContentFile() == $olatFile) {
										if ($books) {
											if ($mactivity->getBook()) {
											$htmlReplace = '&lt;a href=&quot;$@BOOKVIEWBYIDCH*' . (string) ($mactivity->getBookContextID() - 1) . '*' . $mactivity->getChapterID() . '@$$1&quot;&gt;';
										}
										else {
											$htmlReplace = '&lt;a href=&quot;$@' . strtoupper($mactivity->getModuleName()) . 'VIEWBYID*' . $mactivity->getModuleID() . '@$$1&quot;&gt;';
										}
									}
										else {
											$htmlReplace = '&lt;a href=&quot;$@' . strtoupper($mactivity->getModuleName()) . 'VIEWBYID*' . $mactivity->getModuleID() . '@$$1&quot;&gt;';
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
						$javaPattern = '/&lt;a href=&quot;javascript:parent\.gotonode\(' . $jactivity->getActivityID() . '(.*?)\)&quot;.*?&gt;/ism';
						$javaPattern2 = '/&lt;a href=&quot;javascript:parent\.gotonode\(' . (string) ($jactivity->getActivityID() + 50000000000000) . '(.*?)\)&quot;.*?&gt;/ism';
						preg_match($javaPattern, $htmlString, $javaMatches);
						preg_match($javaPattern2, $htmlString, $javaMatches2);
						if (!empty($javaMatches) || !empty($javaMatches2)) {
							if ($books) {
								if ($jactivity->getBook()) {
									$javaReplace = '&lt;a href=&quot;$@BOOKVIEWBYIDCH*' . (string) ($jactivity->getBookContextID() - 1) . '*' . $jactivity->getChapterID() . '@$$1&quot;&gt;';
								}
								else {
									$javaReplace = '&lt;a href=&quot;$@' . strtoupper($jactivity->getModuleName()) . 'VIEWBYID*' . $jactivity->getModuleID() . '@$$1&quot;&gt;';
								}
							}
							else {
								$javaReplace = '&lt;a href=&quot;$@' . strtoupper($jactivity->getModuleName()) . 'VIEWBYID*' . $jactivity->getModuleID() . '@$$1&quot;&gt;';
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
