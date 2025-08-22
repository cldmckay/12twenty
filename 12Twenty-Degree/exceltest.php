<?php
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
		
		ini_set('memory_limit', '8G');
		
		require_once('ExcelReader.php');
		require_once('HelperDenodo.php');
		
		
		echo "The time is " . date("Y/m/d h:i:sa");
		echo "<br />";
		
		require_once('Helper12Twenty.php');
		require_once('HelperDenodo.php');
		require_once('Student.php');
		require_once('CertificateMinor.php');
		require_once('ExcelReader.php');

		$major_list = GetMajorTable();
		$term_list = GetGraduationTermTable();
?>