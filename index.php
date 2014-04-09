<html>
<head>
	<title>OLAT2Moodle</title>
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
	<style type="text/css">
	body {
		background-color: #FFFFAA;
		font-family: "Tahoma", Tahoma, sans-serif;
		font-size: 90%;
	}
	</style>
	<script>
		function showMe (box) {
			var chboxs = document.getElementsByName("books");
			var vis = "none";
			for (var i = 0; i < chboxs.length ;i++) { 
				if(chboxs[i].checked) {
					vis = "block";
					break;
				}
			}
			document.getElementById(box).style.display = vis;
		}
	</script>
</head>
<body>
	<h1>OLAT2Moodle</h1>
	<p>Welcome to OLAT2Moodle, please upload your OLAT export .zip file below.</p>
	<form action="olat2moodle.php" method="post" enctype="multipart/form-data">
		<label for="file">File:</label><br>
		<input type="file" name="file" id="file"><br><br>
		<input type="checkbox" name="books" id="books" onclick="showMe('chaptertype')">Turn pages into books?<br>
		<div id="chaptertype" style="display:none">
			<label for="chaptertype">Chapter numbering?</label>
			<select name="chaptertype">
				<option value="0">None</option>
				<option value="1" selected="selected">Numbers</option>
				<option value="2">Bullets</option>
				<option value="3">Indented</option>
			</select>
		</div>
		<br>
		<input type="submit" name="submit" value="Submit">
	</form>
</body>
</html>
  