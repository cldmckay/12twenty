<?php
	ini_set('memory_limit', '8G');
	ini_set('include_path', '/groups/iuieapi/bin/12Twenty:'.get_include_path());

	require_once('Helper12Twenty.php');
	require_once('HelperDenodo.php');
	require_once('Student.php');
	require_once('CertificateMinor.php');
	require_once('ExcelReader.php');
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
	
	//var_dump($degree_level_array);

	$pair_student_array = $helper12Twenty->GetStudentsIDPair();
	echo count($pair_student_array).' student records found in 12 Twenty DB...<br />';
	
	$helperDenodo = new HelperDenodo;
	$students = $helperDenodo->GetStudentData();
	
	$certificate_array = $helperDenodo->GetCertificateData();
	$minor_array = $helperDenodo->GetMinorData();
	$major_list = GetMajorTable();
	
	$student_list = array();
	for($i = 0; $i < count($students); $i++){
		$student = new Student($students[$i], $major_list);	
		$student->collegeArr[0]->setID($college_array);
		$student->CountryOfCitizenship->setID($country_array);
		$student->department[0]->setID($department_array);
		$student->Ethnicity1->setID($ethnicity_array);
		$student->majorArr[0]->setID($under_major_array);
		$student->degree[0]->setID($degree_array);
		$student->DegreeLevel->setID($degree_level_array);
		$student->WorkAuthorization->setID($work_auth_array);
		array_push($student_list, $student);
	}
	
	$certificate_list = array();
	for($i = 0; $i < count($certificate_array); $i++){
		$certificate = new CertificateMinor($certificate_array[$i]);
		array_push($certificate_list, $certificate);
	}
	$minor_list = array();
	for($i = 0; $i < count($minor_array); $i++){
		$minor = new CertificateMinor($minor_array[$i]);
		array_push($minor_list, $minor);
	}	
	$student_list = CombineDuplicateStudentMajor($student_list);	
	echo count($student_list) .' unique students left...<br />';
	
	AddStudentCertificateAndMinor($student_list, $certificate_list);
	
	//for testing, remove in live
	$minor_list = array_slice($minor_list, 0, 1000);	
	
	AddStudentCertificateAndMinor($student_list, $minor_list);
	
	echo 'Setting parameters to 12 Twenty API...<br />';
	for($i=0; $i < 1000; $i++){
	//for($i=0; $i < count($student_list); $i++){
		$student_list[$i]->SetParameters();
	}
	echo 'All set! <br />';
	echo '<br />';
	echo 'Output data : <br />';
	echo '<br />';
	
	//var_dump($pair_student_array);
	$success_count = 0;
	for($i=0; $i < 1000; $i++){
	//for($i=0; $i < count($student_list); $i++){		
		$method = '';
		$results = '';
		if(isset($pair_student_array[$student_list[$i]->StudentId])){
			$method = 'PUT';
		}else{
			$method = 'POST';
		}
		echo 'REQUEST method...'.$method.'<br />';
		echo 'Student id: '.$student_list[$i]->StudentId.'<br />';
		echo $method.' student 12 Twenty id: '.$pair_student_array[$student_list[$i]->StudentId].'<br />';
		if($method == 'POST'){
			$results = $helper12Twenty->PostStudent(json_encode($student_list[$i]));
		}elseif($method == 'PUT'){
			$results = $helper12Twenty->PutStudent(json_encode($student_list[$i]), $pair_student_array[$student_list[$i]->StudentId]);
		}
		if(!$results || strlen($results) < 1000){
			var_dump($results);
		}else{
			echo 'import success!';
			$success_count++;
		}
		//for testing
		echo json_encode($student_list[$i]);		
		echo '<br />';
	}
	echo $success_count.' students imported!';
	
	function CombineDuplicateStudentMajor($student_list){
		echo 'Merging duplicate student majors...<br />';
		$students_new = array();
		$last_studentID = '';
		$same = false;
		for($i=0; $i<count($student_list); $i++){
			if($student_list[$i]->StudentId != $last_studentID){
				array_push($students_new, $student_list[$i]);
				$last_studentID = $student_list[$i]->StudentId;
			}else{
				//same student
				$student_list[$i-1]->AddMajor($student_list[$i]);
				array_splice($student_list, $i, 1);
				$i--;
			}
		}
		return $students_new;
	}
	
	function AddStudentCertificateAndMinor($student_list, $certificate_minor_list){		
		echo 'Adding student certificates and minors...<br />';
		$StudentId = '';
		foreach($certificate_minor_list as $certificate_minor){
			foreach($student_list as $student){
				if($certificate_minor->StudentId == $student->StudentId){
					//echo 'student'. $student->StudentId. 'has a certificate/minor<br />';
					$student->AddCertificateAndMinor($certificate_minor);
				}
			}
		}
	}
?>
