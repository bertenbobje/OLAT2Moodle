<?php

/**********************************************************
/* Functions used in olat2moodle.php.
/**********************************************************
/* Bert Truyens
/*********************************************************/

require_once("classes/olatclasses.php");
require_once("classes/moodleclasses.php");
require_once("classes/generalclasses.php");

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
				
			// Page
			case "sp":
				// Looks for the only <string> record that starts with a '/' (HTML-reference).
				$chapterPagePath = $xpath->xpath("//*[ident = " . $child->ident . "]/moduleConfiguration/config//string[starts-with(., '/')]");
				foreach ($chapterPagePath as $chapterPage) {
					$noPage++;
					$chapterPageItem = $chapterPage;
				}
				if (isset($chapterPageItem)) {
					// UTF-8 encoding is applied for preservation of unique symbols (like u umlaut).
					$page = file_get_contents($expath . "/coursefolder" . $chapterPageItem);
					if (substr($chapterPageItem, -4) == "html") {
						$chapterObject = new ChapterPage(htmlspecialchars($page, ENT_QUOTES, "UTF-8"));
						// $chapterObject = new ChapterPage(iconv(mb_detect_encoding($page, mb_detect_order(), true), "UTF-8", $page));
					}
					else {
						$chapterObject = new ChapterPage("PDF");
					}
				}
				else {
					$noPage++;
					$emptyHTML = "xxx";
					$chapterObject = new ChapterPage($emptyHTML);
				}
				break;
				
			// Structure
			case "st":
				$noPage++;
				$chapterObject = new Chapter();
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
						$url = $urlPart1 . $urlPart2;
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
		$noStruct = false;
		$moodleSection = new Section($olatChapter->getChapterID(), $olatChapter->getShortTitle(), $number);
		$type = $olatChapter->getType();
		if ($type != "st") {
			$activityID = $olatChapter->getChapterID();
			switch ($type) {
				case "sp":
					$moodleActivity = new ActivityPage(moodleFixHTML($olatChapter->getChapterPage()));
					$moduleName = "page";
					$moodleActivity->setActivityID($olatChapter->getChapterID());
					$moodleActivity->setModuleName($moduleName);
					$moodleActivity->setName($olatChapter->getShortTitle());
					$moodleSection->setActivity(isset($moodleActivity) ? $moodleActivity : null);
					break;
			}
		}
		foreach ($olatChapter->getSubject() as $olatSubject) {
			while (is_object($olatSubject)) {
				$type = $olatSubject->getSubjectType();
				$activityID = $olatSubject->getSubjectID();
				switch ($type) {
					case "sp":
						$moodleActivity = new ActivityPage(moodleFixHTML($olatSubject->getSubjectPage()));
						$moduleName = "page";
						break;
				}
				$moodleActivity->setActivityID($olatSubject->getSubjectID());
				$moodleActivity->setSectionID($olatChapter->getChapterID());
				$moodleActivity->setModuleName($moduleName);
				$moodleActivity->setName($olatSubject->getSubjectShortTitle());
				$moodleSection->setActivity(isset($moodleActivity) ? $moodleActivity : null);
				$olatTemp = $olatSubject->getSubject();
				$olatSubject = $olatTemp;
			}
			$moodleCourse->setSection($moodleSection);
			$number++;
		}
	return $moodleCourse;
	}
}

// Removes the DOCTYPE and the <html>, <head> and <body> tags, including end tags.
// Also fixes the <img src=""> tags to be Moodle-specific and makes the
// .mp3, .flv and .wav references Moodle-specific by turning them into <a> tags.
//
// PARAMETERS
// -> $html = The HTML file (as string)
function moodleFixHTML($html) {
	$patternRemoveStart = '/^.+&lt;body&gt;/ism';
	$replaceRemoveStart = '';
	$fixhtmlRemoveStart = preg_replace($patternRemoveStart, $replaceRemoveStart, $html);
	
	$patternRemoveEnd = '/&lt;\/body&gt;.+$/ism';
	$replaceRemoveEnd = '';
	$fixhtmlRemoveEnd = preg_replace($patternRemoveEnd , $replaceRemoveEnd, $fixhtmlRemoveStart);

	// Media files
	$patternMedia = '/^&lt;object.*file\=(.+?)&quot;.*&lt;\/object&gt;/ism';
	$replaceMedia = '&lt;a href=&quot;@@PLUGINFILE@@/$1&quot;&gt;$1&lt;/a&gt;';
	$fixhtmlMedia = preg_replace($patternMedia, $replaceMedia, $fixhtmlRemoveEnd);
	
	// Images
	$patternImages = '/src=&quot;(.+?)&quot;/i';
	$replaceImages = 'src=&quot;@@PLUGINFILE@@/$1&quot;';
	$fixhtmlImages = preg_replace($patternImages, $replaceImages, $fixhtmlMedia);
	
	return $fixhtmlImages;
}

