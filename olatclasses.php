<?php

/**********************************************************
/* Collection of objects for storing OLAT data.
/**********************************************************
/* Bert Truyens
/*********************************************************/

//                                             ________________
// Course (1)                                 |                |
//    |___> Chapter (1+)										  v                | recursion
//             | |_______________________> Subject (1+) _______|
//             |                              |
// Folder      |                              |
//             - ChapterPage                  - SubjectPage
//             - ChapterLearningObject        - SubjectLearningObject
//             - ChapterDropFolder            - SubjectDropFolder
//                                            - SubjectTest
//

///////////////////////////////////////////////////////////
// COURSE /////////////////////////////////////////////////
///////////////////////////////////////////////////////////
class Course {
	
	protected $id;
	protected $type;
	protected $shortTitle;
	protected $longTitle;
	protected $chapters = array();
	
	public function __construct($id, $type, $shortTitle, $longTitle) {
		$this->id = $id;
		$this->type = $type;
		$this->shortTitle = $shortTitle;
		$this->longTitle = $longTitle;
	}
	
	public function setID($id) {
		$this->id = $id;
	}
	
	public function getID() {
		return $this->id;
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function setShortTitle($shortTitle) {
		$this->shortTitle = $shortTitle;
	}
	
	public function getShortTitle() {
		return $this->shortTitle;
	}
	
	public function setLongTitle($longTitle) {
		if (empty($longtitle)) {
			$this->longTitle = getShortTitle();
		}
		else {
			$this->longTitle = $longTitle;
		}
	}
	
	public function getLongTitle() {
		return $this->longTitle;
	}
	
	public function setChapter($chapters) {
		array_push($this->chapters, $chapters);
	}
	
	public function getChapter() {
		return $this->chapters;
	}
	
}

///////////////////////////////////////////////////////////
// CHAPTER ////////////////////////////////////////////////
///////////////////////////////////////////////////////////
class Chapter {

	protected $id;
	protected $type;
	protected $shortTitle;
	protected $longTitle;
	protected $subjects = array();
	
	public function __construct($id = 0, $type = "", $shortTitle = "", $longTitle = "") {
		$this->id = $id;
		$this->type = $type;
		$this->shortTitle = $shortTitle;
		$this->longTitle = $longTitle;
	}
	
	public function setID($id) {
		$this->id = $id;
	}
	
	public function getID() {
		return $this->id;
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function setShortTitle($shortTitle) {
		$this->shortTitle = $shortTitle;
	}
	
	public function getShortTitle() {
		return $this->shortTitle;
	}
	
	public function setLongTitle($longTitle) {
		if (empty($longtitle)) {
			$this->longTitle = $this->shortTitle;
		}
		else {
			$this->longTitle = $longTitle;
		}
	}
	
	public function getLongTitle() {
		return $this->longTitle;
	}
	
	public function setSubject($subjects) {
		array_push($this->subjects, $subjects);
	}
	
	public function getSubject() {
		return $this->subjects;
	}
	
}

///////////////////////////////////////////////////////////
// CHAPTER PAGE ///////////////////////////////////////////
///////////////////////////////////////////////////////////
class ChapterPage extends Chapter {
	
	protected $htmlPage;
	
	public function __construct($htmlPage) {
		$this->htmlPage = $htmlPage;
	}
	
	public function setChapterPage($htmlPage) {
		$this->htmlPage = $htmlPage;
	}
	
	public function getChapterPage() {
		return $this->htmlPage;
	}
	
}

///////////////////////////////////////////////////////////
// CHAPTER LEARNING OBJECTIVES ////////////////////////////
///////////////////////////////////////////////////////////
class ChapterLearningObjectives extends Chapter {

	protected $learningObjectives;
	
	public function __construct($learningObjectives) {
		$this->learningObjectives = $learningObjectives;
	}
	
	public function setChapterLearningObjectives($learningObjectives) {
		$this->learningObjectives = $learningObjectives;
	}
	
	public function getChapterLearningObjectives() {
		return $this->learningObjectives;
	}
	
}

///////////////////////////////////////////////////////////
// CHAPTER DROPFOLDER /////////////////////////////////////
///////////////////////////////////////////////////////////
class ChapterDropFolder extends Chapter {

	protected $folders = array();
	
	public function __construct() {}
	
	public function setChapterFolders($folders) {
		array_push($this->folders, $folders);
	}
	
	public function getChapterFolders() {
		return $this->folders;
	}
	
}

///////////////////////////////////////////////////////////
// SUBJECT ////////////////////////////////////////////////
///////////////////////////////////////////////////////////
class Subject {

