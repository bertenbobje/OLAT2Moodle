<?php

/**********************************************************
/* Collection of objects for storing Moodle data.
/**********************************************************
/* Bert Truyens
/*********************************************************/

//
// MoodleCourse
//  1 |___> Section
//     inf.   1 |_______________________> Activity 
//                      moduleID's   inf.     |
//                                            |
//                                            - ActivityPage
//                                            - ActivityFolder
//                                            - ActivityURL
//                                            - ActivityResource
//                                            - ActivityWiki
//                                            - ActivityLabel
//                                            - ActivityAssignment
//                                            - ActivityQuiz
//                                                  1 |
//                                                    | inf.
//                                                QuizPage
//                                                  1 |
//                                                    | inf.
//                                               QuizQuestion
//                                                  1 |
//                                   inf.             |
//                  - QuizFeedback _________          |
//                  - QuizPossibility ______|---------
//                                     inf.

///////////////////////////////////////////////////////////
// COURSE /////////////////////////////////////////////////
///////////////////////////////////////////////////////////
class MoodleCourse {
	
	protected $id;
	protected $contextID;
	protected $shortName;
	protected $fullName;
	protected $categoryID;
	protected $sections = array();
	
	public function __construct($id, $shortName, $fullName, $categoryID) {
		$this->id = $id;
		$this->contextID = (string) ($id + 2);
		$this->shortName = $shortName;
		$this->fullName = $fullName;
		$this->categoryID = $categoryID;
	}
	
	public function setID($id) {
		$this->id = $id;
	}
	
	public function getID() {
		return $this->id;
	}
	
	public function setContextID($type) {
		$this->contextID = $contextID;
	}
	
	public function getContextID() {
		return $this->contextID;
	}
	
	public function setShortName($shortName) {
		$this->shortName = $shortName;
	}
	
	public function getShortName() {
		return $this->shortName;
	}
	
	public function setFullName($longName) {
		$this->longName = $longName;
	}
	
	public function getFullName() {
		return $this->fullName;
	}
	
	public function setCategoryID($categoryID) {
		array_push($this->categoryID, $categoryID);
	}
	
	public function getCategoryID() {
		return $this->categoryID;
	}
	
	public function setSection($sections) {
		array_push($this->sections, $sections);
	}
	
	public function getSection() {
		return $this->sections;
	}
	
}

///////////////////////////////////////////////////////////
// SECTION ////////////////////////////////////////////////
///////////////////////////////////////////////////////////
class Section {

	protected $sectionID;
	protected $name;
	protected $number;
	protected $activities = array();
	
	public function __construct($sectionID = "", $name = "", $number = "") {
		$this->sectionID = $sectionID;
		$this->name = $name;
		$this->number = $number;
	}
	
	public function setSectionID($sectionID) {
		$this->sectionID = $sectionID;
	}
	
	public function getSectionID() {
		return $this->sectionID;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function setNumber($number) {
		$this->number = $number;
	}
	
	public function getNumber() {
		return $this->number;
	}
	
	public function setActivity($activities) {
		array_push($this->activities, $activities);
	}
	
	public function getActivity() {
		return $this->activities;
	}
	
}

///////////////////////////////////////////////////////////
// ACTIVITY ///////////////////////////////////////////////
///////////////////////////////////////////////////////////
class Activity {

	protected $activityID;
	protected $moduleID;
	protected $moduleName;
	protected $contextID;
	protected $sectionID;
	protected $olatType;
	protected $name;
	protected $indent;
	protected $book;
	protected $bookcontextID;
	protected $booksubchapter;
	protected $chapterID;
	protected $files = array();
	
	public function __construct($activityID = "", $moduleName = "", $name = "") {
		$this->activityID = $activityID;
		$this->moduleID = (string) ($activityID + 1);
		$this->moduleName = $moduleName;
		$this->contextID = (string) ($activityID + 2);
		$this->name = $name;
	}
	
	public function setActivityID($activityID) {
		$this->activityID = $activityID;
		$this->moduleID = (string) ($activityID + 1);
		$this->contextID = (string) ($activityID + 2);
	}
	
	public function getActivityID() {
		return $this->activityID;
	}
	
	public function setModuleID($moduleID) {
		$this->moduleID = $moduleID;
	}
	
	public function getModuleID() {
		return $this->moduleID;
	}

	public function setModuleName($moduleName) {
		$this->moduleName = $moduleName;
	}
	
	public function getModuleName() {
		return $this->moduleName;
	}
	
	public function setContextID($contextID) {
		$this->contextID = $contextID;
	}
	
	public function getContextID() {
		return $this->contextID;
	}
	