// Creates the backup file that Moodle can use to restore a course.
/*************************************************************************************
 _________________________
|                         |
| MOODLE BACKUP STRUCTURE |
|_________________________|

[ ] = folder   |  (E)  = "empty" -- The same for every backup (empty XML tags).
||| = file     |  (EE) = completely empty (0B) 

[ ] ROOT FOLDER (.mbz)
 |_ [ ] activities ---------- Contains all activities (forums, pages, etc.)
 |   |_ [ ] forum_40 -------} <type of activity>_<moduleID of said activity>
 |   |_ [ ] page_41 --------} 
 |   |   |_ ||| grades.xml -- (E)
 |   |   |_ ||| inforef.xml - Contains references to used media (fileIDs)
 |   |   |_ ||| module.xml -- Contains IDs and general module options
 |   |   |_ ||| page.xml ---- Contains IDs, the name and the content of the page
 |   |   |_ ||| roles.xml --- (E)
 |   |_ ...
 |_ [ ] course -------------- Contains general information about the entire course
 |   |_ ||| course.xml ------ Contains IDs, names and options
 | 	 |_ ||| enrolments.xml -- (E)
 | 	 |_ ||| inforef.xml ----- Contains references (courseID)
 | 	 |_ ||| roles.xml ------- (E)
 |_ [ ] files --------------- Contains the external media files (SHA-1 hashed)
 |   |_ [ ] 6f -------------} First two characters of SHA-1 hash of underlying file
 |   |_ [ ] 9a -------------}
 |   |   |_ ||| 9ac490e3eed9b77a26a91e900af0647ab94554e7 - SHA-1 hashed file
 |   |_ ...
 |_ [ ] sections ------------ Contains all sections (topics)
 |   |_ [ ] section_24 -----} section_<sectionID of said section>
 |   |_ [ ] section_25 -----}
 |   |   |_ ||| inforef.xml - Contains references (E)
 |   |   |_ ||| section.xml - Contains IDs, names, and sequences of sections
 |   |_ ...                                                      --> moduleIDs
 |_ ||| completion.xml ------ (E)
 |_ ||| files.xml ----------- Contains information about the SHA-1 hashed files
 |_ ||| gradebook.xml ------- (E)
 |_ ||| groups.xml ---------- (E)
 |_ ||| moodle_backup.log --- (EE)
 |_ ||| moodle_backup.xml --- General .xml containing all references (biggest XML)
 |_ ||| outcomes.xml -------- (E)
 |_ ||| questions.xml ------- (E)
 |_ ||| roles.xml ----------- (E)
 |_ ||| scales.xml ---------- (E)
*************************************************************************************/
//
// PARAMETERS
// -> $moodleObject = Moodle Object
//        $olatPath = OLAT Object (for the files)
function moodleObjectToMoodleBackup($moodleObject, $olatObject) {
	// Creates a temporary storage name made of random numbers.
	$num = "moodle";
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
	
	// This formats the xml files so it's not all on one line.
	$dom = new DOMDocument('1.0');
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput = true;
	
	// The header of every .xml file is always the same.
	$header = '<?xml version="1.0" encoding="UTF-8"?>';

	// moodle_backup.xml, the general backup .xml file containing everything
	// This will get added to a lot through the process.
	$moodleBackupXmlStart = new SimpleXMLElement($header . "<moodle_backup></moodle_backup>");
	$moodleBackupXml = $moodleBackupXmlStart->addChild('information');
	$moodleBackupXml->addChild('name', 'OLAT2Moodle.mbz');
	$moodleBackupXml->addChild('moodle_version', 2013111802);
	$moodleBackupXml->addChild('moodle_release', '==OLAT2Moodle==');
	$moodleBackupXml->addChild('backup_version', 2013111800);
	$moodleBackupXml->addChild('backup_release', '==OLAT2Moodle==');
	$moodleBackupXml->addChild('backup_date', time());
	$moodleBackupXml->addChild('mnet_remoteusers', 0);
	$moodleBackupXml->addChild('include_files', 1);
	$moodleBackupXml->addChild('include_file_references_to_external_content', 0);
	$moodleBackupXml->addChild('original_wwwroot', '==OLAT2Moodle==');
	$moodleBackupXml->addChild('original_site_identifier_hash', "36492b9f86ba50b90b65082da25006e96b348e1d");
	$moodleBackupXml->addChild('original_course_id', $moodleObject->getID());
	$moodleBackupXml->addChild('original_course_fullname', $moodleObject->getFullName());
	$moodleBackupXml->addChild('original_course_shortname', $moodleObject->getShortName());
	$moodleBackupXml->addChild('original_course_startdate', time());
	$moodleBackupXml->addChild('original_course_contextid', $moodleObject->getContextID());
	$moodleBackupXml->addChild('original_system_contextid', 1);
	$moodleBackupXmlDetails = $moodleBackupXml->addChild('details');
	$moodleBackupXmlDetailsDetail = $moodleBackupXmlDetails->addChild('detail');
	$moodleBackupXmlDetailsDetail->addAttribute('backup_id', sha1($moodleObject->getID()));
	$moodleBackupXmlDetailsDetail->addChild('type', 'course');
	$moodleBackupXmlDetailsDetail->addChild('format', 'moodle2');
	$moodleBackupXmlDetailsDetail->addChild('interactive', 1);
	$moodleBackupXmlDetailsDetail->addChild('mode', 10);
	$moodleBackupXmlDetailsDetail->addChild('execution', 1);
	$moodleBackupXmlDetailsDetail->addChild('executiontime', 1);
	$moodleBackupXmlContents = $moodleBackupXml->addChild('contents');
	$moodleBackupXmlContentsActivities = $moodleBackupXmlContents->addChild('activities');
	$moodleBackupXmlContentsSections = $moodleBackupXmlContents->addChild('sections');
	$moodleBackupXmlContentsCourse = $moodleBackupXmlContents->addChild('course');
	$moodleBackupXmlSettings = $moodleBackupXml->addChild('settings');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'filename');
	$moodleBackupXmlSettingsSetting->addChild('value', 'OLAT2Moodle.mbz');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'imscc11');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'users');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'anonymize');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'role_assignments');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'activities');
	$moodleBackupXmlSettingsSetting->addChild('value', '1');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'blocks');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'filters');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'comments');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'badges');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'calendarevents');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'usercompletion');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'logs');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'grade_histories');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'questionbank');
	$moodleBackupXmlSettingsSetting->addChild('value', '1');
	
	
	////////////////////////////////////////////////////////////////////
	// COURSE
	
	// course folder
	
	$coursePath = $path . "/course";
	
	if (!file_exists($coursePath) and !is_dir($coursePath)) {
		mkdir($coursePath, 0777, true);
	}
	
	// "EMPTY"
	// course/enrolments.xml
	$courseEnrolmentsXml = new SimpleXMLElement($header . "<enrolments></enrolments>");
	$courseEnrolmentsXml->addChild('enrols');
	
	$dom->loadXML($courseEnrolmentsXml->asXML());
	file_put_contents($coursePath . "/enrolments.xml", $dom->saveXML());
	// course/roles.xml
	$courseRolesXml = new SimpleXMLElement($header . "<roles></roles>");
	$courseRolesXml->addChild('role_overrides');
	$courseRolesXml->addChild('role_assignments');
	
	$dom->loadXML($courseRolesXml->asXML());
	file_put_contents($coursePath . "/roles.xml", $dom->saveXML());
	
	// NOT "EMPTY"
	// course/inforef.xml
	$courseInforefXml = new SimpleXMLElement($header . "<inforef></inforef>");
	$courseInforefXml->addChild('roleref')->addChild('role')->addChild('id', $moodleObject->getID());
	
	$dom->loadXML($courseInforefXml->asXML());
	file_put_contents($coursePath . "/inforef.xml", $dom->saveXML());
	// course/course.xml
	$courseCourseXml = new SimpleXMLElement($header . "<course></course>");
	$courseCourseXml->addAttribute('id', $moodleObject->getID());
	$courseCourseXml->addAttribute('contextid', $moodleObject->getID());
	$courseCourseXml->addChild('shortname', $moodleObject->getShortName());
	$courseCourseXml->addChild('fullName', $moodleObject->getFullName());
	$courseCourseXml->addChild('summary', "&lt;p&gt;" . $moodleObject->getFullName() . "&lt;/p&gt;");
	$courseCourseXml->addChild('format', 'topics');
	$courseCourseXml->addChild('startdate', time());
	$courseCourseXml->addChild('visible', 1);
	$courseCourseXml->addChild('defaultgroupingid', 0);
	$courseCourseXml->addChild('lang');
	$courseCourseXml->addChild('timecreated', time());
	$courseCourseXml->addChild('timemodified', time());
	$courseCourseXml->addChild('numsections', count($moodleObject->getSection()));
	
	$dom->loadXML($courseCourseXml->asXML());
	file_put_contents($coursePath . "/course.xml", $dom->saveXML());
	
	// moodle_backup.xml
	$moodleBackupXmlContentsCourse->addChild('courseid', $moodleObject->getID());
	$moodleBackupXmlContentsCourse->addChild('title', $moodleObject->getShortName());
	$moodleBackupXmlContentsCourse->addchild('directory', "course");
	
	////////////////////////////////////////////////////////////////////
	// FILES + files.xml
	
	// files path
	$filesPath = $path . "/files";
	
	// files.xml
	$filesXml = new SimpleXMLElement($header . "<files></files>");
	$fileID = 10;
	
	if (!file_exists($filesPath) and !is_dir($filesPath)) {
		mkdir($filesPath, 0777, true);
	}
	
	// OLAT files
	$olatFilesPath = $olatObject->getRootdir() . "/coursefolder";
	$olatFiles = getDirectoryList($olatObject->getRootdir() . "/coursefolder");
	$fileError = 0;
	foreach ($olatFiles as $olatFile) {
		// Ignore the .html files, they're stored in the .xml itself
		$olatFilePath = $olatFilesPath . "/" . $olatFile;
		if (substr($olatFile, -4) != "html") {
			$fileSHA1 = sha1($olatFile);
			$fileSHA1Dir = $filesPath . "/" . substr($fileSHA1, 0, 2);
			if (!file_exists($fileSHA1Dir) and !is_dir($fileSHA1Dir)) {
				mkdir($fileSHA1Dir, 0777, true);
			}
			if (copy($olatFilesPath . "/" . $olatFile, $fileSHA1Dir . "/" . $fileSHA1)) {
				$filesXmlChild = $filesXml->addChild('file');
				$filesXmlChild->addAttribute('id', $fileID);
				$filesXmlChild->addChild('contenthash', $fileSHA1);
				foreach ($moodleObject->getSection() as $section) {
					foreach ($section->getActivity() as $activity) {
						if (method_exists($activity, 'getContent')) {
							if (strpos($activity->getContent(), $olatFile) !== false) {
								$filesXmlChild->addChild('contextid', $activity->getContextID());
								$activity->setFile($fileID);
							} 
						}
					}
				}
				$filesXmlChild->addChild('component', "mod_page");
				$filesXmlChild->addChild('filearea', "content");
				$filesXmlChild->addChild('itemid', 0);
				$filesXmlChild->addChild('filepath', "/");
				$filesXmlChild->addChild('filename', $olatFile);
				$filesXmlChild->addChild('filesize', filesize($olatFilePath));
				$filesXmlChild->addChild('mimetype', finfo_file(finfo_open(FILEINFO_MIME_TYPE), $olatFilePath));
				$filesXmlChild->addChild('source', $olatFile);
				
				$dom->loadXml($filesXml->asXML());
				file_put_contents($path . "/files.xml", $dom->saveXML());
				$fileID++;
			}
			else {
				echo "<p>ERROR COPYING FILE: " . $olatFile . "</p><br>";
				$fileError++;
			}
		}
	}
	if ($fileError == 0) {
		echo "<p>Files copied</p>";
	}
	else {
		echo "<p>NOK - " . $fileError . " files failed to copy</p>";
	}
	
	////////////////////////////////////////////////////////////////////
	// SECTIONS
	
	// sections folder
	if (!file_exists($path . "/sections") and !is_dir($path . "/sections")) {
		mkdir($path . "/sections", 0777, true);
	}
	
	// This number is for ordening the sections.
	$sectionNumber = 1;
	
	foreach ($moodleObject->getSection() as $section) {
		// Create the folder
		$sectionPath = $path . "/sections/section_" . $section->getSectionID();
		if (!file_exists($sectionPath) and !is_dir($sectionPath)) {
			mkdir($sectionPath, 0777, true);
		}
		
		// "EMPTY"
		// sections/section_[x]/inforef.xml
		$sectionInforefXml = new SimpleXMLElement($header . "<inforef></inforef>");
		
		$dom->loadXML($sectionInforefXml->asXML());
		file_put_contents($sectionPath . "/inforef.xml", $dom->saveXML());
		
		// NOT "EMPTY"
		// sections/section_[x]/section.xml
		$sectionSectionXml = new SimpleXMLElement($header . "<section></section>");
		$sectionSectionXml->addAttribute('id', $section->getSectionID());
		$sectionSectionXml->addChild('number', $section->getNumber());
		$sectionSectionXml->addChild('name', $section->getName());
		$sectionSectionXml->addChild('summary');
		$sectionSectionXml->addChild('summaryformat', 1);
		
		$sectionSequence = "";
		foreach($section->getActivity() as $activity) {
			if ($activity->getSectionID() == $section->getSectionID()) {
				$sectionSequence .= $activity->getModuleID() . ",";
			}
		}
		
		$sectionSectionXml->addChild('sequence', substr($sectionSequence, 0, -1));
		$sectionSectionXml->addChild('visible', 1);
		$sectionSectionXml->addChild('availablefrom', 0);
		$sectionSectionXml->addChild('availableuntil', 0);
		$sectionSectionXml->addChild('showavailability', 0);
		$sectionSectionXml->addChild('groupingid', 0);
		
		$dom->loadXML($sectionSectionXml->asXML());
		file_put_contents($sectionPath . "/section.xml", $dom->saveXML());
		
		// moodle_backup.xml
		$moodleBackupXmlContentsSectionsSection = $moodleBackupXmlContentsSections->addChild('section');
		$moodleBackupXmlContentsSectionsSection->addChild('sectionid', $section->getSectionID());
		$moodleBackupXmlContentsSectionsSection->addChild('title', $section->getName());
		$moodleBackupXmlContentsSectionsSection->addChild('directory', "sections/section_" . $section->getSectionID());
	
		$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
		$moodleBackupXmlSettingsSetting->addChild('level', 'section');
		$moodleBackupXmlSettingsSetting->addChild('section', "section_" . $section->getSectionID());
		$moodleBackupXmlSettingsSetting->addChild('name', "section_" . $section->getSectionID() . "_included");
		$moodleBackupXmlSettingsSetting->addChild('value', 1);
		$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
		$moodleBackupXmlSettingsSetting->addChild('level', 'section');
		$moodleBackupXmlSettingsSetting->addChild('section', "section_" . $section->getSectionID());
		$moodleBackupXmlSettingsSetting->addChild('name', "section_" . $section->getSectionID() . "_userinfo");
		$moodleBackupXmlSettingsSetting->addChild('value', 0);
		
		$sectionNumber++;
	}
	
