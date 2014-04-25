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

// To make sure that every action can happen, even with bigger files.
ini_set('max_execution_time', 300);
//ini_set('memory_limit', '-1');

//ini_set('xdebug.var_display_max_data', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_depth', -1);

echo '<html>
	<head>
		<title>OLAT2Moodle</title>
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
	</head>
	<body>
';

if(isset($_POST['books'])) {
	if ($_POST['books'] == "on") {
		$books = true;
		$chapterFormat = $_POST['chaptertype'];
	}
}
else {
	$books = false;
	$chapterFormat = "";
}

if (isset($_FILES["file"])) {
	if (file_exists($_FILES["file"]["tmp_name"]) || is_uploaded_file($_FILES["file"]["tmp_name"])) {
		// Creates an OLAT Object out of an exported OLAT course.
		$olatObject = olatBackupToOlatObject($_FILES["file"]["tmp_name"]);
		if ($olatObject !== null) {
			echo "<br><p>===OLAT OBJECT===</p>";
			//var_dump($olatObject);
			echo "<p>OK - OLAT Object created</p><br>";
			
			echo "<p>===MOODLE OBJECT===</p>";
			// Converts the OLAT Object to a Moodle object.
			$moodleObject = olatObjectToMoodleObject($olatObject, $books);
			var_dump($moodleObject);
			echo "<p>OK - Moodle Object created</p>";

			if ($books) {
				$moodleObject = checkForBooks($moodleObject);
				var_dump($moodleObject);
				echo "<p style='color:green;'>OK - Books marked</p>";
			}

			//$moodleObject = fixHTMLReferences($moodleObject, $olatObject, $books);
			echo "<p>OK - All HTML references fixed</p>";

			echo "<br><p>===MOODLE BACKUP===</p>";
			// Uses the Moodle Object to make a Moodle backup .mbz file.
			//$moodleBackup = moodleObjectToMoodleBackup($moodleObject, $olatObject, $books, $chapterFormat);
			echo "<p>OK - Moodle backup .mbz created</p><br>";

			//echo "<a href='" . dirname($_SERVER['PHP_SELF']) . $moodleBackup . "'>Download here</a>";
		}
	}
	else {
		echo "<p style='color:red;'>ERROR - No file found, did you land on this page by accident?</p><br><a href='index.php'>Go to start page</a>";
	}
}
else {
	echo "<p style='color:red;'>ERROR - No file found, did you land on this page by accident?</p><br><a href='index.php'>Go to start page</a>";
}

echo '
	</body>
</html>
';

?>
