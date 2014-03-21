<?php

require_once("classes/olatclasses.php");

require_once("functions/general.php");

// Creates an OLAT Object out of an exported OLAT course
// backup file.
//
// PARAMETERS
// -> $path = Path to the exported OLAT .zip file.
function olatBackupToOlatObject($path) {
	// Random integer for storing unzips, so that there will be no overwrites.
	$num = "olat";
	for ($i = 0; $i < 9; $i++) {
		$num .= strval(mt_rand(0, 9));
	}
	
	// Extracts the .zip and puts it in its own folder
	// with randomly generated number for name.
	$zip = new ZipArchive;
	if ($zip->open($path)) {
		$expath = getcwd() . "/tmp/" . $num;
		// Creates both the 'tmp' directory and the random number directory
		// if they don't exist yet.
		if (!file_exists(getcwd() . "/tmp") and !is_dir(getcwd() . "/tmp")) {
			mkdir(getcwd() . "/tmp", 0777, true);
		}
		if (!file_exists($expath) and !is_dir($expath)) {
			mkdir($expath, 0777, true);
		}
		
		// Extract the .zip to the path.
		$zip->extractTo($expath);
		$zip->close();
						
		// $olat will be the root for the XML file
		if (file_exists($expath . "/runstructure.xml")) {
			$olat = file_get_contents($expath . "/runstructure.xml");
			// xPath initialisation for easier access to xml nodes.
			$doc = new DOMDocument();
			$doc->loadXML($olat);
			$xpath = simplexml_load_file($expath . "/runstructure.xml", 'SimpleXMLElement', LIBXML_NOCDATA);
			echo "<p>OLAT backup file opened successfully</p>";
		}
		else {
			echo "<p>Error reading XML</p>";
		}
	}
	else {
		echo "<p>Error parsing file</p>";
	}

	// Course
	$start = $xpath->xpath("/org.olat.course.Structure");
	$item = $start[0];

	$course = new Course(
			isset($item->rootNode->ident) ? (string) $item->rootNode->ident : null,
			isset($item->rootNode->type) ? (string) $item->rootNode->type : null,
			isset($item->rootNode->shortTitle) ? (string) $item->rootNode->shortTitle : null,
			isset($item->rootNode->longTitle) ? (string) $item->rootNode->longTitle : null);
	
	// Saving the rootdir so we can get files out of it later and remove it.
	$course->setRootdir($expath);
	
	// Chapters
	$chapters = $xpath->xpath("/org.olat.course.Structure/rootNode/children/*[type = 'st' or type = 'sp' or type = 'bc' or type = 'en' or type = 'iqtest' or type = 'iqself' or type = 'iqsurv' or type = 'tu']");
	foreach ($chapters as $child) {
		// If noPage still equals zero at the end, the type will be
		// sp or st without a page inside of it.
		$noPage = 0;
		switch ($child->type) {
			// Tests/Quizzes
			case "iqtest":
			case "iqself":
			case "iqsurv":
				$noPage++;
				$chapterObject = new Chapter;
				break;
			
			// Enrollment
			case "en":
				$noPage++;
				$chapterLearningObjectItems = '';
				$chapterLearningObject = $xpath->xpath("//*[ident = " . $child->ident . "]/learningObjectives");
				foreach ($chapterLearningObject as $chapterLearningObjectItem) {
					$chapterLearningObjectItems = (string) $chapterLearningObjectItem;
				}
				$chapterObject = new ChapterLearningObjectives((string) $chapterLearningObjectItems);
				break;

			// Directory
			case "bc":
				$noPage++;
				$chapterObject = new ChapterDropFolder();
				$course_map = getDirectoryList($expath . "/export/" . $child->ident);
				for ($i = 0; $i < count($course_map); $i++) {
					$location = $expath . "/export/" . $child->ident . "/" . $course_map[$i];
					$folderObject = new Folder(
								(string) $course_map[$i],
								(string) $location,
								(string) filesize($location),
								(string) filetype($location),
								(string) date("F d Y H:i:s.", filemtime($location)));
					$chapterObject->setChapterFolders($folderObject);
				}
				break;
				
			// Page & Structure
			case "sp":
			case "st":
				// Looks for the only <string> record that starts with a '/' (HTML-reference).
				$chapterPagePath = $xpath->xpath("//*[ident = " . $child->ident . "]/moduleConfiguration/config//string[starts-with(., '/')]");
				foreach ($chapterPagePath as $chapterPage) {
					$noPage++;
					$chapterPageItem = $chapterPage;
				}
				if (isset($chapterPageItem)) {
					// UTF-8 encoding is applied for preservation of unique symbols (like u umlaut).
					if (substr($chapterPageItem, -4) == "html") {
						$page = file_get_contents($expath . "/coursefolder" . $chapterPageItem);
						$chapterObject = new ChapterPage(htmlspecialchars($page, ENT_QUOTES, "UTF-8"));
						$chapterObject->setSubType("page");
					}
					else {
						$chapterObject = new ChapterResource($chapterPagePath);
						$chapterObject->setSubType("resource");
					}
				}
				else {
					$noPage++;
					$emptyHTML = "xxx";
					$chapterObject = new ChapterPage($emptyHTML);
				}
				break;

			// URL
			case "tu":
				$noPage++;
				$urlPart1Path = $xpath->xpath("//*[ident = " . $child->ident . "]/moduleConfiguration/config//string[starts-with(., 'www')]");
				foreach ($urlPart1Path as $up1p) {
					$urlPart1 = $up1p;
				}		
				$urlPart2Path = $xpath->xpath("//*[ident = " . $child->ident . "]/moduleConfiguration/config//string[starts-with(., '/')]");
				foreach ($urlPart2Path as $up2p) {
					$urlPart2 = $up2p;
				}
				if (isset($urlPart1) && isset($urlPart2)) {
					$url = $urlPart1 . $urlPart2;
					$chapterObject = new ChapterURL($url);
				}
				break;
				
		}
		
		if ($noPage != 0) {
			$chapterObject->setChapterID(isset($child->ident) ? (string) $child->ident : null);
			$chapterObject->setType(isset($child->type) ? (string) $child->type : null);
			$chapterObject->setShortTitle(isset($child->shortTitle) ? (string) $child->shortTitle : null);
			$chapterObject->setLongTitle(isset($child->longTitle) ? (string) $child->longTitle : null);
			olatGetSubjects($chapterObject, $child->ident, $xpath, $expath);
			$course->setChapter($chapterObject);
		}
	}
	
	return $course;
}

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
				case "st":
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
							$subjectObject->setSubjectSubType("page");
						}
						else {
							foreach ($subjectPagePath as $sppath) {
								$spp = $sppath;
							}
							$subjectObject = new SubjectResource(substr($spp, 1));
							$subjectObject->setSubjectSubType("resource");
						}
					}
					else {
						$noPag++;
						$emptyHTML = "xxx";
						$subjectObject = new SubjectPage($emptyHTML);
					}
					break;

				// Structure
					
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
