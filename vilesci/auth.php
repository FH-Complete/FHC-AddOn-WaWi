<?php
	session_start();

	$basepath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))).DIRECTORY_SEPARATOR;

	if (!isset($_SESSION['user']) || $_SESSION['user']=='')
	{
		$_SESSION['request_uri']=$_SERVER['REQUEST_URI'];

		header('Location: ../vilesci/login.php');
		exit;
	}
?>
