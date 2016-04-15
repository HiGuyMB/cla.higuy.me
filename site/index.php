<h1>Pretend there's a frontend here.</h1><br>
If you want the file listing that the game uses, see <a href="getMissionList.php">here</a>.<br>
If you want a specific mission (grab the url from the list), try this:
<form action="getMission.php" method="get" enctype="multipart/form-data">
	<label for="file">Mission: <input type="text" name="file"></label>
	<input type="submit" value="Download">
</form>
<br>
If you want to be confused by a bunch of hex that stands for an interior, try this:
<form action="getDataFile.php" method="get" enctype="multipart/form-data">
	<label for="file">Interior: <input type="text" name="file"></label>
	<input type="submit" value="Download">
</form>
