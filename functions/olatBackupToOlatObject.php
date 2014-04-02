<?php

require_once("classes/olatclasses.php");

require_once("functions/general.php");

ini_set('max_execution_time', 300);
ini_set('memory_limit', '-1');

// Creates an OLAT Object out of an exported OLAT course
// backup file.
//
// Bert Truyens
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
		
		//Checks if double file references in the coursefolder are present.
		checkdoublefilereference($zip);
		
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
	$chapters = $xpath->xpath("/org.olat.course.Structure/rootNode/children/*[type = 'st' or type = 'sp' or type = 'bc' or type = 'en' or type = 'iqtest' or type = 'iqself' or type = 'iqsurv' or type = 'tu' or type = 'wiki']");
	foreach ($chapters as $child) {
		$indentation = 0;
		$ok = 0;
		switch ($child->type) {
			// Tests/Quizzes
			case "iqtest":
			case "iqself":
			case "iqsurv":
				$ok = 1;
				$chapterObject = new Chapter;
				break;
			
			// Enrollment
			case "en":
				$ok = 1;
				$chapterLearningObjectItems = '';
				$chapterLearningObject = $xpath->xpath("//*[ident = " . $child->ident . "]/learningObjectives");
				foreach ($chapterLearningObject as $chapterLearningObjectItem) {
					$chapterLearningObjectItems = (string) $chapterLearningObjectItem;
				}
				$chapterObject = new ChapterLearningObjectives((string) $chapterLearningObjectItems);
				break;

			// Directory
			case "bc":
				$ok = 1;
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
				$ok = 1;
				// Looks for the only <string> record that starts with a '/' (HTML-reference).
				$chapterPagePath = $xpath->xpath("//*[ident = " . $child->ident . "]/moduleConfiguration/config//string[text() = 'file']/following::string[1]");
				foreach ($chapterPagePath as $chapterPage) {
					$chapterPageItem = $chapterPage;
				}
				if (isset($chapterPageItem)) {
					// UTF-8 encoding is applied for preservation of unique symbols (like u umlaut).
					if (substr($chapterPageItem, -4) == "html" || substr($chapterPageItem, -3) == "htm") {
						if (file_exists($expath . "/coursefolder" . $chapterPageItem)) {
							$page = file_get_contents($expath . "/coursefolder" . $chapterPageItem);
							$chapterObject = new ChapterPage(htmlspecialchars($page, ENT_QUOTES, "UTF-8"), (string) substr($chapterPageItem, 1));
							$chapterObject->setSubType("page");
						}
						else {
							echo "<p style='color:red;'> WARNING - " . $subjectPageItem . " not found in OLAT backup file, this page will be ignored in the Moodle course.</p>";
							$ok = 0;
						}
					}
					else {
						$chapterObject = new ChapterResource($chapterPagePath);
						$chapterObject->setSubType("resource");
					}
				}
				else {
					$emptyHTML = "xxx";
					$chapterObject = new ChapterPage($emptyHTML);
					$chapterObject->setSubType("emptypage");
				}
									unset($chapterPageItem);
				break;

				// URL
				case "tu":
					$ok = 1;
					$urlPart1Path = $xpath->xpath("//*[ident = " . $child->ident . "]/moduleConfiguration/config//string[text() = 'host']/following::string[1]");
					foreach ($urlPart1Path as $up1p) {
						$urlPart1 = $up1p;
					}		
					$urlPart2Path = $xpath->xpath("//*[ident = " . $child->ident . "]/moduleConfiguration/config//string[text() = 'uri']/following::string[1]");
					foreach ($urlPart2Path as $up2p) {
						$urlPart2 = $up2p;
					}
					$urlPart3Path = $xpath->xpath("//*[ident = " . $child->ident . "]/moduleConfiguration/config//string[text() = 'query']/following::string[1]");
					foreach ($urlPart3Path as $up3p) {
						$urlPart3 = $up3p;
					}
					
					if (isset($urlPart1)) {
						$url = "http://" . $urlPart1;
						if (isset($urlPart2)) {
							$url = $url . $urlPart2;
						}
						if (isset($urlPart3)) {
							$url = $url . "?" . $urlPart3;
						}
						$chapterObject = new ChapterURL($url);
					}
					break;
			
			// Wiki
			case "wiki":
				$ok = 1;
				$chapterObject = new ChapterWiki();
				break;
		}
		
		if ($ok != 0 && isset($chapterObject)) {
			$chapterObject->setChapterID(isset($child->ident) ? (string) $child->ident : null);
			$chapterObject->setType(isset($child->type) ? (string) $child->type : null);
			$chapterObject->setShortTitle(isset($child->shortTitle) ? (string) $child->shortTitle : null);
			$chapterObject->setLongTitle(isset($child->longTitle) ? (string) $child->longTitle : null);
			$chapterObject->setIndentation($indentation);
			
			olatGetSubjects($chapterObject, $child->ident, $xpath, $expath, $indentation);
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
function olatGetSubjects(&$object, $id, $xpath, $pathCourse, &$indentation) {
	$subjects = $xpath->xpath("/org.olat.course.Structure//*[ident='" . $id . "']/children/*[type = 'st' or type = 'sp' or type = 'bc' or type = 'en' or type = 'iqtest' or type = 'iqself' or type = 'iqsurv' or type = 'tu' or type = 'wiki']");
	if ($subjects != null) {
		$indentation++;
		foreach ($subjects as $schild) {
			$ok = 0;
			switch ($schild->type) {
				// Tests/Quizzes
				case "iqtest":
				case "iqself":
				case "iqsurv":
					$ok = 1;
					$subjectObject = new Subject;
					break;
				
				// Enrollment
				case "en":
					$ok = 1;
					$subjectLearningObject = $xpath->xpath("//*[ident = " . $schild->ident . "]/learningObjectives");
					foreach ($subjectLearningObject as $subjectLearningObjectItem) {
						$subjectLearningObjectItems = (string) $subjectLearningObjectItem;
					}
					$subjectObject = new SubjectLearningObjectives((string) $subjectLearningObjectItems);
					break;
				
				// Directory
				case "bc":
					$ok = 1;
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
				
				// Page & Structure
				case "sp":
				case "st":
					$ok = 1;
					// Looks for the only <string> record that starts with a '/' (HTML-reference).
					$subjectPagePath = $xpath->xpath("//*[ident = " . $schild->ident . "]/moduleConfiguration/config//string[text() = 'file']/following::string[1]");
					foreach ($subjectPagePath as $subjectPage) {
						$subjectPageItem = $subjectPage;
					}
					if (isset($subjectPageItem)) {
						if (substr($subjectPageItem, -4) == "html" || substr($subjectPageItem, -3) == "htm") {
							if (file_exists($pathCourse . "/coursefolder" . $subjectPageItem)) {
								$page = file_get_contents($pathCourse . "/coursefolder" . $subjectPageItem);
								// UTF-8 encoding is applied for preservation of unique symbols (like u umlaut).
								$subjectObject = new SubjectPage(htmlspecialchars($page, ENT_QUOTES, "UTF-8"), (string) substr($subjectPageItem, 1));
								$subjectObject->setSubjectSubType("page");
							}
							else {
								echo "<p style='color:red;'> WARNING - " . $subjectPageItem . " not found in OLAT backup file, this page will be ignored in the Moodle course.</p>";
								$ok = 0;
							}
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
						$emptyHTML = "xxx";
						$subjectObject = new SubjectPage($emptyHTML);
						$subjectObject->setSubjectSubType("emptypage");
					}
					unset($subjectPageItem);
					break;
					
				// URL
				case "tu":
					$ok = 1;
					$urlPart1Path = $xpath->xpath("//*[ident = " . $schild->ident . "]/moduleConfiguration/config//string[text() = 'host']/following::string[1]");
					foreach ($urlPart1Path as $up1p) {
						$urlPart1 = $up1p;
					}		
					$urlPart2Path = $xpath->xpath("//*[ident = " . $schild->ident . "]/moduleConfiguration/config//string[text() = 'uri']/following::string[1]");
					foreach ($urlPart2Path as $up2p) {
						$urlPart2 = $up2p;
					}
					$urlPart3Path = $xpath->xpath("//*[ident = " . $schild->ident . "]/moduleConfiguration/config//string[text() = 'query']/following::string[1]");
					foreach ($urlPart3Path as $up3p) {
						$urlPart3 = $up3p;
					}
					
					if (isset($urlPart1)) {
						$url = "http://" . $urlPart1;
						if (isset($urlPart2)) {
							$url = $url . $urlPart2;
						}
						if (isset($urlPart3)) {
							$url = $url . "?" . $urlPart3;
						}
						$subjectObject = new SubjectURL($url);
					}
					break;
					
				// Wiki
				case "wiki":
					$ok = 1;
					$subjectObject = new SubjectWiki();
					break;
			}

			if ($ok != 0 && isset($subjectObject)) {
				$subjectObject->setSubjectID(isset($schild->ident) ? (string) $schild->ident : null);
				$subjectObject->setSubjectType(isset($schild->type) ? (string) $schild->type : null);
				$subjectObject->setSubjectShortTitle(isset($schild->shortTitle) ? (string) $schild->shortTitle : null);
				$subjectObject->setSubjectLongTitle(isset($schild->longTitle) ? (string) $schild->longTitle : null);
				$subjectObject->setSubjectIndentation($indentation);
				// Recursion for deeper children.
				olatGetSubjects($subjectObject, $schild->ident, $xpath, $pathCourse, $indentation);
				$object->setSubject($subjectObject);
			}
		}
		$indentation--;
	}
}

?>