	public function setSectionID($sectionID) {
		$this->sectionID = $sectionID;
	}
	
	public function getSectionID() {
		return $this->sectionID;
	}
	
	public function setOlatType($olatType) {
		$this->olatType = $olatType;
	}
	
	public function getOlatType() {
		return $this->olatType;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function setIndent($indent) {
		$this->indent = $indent;
	}
	
	public function getIndent() {
		return $this->indent;
	}
	
	public function setBook($book) {
		$this->book = $book;
	}
	
	public function getBook() {
		return $this->book;
	}
	
	public function setBookContextID($bookcontextID) {
		$this->bookcontextID = $bookcontextID;
	}
	
	public function getBookContextID() {
		return $this->bookcontextID;
	}
	
	public function setBookSubChapter($booksubchapter) {
		$this->booksubchapter = $booksubchapter;
	}
	
	public function getBookSubChapter() {
		return $this->booksubchapter;
	}
	
	public function setChapterID($chapterID) {
		$this->chapterID = $chapterID;
	}
	
	public function getChapterID() {
		return $this->chapterID;
	}
	
	public function setFile($files) {
		array_push($this->files, $files);
	}
	
	public function getFile() {
		return $this->files;
	}
	
}

///////////////////////////////////////////////////////////
// ACTIVITY PAGE //////////////////////////////////////////
///////////////////////////////////////////////////////////
class ActivityPage extends Activity {

	protected $content;
	protected $contentfile;
	
	public function __construct($content, $contentfile) {
		$this->content = $content;
		$this->contentfile = $contentfile;
	}
	
	public function setContent($content) {
		$this->content = $content;
	}
	
	public function getContent() {
		return $this->content;
	}
	
	public function setContentFile($contentfile) {
		$this->contentfile = $contentfile;
	}
	
	public function getContentFile() {
		return $this->contentfile;
	}
	
}

///////////////////////////////////////////////////////////
// ACTIVITY FOLDER ////////////////////////////////////////
///////////////////////////////////////////////////////////
class ActivityFolder extends Activity {

	protected $folderFiles = array();

	public function __construct($folderFiles) {
		$this->folderFiles = $folderFiles;
	}
	
	public function setFolderFile($folderFiles) {
		array_push($this->folderFiles, $folderFiles);
	}
	
	public function getFolderFile() {
		return $this->folderFiles;
	}
	
}

///////////////////////////////////////////////////////////
// ACTIVITY URL ///////////////////////////////////////////
///////////////////////////////////////////////////////////
class ActivityURL extends Activity {

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
// ACTIVITY RESOURCE //////////////////////////////////////
///////////////////////////////////////////////////////////
class ActivityResource extends Activity {

	protected $aResource;

	public function __construct($aResource) {
		$this->aResource = $aResource;
	}
	
	public function setResource($aResource) {
		$this->aResource = $aResource;
	}
	
	public function getResource() {
		return $this->aResource;
	}
	
}

///////////////////////////////////////////////////////////
// ACTIVITY WIKI //////////////////////////////////////////
///////////////////////////////////////////////////////////
class ActivityWiki extends Activity {

	public function __construct() {}
	
}

///////////////////////////////////////////////////////////
// ACTIVITY ASSIGNMENT ////////////////////////////////////
///////////////////////////////////////////////////////////
class ActivityAssignment extends Activity {
	
	protected $assignmentText;
	
	public function __construct($assignmentText) {
		$this->assignmentText = $assignmentText;
	}
	
	public function setAssignmentText($assignmentText) {
		$this->assignmentText = $assignmentText;
	}
	
	public function getAssignmentText() {
		return $this->assignmentText;
	}
	
}

///////////////////////////////////////////////////////////
// ACTIVITY LABEL /////////////////////////////////////////
///////////////////////////////////////////////////////////
class ActivityLabel extends Activity {

	public function __construct() {}
	
}

///////////////////////////////////////////////////////////
// ACTIVITY QUIZ //////////////////////////////////////////
///////////////////////////////////////////////////////////
class ActivityQuiz extends Activity {

	protected $description;
	protected $duration;
	protected $passingScore;
	protected $clustering;
	protected $quizPages = array();

	public function __construct($description, $duration, $passingScore, $clustering) {
		$this->description = $description;
		$this->duration = $duration;
		$this->passingScore = $passingScore;
		$this->clustering = $clustering;
	}
	
	public function setDescription($description) {
		$this->description = $description;
	}
	
	public function getDescription() {
		return $this->description;
	}
	
	public function setDuration($duration) {
		$this->duration = $duration;
	}
	
	public function getDuration() {
		return $this->duration;
	}
	
	public function setPassingScore($passingScore) {
		$this->passingScore = $passingScore;
	}
	
