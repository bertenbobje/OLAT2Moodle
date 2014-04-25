<?php
require_once("classes/olatClassesQuiz.php");
require_once("classes/exercises/FillInQuestion.php");
require_once("functions.inc");

ini_set('xdebug.var_display_max_data', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_depth', -1);

 $filename = "qti.xml";
 if (file_exists($filename)) {
    $xml = new SimpleXMLElement($filename, null, true);
  } else {
    exit('Failed to open ' . $filename);
  }

  $sections = $xml->assessment->section;
  $categories = array();
  
  $testDescription = _qtici_get_testDescription((string) getDataIfExists($xml, 'assessment', 'objectives', 'material', 'mattext'), $categories);
	
  $test_values = array(
    'title' => (string) getDataIfExists($xml, 'assessment', 'attributes()', 'title'),
    'description' => $testDescription,
    'duration' => (string) getDataIfExists($xml, 'assessment', 'duration'),
    'passing_score' => (string) getDataIfExists($xml, 'assessment', 'outcomes_processing', 'outcomes', 'decvar', 'attributes()', 'cutvalue'),
    // Only one bundle for now
    'bundle' => 'qtici_test',
  );

  $testObject = new Test;
  $testObject->saveCategories($categories);

  // Loop through each section
  foreach ($sections as $section) {
    $sectionObject = new Section((string) getDataIfExists($section, 'attributes()', 'ident'), (string) getDataIfExists($section, 'attributes()', 'title'), (string) getDataIfExists($section, 'objectives', 'material', 'mattext'), (string) getDataIfExists($section, 'selection_ordering', 'order', 'attributes()', 'order_type'));
    $testObject->setSection($sectionObject);
		
    // Loop through each item
    $items = getDataIfExists($section, 'item');
    foreach ($items as $item) {
      // Each question type has be treated differently
      $questionType = getQuestionType($item->attributes()->ident);
			if ($questionType == "MCQ")
				$QObject = new MultipleChoiceQuestion;
			if ($questionType == "SCQ")
				$QObject = new SingleChoiceQuestion;
			if ($questionType == "FIB")
				$QObject = new FillInQuestion;
      $QObject->parseXML($item);
      // Replace video and audio in possibilities
      /*$possibilities = $QObject->getPossibilities();
      foreach ($possibilities as $poss) {
        $newPoss = checkIfExistAndCast($poss, $filename, $folderID);
        $QObject->setPossibility($newPoss);
      }*/

      $question = (string) getDataIfExists($item, 'presentation', 'material', 'mattext');
      if ($questionType === 'FIB') {
        // For FIB
        $question = (string) getDataIfExists($item, 'presentation', 'flow', 'material', 'mattext');
        $content = unserialize($QObject->content);
        $content = checkIfExistAndCast($content, $filename);
        $QObject->setContent($content);
      }
      $question2 = checkIfExistAndCast($question, $filename);
      $QObject->setQuestion($question2);

      $sectionObject->setItem($QObject);
    }
  }
  var_dump($testObject);

function getQuestionType($input) {
  $length = (strrpos($input, ':') - 1) - strpos($input, ':');
  return substr($input, strpos($input, ':') + 1, $length);
}

// Quotation for FIB or MCQ can be either allCorrect or perAnswer
// Function returns also the results form xpath!
function getQuotationType($item) {
  // XML structure is different when quatation is different (ALL/PER correct answer)
  $results = $item->xpath('resprocessing/respcondition[setvar and not(conditionvar/other)]');
  if (count($results[0]->conditionvar->and) > 0) {
    $quotation = 'allCorrect';
  } else {
    $quotation = 'perAnswer';
  }

  return array('quotation' => $quotation,
    'results' => $results
  );
}

// Function for fetching Feedback, Hints & SolutionFeedback
function qtici_fetchFeedback(&$object, $item) {
  $hint = $item->xpath('itemfeedback/hint');
  $object->setHint(isset($hint[0]->hintmaterial->material->mattext) ? (string) $hint[0]->hintmaterial->material->mattext : null);
  $solutionFeedback = $item->xpath('itemfeedback/solution');
  $object->setSolutionFeedback(isset($solutionFeedback[0]->solutionmaterial->material->mattext) ? (string) $solutionFeedback[0]->solutionmaterial->material->mattext : null);

  $feedbackitems = $item->xpath('itemfeedback[material[1]]');
  foreach ($feedbackitems as $feedbackitem) {
    $feedbackObject = new Feedback((string) $feedbackitem->attributes()->ident, (string) $feedbackitem->material->mattext
    );
    $object->setFeedback($feedbackObject);
  }
}
?>
