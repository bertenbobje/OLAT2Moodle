<!DOCTYPE html>
	<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<title>OLAT2Moodle</title>
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
		<link href="css/reset.css" rel="stylesheet" type="text/css">
		<link href="css/style.css" rel="stylesheet" type="text/css">
		<script type="text/javascript">
		function toggle_visibility(id) {
			var e = document.getElementById(id);
			if (e.style.display == 'block') {
				e.style.display = 'none';
				document.getElementById('showerrors').innerHTML = 'Show HTML validation errors';
			}
			else {
				e.style.display = 'block';
				document.getElementById('showerrors').innerHTML = 'Hide HTML validation errors';
			}
		}
		</script>
	</head>
	<body>
		<div class="container">
			<header>
				<h1>OLAT2Moodle</h1>
			</header>
			<div class="nav">
				<ul>
					<li><a href="index.html">Home</a></li>
					<li><a href="">Documentation</a></li>
				</ul>
			</div>
			
<?php

/*************************************************************
/* Converts an .OLAT backup to an object, converts
/* this to a Moodle object, then this object gets used to
/* write the backup files to restore into Moodle.
/*************************************************************
/* Bert Truyens
/************************************************************/

require_once("classes/generalclasses.php");

require_once("functions/olatBackupToOlatObject.php");
require_once("functions/olatObjectToMoodleObject.php");
require_once("functions/moodleObjectToMoodleBackup.php");

if(isset($_POST['books'])) {
	if ($_POST['books'] == "on") {
		$books = true;
		$chapterFormat = $_POST['chaptertypeselect'];
	}
}
else {
	$books = false;
	$chapterFormat = "";
}

// The error handler initialization, this will contain all the errors at the end.
$error = new o2mErrorHandler();

if (isset($_FILES["file"])) {
	if (file_exists($_FILES["file"]["tmp_name"]) && is_uploaded_file($_FILES["file"]["tmp_name"])) {
		// Creates an OLAT Object out of an exported OLAT course.
		$olatObject = olatBackupToOlatObject($_FILES["file"]["tmp_name"], $error);
		if ($olatObject !== null) {
			echo "<br><p>===OLAT OBJECT===</p>";
			echo "<p>OK - OLAT Object created</p><br>";
			
			echo "<p>===MOODLE OBJECT===</p>";
			// Converts the OLAT Object to a Moodle object.
			$moodleObject = olatObjectToMoodleObject($olatObject, $error);
			echo "<p>OK - Moodle Object created</p>";

			if ($books) {
				$moodleObject = checkForBooks($moodleObject);
				echo "<p style='color:green;'>OK - Books marked</p>";
			}

			$moodleObject = fixHTMLReferences($moodleObject, $olatObject, $books);
			echo "<p>OK - All HTML references fixed</p>";

			echo "<br><p>===MOODLE BACKUP===</p>";
			// Uses the Moodle Object to make a Moodle backup .mbz file.
			$moodleBackup = moodleObjectToMoodleBackup($moodleObject, $olatObject, $books, $chapterFormat, $error);
			echo "<p>OK - Moodle backup .mbz created</p><br>";

			echo "<a href='" . dirname($_SERVER['PHP_SELF']) . $moodleBackup . "' class='download'>Download</a><br>";
		}
	}
	else {
		$error->setError(new Error("ERROR", 2, "No file uploaded.", 0));
	}
}
else {
	$error->setError(new Error("ERROR", 2, "No file found (or the file is too big)", 0));
}

$errors = $error->getErrors();
if (empty($errors)) {
	echo "<p style='color:green;'>OK - No warnings or errors found in the process.</p>";
}
else {
	echo "<p style='color:darkorange;font-weight:bolder;'>There were some issues with this course. Everything will still work but it might be for the best if these issues were resolved.</p><br>";
	
	foreach ($errors as $e) {
		if ($e->getLevel() == 2) {
			if ($e->getType() == "WARNING") {
				echo "<p style='color:darkorange;'>" . $e->getErrorText() . "</p>";
			}
			else {
				echo "<p style='color:red;'>" . $e->getErrorText() . "</p>";
			}
		}
	}
	$showButton = true;
	foreach ($errors as $e) {
		if ($e->getLevel() == 1) {
			if ($showButton) {
				echo "<br><button type='button' id='showerrors' onclick='toggle_visibility(\"errors\")'>Show HTML validation errors</button><div id='errors'>";
				$showButton = false;
			}
			if ($e->getPartOf() == 1) {
				if ($e->getType() == "WARNING") {
					echo "<p style='color:darkorange;margin-left:15px;'>" . $e->getErrorText() . "</p>";
				}
				else {
					echo "<p style='color:red;margin-left:15px;'>" . $e->getErrorText() . "</p>";
				}
			}
			else {
				if ($e->getType() == "WARNING") {
					echo "<p style='color:darkorange;'>" . $e->getErrorText() . "</p>";
				}
				else {
					echo "<p style='color:red;'>" . $e->getErrorText() . "</p>";
				}
			}
		}
	}
}

?>
			</div>
			<footer>
				<p>Original version by Bert Truyens and Sam Wouters - Source code can be found <a href="https://bitbucket.org/truyb/olat2moodle">here</a></p>
			</footer>
		</div>
	</body>
</html>
