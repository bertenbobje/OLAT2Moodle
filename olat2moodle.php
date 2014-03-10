<?php
  /**********************************************************
  // Stores the OLAT .zip that was uploaded previously, 
	// unzips it in a temporary folder with a random integer,
	// (so that there can't be any overwrites).
	// Then it reads out runstructure.xml and gets out
	// the important parts and shows them on the page.
	// ********************************************************
	// Bert Truyens
	**********************************************************/
	if(isset($_FILES["file"])) {
		if($_FILES["file"]) {
		
			// XmlReader has difficulties with .'s, so they're all put in their
			// own variables.
			$st = 'org.olat.course.nodes.STCourseNode';				// Structure
			$en = 'org.olat.course.nodes.ENCourseNode';				// Enrollment
			$sp = 'org.olat.course.nodes.SPCourseNode';				// Course pages
			$iq = 'org.olat.course.nodes.IQTESTCourseNode';		// Tests
			
			// Random integer for storing unzips, so that there will be no overwrites.
			$num = "";
			for ($i = 0; $i < 9; $i++) {
				$num .= strval(mt_rand(0, 9));
			}
			
			$path = $_FILES["file"]["tmp_name"];
			$zip = new ZipArchive;
			if ($zip->open($path)) {
				$expath = getcwd() . "/tmp/" . $num . "/";
				$zip->extractTo($expath);
				$zip->close();
				if($olat = simplexml_load_file($expath . "runstructure.xml")) {
				}
				else {
					echo 'Error reading XML';
					echo '<a href="index.php">Go back</a>';
				}
			}
			else {
				echo 'Error parsing file. <br>';
				echo '<a href="index.php">Go back</a>';
			}
		}
	}
	else {
		echo 'No file found, did you land on this page by accident? <br>';
		echo '<a href="index.php">Go back</a>';
	}
?>
<!DOCTYPE html>
<html>
<head>
    <title>OLAT2Moodle</title>
    <meta charset="utf-8">
</head>
<body>
	<h1><?php echo $olat->rootNode->longTitle; ?></h1>
	<h2><?php echo $olat->rootNode->shortTitle; ?></h2>
	<p><?php echo $olat->rootNode->children->$st->children->$st->children->$sp->longTitle; ?></p>
	<?php foreach ($olat->rootNode->children->$st->children->$st->children->$sp->moduleConfiguration->config->entry as $strng) {
		if($strng->string == "file") { ?>
			<p><?php echo (file_get_contents($expath . "coursefolder" . $strng->string[1])); ?></p>
		<?php } } ?>
</body>
</html>