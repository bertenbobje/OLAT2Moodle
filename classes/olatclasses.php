<?php

require_once("functions/general.php");

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
//     _______________|______________________________|
//    |
// Section
// Feedback
// ElementTypes
// Possibility
// Item -- SingleChoiceQuestion
//      -- MultipleChoiceQuestion
//      -- FillInBlanks
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

  protected $title;
  protected $description;
  protected $duration;
  protected $passingScore;
  protected $quizSections = array();

  function __construct() {}

  function myConstruct($title, $description, $duration, $passingScore, $quizSections) {
    $this->title = $title;
    $this->description = $description;
    $this->duration = $duration;
    $this->passingScore = $passingScore;
    array_push($this->quizSections, $quizSections);
  }

  public function setTitle($title) {
    $this->title = $title;
  }

  public function getTitle() {
    return $this->title;
  }

  public function setDescription($description) {
		$this->description = $description;
  }
	
  public function setDuration($duration) {
    $this->duration = $duration;
  }

  public function getDuration() {
    return $this->duration;
  }

  public function getPassingScore() {
    return $this->passingScore;
  }

  public function setPassingScore($passingScore) {
    $this->passingScore = $passingScore;
  }
	
  public function setQuizSection($quizSections) {
    array_push($this->quizSections, $quizSections);
  }

  public function getQuizSections() {
    return $this->quizSections;
  }
	
  /**
   * Saves the categories of a test
   */
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

  protected $title;
  protected $description;
  protected $duration;
  protected $passingScore;
  protected $quizSections = array();

  function __construct() {}

  function myConstruct($title, $description, $duration, $passingScore, $quizSections) {
    $this->title = $title;
    $this->description = $description;
    $this->duration = $duration;
    $this->passingScore = $passingScore;
    array_push($this->quizSections, $quizSections);
  }

  public function setTitle($title) {
    $this->title = $title;
  }

  public function getTitle() {
    return $this->title;
  }

  public function setDescription($description) {
		$this->description = $description;
  }
	
  public function setDuration($duration) {
    $this->duration = $duration;
  }

  public function getDuration() {
    return $this->duration;
  }

  public function getPassingScore() {
    return $this->passingScore;
  }

  public function setPassingScore($passingScore) {
    $this->passingScore = $passingScore;
  }
	
  public function setQuizSection($quizSections) {
    array_push($this->quizSections, $quizSections);
  }

  public function getQuizSections() {
    return $this->quizSections;
  }
}


///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
// TESTS //////////////////////////////////////////////////
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////

class Item {

  protected $ident;
  protected $type;
  protected $title;
  protected $objective;
  protected $feedback = array();
  protected $hint;
  protected $solutionFeedback;
  protected $max_attempts;
  protected $possibilities = array();
  protected $question;
  protected $id;
	public $content;
  protected $sectionid;
  protected $description;

  public function __construct() {

  }

  public function myConstruct($ident, $type, $title, $objective = null, $max_attempts = '') {
    $this->id = $ident;
    $this->type = $type;
    $this->title = $title;
    //$this->objective = $objective;
    $this->max_attempts = $max_attempts;
  }

  public function getDescription() {
    return $this->description;
  }

  public function setDescription($description) {
    $this->description = $description;
  }

  public function getSectionid() {
    return $this->sectionid;
  }

  public function setSectionid($sectionid) {
    $this->sectionid = $sectionid;
  }

  public function setId($id) {
    $this->id = $id;
  }

  public function getId() {
    return $this->id;
  }

  public function getType() {
    return $this->type;
  }

  public function setType($type) {
    $this->type = $type;
  }

  public function getQuestion() {
    return $this->question;
  }

  public function setQuestion($question) {
    $this->question = $question;
  }

  public function setTitle($title) {
    $this->title = $title;
  }

  public function getTitle() {
    return $this->title;
  }

  public function setObjective($objective) {
    $this->objective = $objective;
  }

  public function getObjective() {
    return $this->objective;
  }

  public function setFeedback($feedback) {
    array_push($this->feedback, $feedback);
  }

