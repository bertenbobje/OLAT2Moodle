<?php

/**********************************************************
/* Collection of objects for storing OLAT data.
/**********************************************************
/* Bert Truyens
/*********************************************************/

//                                             ________________
// Course (1)                            inf. |                |
//   1 |___> Chapter (1+)										  v                | recursion
//      inf.   | |_______________________> Subject (1+) _______|
//             |1   1             inf.        | 1        1
// Folder      |                              | 
//        inf. |                         inf. |
//             - ChapterPage                  - SubjectPage
//             - ChapterLearningObject        - SubjectLearningObject
//             - ChapterDropFolder            - SubjectDropFolder
//             - ChapterURL										- SubjectURL
//             - ChapterResource              - SubjectResource
//             - ChapterTest                  - SubjectTest
//                    | 1                          1 |
//                    |                              |
//                    |______________________________|
//                                    | inf.
//                                    |
//                              - TestQuestion ---------------- - TestAnswer
//                                              1         inf.
//

///////////////////////////////////////////////////////////
// COURSE /////////////////////////////////////////////////
///////////////////////////////////////////////////////////
class Course {
	
	protected $id;
	protected $type;
	protected $shortTitle;
	protected $longTitle;
	protected $rootDir;
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
	
	public function setRootDir($rootDir) {
		$this->rootDir = $rootDir;
	}
	
