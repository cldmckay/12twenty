<?php
	ini_set('memory_limit', '8G');
	ini_set('include_path', '/groups/iuieapi/bin/12Twenty:'.get_include_path());

	//ini_set('display_errors', 1);
	//ini_set('display_startup_errors', 1);
	//error_reporting(E_ALL);

	echo "The time is " . date("Y/m/d h:i:sa");
	echo "<br />"; 
	
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

	//$pair_student_array = $helper12Twenty->GetStudentsIDPair();
	$pair_student_array = $helper12Twenty->GetStudentsIDPairFromFile();
	echo count($pair_student_array).' student records found in 12 Twenty DB...<br />';
	
	$helperDenodo = new HelperDenodo;
	$students = $helperDenodo->GetStudentDataFromFile();
	
	$certificate_array = $helperDenodo->GetCertificateDataFromFile();
	$minor_array = $helperDenodo->GetMinorDataFromFile();
	$major_list = GetMajorTable();
	$term_list = GetGraduationTermTable();
	
	$student_list = array();
	for($i = 0; $i < count($students); $i++){
	//for($i = 0; $i < 10; $i++){
		$student = new Student($students[$i], $major_list, $term_list);	
		//if($student->StudentId == "2000228841"){
			$student->collegeArr[0]->setID($college_array);
			$student->CountryOfCitizenship->setID($country_array);
			$student->department[0]->setID($department_array);
			$student->Ethnicity1->setID($ethnicity_array);
			$student->majorArr[0]->setID($under_major_array);
			$student->degree[0]->setID($degree_array);
			$student->DegreeLevel->setID($degree_level_array);
			$student->WorkAuthorization->setID($work_auth_array);
			//var_dump($student);
			//echo "<br />";
			array_push($student_list, $student);
		//}
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
	//$minor_list = array_slice($minor_list, 0, 1000);	
	
	AddStudentCertificateAndMinor($student_list, $minor_list);
	
	echo 'Setting parameters to 12 Twenty API...<br />';
	//for($i=0; $i < 500; $i++){
	$student_jsonmodel_list = array();
	for($i=0; $i < count($student_list); $i++){
		for($j=0; $j< count($student_list[$i]->minorArr); $j++){
			$student_list[$i]->minorArr[$j]->setID($under_minor_array);
		}
		
		$student_list[$i]->SetParameters();
	}
	
	echo 'All set! <br />';
	echo '<br />';
	echo 'Output data : <br />';
	echo '<br />';
	
	//debug
	//for($i=0; $i < 1; $i++){
	//		var_dump(json_encode($student_list[$i]));
	//}
	//die();
	
	
	//var_dump($pair_student_array);
	
	$success_count = 0;
	//for($i=0; $i < 500; $i++){
	for($i=0; $i < count($student_list); $i++){	
		$method = '';
		$results = '';
		
		if(isset($pair_student_array[$student_list[$i]->StudentId])){
			$method = 'PUT';
			$student_existingjson_model = new ExistingStudentJsonModel($student_list[$i]);
		}else{
			$method = 'POST';
			$student_newjson_model = new NewStudentJsonModel($student_list[$i]);
		}
		//$method = 'POST';
		//$student_newjson_model = new NewStudentJsonModel($student_list[$i]);
		echo 'REQUEST method...'.$method.'<br />';
		echo 'Student id: '.$student_list[$i]->StudentId.'<br />';
		echo '12twenty id: '.$pair_student_array[$student_list[$i]->StudentId].'<br />';
		
		if($method == 'POST'){
			echo $method.' new student id: '.$student_list[$i]->StudentId.'<br />';
		}else{
			echo $method.' student id: '.$pair_student_array[$student_list[$i]->StudentId].'<br />';
		}
		
		if($method == 'POST'){
			$results = $helper12Twenty->PostStudent(json_encode($student_newjson_model));
			//var_dump(json_encode($student_newjson_model));
		}elseif($method == 'PUT'){
			/*foreach($pair_student_array as $k=>$v){
				$student12twentyId = "";
				
				if($k == $student_list[$i]->StudentId){
					$student12twentyId = $v;
					echo "<br />found!";
					echo $k."  -->  ".$v." matching -->". $student_list[$i]->StudentId;
					echo "<br />";
					break;
				}
				
				
			}*/
			$student12twentyId = $pair_student_array[$student_list[$i]->StudentId];
			$results = $helper12Twenty->PutStudent(json_encode($student_existingjson_model), $student12twentyId);
			//var_dump(json_encode($student_existingjson_model));
		}
		if(!$results || strlen($results) < 1000){
			var_dump($results);
		}else{
			echo 'import success!';
			var_dump($results);
			echo '<hr />';
			$success_count++;
		}
		//for testing
		//echo json_encode($student_list[$i]);		
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
				//add college too
				$student_list[$i-1]->AddCollege($student_list[$i]);
				//add degree too
				$student_list[$i-1]->AddDegree($student_list[$i]);
				//add devision too
				$student_list[$i-1]->AddDivision($student_list[$i]);
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
	
	class NewStudentJsonModel{
		public function __construct($student) {
			$this->RoleId = $student->RoleId;
			$this->FirstName = $student->FirstName;
			$this->MiddleName = $student->MiddleName;
			$this->LastName = $student->LastName;
			$this->EmailAddress = $student->EmailAddress;
			$this->GraduationYearId = $student->GraduationYearId;
			$this->GraduationTerm = $student->GraduationTerm;
			$this->GraduationClass = $student->GraduationClass;
			$this->StudentId = $student->StudentId;
			$this->SsoId = $student->SsoId;
			$this->Gender = $student->Gender;
			$this->IsAlumni = $student->IsAlumni;
			$this->IncludeInResumeBook = $student->IncludeInResumeBook;
			$this->PreferredEmailAddress = $student->PreferredEmailAddress;
			$this->Phone1 = $student->Phone1;
			$this->Phone2 = $student->Phone2;
			if(isset($student->IsEnrolled)){
				if($student->IsEnrolled == "Yes"){
					$this->IsEnrolled = True;
				}else{
					$this->IsEnrolled = False;
				}
			}
			$this->IsFerpa = $student->IsFerpa;
			$this->CountryOfCitizenship = $student->CountryOfCitizenship;
			$this->DegreeLevel = $student->DegreeLevel;
			$this->Ethnicity1 = $student->Ethnicity1;
			$this->WorkAuthorization = $student->WorkAuthorization;
			$this->CustomAttributeValues = $student->CustomAttributeValues;
			$this->MilitaryBackground = $student->MilitaryBackground;
			$this->College = $student->College;
			$this->College2 = $student->College2;
			$this->College3 = $student->College3;
			$this->Department1 = $student->Department1;
			$this->Department2 = $student->Department2;
			$this->Department3 = $student->Department3;
			$this->UndergradMajor = $student->UndergradMajor;
			$this->UndergradMajor2 = $student->UndergradMajor2;
			$this->UndergradMajor3 = $student->UndergradMajor3;
			$this->UndergradMajor4 = $student->UndergradMajor4;
			$this->UndergradMajor5 = $student->UndergradMajor5;
			$this->UndergradMinor = $student->UndergradMinor;
			$this->UndergradMinor2 = $student->UndergradMinor2;
			$this->UndergradMinor3 = $student->UndergradMinor3;
			$this->UndergradMinor4 = $student->UndergradMinor4;
			$this->UndergradMinor5 = $student->UndergradMinor5;
			$this->Degree1 = $student->Degree1;
			$this->Degree2 = $student->Degree2;
			$this->Degree3 = $student->Degree3;
		}
	}
	class ExistingStudentJsonModel{
		public function __construct($student) {
			$this->RoleId = $student->RoleId;
			$this->FirstName = $student->FirstName;
			$this->MiddleName = $student->MiddleName;
			$this->LastName = $student->LastName;
			$this->EmailAddress = $student->EmailAddress;
			$this->GraduationYearId = $student->GraduationYearId;
			$this->GraduationTerm = $student->GraduationTerm;
			$this->GraduationClass = $student->GraduationClass;
			$this->StudentId = $student->StudentId;
			$this->SsoId = $student->SsoId;
			$this->Gender = $student->Gender;
			$this->IsAlumni = $student->IsAlumni;
			$this->IncludeInResumeBook = $student->IncludeInResumeBook;
			$this->PreferredEmailAddress = $student->PreferredEmailAddress;
			$this->Phone1 = $student->Phone1;
			$this->Phone2 = $student->Phone2;
			if(isset($student->IsEnrolled)){
				if($student->IsEnrolled == "Yes"){
					$this->IsEnrolled = True;
				}else{
					$this->IsEnrolled = False;
				}
			}
			$this->IsFerpa = $student->IsFerpa;
			$this->CountryOfCitizenship = $student->CountryOfCitizenship;
			$this->DegreeLevel = $student->DegreeLevel;
			$this->Ethnicity1 = $student->Ethnicity1;
			$this->WorkAuthorization = $student->WorkAuthorization;
			$this->CustomAttributeValues = $student->CustomAttributeValues;
			$this->MilitaryBackground = $student->MilitaryBackground;
			$this->College = $student->College;
			$this->College2 = $student->College2;
			$this->College3 = $student->College3;
			$this->Department1 = $student->Department1;
			$this->Department2 = $student->Department2;
			$this->Department3 = $student->Department3;
			$this->UndergradMajor = $student->UndergradMajor;
			$this->UndergradMajor2 = $student->UndergradMajor2;
			$this->UndergradMajor3 = $student->UndergradMajor3;
			$this->UndergradMajor4 = $student->UndergradMajor4;
			$this->UndergradMajor5 = $student->UndergradMajor5;
			$this->UndergradMinor = $student->UndergradMinor;
			$this->UndergradMinor2 = $student->UndergradMinor2;
			$this->UndergradMinor3 = $student->UndergradMinor3;
			$this->UndergradMinor4 = $student->UndergradMinor4;
			$this->UndergradMinor5 = $student->UndergradMinor5;
			$this->Degree1 = $student->Degree1;
			$this->Degree2 = $student->Degree2;
			$this->Degree3 = $student->Degree3;
		}
	}
?>