  public function getFeedback() {
    return $this->feedback;
  }

  public function setHint($hint) {
    $this->hint = $hint;
  }

  public function getHint() {
    return $this->hint;
  }

  public function setSolutionFeedback($solutionFeedback) {
    $this->solutionFeedback = $solutionFeedback;
  }

  public function getSolutionFeedback() {
    return $this->solutionFeedback;
  }

  public function getMax_attempts() {
    return $this->max_attempts;
  }

  public function setMax_attempts($max_attempts) {
    $this->max_attempts = $max_attempts;
  }

  public function setPossibility($possibilities) {
    array_push($this->possibilities, $possibilities);
  }

  public function getPossibilities() {
    return $this->possibilities;
  }
	
	public function setContent($content) {
    $this->content = serialize($content);
  }

  public function getContent() {
    return unserialize($this->content);
  }

  /**
   * Queries of this class
   */

  public function parseXML($item) {
    $this->setId((string) getDataIfExists($item, 'attributes()', 'ident'));
    $this->setTitle((string) getDataIfExists($item, 'attributes()', 'title'));
    $this->setObjective((string) getDataIfExists($item, 'objectives', 'material', 'mattext'));
    $this->setDescription((string) getDataIfExists($item, 'objectives', 'material', 'mattext'));

		$hint = $item->xpath('itemfeedback/hint');
		$this->setHint(isset($hint[0]->hintmaterial->material->mattext) ? (string) $hint[0]->hintmaterial->material->mattext : null);
		$solutionFeedback = $item->xpath('itemfeedback/solution');
		$this->setSolutionFeedback(isset($solutionFeedback[0]->solutionmaterial->material->mattext) ? (string) $solutionFeedback[0]->solutionmaterial->material->mattext : null);
		$feedbackitems = $item->xpath('itemfeedback[material[1]]');
		foreach ($feedbackitems as $feedbackitem) {
			//$feedbackObject = new Feedback((string) $feedbackitem->attributes()->ident, (string) $feedbackitem->material->mattext);
			$feedbackObject = new Feedback((string) getDataIfExists($feedbackitem, 'attributes()', 'ident'), (string) getDataIfExists($feedbackitem, 'material', 'mattext'));
			$this->setFeedback($feedbackObject);
		}
  }
	
}

class SingleChoiceQuestion extends Item {

  protected $answer;
  protected $score;
  protected $randomOrder;

  public function __construct($values = array()) {
    parent::__construct($values, 'qtici_SCQ');
  }

  function myFullConstruct($item) {
    $this->type = $item->type;
    $this->title = $item->title;
    $this->objective = NULL;
    $this->feedback = NULL;
    $this->hint = NULL;
    $this->solutionFeedback = NULL;
    $this->max_attempts = $item->max_attempts;
    $this->possibilities = NULL;
    $this->question = $item->question;
    $this->id = $item->id;
    $this->answer = NULL;
    $this->score = $item->score;
    $this->randomOrder = $item->ordering;
  }

  public function setAnswer($answer) {
    $this->answer = $answer;
  }

  public function getAnswer() {
    return $this->answer;
  }

  public function setScore($score) {
    $this->score = $score;
  }

  public function getScore() {
    return $this->score;
  }

  public function setRandomOrder($randomOrder) {
    if ($randomOrder == 'Yes') {
      $this->randomOrder = TRUE;
    }
    else {
      $this->randomOrder = FALSE;
    }
  }

  public function getRandomOrder() {
    return $this->randomOrder;
  }

