<?php

/**********************************************************
/* Functions used in olat2moodle.php.
/**********************************************************
/* Bert Truyens
/*********************************************************/

require_once("olatclasses.php");
require_once("moodleclasses.php");

// Reads out all the subjects of a parent chapter.
// Recursion at the end to find subjects of subjects, until
// nothing remains.
function getSubjects(&$object, $id, $xpath, $pathCourse) {
	// If noPage still equals zero at the end, the type will be
	// sp or st without a page inside of it.
	$noPag = 0;
	$subjects = $xpath->xpath("/org.olat.course.Structure//*[ident='" . $id . "']/children/*[type = 'st' or type = 'sp' or type = 'bc' or type = 'en' or type = 'iqtest' or type = 'iqself' or type = 'iqsurv']");
	if ($subjects != null) {
		foreach ($subjects as $schild) {
			switch ($schild->type) {
				// Tests/Quizzes
				case "iqtest":
				case "iqself":
				case "iqsurv":
					$noPag++;
					$subjectObject = new Subject;
					break;
				
				// Enrollment
				case "en":
					$noPag++;
					$subjectLearningObject = $xpath->xpath("//*[ident = " . $schild->ident . "]/learningObjectives");
					foreach ($subjectLearningObject as $subjectLearningObjectItem) {
						$subjectLearningObjectItems = (string) $subjectLearningObjectItem;
					}
					$subjectObject = new SubjectLearningObjectives((string) $subjectLearningObjectItems);
					break;
				
				// Directory
				case "bc":
					$noPage++;
					$subjectObject = new SubjectDropFolder();
					$course_map = getDirectoryList($pathCourse . "/export/" . $schild->ident);
					for ($i = 0; $i < count($course_map); $i++) {
						$location = $pathCourse . "/export/" . $schild->ident . "/" . $course_map[$i];
						$folderObject = new Folder(
									(string) $course_map[$i],
									(string) $location,
									(string) filesize($location),
									(string) filetype($location),
									(string) date("F d Y H:i:s.", filemtime($location)));
						$subjectObject->setSubjectFolders($folderObject);
					}
					break;
				
				// Page        -----
				case "sp": //       | --- Structures and pages pretty much match.
				// Structure   -----
				case "st":
					// Looks for the only <string> record that starts with a '/' (HTML-reference).
					$subjectPagePath = $xpath->xpath("//*[ident = " . $schild->ident . "]/moduleConfiguration/config//string[starts-with(., '/')]");
					foreach ($subjectPagePath as $subjectPage) {
						$noPag++;
						$subjectPageItem = $subjectPage;
					}
					if (isset($subjectPageItem)) {
						// UTF-8 encoding is applied for preservation of unique symbols (like u umlaut).
						$page = file_get_contents($pathCourse . "/coursefolder" . $subjectPageItem);
						if (substr($subjectPageItem, -4) == "html") {
							$subjectObject = new SubjectPage(htmlspecialchars($page, ENT_QUOTES, "UTF-8"));
							// $subjectObject = new SubjectPage(iconv(mb_detect_encoding($page, mb_detect_order(), true), "UTF-8", $page));
						}
						else {
							$subjectObject = new SubjectPage("PDF");
						}
					}
					else {
						$noPag++;
						$emptyHTML = "xxx";
						$subjectObject = new SubjectPage($emptyHTML);
					}
					break;
			}
			
			if ($noPag != 0) {
				$subjectObject->setSubjectID(isset($schild->ident) ? (string) $schild->ident : null);
				$subjectObject->setSubjectType(isset($schild->type) ? (string) $schild->type : null);
				$subjectObject->setSubjectShortTitle(isset($schild->shortTitle) ? (string) $schild->shortTitle : null);
				$subjectObject->setSubjectLongTitle(isset($schild->longTitle) ? (string) $schild->longTitle : null);
				
				// Recursion for deeper children.
				getSubjects($subjectObject, $schild->ident, $xpath, $pathCourse);
				$object->setSubject($subjectObject);
			}
		}
	}
}

// Creates an as good as possible Moodle object from
// the given object parameter (OLAT backup object).
// -- $object - The OLAT object to get data of
function OLATObjectToMoodleObject($olatObject) {
	// This needs to be different for every course that goes through here.
	// Need to find a way to save this number somewhere safe.

	$number = 1;
	
	$courseID = $olatObject->getID();
	$courseContextID = (string) ($olatObject->getID() + 1);
	
	$moodleCourse = new MoodleCourse(
							$courseID, 
							$courseContextID,
							$olatObject->getShortTitle(),
							$olatObject->getLongTitle(),
							1);
	
	foreach ($olatObject->getChapter() as $olatChapter) {
		$moodleSection = new Section($olatChapter->getID(), $olatChapter->getShortTitle(), $number);
		foreach ($olatChapter->getSubject() as $olatSubject) {
			$type = $olatSubject->getSubjectType();
			$activityID = $olatSubject->getSubjectID();
			if ($type == "sp") {
				$moodleActivity = new ActivityPage($activityID, $olatSubject->getSubjectPage());
				$moodleActivity->setContent(fixHTML($moodleActivity->getContent()));
				$moduleName = "page";
			}
			$moodleActivity->setActivityID($olatSubject->getSubjectID());
			$moodleActivity->setModuleID($olatSubject->getSubjectID());
			$moodleActivity->setModuleName($moduleName);
			$moodleActivity->setContextID($olatSubject->getSubjectID());
			$moodleActivity->setName($olatSubject->getSubjectShortTitle());
		}
		isset($moodleActivity) ? $moodleSection->setActivity($moodleActivity) : null;
		$moodleCourse->setSection($moodleSection);
		$number++;
	}
	return $moodleCourse;
}

