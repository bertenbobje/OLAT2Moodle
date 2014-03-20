<?php

require_once("classes/olatclasses.php");
require_once("classes/moodleclasses.php");
require_once("classes/generalclasses.php");

require_once("functions/general.php");

// Reads out all the subjects of a parent chapter.
// Recursion at the end to find subjects of subjects,
// until nothing remains.
//
// PARAMETERS
// ->    &$object = The OLAT Chapter object
//            $id = The ident (ID) of the child to get subjects from
//         $xpath = runstructure.xml, loaded as a SimpleXMLElement
//    $pathCourse = Path to the exported OLAT .zip file
function olatGetSubjects(&$object, $id, $xpath, $pathCourse) {
	// If noPag still equals zero at the end, the type will be
	// sp or st without a page inside of it.
	$noPag = 0;
	$subjects = $xpath->xpath("/org.olat.course.Structure//*[ident='" . $id . "']/children/*[type = 'st' or type = 'sp' or type = 'bc' or type = 'en' or type = 'iqtest' or type = 'iqself' or type = 'iqsurv' or type = 'tu']");
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
					$noPag++;
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
				
				// Page
				case "sp":
					// Looks for the only <string> record that starts with a '/' (HTML-reference).
					$subjectPagePath = $xpath->xpath("//*[ident = " . $schild->ident . "]/moduleConfiguration/config//string[starts-with(., '/')]");
					foreach ($subjectPagePath as $subjectPage) {
						$noPag++;
						$subjectPageItem = $subjectPage;
					}
					if (isset($subjectPageItem)) {
						// UTF-8 encoding is applied for preservation of unique symbols (like u umlaut).
						if (substr($subjectPageItem, -4) == "html") {
							$page = file_get_contents($pathCourse . "/coursefolder" . $subjectPageItem);
							$subjectObject = new SubjectPage(htmlspecialchars($page, ENT_QUOTES, "UTF-8"));
							$subjectObject->setSubjectSubType = "page";
						}
						else {
							$subjectObject = new SubjectResource($subjectPagePath);
							$subjectObject->setSubjectSubType = "resource";
						}
					}
					else {
						$noPag++;
						$emptyHTML = "xxx";
						$subjectObject = new SubjectPage($emptyHTML);
					}
					break;

				// Structure
				case "st":
					$noPag++;
					$chapterObject = new Chapter();
					break;
					
				// URL
				case "tu":
					$noPag++;
					$urlPart1Path = $xpath->xpath("//*[ident = " . $schild->ident . "]/moduleConfiguration/config//string[starts-with(., 'www')]");
					foreach ($urlPart1Path as $up1p) {
						$urlPart1 = $up1p;
					}		
					$urlPart2Path = $xpath->xpath("//*[ident = " . $schild->ident . "]/moduleConfiguration/config//string[starts-with(., '/')]");
					foreach ($urlPart2Path as $up2p) {
						$urlPart2 = $up2p;
					}
					if (isset($urlPart1) && isset($urlPart2)) {
						$url = "http://" . $urlPart1 . $urlPart2;
						$subjectObject = new SubjectURL($url);
					}
					break;
			}

			if ($noPag != 0) {
				$subjectObject->setSubjectID(isset($schild->ident) ? (string) $schild->ident : null);
				$subjectObject->setSubjectType(isset($schild->type) ? (string) $schild->type : null);
				$subjectObject->setSubjectShortTitle(isset($schild->shortTitle) ? (string) $schild->shortTitle : null);
				$subjectObject->setSubjectLongTitle(isset($schild->longTitle) ? (string) $schild->longTitle : null);
				// Recursion for deeper children.
				olatGetSubjects($subjectObject, $schild->ident, $xpath, $pathCourse);
				$object->setSubject($subjectObject);
			}
		}
	}
}


?>
