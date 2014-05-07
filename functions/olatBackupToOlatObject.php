<?php

require_once("classes/olatclasses.php");

require_once("functions/general.php");

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
	
	$fileName = $_FILES["file"]["name"];
	$fileExtension = substr($fileName, strrpos($fileName, "."));
	if ($fileExtension != ".zip") {
		echo "<p style='color:red;'>ERROR - " . $fileExtension . " uploaded, .zip expected</p><a href='index.html'>Go to start page</a>";
		return null;
	}
	else {
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
			
			// ZipArchive has trouble with unzipping files with special characters in Linux.
			// Setting the locale to de_DE solves this problem.
			if (PHP_OS == "Linux") {
				if (!setlocale(LC_ALL, 'de_DE@euro')) {
					echo "<p style='color:darkorange;'>Can't set locale to de_DE, this means that HTML files with special characters in the filename might not load (LINUX ONLY)</p>";
				}
			}
			// Extract the .zip to the path.
			if ($zip->extractTo($expath)) {
				// Checks if double file references in the coursefolder are present.
				checkdoublefilereference($zip);
				$zip->close();
			}
			else {
				echo "<p style='color:red;'>ERROR - Error parsing ZIP, are you sure the .zip file isn't corrupt?</p><a href='index.html'>Go to start page</a>";
				return null;
			}
			
			// $olat will be the root for the XML file
			if (file_exists($expath . "/runstructure.xml")) {
				$olat = file_get_contents($expath . "/runstructure.xml");
				// xPath initialisation for easier access to xml nodes.
				$doc = new DOMDocument();
				$doc->loadXML($olat);
				$xpath = simplexml_load_file($expath . "/runstructure.xml", 'SimpleXMLElement', LIBXML_NOCDATA);
				echo "<p>OK - OLAT backup file opened successfully</p>";
				
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
				
				// Some structures contain learning objectives
				if ((string) $item->rootNode->type == "st") {
					$chapterLearningObject = $xpath->xpath("//*[ident = " . (string) $item->rootNode->ident . "]/learningObjectives");
					foreach ($chapterLearningObject as $chapterLearningObjectItem) {
						$chapterLearningObjectItems = (string) $chapterLearningObjectItem;
					}
					if (isset($chapterLearningObjectItems) && !empty($chapterLearningObjectItems)) {
						$chapterObject = new ChapterPage(htmlspecialchars($chapterLearningObjectItems, ENT_QUOTES, "UTF-8"), "LEARNINGOBJECTIVES");
						$chapterObject->setChapterID("99999999999999");
						$chapterObject->setType("sp");
						$chapterObject->setShortTitle("Learning objectives");
						$chapterObject->setLongTitle("Learning objectives");
						$chapterObject->setIndentation(0);
						$chapterObject->setSubType("page");
						
						$course->setChapter($chapterObject);
					}
				}
				
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
							$chapterObject = new ChapterTest();
							$testFolder = $expath . "/export/" . $child->ident;
							$newChapterObject = olatQuizParse($chapterObject, $testFolder, "chapter");
							$chapterObject = $newChapterObject;
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
							$course_map = listFolderFiles($expath . "/export/" . $child->ident);
							foreach ($course_map as $courseFile) {
								$location = $expath . "/export/" . $child->ident . "/" . $courseFile;
								$folderObject = new Folder(
											(string) preg_replace("/[\/\\\]/", "", substr($courseFile, strrpos($courseFile, DIRECTORY_SEPARATOR))),
											(string) $location,
											(string) filesize($location),
											(string) filetype($location),
											(string) date("F d Y H:i:s", filemtime($location)));
								$chapterObject->setChapterFolders($folderObject);
							}
							break;
							
						// Page & Structure
						case "sp":
						case "st":
							$ok = 1;
							// Looks for the HTML file (if it exists)
							$chapterPagePath = $xpath->xpath("//*[ident = " . $child->ident . "]/moduleConfiguration/config//string[starts-with(.,'/')]");
							foreach ($chapterPagePath as $chapterPage) {
								$chapterPageItem = $chapterPage;
							}
							if (isset($chapterPageItem)) {
								if (substr($chapterPageItem, -4) == "html" || substr($chapterPageItem, -3) == "htm") {
									if (file_exists($expath . "/coursefolder" . $chapterPageItem)) {
										$page = file_get_contents($expath . "/coursefolder" . $chapterPageItem);
										// UTF-8 encoding is applied for preservation of unique symbols (like u umlaut).
										$chapterObject = new ChapterPage(htmlspecialchars($page, ENT_QUOTES, "UTF-8"), (string) substr($chapterPageItem, 1));
										$chapterObject->setSubType("page");
									}
									else {
										echo "<p style='color:darkorange;'>WARNING - " . $chapterPageItem . " not found in OLAT backup file, this page will be ignored in the Moodle course.</p>";
										$ok = 0;
									}
								}
								else {
									foreach ($chapterPagePath as $cppath) {
										$cpp = $cppath;
									}
									$chapterObject = new ChapterResource(substr($cpp, 1));
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
						
						// Task
						case "ta":
							$ok = 1;
							$taskTexts = $xpath->xpath("//*[ident = " . $child->ident . "]/moduleConfiguration/config//string[text() = 'task_text']/following::string[1]");
							foreach ($taskTexts as $t) {
								$taskText .= $t;
							}
							$chapterObject = new ChapterTask($taskText);
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
			else {
				echo "<p style='color:red;'>ERROR - Error reading XML, are you sure you uploaded an OLAT export .zip?</p><a href='index.html'>Go to start page</a>";
				return null;
			}
		}
		else {
			echo "<p style='color:red;'>ERROR - Error parsing ZIP, are you sure the .zip file isn't corrupt?</p><a href='index.html'>Go to start page</a>";
			return null;
		}
	}
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
//  &$indentation = The indentation of the OLAT subject
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
					$subjectObject = new SubjectTest();
					$testFolder = $pathCourse . "/export/" . $schild->ident;
					$newSubjectObject = olatQuizParse($subjectObject, $testFolder, "subject");
					$subjectObject = $newSubjectObject;
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
					$course_map = listFolderFiles($pathCourse . "/export/" . $schild->ident);
					foreach ($course_map as $courseFile) {
						$location = $pathCourse. "/export/" . $schild->ident . "/" . $courseFile;
						$folderObject = new Folder(
									(string) preg_replace("/[\/\\\]/", "", substr($courseFile, strrpos($courseFile, DIRECTORY_SEPARATOR))),
									(string) $location,
									(string) filesize($location),
									(string) filetype($location),
									(string) date("F d Y H:i:s", filemtime($location)));
						$subjectObject->setSubjectFolders($folderObject);
					}
					break;
				
				// Page & Structure
				case "sp":
				case "st":
					$ok = 1;
					// Looks for the HTML file (if it exists)
					$subjectPagePath = $xpath->xpath("//*[ident = " . $schild->ident . "]/moduleConfiguration/config//string[starts-with(.,'/')]");
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
								echo "<p style='color:darkorange;'>WARNING - " . $subjectPageItem . " not found in OLAT backup file, this page will be ignored in the Moodle course.</p>";
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
				
				// Task
				case "ta":
					$ok = 1;
					$taskTexts = $xpath->xpath("//*[ident = " . $child->ident . "]/moduleConfiguration/config//string[text() = 'task_text']/following::string[1]");
					foreach ($taskTexts as $t) {
						$taskText .= $t;
					}
					$subjectObject = new SubjectTask($taskText);
					break;
			}

			if ($ok != 0 && isset($subjectObject)) {
				$subjectObject->setSubjectID(isset($schild->ident) ? (string) $schild->ident : null);
				$subjectObject->setSubjectType(isset($schild->type) ? (string) $schild->type : null);
				$subjectObject->setSubjectShortTitle(isset($schild->shortTitle) ? (string) $schild->shortTitle : null);
				$subjectObject->setLongTitle(isset($schild->longTitle) ? (string) $schild->longTitle : null);
				$subjectObject->setSubjectIndentation($indentation);
				// Recursion for deeper children.
				olatGetSubjects($subjectObject, $schild->ident, $xpath, $pathCourse, $indentation);
				$object->setSubject($subjectObject);
			}
		}
		$indentation--;
	}
}