	protected $id;
	protected $type;
	protected $shortTitle;
	protected $longTitle;
	protected $subjects = array();
	
	public function __construct($id = 0, $type = "", $shortTitle = "", $longTitle = "") {
		$this->id = $id;
		$this->type = $type;
		$this->shortTitle = $shortTitle;
		$this->longTitle = $longTitle;
	}
	
	public function setSubjectID($id) {
		$this->id = $id;
	}
	
	public function getSubjectID() {
		return $this->id;
	}
	
	public function setSubjectType($type) {
		$this->type = $type;
	}
	
	public function getSubjectType() {
		return $this->type;
	}
	
	public function setSubjectShortTitle($shortTitle) {
		$this->shortTitle = $shortTitle;
	}
	
	public function getSubjectShortTitle() {
		return $this->shortTitle;
	}
	
	public function setSubjectLongTitle($longTitle) {
		if (empty($longtitle)) {
			$this->longTitle = $this->shortTitle;
		}
		else {
			$this->longTitle = $longTitle;
		}
	}
	
	public function getSubjectLongTitle() {
		return $this->longTitle;
	}
	
	public function setSubject($subjects) {
		array_push($this->subjects, $subjects);
	}
	
	public function getSubject() {
		return $this->subjects;
	}
	
}

///////////////////////////////////////////////////////////
// SUBJECT PAGE ///////////////////////////////////////////
///////////////////////////////////////////////////////////
class SubjectPage extends Subject {

	protected $htmlPage;
	
	public function __construct($htmlPage) {
		$this->htmlPage = $htmlPage;
	}
	
	public function setSubjectPage($htmlPage) {
		$this->htmlPage = $htmlPage;
	}
	
	public function getSubjectPage() {
		return $this->htmlPage;
	}
	
}

///////////////////////////////////////////////////////////
// SUBJECT LEARNING OBJECTIVES ////////////////////////////
///////////////////////////////////////////////////////////
class SubjectLearningObjectives extends Subject {

	protected $learningObjectives;
	
	public function __construct($learningObjectives) {
		$this->learningObjectives = $learningObjectives;
	}
	
	public function setSubjectLearningObjectives($learningObjectives) {
		$this->learningObjectives = $learningObjectives;
	}
	
	public function getSubjectLearningObjectives() {
		return $this->learningObjectives;
	}
	
}

///////////////////////////////////////////////////////////
// SUBJECT DROPFOLDER /////////////////////////////////////
///////////////////////////////////////////////////////////
class SubjectDropFolder extends Subject {

	protected $folders = array();
	
	public function __construct() {}
	
	public function setSubjectFolders($folders) {
		array_push($this->folders, $folders);
	}
	
	public function getSubjectFolders() {
		return $this->folders;
	}
	
}

///////////////////////////////////////////////////////////
// SUBJECT TEST ///////////////////////////////////////////
///////////////////////////////////////////////////////////
class SubjectTest extends Subject {

	protected $test;

	public function __construct($test) {
		$this->test= $test;
	}

	public function setSubjectTest($test) {
		$this->test = $test;
	}
	
	public function getSubjectTest() {
		return $this->test;
	}

}

///////////////////////////////////////////////////////////
// FOLDER /////////////////////////////////////////////////
///////////////////////////////////////////////////////////
class Folder {

	protected $fileName;
	protected $fileLocation;
	protected $fileSize;
	protected $fileType;
	protected $fileModified;

	public function __construct($fileName = "", $fileLocation = "", $fileSize = "", $fileType = "", $fileModified = "") {
		$this->fileName = $fileName;
		$this->fileLocation = $fileLocation;
		$this->fileSize = $fileSize;
		$this->fileType = $fileType;
		$this->fileModified = $fileModified;
	}
	
	public function setFileName($fileName) {
		$this->fileName = $fileName;
	}
	
	public function getFileName() {
		return $this->fileName;
	}

	public function setFileLocation($fileLocation) {
		$this->fileLocation = $fileLocation;
	}
	
	public function getFileLocation() {
		return $this->fileLocation;
	}

	public function setFileSize($fileSize) {
		$this->fileSize = $fileSize;
	}
	
	public function getFileSize() {
		return $this->fileSize;
	}

	public function setFileType($fileType) {
		$this->fileType = $fileType;
	}
	
	public function getFileType() {
		return $this->fileType;
	}

	public function setFileModified($fileModified) {
		$this->fileModified = $fileModified;
	}
	
	public function getFileModified() {
		return $this->fileModified;
	}
	
}
	
?>
