<?php

  /**********************************************************
  /* Functions used in olat2moodle.php.
	/**********************************************************
	/* Bert Truyens
	/*********************************************************/

	// Reads out all the subjects of a parent chapter.
	// Recursion at the end to find subjects of subjects, until
	// nothing remains.
	mb_internal_encoding("UTF-8");
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
							$subjectObject = new SubjectPage(utf8_encode(file_get_contents($pathCourse . "/coursefolder" . $subjectPageItem)));
						}
						else {
							$noPag++;
							$emptyHTML = "xxx";
							$subjectObject = new SubjectPage($emptyHTML);
						}
						break;

					// Structure
					case "st":
						// Looks for the only <string> record that starts with a '/' (HTML-reference).
						$subjectPagePath = $xpath->xpath("//*[ident = " . $schild->ident . "]/moduleConfiguration/config//string[starts-with(., '/')]");
						foreach ($subjectPagePath as $subjectPage) {
							$noPag++;
							$subjectPageItem = $subjectPage;
						}
						if (isset($subjectPageItem)) {
							// UTF-8 encoding is applied for preservation of unique symbols (like u umlaut).
							$subjectObject = new SubjectPage(utf8_encode(file_get_contents($pathCourse . "/coursefolder" . $subjectPageItem)));
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
				
				var_dump($object);
			}
		}
	}
	
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