////////////////////////////////////////////////////////////////////
	// ACTIVITIES
	
	// activities folder
	if (!file_exists($path . "/activities") and !is_dir($path . "/activities")) {
		mkdir($path . "/activities", 0777, true);
	}

	foreach ($moodleObject->getSection() as $section) {
		foreach ($section->getActivity() as $activity) {
			// Create the folder
			$activityPath = $path . "/activities/" . $activity->getModuleName() . "_" . $activity->getModuleID();
			if (!file_exists($activityPath) and !is_dir($activityPath)) {
				mkdir($activityPath, 0777, true);
			}
			
			// "EMPTY"
			// activities/[activity]_[x]/grades.xml
			$activityGradesXml = new SimpleXMLElement($header . "<activity_gradebook></activity_gradebook>");
			$activityGradesXml->addChild('grade_items');
			$activityGradesXml->addChild('grade_letters');
			
			$dom->loadXML($activityGradesXml->asXML());
			file_put_contents($activityPath . "/grades.xml", $dom->saveXML());
			// activities/[activity]_[x]/roles.xml
			$activityRolesXml = new SimpleXMLElement($header . "<roles></roles>");
			$activityRolesXml->addChild('role_overrides');
			$activityRolesXml->addChild('role_assignments');
			
			$dom->loadXML($activityRolesXml->asXML());
			file_put_contents($activityPath . "/roles.xml", $dom->saveXML());
			
			// NOT "EMPTY"
			// activities/[activity]_[x]/inforef.xml
			$activityInforefXml = new SimpleXMLElement($header . "<inforef></inforef>");
			if ($activity->getFile()) {
				$activityInforefXmlFileRef = $activityInforefXml->addChild('fileref');
				foreach ($activity->getFile() as $aFile) {
					$activityInforefXmlFileRefFile = $activityInforefXmlFileRef->addChild('file');
					$activityInforefXmlFileRefFile->addChild('id', $aFile);
				}
			}
			$dom->loadXML($activityInforefXml->asXML());
			file_put_contents($activityPath . "/inforef.xml", $dom->saveXML());
			
			
			// activities/[activity]_[x]/module.xml
			$activityModuleXml = new SimpleXMLElement($header . "<module></module>");
			$activityModuleXml->addAttribute('id', $activity->getActivityID());
			$activityModuleXml->addAttribute('version', 2013110500);
			$activityModuleXml->addChild('modulename', $activity->getModuleName());
			$activityModuleXml->addChild('sectionid', $section->getSectionID());
			$activityModuleXml->addChild('idnumber');
			$activityModuleXml->addChild('added', time());
			$activityModuleXml->addChild('visible', 1);
			$activityModuleXml->addChild('visibleold', 1);
			
			$dom->loadXML($activityModuleXml->asXML());
			file_put_contents($activityPath . "/module.xml", $dom->saveXML());
			
			// activities/[activity]_[x]/[activity].xml
			if ($activity->getModuleName() == "page") {
				$activityActivityXml = new SimpleXMLElement($header . "<activity></activity>");
				$activityActivityXml->addAttribute('id', $activity->getActivityID());
				$activityActivityXml->addAttribute('moduleid', $activity->getModuleID());
				$activityActivityXml->addAttribute('modulename', $activity->getModuleName());
				$activityActivityXml->addAttribute('contextid', $activity->getContextID());
				$activityActivityChildXml = $activityActivityXml->addChild($activity->getModuleName());
				$activityActivityChildXml->addAttribute('id', $activity->getActivityID());
				$activityActivityChildXml->addChild('name', $activity->getName());
				$activityActivityChildXml->addChild('intro', "&lt;p&gt;" . $activity->getName() . "&lt;/p&gt;");
				$activityActivityChildXml->addChild('introformat', 1);
				$activityActivityChildXml->addChild('content', $activity->getContent());
				$activityActivityChildXml->addChild('contentformat', 1);
				$activityActivityChildXml->addChild('legacyfiles', 0);
				$activityActivityChildXml->addChild('legacyfileslast', "$@NULL@$");
				$activityActivityChildXml->addChild('display', 5);
				$activityActivityChildXml->addChild('displayoptions', 'a:1:{s:10:"printintro";s:1:"0";}');
				$activityActivityChildXml->addChild('revision', 1);
				$activityActivityChildXml->addChild('timemodified', time());
				
				$dom->loadXML($activityActivityXml->asXML());
				file_put_contents($activityPath . "/" . $activity->getModuleName() . ".xml", $dom->saveXML());
			}
			
			// moodle_backup.xml
			$moodleBackupXmlContentsActivitiesActivity = $moodleBackupXmlContentsActivities->addChild('activity');
			$moodleBackupXmlContentsActivitiesActivity->addChild('moduleid', $activity->getModuleID());
			$moodleBackupXmlContentsActivitiesActivity->addChild('sectionid', $activity->getSectionID());
			$moodleBackupXmlContentsActivitiesActivity->addChild('modulename', $activity->getModuleName());
			$moodleBackupXmlContentsActivitiesActivity->addChild('title', $activity->getName());
			$moodleBackupXmlContentsActivitiesActivity->addChild('directory', "activities/" . $activity->getModuleName() . "_" . $activity->getModuleID());
		
			$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
			$moodleBackupXmlSettingsSetting->addChild('level', 'activity');
			$moodleBackupXmlSettingsSetting->addChild('activity', $activity->getModuleName() . "_" . $activity->getModuleID());
			$moodleBackupXmlSettingsSetting->addChild('name', $activity->getModuleName() . "_" . $activity->getModuleID() . "_included");
			$moodleBackupXmlSettingsSetting->addChild('value', 1);
			$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
			$moodleBackupXmlSettingsSetting->addChild('level', 'activity');
			$moodleBackupXmlSettingsSetting->addChild('activity', $activity->getModuleName() . "_" . $activity->getModuleID());
			$moodleBackupXmlSettingsSetting->addChild('name', $activity->getModuleName() . "_" . $activity->getModuleID() . "_userinfo");
			$moodleBackupXmlSettingsSetting->addChild('value', 0);
		}
	}
	
	////////////////////////////////////////////////////////////////////
	// ROOT FILES
	
	// "EMPTY"
	// completion.xml
	$completionXml = new SimpleXMLElement($header . "<course_completion></course_completion>");
	
	$dom->loadXML($completionXml->asXML());
	file_put_contents($path . "/completion.xml", $dom->saveXML());
	// gradebook.xml
	$gradebookXml = new SimpleXMLElement($header . "<gradebook></gradebook>");
	$gradebookXml->addChild('grade_categories');
	$gradebookXml->addChild('grade_items');
	$gradebookXml->addChild('grade_letters');
	$gradebookXml->addChild('grade_settings');
	
	$dom->loadXML($gradebookXml->asXML());
	file_put_contents($path . "/gradebook.xml", $dom->saveXML());
	// groups.xml
	$groupsXml = new SimpleXMLElement($header . "<groups></groups>");
	
	$dom->loadXML($groupsXml->asXML());
	file_put_contents($path . "/groups.xml", $dom->saveXML());
	// moodle_backup.log
	file_put_contents($path . "/moodle_backup.log", "");
	// outcomes.xml
	$outcomesXml = new SimpleXMLElement($header . "<outcomes_definition></outcomes_definition>");
	
	$dom->loadXML($outcomesXml->asXML());
	file_put_contents($path . "/outcomes.xml", $dom->saveXML());
	// questions.xml
	$questionsXml = new SimpleXMLElement($header . "<question_categories></question_categories>");
	
	$dom->loadXML($questionsXml->asXML());
	file_put_contents($path . "/questions.xml", $dom->saveXML());
	// roles.xml
	$rolesXml = new SimpleXMLElement($header . "<roles_definition></roles_definition>");
	
	$dom->loadXML($rolesXml->asXML());
	file_put_contents($path . "/roles.xml", $dom->saveXML());
	// scales.xml
	$scalesXml = new SimpleXMLElement($header . "<scales_definition></scales_definition>");
	
	$dom->loadXML($scalesXml->asXML());
	file_put_contents($path . "/scales.xml", $dom->saveXML());
	
	// NOT "EMPTY"
	// moodle_backup.xml
	$dom->loadXML($moodleBackupXmlStart->asXML());
	file_put_contents($path . "/moodle_backup.xml", $dom->saveXML());
	//file_put_contents($path . "/moodle_backup.xml", $moodleBackupXmlStart->asXML());
	
	// .MBZ
	// Creates the .zip file with all the Moodle backup contents
	
	try {
		$zipPath = $path . ".zip";
		App_File_Zip::CreateFromFilesystem($path, $zipPath);
		echo "<p>OK - .zip created</p>";
	}
	catch (App_File_Zip_Exception $e) {
		echo "<p>NOK - .zip failed to create: " . $e . "</p>";
	}
	
	// Renames the .zip to .mbz (.mbz is just a renamed .zip anyway)
	if(rename($zipPath, $path . ".mbz")) {
		echo "<p>OK - .zip renamed to .mbz</p>";
	}
	else {
		echo "<p>NOK - .zip failed to rename</p>";
	}
	
	// Remove both the OLAT and Moodle temporary directory
	rrmdir($path);
	echo "<p>OK - OLAT temp folder removed</p>";
	rrmdir($olatObject->getRootDir());
	echo "<p>OK - Moodle temp folder removed</p>";
	
	return "/tmp/" . $num . ".mbz";
}

///////////////////////////////////////////////////////////
// GENERAL FUNCTIONS //////////////////////////////////////
///////////////////////////////////////////////////////////

// Creates an array to hold the directory list.
//
// PARAMETERS
// -> $dir = The directory
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
//
// PARAMETERS
// -> $dir = The directory
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
