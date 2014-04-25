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
				echo "<p style='color:darkorange;'>WARNING - The coursefolder directory contains duplicate files, these will be discarded. 
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

?>
