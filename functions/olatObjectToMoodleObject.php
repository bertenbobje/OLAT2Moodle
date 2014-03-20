<?php

require_once("classes/olatclasses.php");
require_once("classes/moodleclasses.php");
require_once("classes/generalclasses.php");

require_once("functions/moodleFixHTML.php");

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
					$moodleActivity->setActivityID((string) ($olatChapter->getChapterID() / 2));
					$moodleActivity->setSectionID($olatChapter->getChapterID());
					$moodleActivity->setModuleName($moduleName);
					$moodleActivity->setName($olatChapter->getShortTitle());
					$moodleActivity->setIndent($indent);
					$moodleSection->setActivity(isset($moodleActivity) ? $moodleActivity : null);
					break;
				case "tu":
					$ok = 1;
					$moduleName = "url";
					$moodleActivity = new ActivityURL($olatChapter->getURL());
					$moodleActivity->setActivityID((string) ($olatChapter->getChapterID() / 2));
					$moodleActivity->setSectionID($olatChapter->getChapterID());
					$moodleActivity->setModuleName($moduleName);
					$moodleActivity->setName($olatChapter->getShortTitle());
					$moodleActivity->setIndent($indent);
					$moodleSection->setActivity(isset($moodleActivity) ? $moodleActivity : null);
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


?>
