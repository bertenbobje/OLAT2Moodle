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
//        $error = The error handler
function checkDoubleFileReference($zippedzip, &$error) {
	$courseFolderReached = false;
	$i = 0;
	for ($i = 0; $i < $zippedzip->numFiles; $i++) {
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
				$error->setError(new Error("WARNING", 4, "The coursefolder directory contains duplicate files, these will be discarded.
							Some content may not load in Moodle as a result. The discarded content is named: " . $key, 0));
			}
		}
	}
}

// Rounds up a number to the nearest factor of 5
// This is for the essay textbox size
// (maximum size = 40, minimum size = 5)
//
// PARAMETERS
// -> $n = The number
// -> $x = The nearest factor of x to round up to (in this case: 5)
function roundUpToAny($n, $x = 5) {
		$v = (ceil($n) % $x === 0) ? round($n) : round(($n + $x / 2) / $x) * $x;
		if ($v < 5) {
			return 5;
		}
		else if ($v > 40) {
			return 40;
		}
		else {
			return $v;
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

// Gets the question type (SCQ, MCQ or FIB)
//
// PARAMETERS
// -> $input = The string that contains the question type
function getQuestionType($input) {
	$length = (strrpos($input, ':') - 1) - strpos($input, ':');
	return substr($input, strpos($input, ':') + 1, $length);
}

// Quotation for FIB or MCQ can be either allCorrect or perAnswer
// Function also returns the results from xpath!
//
// PARAMETERS
// -> $item = The current question item
function getQuotationType($item) {
	// XML structure is different when quotation is different (ALL/PER correct answer)
	$results = $item->xpath('resprocessing/respcondition[setvar and not(conditionvar/other)]');
	$quotation = "";
	if (!empty($results[0])) {
		if (count($results[0]->conditionvar->and) > 0) {
			$quotation = 'allCorrect';
		} else {
			$quotation = 'perAnswer';
		}
	}
	return array('quotation' => $quotation, 'results' => $results);
}

?>