	public function getPassingScore() {
		return $this->passingScore;
	}
	
	public function setClustering($clustering) {
		$this->clustering = $clustering;
	}
	
	public function getClustering() {
		return $this->clustering;
	}
	
	public function setQuizPage($quizPages) {
		array_push($this->quizPages, $quizPages);
	}
	
	public function getQuizPages() {
		return $this->quizPages;
	}
	
}

///////////////////////////////////////////////////////////
// QUIZ PAGE //////////////////////////////////////////////
///////////////////////////////////////////////////////////
class QuizPage {

	protected $pageID;
	protected $pageTitle;
	protected $pageDescription;
	protected $pageOrdering;
	protected $pageAmount;
	protected $pageDescriptionElement;
	protected $pageQuestions = array();
	protected $randomQuestionIDs = array();
	
	public function __construct($pageID, $pageTitle, $pageDescription, $pageOrdering, $pageAmount) {
		$this->pageID = $pageID;
		$this->pageTitle = $pageTitle;
		$this->pageDescription = $pageDescription;
		$this->pageOrdering = $pageOrdering;
		$this->pageAmount = $pageAmount;
		$this->pageDescriptionElement = false;
	}
	
	public function setPageID($pageID) {
		$this->pageID = $pageID;
	}
	
	public function getPageID() {
		return $this->pageID;
	}
	
	public function setPageTitle($pageTitle) {
		$this->pageTitle = $pageTitle;
	}
	
	public function getPageTitle() {
		return $this->pageTitle;
	}
	
	public function setPageDescription($pageDescription) {
		$this->pageDescription = $pageDescription;
	}
	
	public function getPageDescription() {
		return $this->pageDescription;
	}
	
	public function setPageOrdering($pageOrdering) {
		$this->pageOrdering = $pageOrdering;
	}
	
	public function getPageOrdering() {
		return $this->pageOrdering;
	}
	
	public function setPageAmount($pageAmount) {
		$this->pageAmount = $pageAmount;
	}
	
	public function getPageAmount() {
		return $this->pageAmount;
	}
	
	public function setPageDescriptionElement($pageDescriptionElement) {
		$this->pageDescriptionElement = $pageDescriptionElement;
	}
	
	public function getPageDescriptionElement() {
		return $this->pageDescriptionElement;
	}
	
	public function setPageQuestion($pageQuestions) {
		array_push($this->pageQuestions, $pageQuestions);
	}
	
	public function getPageQuestions() {
		return $this->pageQuestions;
	}
	
	public function setRandomQuestionID($randomQuestionID) {
		array_push($this->randomQuestionIDs, $randomQuestionID);
	}
	
	public function getRandomQuestionIDs() {
		return $this->randomQuestionIDs;
	}

}

///////////////////////////////////////////////////////////
// QUIZ QUESTION //////////////////////////////////////////
///////////////////////////////////////////////////////////
class QuizQuestion {
	
	protected $qID;
	protected $qTitle;
	protected $qType;
	protected $qQuotation;
	protected $qScore;
	protected $qDescription;
	protected $qQuestion;
	protected $qShuffle;						// For SCQ and MCQ
	protected $qHint;
	protected $qSolutionFeedback;
	protected $qMaxAttempts;
	protected $qMedia = array();		// For FIB questions
	protected $qPossibilities = array();
	protected $qFeedback = array();
	
	public function __construct($qID, $qTitle, $qType, $qQuotation, $qScore, $qDescription, $qQuestion, $qShuffle, $qHint, $qSolutionFeedback, $qMaxAttempts, $qMedia = "", $qLines) {
		$this->qID = $qID;
		$this->qTitle = $qTitle;
		$this->qType = $qType;
		$this->qQuotation = $qQuotation;
		$this->qScore = $qScore;
		$this->qDescription = $qDescription;
		$this->qQuestion = $qQuestion;
		$this->qShuffle = $qShuffle;
		$this->qHint = $qHint;
		$this->qSolutionFeedback = $qSolutionFeedback;
		$this->qMaxAttempts = $qMaxAttempts;
		$this->qMedia = $qMedia;
		$this->qLines = $qLines;
	}
	
	public function setQID($qID) {
		$this->qID = $qID;
	}
	
	public function getQID() {
		return $this->qID;
	}
	
	public function setQTitle($qTitle) {
		$this->qTitle = $qTitle;
	}
	
	public function getQTitle() {
		return $this->qTitle;
	}
	
	public function setQType($qType) {
		$this->qType = $qType;
	}
	
	public function getQType() {
		return $this->qType;
	}
	
