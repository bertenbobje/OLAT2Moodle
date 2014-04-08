<?php

ini_set('display_errors', 1);

/**
 *
 * This script converts a QTI test from OLAT to an object.
 *
 * The QTI elements supported are: Single Choice Questions, Multiple Choice
 * Questions, Fill In Questions and KPRIM-Questions
 *
 * Joey Lemmens
 *
 */
function getDataIfExists() {
  // We accept an unknown number of arguments
  $args = func_get_args();

  if (!count($args)) {
    trigger_error('getDataIfExists() expects a minimum of 1 argument', E_USER_WARNING);
    return NULL;
  }

  // The object we are working with
  $baseObj = array_shift($args);

  // Check it actually is an object
  if (!is_object($baseObj)) {
    trigger_error('getDataIfExists(): first argument must be an object', E_USER_WARNING);
    return NULL;
  }

  // Loop subsequent arguments, check they are valid and get their value(s)
  foreach ($args as $arg) {
    $arg = $arg;
    if (substr($arg, -2) == '()') { // method
      $arg = substr($arg, 0, -2);
      if (!method_exists($baseObj, $arg))
        return NULL;
      $baseObj = $baseObj->$arg();
    } else { // property
      if (!isset($baseObj->$arg))
        return NULL;
      $baseObj = $baseObj->$arg;
    }
  }
  // If we get here $baseObj will contain the item referenced by the supplied chain
  return $baseObj;
}

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

function parseQTIToObject($filename, $folderID) {

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

  $testObject = entity_create('qtici_test', $test_values);
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
      $QObject = entity_create('qtici_' . $questionType, array());
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
        $content = checkIfExistAndCast($content, $filename, $folderID);
        $QObject->setContent($content);
      }
      $question2 = checkIfExistAndCast($question, $filename, $folderID);
      $QObject->setQuestion($question2);

      $sectionObject->setItem($QObject);
    }
  }
  
  return $testObject;
}

?>
