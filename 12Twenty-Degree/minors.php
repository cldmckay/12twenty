<?php
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
		
		ini_set('memory_limit', '8G');
		
		require_once('ExcelReader.php');
		require_once('HelperDenodo.php');
		
		echo "The time is " . date("Y/m/d h:i:sa");
		echo "<br />";
		$helperDenodo = new HelperDenodo;
		//$students = $helperDenodo->GetStudentData();
		$helperDenodo->SaveMinorDataToFile();
?>