  /**
   * Parser function. $item is the loaded XML object
   */
  public function parseXML($item) {
    
    $this->setMax_attempts((string) getDataIfExists($item, 'attributes()', 'maxattempts'));
    $this->setRandomOrder((string) getDataIfExists($item, 'presentation', 'response_lid', 'render_choice', 'attributes()', 'shuffle'));
    // Set Type
    $this->setType('SCQ');
    // Get correct answers
    $correct = array();
    foreach ($item->resprocessing->respcondition as $resp) {
      if ($resp->attributes()->title == 'Mastery') {
        if (getDataIfExists($resp, 'conditionvar', 'and', 'varequal')) {
          $correct[] = (int) getDataIfExists($resp, 'conditionvar', 'and', 'varequal');
        }
        else {
          $correct[] = (int) getDataIfExists($resp, 'conditionvar', 'varequal');
        }
      }
    }
    // For SCQ we only need to handle answers, the rest of the data is generic to all items
    foreach ($item->presentation->response_lid->render_choice->children() as $flow_label) {
      $content['value'] = (string) getDataIfExists($flow_label, 'response_label', 'material', 'mattext');
      $content['format'] = (string) getDataIfExists($flow_label, 'response_label', 'material', 'mattext', 'texttype');
      if (empty($content['format'])) {
        $content['format'] = 'full_html';
      }
      $ident = (int) getDataIfExists($flow_label, 'response_label', 'attributes()', 'ident');
      $is_correct = 0;
      if (in_array($ident, $correct)) {
        $is_correct = 1;
      }
			$possibility = new Possibility(
						$ident,
						ElementTypes::RADIOBUTTON,
						serialize($content),
						$is_correct
			);
      $this->setPossibility($possibility);
    }

    parent::parseXML($item);
  }

}

class MultipleChoiceQuestion extends Item {

  protected $quotation;
  protected $answers = array();
  protected $score;
  protected $randomOrder;

  public function __construct($values = array()) {
    parent::__construct($values, 'qtici_MCQ');
  }

  function myFullConstruct($item) {
    $this->type = $item->type;
    $this->title = $item->title;
    $this->objective = NULL;
    $this->feedback = NULL;
    $this->hint = NULL;
    $this->solutionFeedback = NULL;
    $this->max_attempts = $item->max_attempts;
    $this->possibilities = NULL;
    $this->question = $item->question;
    $this->id = $item->id;
    $this->quotation = $item->quotation;
    $this->answers = NULL;
    $this->score = $item->score;
    $this->randomOrder = $item->ordering;
  }

  public function setQuotation($quotation) {
    $this->quotation = $quotation;
  }

  public function getQuotation() {
    return $this->quotation;
  }

  public function setAnswer($answers) {
    array_push($this->answers, $answers);
  }

  public function getAnswer() {
    return $this->answers;
  }

  public function setScore($score) {
    $this->score = $score;
  }

  public function getScore() {
    return $this->score;
  }

  public function setRandomOrder($randomOrder) {
    if ($randomOrder == 'Yes') {
      $this->randomOrder = TRUE;
    }
    else {
      $this->randomOrder = FALSE;
    }
  }

  public function getRandomOrder() {
    return $this->randomOrder;
  }

