<?php
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
		
		ini_set('memory_limit', '8G');
		
		require_once('ExcelReader.php');
		require_once('Helper12Twenty.php');
		
		echo "The time is " . date("Y/m/d h:i:sa");
		echo "<br />";
		$helper12Twenty = new Helper12Twenty;
		$college_array = json_decode($helper12Twenty->GetOptions('College list'), true);	
		$academic_term_array= json_decode($helper12Twenty->GetOptions('Academic term'), true);
		$country_array = json_decode($helper12Twenty->GetOptions('Country'), true);
		$ethnicity_array = json_decode($helper12Twenty->GetOptions('Ethnicity'), true);
		$department_array = json_decode($helper12Twenty->GetOptions('Department'), true);
		$under_major_array = json_decode($helper12Twenty->GetOptions('Major'), true);
		$under_minor_array = json_decode($helper12Twenty->GetOptions('Minor'), true);
		$degree_array = json_decode($helper12Twenty->GetOptions('Degree'), true);
		$degree_level_array = json_decode($helper12Twenty->GetOptions('Degree Level'), true);
		$work_auth_array = json_decode($helper12Twenty->GetOptions('Work Auth'), true);
		//$students = $helperDenodo->GetStudentData();
		$helper12Twenty->SaveStudentsIDPairToFile();
?>