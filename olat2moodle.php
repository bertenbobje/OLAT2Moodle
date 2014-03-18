<?php

/*************************************************************
/* Converts an .OLAT backup to an object, converts
/* this to a Moodle object, then this object gets used to
/* write the backup files to restore into Moodle.
/*************************************************************
/* Bert Truyens
/************************************************************/

require_once("functions.php");

if(isset($_FILES["file"]) && $_FILES["file"]) {
	// Creates an OLAT Object out of an exported OLAT course.
	$olatObject = olatBackupToOlatObject($_FILES["file"]["tmp_name"]);
}
else {
	echo "<p>No file found, did you land on this page by accident?</p><br>";
	echo "<a href='index.php'>Go back</a>";
}

// Converts the OLAT Object to a Moodle object.
$moodleObject = olatObjectToMoodleObject($olatObject);

// Uses the Moodle Object to make a Moodle backup .mbz file.
moodleObjectToMoodleBackup($moodleObject);

echo "OK";
	
?>