  /**
   * Parser function. $item is the loaded XML object
   */
  public function parseXML($item) {
    $this->setRandomOrder((string) getDataIfExists($item, 'presentation', 'response_lid', 'render_choice', 'attributes()', 'shuffle'));
    // Set Type
    $this->setType('MCQ');
    // Get Quotation
    $outputArray = getQuotationType($item);
    $quotation = $outputArray['quotation'];
    $results = $outputArray['results'];

    $this->setQuotation($quotation);

    // Get correct answers
    $correct = array();
    foreach ($item->resprocessing->respcondition as $resp) {
      if ($resp->attributes()->title == 'Mastery') {
        if (getDataIfExists($resp, 'conditionvar', 'and', 'varequal')) {
          foreach ($resp->conditionvar->and->varequal as $varequal) {
            $correct[] = (int) getDataIfExists($varequal);
          }
        }
        else {
          foreach ($resp->conditionvar->varequal as $varequal) {
            $correct[] = (int) getDataIfExists($varequal);
          }
        }
      }
    }
 
    // Get answers
    foreach ($item->presentation->response_lid->render_choice->children() as $child) {
      $content['value'] = (string) getDataIfExists($child, 'response_label', 'material', 'mattext');
      $content['format'] = (string) getDataIfExists($child, 'response_label', 'material', 'mattext', 'texttype');
      if (empty($content['format'])) {
        $content['format'] = 'full_html';
      }
      $ident = (int) getDataIfExists($child, 'response_label', 'attributes()', 'ident');
      $is_correct = 0;
      if (in_array($ident, $correct)) {
        $is_correct = 1;
      }
			$possibility = new Possibility(
							$ident,
							ElementTypes::CHECKBOX,
							serialize($content),
							$is_correct
			);
      $this->setPossibility($possibility);
    }

    // Set Score
    $answers = array();
    if ($quotation == 'allCorrect') {
      foreach ($results as $result) {
        $arrayAnswer = $result->conditionvar->and;
        if (count($arrayAnswer->varequal) != 0) {
          for ($i = 0; $i < count($arrayAnswer->varequal); $i++) {
            $title = (string) $result->attributes()->title;
            $answers[$title] = (string) $arrayAnswer->varequal[$i];
          }
        }
        $this->setScore((string) $results[0]->setvar);
      }
    }
    elseif ($quotation == 'perAnswer') {
      foreach ($results as $result) {
        $answers[$result->attributes()->title] = array(
          'value' => (string) $result->conditionvar->varequal,
          'score' => (string) $result->setvar,
        );
      }
    }
    $this->setAnswer($answers);

    parent::parseXML($item);
  }

}

class FillInBlanks extends Item {

  protected $quotation;
  protected $answers = array();
  protected $score;
	public $content;

  public function __construct($values = array()) {
    parent::__construct($values, 'qtici_FIB');
  }

  function myFullConstruct($item) {
    $this->type = $item->type;
    $this->title = $item->title;
    $this->objective = NULL;
    $this->feedback = NULL;
    $this->hint = NULL;
    $this->solutionFeedback = NULL;
    $this->max_attempts = $item->max_attempts;
    $this->possibilities = NULL;
    $this->question = $item->question;
    $this->id = $item->id;
    $this->quotation = $item->quotation;
    $this->answers = NULL;
    $this->score = $item->score;
    $this->quotation = $item->quotation;
  }

  public function setQuotation($quotation) {
    $this->quotation = $quotation;
  }

  public function getQuotation() {
    return $this->quotation;
  }

  public function setAnswer($answers) {
    array_push($this->answers, $answers);
  }

  public function getAnswer() {
    return $this->answers;
  }

  public function setScore($score) {
    $this->score = $score;
  }

  public function getScore() {
    return $this->score;
  }

  public function parseXML($item) {
    // Set Type
    $this->setType('FIB');
    // Get Quotation
    $outputArray = getQuotationType($item);
    $quotation = $outputArray['quotation'];
    $results = $outputArray['results'];

    $this->setQuotation($quotation);

    // Get Answers
    $answers = array();
    if ($quotation == 'allCorrect') {
      foreach ($results as $result) {
        foreach ($result->conditionvar->and->or as $arrayAnswer) {
          $ident = (int) $arrayAnswer->varequal->attributes()->respident;
          $answers[$ident] = (string) $arrayAnswer->varequal;
        }
        $this->setAnswer($answers);
        $this->setScore((string) $result->setvar);
      }
    }
    elseif ($quotation == 'perAnswer') {
      foreach ($results as $result) {
        $answers[(string) $result->conditionvar->or->varequal['respident']] = array(
          'value' => (string) $result->conditionvar->or->varequal,
          'score' => (string) $result->setvar,
        );
        $this->setAnswer($answers);
      }
    }

    $content = '';
    foreach ($item->presentation->flow->children() as $child) {
      // MATERIAL can have the mattext or matimage elements (text/image)
      if ($child->getName() == 'material') {
        $materialArray = $child->xpath('*');
        foreach ($materialArray as $element) {
          if ($element->getName() == 'mattext') {
            $content .= (string) $element;
          }
          if ($element->getName() == 'matimage') {
            // Save image
						echo (string) $element['uri'] . "<br>";
            //$newFile = file_save_upload((string) $element['uri'], array('file_validate_extensions' => array($allowed)));
           // $newFile = file_move($newFile, 'public://');
           // $newFile->status = 1; // Make permanent
           // $newFile = file_save($newFile);
           // $content .= ':img' . $newFile->fid . 'fid:';
					 $content = "test";
          }
        }
      }
      elseif ($child->getName() == 'response_str') { // TEXTBOX
        $ident = (int) getDataIfExists($child, 'attributes()', 'ident');
        $content .= ':text' . $ident . 'box:';
        $answer = NULL;
        if ($ident && !empty($answers[$ident])) {
          if (is_object($answers[$ident])) {
            $answer = $answers[$ident]->value;
          }
          else {
            $answer = $answers[$ident];
          }
        }
        $dumbAns['value'] = $answer;
        $dumbAns['format'] = 'full_html';
				$possibility = new Possibility(
								(string) getDataIfExists($child, 'attributes()', 'ident'),
								ElementTypes::TEXTBOX,
								serialize($dumbAns),
								NULL
				);
        $this->setPossibility($possibility);
      }
    }
		$this->setContent(html_entity_decode($content));
		
    parent::parseXML($item);
  }

}

