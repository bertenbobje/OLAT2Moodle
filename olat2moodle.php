<?php

/*************************************************************
/* Converts an .OLAT backup to an object, converts
/* this to a Moodle object, then this object gets used to
/* write the backup files to restore into Moodle.
/*************************************************************
/* Bert Truyens
/************************************************************/

require_once("functions/olatBackupToOlatObject.php");
require_once("functions/olatObjectToMoodleObject.php");
require_once("functions/moodleObjectToMoodleBackup.php");

echo "<p>===OLAT OBJECT===</p>";
if(isset($_FILES["file"]) && $_FILES["file"]) {
	// Creates an OLAT Object out of an exported OLAT course.
	$olatObject = olatBackupToOlatObject($_FILES["file"]["tmp_name"]);
	echo "<p>OK - OLAT Object created</p><br>";
}
else {
	echo "<p>No file found, did you land on this page by accident?</p><br><a href='index.php'>Go back</a>";
}

echo "<p>===MOODLE OBJECT===</p>";
// Converts the OLAT Object to a Moodle object.
$moodleObject = olatObjectToMoodleObject($olatObject);
echo "<p>OK - Moodle Object created</p><br>";

echo "<p>===MOODLE BACKUP===</p>";
// Uses the Moodle Object to make a Moodle backup .mbz file.
$moodleBackup = moodleObjectToMoodleBackup($moodleObject, $olatObject);
echo "<p>OK - Moodle backup .mbz created</p><br>";

echo "<a href='" . dirname($_SERVER['PHP_SELF']) . $moodleBackup . "'>Download here</a>";

?>