// Fixes the <img src=""> tags to be Moodle-specific and makes the
// .mp3, .flv and .wav references Moodle-specific by turning them into <a> tags.
function fixHTML($html) {
	// Images
	$patternImages = '/img src=&quot;(.+)&quot;/i';
	$replaceImages = 'img src=&quot;@@PLUGINFILE@@/$1&quot;';
	$fixhtmlImages = preg_replace($patternImages, $replaceImages, $html);
	
	// Media files
	$patternMedia = '/^&lt;object.*\n.*\n.*file\=(.*)\".*\n.*\n.*/mi';
	$replaceMedia = '&lt;a href=&quot;@@PLUGINFILE@@/$1&quot;&gt;$1&lt;/a&gt;';
	$fixhtmlMedia = preg_replace($patternMedia, $replaceMedia, $fixhtmlImages);
	
	return $fixhtmlMedia;
}

// Creates the backup file that Moodle can use to restore
// a course.
// -- $object - The Moodle object to make the backup file of.
function moodleObjectToMoodleBackup($object) {
	// Creates a temporary storage name made of random numbers.
	$num = "";
	for ($i = 0; $i < 9; $i++) {
		$num .= strval(mt_rand(0, 9));
	}
	
	$path = getcwd() . "/tmp/" . $num;
	
	// Checks if the folders exist and creates them if they do not.
	if (!file_exists(getcwd() . "/tmp") and !is_dir(getcwd() . "/tmp")) {
		mkdir(getcwd() . "/tmp", 0777, true);
	}
	if (!file_exists($path) and !is_dir($path)) {
		mkdir($path, 0777, true);
	}
	
	// Header of every .xml file is always the same.
	$header = '<?xml version="1.0" encoding="UTF-8"?>';
	
	////////////////////////////////////////////////////////////////////
	// ROOT FILES
	
	// DEFAULT
	// completion.xml
	$completionXml = new SimpleXMLElement($header . "<course_completion></course_completion>");
	file_put_contents($path . "/completion.xml", $completionXml->asXML());
	// groups.xml
	$groupsXml = new SimpleXMLElement($header . "<groups></groups>");
	file_put_contents($path . "/groups.xml", $groupsXml->asXML());
	// moodle_backup.log (it's empty)
	file_put_contents($path . "/moodle_backup.log", "");
	// outcomes.xml
	$outcomesXml = new SimpleXMLElement($header . "<outcomes_definition></outcomes_definition>");
	file_put_contents($path . "/outcomes.xml", $outcomesXml->asXML());
	// questions.xml
	$questionsXml = new SimpleXMLElement($header . "<question_categories></question_categories>");
	file_put_contents($path . "/questions.xml", $questionsXml->asXML());
	// roles.xml
	$rolesXml = new SimpleXMLElement($header . "<roles_definition></roles_definition>");
	file_put_contents($path . "/roles.xml", $rolesXml->asXML());
	// scales.xml
	$scalesXml = new SimpleXMLElement($header . "<scales_definition></scales_definition>");
	file_put_contents($path . "/scales.xml", $scalesXml->asXML());
	
	// DIFFERENT
	
	
	////////////////////////////////////////////////////////////////////
	// COURSE FILES
	
	// course folder
	if (!file_exists($path . "/course") and !is_dir($path . "course")) {
		mkdir($path . "/course", 0777, true);
	}
	
	// DEFAULT
	// course/enrolments.xml
	$enrolmentsXml = new SimpleXMLElement($header . "<enrolments></enrolments>");
	$enrolmentsXml->addChild('enrols');
	file_put_contents($path . "/course/enrolments.xml", $enrolmentsXml->asXML());
	// course/roles.xml
	$rolesXml = new SimpleXMLElement($header . "<roles></roles>");
	$rolesXml->addChild('role_overrides');
	$rolesXml->addChild('role_assignments');
	file_put_contents($path . "/course/roles.xml", $rolesXml->asXML());
	
	// DIFFERENT
	// course/inforef.xml
	$courseInforefXml = new SimpleXMLElement($header . "<inforef></inforef>");
	$courseInforefXml->addChild('roleref')->addChild('role')->addChild('id', $object->getID());
	file_put_contents($path . "/course/inforef.xml", $courseInforefXml->asXML());
	// course/course.xml
	$courseXml = new SimpleXMLElement($header . "<course></course>");
	$courseXml->addAttribute('id', $object->getID());
	$courseXml->addAttribute('contextid', $object->getID());
	$courseXml->addChild('shortname', $object->getShortName());
	$courseXml->addChild('fullName', $object->getFullName());
	$courseXml->addChild('summary', "&lt;p&gt;" . $object->getFullName() . "&lt;/p&gt;");
	$courseXml->addChild('format', 'topics');
	$courseXml->addChild('startdate', time());
	$courseXml->addChild('visible', 1);
	$courseXml->addChild('timecreated', time());
	$courseXml->addChild('timemodified', time());
	$courseXml->addChild('numsections', count($object->getSection()));
	file_put_contents($path . "/course/course.xml", $courseXml->asXML());
}

///////////////////////////////////////////////////////////
// GENERAL FUNCTIONS //////////////////////////////////////
///////////////////////////////////////////////////////////

// Creates an array to hold the directory list.
function getDirectoryList($dir) {
	$results = array();
	if (file_exists($dir)) {
		$handler = opendir($dir);
		while ($file = readdir($handler)) {
			if ($file != "." && $file != "..") {
				$results[] = $file;
			}
		}
		closedir($handler);
	}
	return $results;
}

// Removes a folder with all its subfolders and files.
function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir . "/" . $object) == "dir") {
					rrmdir($dir . "/" . $object); 
				}
				else {
					unlink($dir . "/" . $object);
				}
			}
		}
		reset($objects);
		rmdir($dir);
	}
}

?>