	public function getRootDir() {
		return $this->rootDir;
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

	protected $chapterID;
	protected $type;
	protected $subtype;
	protected $shortTitle;
	protected $longTitle;
	protected $indentation;
	protected $subjects = array();
	
	public function __construct($chapterID = 0, $type = "", $shortTitle = "", $longTitle = "") {
		$this->chapterID = $chapterID;
		$this->type = $type;
		$this->shortTitle = $shortTitle;
		$this->longTitle = $longTitle;
	}
	
	public function setChapterID($chapterID) {
		$this->chapterID = $chapterID;
	}
	
	public function getChapterID() {
		return $this->chapterID;
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function setSubType($subtype) {
		$this->subtype = $subtype;
	}
	
	public function getSubType() {
		return $this->subtype;
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

	public function setIndentation($indentation) {
		$this->indentation = $indentation;
	}
	
	public function getIndentation() {
		return $this->indentation;
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
	protected $contentfile;
	
	public function __construct($htmlPage, $contentfile = "") {
		$this->htmlPage = $htmlPage;
		$this->contentfile = $contentfile;
	}
	
	public function setChapterPage($htmlPage) {
		$this->htmlPage = $htmlPage;
	}
	
	public function getChapterPage() {
		return $this->htmlPage;
	}
	
	public function setContentFile($contentfile) {
		$this->contentfile = $contentfile;
	}
	
	public function getContentFile() {
		return $this->contentfile;
	}
	
}

///////////////////////////////////////////////////////////
// CHAPTER RESOURCE ///////////////////////////////////////
///////////////////////////////////////////////////////////
class ChapterResource extends Chapter {
	
	protected $resource;
	
	public function __construct($resource) {
		$this->resource = $resource;
	}
	
	public function setChapterResource($resource) {
		$this->resource = $resource;
	}
	
	public function getChapterResource() {
		return $this->resource;
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
// CHAPTER URL ////////////////////////////////////////////
///////////////////////////////////////////////////////////
class ChapterURL extends Chapter {

	protected $url;
	
	public function __construct($url) {
		$this->url = $url;
	}
	
	public function setURL($url) {
		$this->url = $url;
	}
	
	public function getURL() {
		return $this->url;
	}
	
}

///////////////////////////////////////////////////////////
// CHAPTER WIKI ///////////////////////////////////////////
///////////////////////////////////////////////////////////
class ChapterWiki extends Chapter {
	
	public function __construct() {}
	
}

///////////////////////////////////////////////////////////
// CHAPTER TEST ///////////////////////////////////////////
///////////////////////////////////////////////////////////
class ChapterTest extends Chapter {
	
	protected $questions = array();
	protected $attempts;
	protected $intropage;
	protected $scoremodel;
	protected $selectionnumber;
	protected $ordertype;
	protected $information;
	
	public function __construct($questions = "", $attempts = "", $intropage = "", $scoremodel = "", $selectionnumber = "", $ordertype = "", $information = "") {
		$this->questions = $questions;
		$this->attempts = $attempts;
		$this->intropage = $intropage;
		$this->scoremodel = $scoremodel;
		$this->selectionnumber = $selectionnumber;
		$this->ordertype = $ordertype;
		$this->information = $information;
	}
	
	public function setQuestions($questions) {
		array_push($this->questions, $questions);
	}
	
	public function getQuestions() {
		return $this->questions;
	}
	
	public function setAttempts($attempts) {
		array_push($this->attempts, $attempts);
	}
	
	public function getAttempts() {
		return $this->attempts;
	}
	
	public function setIntroPage($intropage) {
		$this->intropage = $intropage;
	}
	
	public function getIntroPage() {
		return $this->intropage;
	}
	
	public function setScoreModel($scoremodel) {
		$this->scoremodel = $scoremodel;
	}
	
	public function getScoreModel() {
		return $this->scoremodel;
	}
	
	public function setSelectionNumber($selectionnumber) {
		$this->selectionnumber = $selectionnumber;
	}
	
	public function getSelectionNumber() {
		return $this->selectionnumber;
	}
	
	public function setOrderType($ordertype) {
		$this->ordertype = $ordertype;
	}
	
	public function getOrderType() {
		return $this->ordertype;
	}
	
	public function setInformation($information) {
		$this->information = $information;
	}
	
	public function getInformation() {
		return $this->information;
	}
	
}

///////////////////////////////////////////////////////////
// SUBJECT ////////////////////////////////////////////////
///////////////////////////////////////////////////////////
class Subject {

	protected $id;
	protected $type;
	protected $subtype;
	protected $shortTitle;
	protected $longTitle;
	protected $indentation;
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
	
	public function setSubjectSubType($subtype) {
		$this->subtype = $subtype;
	}
	
	public function getSubjectSubType() {
		return $this->subtype;
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
	
	public function setSubjectIndentation($indentation) {
		$this->indentation = $indentation;
	}
	
	public function getSubjectIndentation() {
		return $this->indentation;
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
	protected $contentfile;
	
	public function __construct($htmlPage, $contentfile = "") {
		$this->htmlPage = $htmlPage;
		$this->contentfile = $contentfile;
	}
	
	public function setSubjectPage($htmlPage) {
		$this->htmlPage = $htmlPage;
	}
	
	public function getSubjectPage() {
		return $this->htmlPage;
	}
	
	public function setSubjectContentFile($contentfile) {
		$this->contentfile = $contentfile;
	}
	
	public function getSubjectContentFile() {
		return $this->contentfile;
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
// SUBJECT URL ////////////////////////////////////////////
///////////////////////////////////////////////////////////
class SubjectURL extends Subject {

	protected $url;
	
	public function __construct($url) {
		$this->url = $url;
	}
	
	public function setSubjectURL($url) {
		$this->url = $url;
	}
	
	public function getSubjectURL() {
		return $this->url;
	}
	
}

///////////////////////////////////////////////////////////
// SUBJECT RESOURCE ///////////////////////////////////////
///////////////////////////////////////////////////////////
class SubjectResource extends Subject {
	
	protected $resource;
	
	public function __construct($resource) {
		$this->resource = $resource;
	}
	
	public function setSubjectResource($resource) {
		$this->resource = $resource;
	}
	
	public function getSubjectResource() {
		return $this->resource;
	}
	
}

///////////////////////////////////////////////////////////
// SUBJECT WIKI ///////////////////////////////////////////
///////////////////////////////////////////////////////////
class SubjectWiki extends Subject {
	
	public function __construct() {}
	
}

///////////////////////////////////////////////////////////
// SUBJECT TEST ///////////////////////////////////////////
///////////////////////////////////////////////////////////
class SubjectTest extends Subject {
	
	protected $questions = array();
	protected $attempts;
	protected $intropage;
	protected $scoremodel;
	protected $selectionnumber;
	protected $ordertype;
	protected $information;
	
	public function __construct($questions = "", $attempts = "", $intropage = "", $scoremodel = "", $selectionnumber = "", $ordertype = "", $information = "") {
		$this->questions = $questions;
		$this->attempts = $attempts;
		$this->intropage = $intropage;
		$this->scoremodel = $scoremodel;
		$this->selectionnumber = $selectionnumber;
		$this->ordertype = $ordertype;
		$this->information = $information;
	}
	
	public function setQuestions($questions) {
		array_push($this->questions, $questions);
	}
	
	public function getQuestions() {
		return $this->questions;
	}
	
	public function setAttempts($attempts) {
		array_push($this->attempts, $attempts);
	}
	
	public function getAttempts() {
		return $this->attempts;
	}
	
	public function setIntroPage($intropage) {
		$this->intropage = $intropage;
	}
	
	public function getIntroPage() {
		return $this->intropage;
	}
	
	public function setScoreModel($scoremodel) {
		$this->scoremodel = $scoremodel;
	}
	
	public function getScoreModel() {
		return $this->scoremodel;
	}
	
	public function setSelectionNumber($selectionnumber) {
		$this->selectionnumber = $selectionnumber;
	}
	
	public function getSelectionNumber() {
		return $this->selectionnumber;
	}
	
	public function setOrderType($ordertype) {
		$this->ordertype = $ordertype;
	}
	
	public function getOrderType() {
		return $this->ordertype;
	}
	
	public function setInformation($information) {
		$this->information = $information;
	}
	
	public function getInformation() {
		return $this->information;
	}
	
}

///////////////////////////////////////////////////////////
// TEST QUESTION //////////////////////////////////////////
///////////////////////////////////////////////////////////

class TestQuestion {

	protected $questionID;
	protected $question;
	protected $questiontype;
	protected $answers = array();
	
	public function __construct($questionID, $question, $questiontype, $answer) {
		$this->questionID = $questionID;
		$this->question = $question;
		$this->questiontype = $questiontype;
		$this->answers = $answers;
	}
	
	public function setQuestionID($questionID) {
		$this->questionID = $questionID;
	}
	
	public function getQuestionID() {
		return $this->questionID;
	}
	
	public function setQuestion($question) {
		$this->question = $question;
	}
	
	public function getQuestion() {
		return $this->question;
	}
	
	public function setQuestionType($questiontype) {
		$this->questiontype = $questiontype;
	}
	
	public function getQuestionType() {
		return $this->questiontype;
	}
	
	public function setAnswers($answers) {
		array_push($this->answers, $answers);
	}
	
	public function getAnswers() {
		return $this->answers;
	}
	
}

///////////////////////////////////////////////////////////
// TEST ANSWER ////////////////////////////////////////////
///////////////////////////////////////////////////////////

class TestAnswer {

	protected $answer;
	protected $correct;
	
	public function __construct($answer, $correct) {
		$this->answer = $answer;
		$this->correct = $correct;
	}
	
	public function setAnswer($answer) {
		$this->answer = $answer;
	}
	
	public function getAnswer() {
		return $this->answer;
	}
	
	public function setCorrect($correct) {
		$this->correct = $correct;
	}
	
	public function getCorrect() {
		return $this->correct;
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
