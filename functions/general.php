<?php

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

//Here we read the archived file without unzipping it.
//We run a check in the files under coursefolder to see if any duplicate file names exist
//If so, we trigger an error that also shows the file name in question.
//PARAMETERS = $zippedzip = The archived object.
function checkDoubleFileReference($zippedzip) {
	$courseFolderReached = false;
	$i = 0;
	for($i = 0; $i < $zippedzip->numFiles; $i++) {
		$stat = $zippedzip->statIndex($i);
		$zipfiles[$i] = basename( $stat['name'] );
	}
		
	foreach($zipfiles as $entry) {
		if ($entry == "coursefolder") {
			$courseFolderReached = true;
		}
		if ($entry == "editortreemodel.xml") {
			$courseFolderReached = false;
		}
		if($courseFolderReached == true) {
			$i++;
			$dirdump[$i]=$entry;
		}
	}
		
	$dirdump = array_map('strtolower', $dirdump);
	$diff = array_count_values($dirdump);
	foreach($diff as $key=>$val){
		if ($val != 1){
			trigger_error("Coursefolder includes duplicate references, these will be discarded. 
			Some content may not be imported as a result. The duplicate content discarded is named: " . $key,  E_USER_WARNING);
		}
	}
}

// Cleans a string for any characters that could break a filename.
function clean($string) {
	$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
	$string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
}

?>