// Reads out the Quiz (QTI) files and puts them in their respective objects.
//
// PARAMETERS
// -> $object = the Test object (chapter or subject)
//      $path = Path to quiz folder (/coursefolder/[ident]/)
//  $olatType = Either chapter or subject
//
function olatQuizParse($object, $path, $olatType) {
	$QObject = $object;

	// Unpack the repo.zip archive
	$testZip = new ZipArchive;
	if ($testZip->open($path . "/repo.zip")) {
		// ZipArchive has trouble with unzipping files with special characters in Linux.
		// Setting the locale to de_DE solves this problem.
		if (PHP_OS == "Linux") {
			if (!setlocale(LC_ALL, 'de_DE@euro')) {
				echo "<p style='color:darkorange;'>Can't set locale to de_DE, this means that HTML files with special characters in the filename might not load (LINUX ONLY)</p>";
			}
		}
		if (!file_exists($path . "/repo") and !is_dir($path . "/repo")) {
			mkdir($path . "/repo", 0777, true);
		}
		$testZip->extractTo($path . "/repo");
		$testZip->close();
	}
	
	// Load the important XML files in a SimpleXMLElement
	$repoXml = new SimpleXMLElement($path . "/repo.xml", null, true);
	
	$filename = $path . "/repo/qti.xml";
	$qtiXml = new SimpleXMLElement($filename, null, true);
	
	$qtiSections = $qtiXml->assessment->section;
  $qtiCategories = array();
	
	if ($olatType == "chapter") {
		$testObject = new ChapterTest;
	}
	else {
		$testObject = new SubjectTest;
	}
	
	$testObject->setTitle((string) getDataIfExists($qtiXml, 'assessment', 'attributes()', 'title'));
	
	$qtiDescription = (string) getDataIfExists($qtiXml, 'assessment', 'objectives', 'material', 'mattext');
	$qtiDescription = str_replace("<![CDATA[", "", $qtiDescription);
	$qtiDescription = str_replace("]]>", "", $qtiDescription);
	$testObject->setDescription($qtiDescription);
	
	$testObject->setDuration((string) getDataIfExists($qtiXml, 'assessment', 'duration'));
	$testObject->setPassingScore((string) getDataIfExists($qtiXml, 'assessment', 'outcomes_processing', 'outcomes', 'decvar', 'attributes()', 'cutvalue'));

  // Loop through each section
  foreach ($qtiSections as $qtiSection) {
    $sectionObject = new QuizSection(
						(string) getDataIfExists($qtiSection, 'attributes()', 'ident'), 
						(string) getDataIfExists($qtiSection, 'attributes()', 'title'), 
						(string) getDataIfExists($qtiSection, 'objectives', 'material', 'mattext'), 
						(string) getDataIfExists($qtiSection, 'selection_ordering', 'order', 'attributes()', 'order_type'),
						(string) getDataIfExists($qtiSection, 'selection_ordering', 'selection', 'selection_number')
		);
    $testObject->setQuizSection($sectionObject);
		
    // Loop through each item
    $qtiItems = getDataIfExists($qtiSection, 'item');
    foreach ($qtiItems as $qtiItem) {
      // Each question type has be treated differently
      $questionType = getQuestionType($qtiItem->attributes()->ident);
			switch ($questionType) {
				case "MCQ":
					$QObject = new MultipleChoiceQuestion;
					break;
				case "SCQ":
					$QObject = new SingleChoiceQuestion;
					break;
				case "FIB":
					$QObject = new FillInBlanks;
					break;
				case "ESSAY":
					$QObject = new EssayQuestion;
					break;
			}
			$QObject->parseXML($qtiItem);
			$question = (string) getDataIfExists($qtiItem, 'presentation', 'material', 'mattext');
			if ($questionType == 'FIB') {
				// For FIB
				$question = (string) getDataIfExists($qtiItem, 'presentation', 'flow', 'material', 'mattext');
				$content = unserialize($QObject->content);
				$QObject->setContent($content);
			}
			$QObject->setQuestion($question);
			$sectionObject->setItem($QObject);
    }
  }
	return $testObject;
}

?>
