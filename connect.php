<?php
// local
	$conn = mysqli_connect("localhost", "root", "", "pastry1");
// production
	// $conn = mysqli_connect("localhost", "u801377270_croiss_2025", "Croiss_2025", "u801377270_croiss_2025");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>

	