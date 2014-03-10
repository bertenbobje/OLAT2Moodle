<?php
  /**********************************************************
  /* Stores the OLAT .zip that was uploaded previously, 
	/* unzips it in a temporary folder with a random integer,
	/* (so that there can't be any accidental overwrites).
	/* Then it reads out runstructure.xml and gets out
	/* the important parts and shows them on the page.
	/**********************************************************
	/* Bert Truyens
	/*********************************************************/
	
	require_once("functions.php");
	
	if(isset($_FILES["file"])) {
		if($_FILES["file"]) {
		
			// PHP has difficulties with .'s, so they're all put in their
			// own variables.
			$st = "org.olat.course.nodes.STCourseNode";				// Structure
			$en = "org.olat.course.nodes.ENCourseNode";				// Enrollment
			$sp = "org.olat.course.nodes.SPCourseNode";				// Course pages
			$iq = "org.olat.course.nodes.IQTESTCourseNode";		// Tests
			$fo = "org.olat.course.nodes.FOCourseNode";				// Forums
			
			// Random integer for storing unzips, so that there will be no overwrites.
			$num = "";
			for ($i = 0; $i < 9; $i++) {
				$num .= strval(mt_rand(0, 9));
			}
			
			$path = $_FILES["file"]["tmp_name"];
			
			// Extracts the .zip and puts it in its own folder
			// with randomly generated number for name.
			$zip = new ZipArchive;
			if ($zip->open($path)) {
				$expath = getcwd() . "/tmp/" . $num . "/";
				if (!file_exists($expath) and !is_dir($expath)) {
					mkdir(getcwd() . "/tmp/" . $num . "/", 0777, true);
				}
				//$expath = getcwd() . "/tmp/";
				$zip->extractTo($expath);
				$zip->close();
			        	
				// $olat will be the root for the XML file.
				if($olat = simplexml_load_file($expath . "runstructure.xml")) {}
				else {
					echo "<p>Error reading XML.</p><br>";
					echo "<a href='index.php'>Go back</a>";
				}
			}
			else {
				echo "<p>Error parsing file.</p><br>";
				echo "<a href='index.php'>Go back</a>";
			}
		}
	}
	else {
		echo "<p>No file found, did you land on this page by accident?</p><br>";
		echo "<a href='index.php'>Go back</a>";
	}
	
?>
<!DOCTYPE html>
<html>
<head>
	<title>OLAT2Moodle</title>
	<meta charset="utf-8">
	<!--<link rel="stylesheet" type="text/css" href="css/reset.css">-->
	<link rel="stylesheet" type="text/css" href="css/style.css">
	<script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
    <script type="text/javascript">
		$(function() {
			$('img').each(function() {
				var $img = $(this);
				var oldimgsrc = $img.attr('src');
				
				var newimgsrc = "/olat2moodle/tmp/<?php echo $num ?>/coursefolder/" + oldimgsrc;
				
				$img.attr('src', newimgsrc);
			});
		});
	</script>
</head>
<body>
	<h1><?php echo $olat->rootNode->longTitle; ?></h1>
	<h2><?php echo $olat->rootNode->shortTitle; ?></h2>
	<h3>Type of course: <?php echo $olat->rootNode->attributes(); ?></h3>
	<?php foreach ($olat->rootNode->children->children() as $child) { 				// Reads out all children, and the children's children. ?>
		<div class="root">
		<?php echo $child->getName() . "<br>"; ?>
		</div>
		<?php if ($child->children->count() > 0) {
			foreach ($child->children->children() as $child2) { ?>
				<div class="rootc1">
				<?php echo "> " . $child2->getName() . "<br>";
				echo $child2->shortTitle . "<br>"; ?>
				</div>
				<?php if ($child2->children->count() > 0) {
					foreach ($child2->children->children() as $child3) { ?>
						<div class="rootc2">
						<?php echo ">> " . $child3->getName() . "<br>";
						echo $child3->shortTitle . "<br>";
						foreach ($child3->moduleConfiguration->config->entry as $strng) {
							if($strng->string == "file") {
								$url = $expath . "coursefolder" . $strng->string[1];
								echo "<p>" . (file_get_contents($url)) . "</p>";
							}
						} ?>
						</div>
					<?php }
				}
			}
		}
		echo "<br>";
	} ?>
</body>
</html>
