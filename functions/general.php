<?php

///////////////////////////////////////////////////////////
// GENERAL FUNCTIONS //////////////////////////////////////
///////////////////////////////////////////////////////////

// Creates an array to hold the directory list.
// Checks for every file, including in subfolders.
//
// PARAMETERS
// -> $dir = The directory
function listFolderFiles($dir){
	$result = array();
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($dir), RecursiveDirectoryIterator::SKIP_DOTS)) as $filename) {
		$result[] = substr($filename, strlen($dir) + 1);
	}
	return $result;
}

// Removes a folder with all its subfolders and files.
// (Pretty much a 'rm -rf' command for PHP)
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

// Here we read the archived file without unzipping it.
// We run a check in the files under coursefolder to see if any duplicate file names exist
// If so, we trigger a warning that also shows the file name in question.
//
// PARAMETERS
// -> $zippedzip = The .zip file
function checkDoubleFileReference($zippedzip) {
	$courseFolderReached = false;
	$i = 0;
	for($i = 0; $i < $zippedzip->numFiles; $i++) {
		$stat = $zippedzip->statIndex($i);
		$zipfiles[$i] = basename($stat['name']);
	}
		
	foreach ($zipfiles as $entry) {
		if ($entry == "coursefolder") {
			$courseFolderReached = true;
		}
		if ($entry == "editortreemodel.xml") {
			$courseFolderReached = false;
		}
		if($courseFolderReached == true) {
			$i++;
			$dirdump[$i] = $entry;
		}
	}
	
	if (isset($dirdump)) {
		$dirdump = array_map('strtolower', $dirdump);
		$diff = array_count_values($dirdump);
		foreach ($diff as $key=>$val) {
			if ($val != 1) {
				echo "<p style='color:red;'>WARNING - The coursefolder directory contains duplicate files, these will be discarded. 
						Some content may not load in Moodle as a result. The discarded content is named: " . $key . "</p>";
			}
		}
	}
}

// Cleans a string for any characters that could break a filename.
//
// PARAMETERS
// -> $string = The string
function clean($string) {
	// Replaces all spaces with hyphens.
	$string = str_replace(' ', '-', $string);
	// Removes special chars.
	$string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
	// Replaces multiple hyphens with single one.
	return preg_replace('/-+/', '-', $string);
}

// This is mainly for quizzes, to see if an XML node exists.
// If it does, the data will be fetched.
//
// PARAMETERS
// -> Anything
function getDataIfExists() {
	// We accept an unknown number of arguments
	$args = func_get_args();

	// If there are no arguments, nothing will happen.
	if (!count($args)) {
    trigger_error('getDataIfExists() expects a minimum of 1 argument', E_USER_WARNING);
    return NULL;
  }
	
	// The object we are working with
	$baseObj = array_shift($args);
	
	// Check if it's actually an object
  if (!is_object($baseObj)) {
		trigger_error('getDataIfExists(): first argument must be an object', E_USER_WARNING);
		return NULL;
	}

  // Loop subsequent arguments, check they are valid and get their value(s)
  foreach ($args as $arg) {
		if (substr($arg, -2) == '()') { // method
			$arg = substr($arg, 0, -2);
			if (!method_exists($baseObj, $arg)) {
				return NULL;
			}
			else {
				$baseObj = $baseObj->$arg();
			}
    }
		else { // property
			if (!isset($baseObj->$arg)) {
				return NULL;
			}
			else {
				$baseObj = $baseObj->$arg;
			}
    }
  }
  // If we get to this point $baseObj will contain the item referenced by the supplied chain
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
	$quotation = "xxxxx";
	if (!empty($results[0])) {
  if (count($results[0]->conditionvar->and) > 0) {
    $quotation = 'allCorrect';
  } else {
    $quotation = 'perAnswer';
  }}

  return array('quotation' => $quotation,
    'results' => $results
  );
}

// Function for fetching Feedback, Hints & SolutionFeedback
function fetchFeedback(&$object, $item) {
  $hint = $item->xpath('itemfeedback/hint');
  $object->setHint(isset($hint[0]->hintmaterial->material->mattext) ? (string) $hint[0]->hintmaterial->material->mattext : null);
  $solutionFeedback = $item->xpath('itemfeedback/solution');
  $object->setSolutionFeedback(isset($solutionFeedback[0]->solutionmaterial->material->mattext) ? (string) $solutionFeedback[0]->solutionmaterial->material->mattext : null);

  $feedbackitems = $item->xpath('itemfeedback[material[1]]');
  foreach ($feedbackitems as $feedbackitem) {
    $feedbackObject = new Feedback((string) $feedbackitem->attributes()->ident, (string) $feedbackitem->material->mattext);
    $object->setFeedback($feedbackObject);
  }
}

?>