	public function setQQuotation($qQuotation) {
		$this->qQuotation = $qQuotation;
	}
	
	public function getQQuotation() {
		return $this->qQuotation;
	}
	
	public function setQScore($qScore) {
		$this->qScore = $qScore;
	}
	
	public function getQScore() {
		return $this->qScore;
	}
	
	public function setQDescription($qDescription) {
		$this->qDescription = $qDescription;
	}
	
	public function getQDescription() {
		return $this->qDescription;
	}
	
	public function setQQuestion($qQuestion) {
		$this->qQuestion = $qQuestion;
	}
	
	public function getQQuestion() {
		return $this->qQuestion;
	}
	
	public function setQShuffle($qShuffle) {
		$this->qShuffle = $qShuffle;
	}
	
	public function getQShuffle() {
		return $this->qShuffle;
	}
	
	public function setQHint($qHint) {
		$this->qHint = $qHint;
	}
	
	public function getQHint() {
		return $this->qHint;
	}
	
	public function setQSolutionFeedback($qSolutionFeedback) {
		$this->qSolutionFeedback = $qSolutionFeedback;
	}
	
	public function getQSolutionFeedback() {
		return $this->qSolutionFeedback;
	}
	
	public function setQMaxAttempts($qMaxAttempts) {
		$this->qMaxAttempts = $qMaxAttempts;
	}
	
	public function getQMaxAttempts() {
		return $this->qMaxAttempts;
	}
	
	public function setQMedia($qMedia) {
		array_push($this->qMedia, $qMedia);
	}
	
	public function getQMedia() {
		return $this->qMedia;
	}
	
	public function setQLines($qLines) {
		$this->qLines = $qLines;
	}
	
	public function getQLines() {
		return $this->qLines;
	}
	
	public function setQPossibility($qPossibilities) {
		array_push($this->qPossibilities, $qPossibilities);
	}
	
	public function getQPossibilities() {
		return $this->qPossibilities;
	}
	
	public function setQFeedback($qFeedback) {
		array_push($this->qFeedback, $qFeedback);
	}
	
	public function getQFeedback() {
		return $this->qFeedback;
	}
	
}

///////////////////////////////////////////////////////////
// QUIZ FEEDBACK //////////////////////////////////////////
///////////////////////////////////////////////////////////
class QuizFeedback {
	
	protected $qfID;
	protected $qfFeedback;
	
	public function __construct($qfID, $qfFeedback) {
		$this->qfID = $qfID;
		$this->qfFeedback = $qfFeedback;
	}
	
	public function setQFID($qfID) {
		$this->qfID = $qfID;
	}
	
	public function getQFID() {
		return $this->qfID;
	}
	
	public function setQFFeedback($qfFeedback) {
		$this->qfFeedback = $qfFeedback;
	}
	
	public function getQFFeedback() {
		return $this->qfFeedback;
	}
	
}

///////////////////////////////////////////////////////////
// QUIZ POSSIBILITY ///////////////////////////////////////
///////////////////////////////////////////////////////////
class QuizPossibility {

	protected $qpID;
	protected $qpAnswer;
	protected $qpScore;
	protected $qpCase;
	protected $qpIsCorrect;
	protected $qpFeedback;
	
	public function __construct($qpID, $qpAnswer, $qpScore, $qpCase, $qpIsCorrect, $qpFeedback) {
		$this->qpID = $qpID;
		$this->qpAnswer = $qpAnswer;
		$this->qpScore = $qpScore;
		$this->qpCase = $qpCase;
		$this->qpIsCorrect = $qpIsCorrect;
		$this->qpFeedback = $qpFeedback;
	}
	
	public function setQPID($qpID) {
		$this->qpID = $qpID;
	}
	
	public function getQPID() {
		return $this->qpID;
	}
	
	public function setQPAnswer($qpAnswer) {
		$this->qpAnswer = $qpAnswer;
	}
	
	public function getQPAnswer() {
		return $this->qpAnswer;
	}
	
	public function setQPScore($qpScore) {
		$this->qpScore = $qpScore;
	}
	
	public function getQPScore() {
		return $this->qpScore;
	}
	
	public function setQPCase($qpCase) {
		$this->qpCase = $qpCase;
	}
	
	public function getQPCase() {
		return $this->qpCase;
	}
	
	public function setQPIsCorrect($qpIsCorrect) {
		$this->qpIsCorrect = $qpIsCorrect;
	}
	
	public function getQPIsCorrect() {
		return $this->qpIsCorrect;
	}
	
	public function setQPFeedback($qpFeedback) {
		$this->qpFeedback = $qpFeedback;
	}
	
	public function getQPFeedback() {
		return $this->qpFeedback;
	}

}

?>
