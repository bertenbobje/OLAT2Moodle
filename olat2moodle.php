<?php

/*************************************************************
/* Converts an .OLAT backup to an object, translates
/* this to a Moodle object, then this object gets used to
/* write the backup files to restore into Moodle.
/*************************************************************
/* Bert Truyens
/************************************************************/

require_once("functions.php");
require_once("olatclasses.php");
require_once("moodleclasses.php");

if(isset($_FILES["file"])) {
	if($_FILES["file"]) {
		
		// Random integer for storing unzips, so that there will be no overwrites.
		$num = "";
		for ($i = 0; $i < 9; $i++) {
			$num .= strval(mt_rand(0, 9));
		}
		
		// Path to the stored .zip on the server.
		$path = $_FILES["file"]["tmp_name"];
		
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
			}
			else {
				echo "<p>Error reading XML.</p><br>";
				echo "<a href='index.php'>Go back</a>";
			}
		}
		else {
			echo "<p>Error parsing file.</p><br>";
			echo "<a href='index.php'>Go back</a>";
		}
	}
}
else {
	echo "<p>No file found, did you land on this page by accident?</p><br>";
	echo "<a href='index.php'>Go back</a>";
}

// Course
$start = $xpath->xpath("/org.olat.course.Structure");
$item = $start[0];

$course = new Course(
		isset($item->rootNode->ident) ? (string) $item->rootNode->ident : null,
		isset($item->rootNode->type) ? (string) $item->rootNode->type : null,
		isset($item->rootNode->shortTitle) ? (string) $item->rootNode->shortTitle : null,
		isset($item->rootNode->longTitle) ? (string) $item->rootNode->longTitle : null);

// Chapters
$chapters = $xpath->xpath("/org.olat.course.Structure/rootNode/children/*[type = 'st' or type = 'sp' or type = 'bc' or type = 'en' or type = 'iqtest' or type = 'iqself' or type = 'iqsurv']");
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
		// Structure
		case "st":
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
	}
	
	if ($noPage != 0) {
		$chapterObject->setID(isset($child->ident) ? (string) $child->ident : null);
		$chapterObject->setType(isset($child->type) ? (string) $child->type : null);
		$chapterObject->setShortTitle(isset($child->shortTitle) ? (string) $child->shortTitle : null);
		$chapterObject->setLongTitle(isset($child->longTitle) ? (string) $child->longTitle : null);
		
		getSubjects($chapterObject, $child->ident, $xpath, $expath);
		$course->setChapter($chapterObject);
	}
}

$moodleCourse = OLATObjectToMoodleObject($course);

echo "<p>MOODLE COURSE</p>";
var_dump($moodleCourse);
var_dump($moodleCourse->getSection());
foreach ($moodleCourse->getSection() as $sct) {
	var_dump($sct->getActivity());
}

moodleObjectToMoodleBackup($moodleCourse);

// Removes the temporary folder and all its contents.
rrmdir($expath);
	
?>
