<?php
	
?>
<html>
<head>
	<title>OLAT2Moodle</title>
</head>
<body>
	<h1>OLAT2Moodle</h1>
	<p>Welcome to OLAT2Moodle, please upload your download.zip export from OLAT below.</p>
	<form action="olat2moodle.php" method="post" enctype="multipart/form-data">
		<label for="file">Filename:</label><br>
		<input type="file" name="file" id="file"><br><br>
		<input type="submit" name="submit" value="Submit">
	</form>
</body>
</html>
  