<h1><?=$heading?></h1>
<?php
	if (isset($_SESSION['form-output'])) {
		$output = $_SESSION['form-output'];
		echo "<h5>" . $output . "</h5>";                
		unset($_SESSION['form-output']);
	}
	if (isset($content)) {
		echo $content;
	}
?>