<html>
<head>
	<title>OLAT2Moodle</title>
	<style>
	body {
		background-color: #FFFFAA;
		font-family: "Tahoma", Tahoma, sans-serif;
		font-size: 90%;
	}
	</style>
</head>
<body>
	<h1>OLAT2Moodle</h1>
	<p>Welcome to OLAT2Moodle, please upload your OLAT export .zip file below.</p>
	<form action="olat2moodle.php" method="post" enctype="multipart/form-data">
		<label for="file">Filename:</label><br>
		<input type="file" name="file" id="file"><br><br>
		<input type="submit" name="submit" value="Submit">
	</form>
</body>
</html>
  