class QuizSection {

  protected $id;
  protected $title;
  protected $objective;
  protected $description;
  protected $ordering;
	protected $amount;
  protected $items = array();

  function __construct($id, $title, $description, $ordering, $amount) {
    $this->id = $id;
    $this->title = $title;
    $this->description = $description;
    $this->ordering = $ordering;
		$this->amount = $amount;
  }

  public function getId() {
    return $this->id;
  }

  public function setId($id) {
    $this->id = $id;
  }

  public function setTitle($title) {
    $this->title = $title;
  }

  public function getTitle() {
    return $this->title;
  }

  public function setObjective($objective) {
    $this->objective = $objective;
  }

  public function getObjective() {
    return $this->objective;
  }

  public function getDescription() {
    return $this->description;
  }

  public function setDescription($description) {
    $this->description = $description;
  }

  public function setOrdering($ordering) {
    $this->ordering = $ordering;
  }

  public function getOrdering() {
    return $this->ordering;
  }
	
	public function setAmount($amount) {
    $this->amount = $amount;
  }

  public function getAmount() {
    return $this->amount;
  }

  public function setItem($item) {
    array_push($this->items, $item);
  }

  public function getItems() {
    return $this->items;
  }
}

class Feedback {

  protected $id;
  protected $feedback;
  function __construct($id, $feedback) {
    $this->id = $id;
		$this->feedback = $feedback;
  }

  public function getId() {
    return $this->id;
  }

  public function setId($id) {
    $this->id = $id;
  }

  public function getFeedback() {
		return $this->feedback;
	}
	
	public function setFeedback($feedback) {
		$this->feedback = $feedback;
	}
}

class ElementTypes {
  const TEXT = 'text';
  const TEXTBOX = 'textbox';
  const RADIOBUTTON = 'radiobutton';
  const CHECKBOX = 'checkbox';
  const IMAGE = 'image';
}

class Possibility  {

  protected $id;
  protected $possibility; // Type of possibility: radio, checkbox, textbox
  protected $answer; // Content of the possibility
  protected $is_correct;

  public function __construct($id, $possibility, $answer, $is_correct) {
    $this->id = $id;
    $this->possibility = $possibility;
    $this->answer = $answer;
    $this->is_correct = $is_correct;
  }

  public function getId() {
    return $this->id;
  }

  public function setId($id) {
    $this->id = $id;
  }

  public function setPossibility($possibility) {
    $this->possibility = $possibility;
  }

  public function getPossibility() {
    return $this->possibility;
  }

  public function getAnswer() {
    return $this->answer;
  }

  public function setAnswer($answer) {
    $this->answer = $answer;
  }

  public function getIs_correct() {
    return $this->is_correct;
  }

  public function setIs_correct($is_correct) {
    $this->is_correct = $is_correct;
  }
}


?>
