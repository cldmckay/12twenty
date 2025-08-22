<?php
	ini_set('memory_limit', '8G');
	ini_set('include_path', '/groups/iuieapi/bin/12Twenty:'.get_include_path());

	//ini_set('display_errors', 1);
	//ini_set('display_startup_errors', 1);
	//error_reporting(E_ALL);
	include('Helper12Twenty.php');
	include('HelperDenodo.php');
	include('Student.php');
	include('CertificateMinor.php');
	include('ExcelReader.php');
	
	Class MainApp{
		public Helper12Twenty $helper12Twenty;
		public HelperDenodo $helperDenodo;
		
		public function __construct(){
			$test = true;
			$debug = false;
			
			//$this->logProgress("Debug mode...");
			//die();
			
			$this->helper12Twenty = new Helper12Twenty($test, $debug);
			$this->helperDenodo = new HelperDenodo();
			
			$this->logProgress("Script starts");
			
			$filePath12Twenty = "/groups/iuieapi/bin/12TwentyFiles/";
			
			$this->logProgress("Getting options from 12Twenty...");		
			$college_array = $this->LoadOptionsFrom12Twenty("College list");
			$academic_term_array = $this->LoadOptionsFrom12Twenty("Academic term");
			$country_array = $this->LoadOptionsFrom12Twenty("Country");
			$ethnicity_array = $this->LoadOptionsFrom12Twenty("Ethnicity");
			$department_array = $this->LoadOptionsFrom12Twenty("Department");
			$under_major_array = $this->LoadOptionsFrom12Twenty("Major");
			$under_minor_array = $this->LoadOptionsFrom12Twenty("Minor");
			$degree_array = $this->LoadOptionsFrom12Twenty("Degree");
			$degree_level_array = $this->LoadOptionsFrom12Twenty("Degree Level");
			$work_auth_array = $this->LoadOptionsFrom12Twenty("Work Auth");
			
			$this->logProgress("Getting data from Denodo...");		

			$filePathDenodo = "/groups/iuieapi/bin/DenodoFiles/";	
			
			$this->SaveFileFromDenodo($filePathDenodo, "students.txt");
			$this->SaveFileFromDenodo($filePathDenodo, "certificate.txt");
			$this->SaveFileFromDenodo($filePathDenodo, "minor.txt");

			$this->logProgress("Denodo data downloaded!");
			
			
			$this->logProgress("Reading Denodo files...");
			$this->logProgress("Reading StudentData file...");	
			$students = $this->helperDenodo->GetStudentDataFromFile();			
			$this->logProgress("Reading CertificateData file...");	
			$certificate_array = $this->helperDenodo->GetCertificateDataFromFile();
			$this->logProgress("Reading MinorData file...");
			$minor_array = $this->helperDenodo->GetMinorDataFromFile();
			$this->logProgress("Reading MajorTable file...");
			$major_list = GetMajorTable();	
			$this->logProgress(count($major_list)." major entries imported from file!");
			$this->logProgress("Reading GraduationTerm file...");
			$term_list = GetGraduationTermTable();
			$this->logProgress(count($term_list)." term entries imported from file!");
			$this->logProgress("All data imported!");
			
			$this->logProgress("Creating Student records...");
			
			$student_list = array();
			for($i = 0; $i < count($students); $i++){
			//for($i = 0; $i < 3; $i++){
				if(gettype($students[$i]['prsn_pref_1st_nm']) != 'array'){
					$student = new Student($students[$i], $major_list, $term_list);	
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
			
			
			
			$this->logProgress(count($student_list)." student records created!");
			
			
			$this->logProgress("Adding certificate to student..");
			$this->AddStudentCertificateAndMinor($student_list, $certificate_list);
			$this->logProgress("Adding minor to student..");
			$this->AddStudentCertificateAndMinor($student_list, $minor_list);
			
			$this->logProgress("Combining Student records...");
			$student_list = $this->CombineDuplicateStudentMajor($student_list);
			$this->logProgress(count($student_list) ." unique student degree level records left!");
			

			
			$this->logProgress("Setting parameters for 12 Twenty API..");
			$student_jsonmodel_list = array();
			for($i=0; $i < count($student_list); $i++){
				for($j=0; $j< count($student_list[$i]->minorArr); $j++){
					$student_list[$i]->minorArr[$j]->setID($under_minor_array);
				}				
				$student_list[$i]->SetParameters();
			}
			
			$this->logProgress("Removing invalid student data..");
			
			$student_list_degree = array();
			foreach($student_list as $studentRecord){
				if(!$studentRecord->InvalidData){
					if(!isset($student_list_degree[$studentRecord->StudentId])){
						$student_list_degree[$studentRecord->StudentId] = array();
					}
					array_push($student_list_degree[$studentRecord->StudentId], $studentRecord);		
				}
				//$this->logProgress("Student ID :".$studentRecord->StudentId. " has been removed!");				
			}
			
			$this->logProgress(count($student_list_degree) ." student group records left!");
			
			$this->logProgress("Data processing done..");
			
			$this->logProgress("--------------------------------------------------------");
			$this->logProgress("--------------------------------------------------------");
			if($test == true){
				$this->logProgress("Sending data to sandbox server..");				
			}else{
				$this->logProgress("Sending data to live server..");	
			}
			
			
			$success_count = 0;
			$failed_count = 0;
			$failed_student_id = array();
			$totalCount = 0;
			
			foreach($student_list_degree as $studentGroup){		
				
				$method = '';
				$results = '';
				$student_json_model = '';
				$counter = 0;
				$studentMatch = false;
				$degreeMatch = false;
				$studentMatchSystemId = 0;
				foreach($studentGroup as $studentRecord){
					
					$studentMatch = false;
					$degreeMatch = false;
					$studentMatchSystemId = 0;
					$pair_student_array = $this->helper12Twenty->GetStudentIn12Twenty($studentRecord->StudentId);
					var_dump($studentRecord->StudentId);
					//check if record is in the 12twenty database
					//check for both ID and deree
					foreach($pair_student_array as $studentInSystem){
						if($studentInSystem->StudentId == $studentRecord->StudentId){
							
							//student ID found in 12twenty
							//var_dump($studentInSystem);
							$studentMatch = true;
							$studentMatchSystemId = $studentInSystem->SystemId;
							//echo "comparing ".$studentInSystem->DegreeLevel." with ".$studentRecord->DegreeLevel->Name;
							if($studentInSystem->DegreeLevel == $studentRecord->DegreeLevel->Name){
								$studentRecord->SetAPIMethod("PATCH");
								$studentRecord->SetLinkedAccount($studentInSystem->IsMultipleEnrollmentLinkedAccount, "");
								//student degree found in 12twenty
								//echo "student degree found in 12twenty";
								//change the method to PATCH to overwrite the current
								$degreeMatch = true;
								$studentRecord->SystemID = $studentInSystem->SystemId;
							}
						}
					}
					
					if($studentMatch == true && $degreeMatch == false){
						//if only student id found but no degree found, set as a new linked record.
						$studentRecord->SetLinkedAccount(true, $studentMatchSystemId);
					}
				}
				
				foreach($studentGroup as $studentRecord){
					if($studentRecord->APIMethod == ""){
						$studentRecord->SetAPIMethod("POST");
					}
				}
					
				$allPost = true;
				if(count($studentGroup) > 1){					
					foreach($studentGroup as $studentRecord){
						if($studentRecord->APIMethod != "POST"){
							$allPost = false;
						}
					}
				}

				if($allPost == true && count($studentGroup) > 1){
					$firstPost = false;
					$firstStudentSystemId = "";
					foreach($studentGroup as $studentRecord){
						if($firstPost == false){
							//$this->logProgress("Post new student ".$studentRecord->StudentId);
							$student_json_model = json_encode(new NewStudentJsonModel($studentRecord));
							if($debug) var_dump($student_json_model);
							if($studentRecord->MultipleEnrollmentLinkedAccountUserId == ""){
								$student_json_model = str_replace('"MultipleEnrollmentLinkedAccountUserId":"",', "", $student_json_model);
							}
							$results = $this->helper12Twenty->PostStudent($student_json_model, $studentRecord->StudentId);
							if(!is_numeric($results)){
								$this->logProgress($results);
								$failed_count++;								
							}else{
								$firstPost = true;
								//$this->logProgress($results . " uploaded!");
								$firstStudentSystemId =	$results;
								$success_count++;
							}							
						}else{
							$studentRecord->SetLinkedAccount(true, $firstStudentSystemId);
							$this->logProgress("Post new student record ".$studentRecord->StudentId ." links to".$firstStudentSystemId);
							$student_json_model = json_encode(new NewStudentJsonModel($studentRecord));
							if($debug) var_dump($student_json_model);
							$results = $this->helper12Twenty->PostStudent($student_json_model, $studentRecord->StudentId);
							if(!is_numeric($results)){
								$this->logProgress($results);
								$failed_count++;
								array_push($failed_student_id, $studentRecord->StudentId);								
							}else{
								//$this->logProgress($results . " uploaded!");
								$success_count++;
							}	
						}						
					}
					
				}
				else{
					foreach($studentGroup as $studentRecord){	
						if(count($studentGroup) > 1){
							$this->logProgress("Multiple records found for student ".$studentRecord->StudentId);
							if($studentRecord->MultipleEnrollmentLinkedAccountUserId != ""){
								$this->logProgress("MultipleEnrollmentLinkedAccountUserId is ".$studentRecord->MultipleEnrollmentLinkedAccountUserId);
							}
						}
						if($studentRecord->APIMethod == "POST"){
							$this->logProgress("Post new student ".$studentRecord->StudentId);
							$student_json_model = json_encode(new NewStudentJsonModel($studentRecord));								
							if($studentRecord->MultipleEnrollmentLinkedAccountUserId == ""){
								$student_json_model = str_replace('"MultipleEnrollmentLinkedAccountUserId":"",', "", $student_json_model);
							}
							if($debug) var_dump($student_json_model);
							$results = $this->helper12Twenty->PostStudent($student_json_model, $studentRecord->StudentId);
							
						}else if($studentRecord->APIMethod == "PATCH"){
							$this->logProgress("PATCH existing student ".$studentRecord->StudentId);
							$student_json_model = json_encode(new ExistingStudentJsonModel($studentRecord));
							
							if($studentRecord->IsMultipleEnrollmentLinkedAccount == true && $studentRecord->MultipleEnrollmentLinkedAccountUserId == ""){
								$student_json_model = str_replace('"IsMultipleEnrollmentLinkedAccount": true,', "", $student_json_model);
								$student_json_model = str_replace('"MultipleEnrollmentLinkedAccountUserId":"",', "", $student_json_model);
							}else if($studentRecord->MultipleEnrollmentLinkedAccountUserId == ""){
								$student_json_model = str_replace('"MultipleEnrollmentLinkedAccountUserId":"",', "", $student_json_model);
							}
							if($debug) var_dump($student_json_model);
							$results = $this->helper12Twenty->PutStudent($student_json_model, $studentRecord->SystemID, $studentRecord->StudentId);
						}
						
						if(!is_numeric($results)){
							$this->logProgress($results);	
							$failed_count++;
							array_push($failed_student_id, $studentRecord->StudentId);
						}else{
							//$this->logProgress($results." uploaded!");
							$success_count++;
						}		
					}
				}				
				$totalCount ++;
			}
			
			$this->logProgress("--------------------------------------------------------");
			$this->logProgress("--------------------------------------------------------");
			$this->logProgress($success_count." students successfully imported!");
			$this->logProgress($failed_count." students did not get imported correctly!");
			for($i = 0; $i < count($failed_student_id); $i++){
				$this->logProgress("Please check these student records: StudentID ".$failed_student_id[$i]);
			}
			
			$this->logProgress("--------------------------------------------------------");
			$this->logProgress("Deleting temp data...");
			shell_exec('rm -r /groups/iuieapi/bin/DenodoFiles/*');
			$this->SendLog();
		}
	
		function SaveFileFromDenodo($filePath, $fileName){
			
			$fileDownloadCount = 0;
			$fileReady = false;
			while($fileReady == false){
				$fullPath = $filePath.$fileName;
				if (file_exists($fullPath)) shell_exec('rm -r '.$fullPath);
				switch($fileName){
					case "students.txt" : 						
						$this->helperDenodo->SaveStudentDataToFile();
					break;
					case "certificate.txt" : 
						$this->helperDenodo->SaveCertificateDataToFile();
					break;
					case "minor.txt" : 
						$this->helperDenodo->SaveMinorDataToFile();
					break;				
				}
				$fileReady = $this->CheckFile($fullPath);
				if(!$fileReady){
					$fileDownloadCount++;
					$this->logProgress($fullPath." not ready... trying ".$fileDownloadCount. " time...");
					if($fileDownloadCount >= 10){
						$this->logProgress("File download failed... exiting application...");
						$this->SendLog();
						exit();
					}
					sleep(5);
				}
			}			
		}
			
		function CheckFile($file_path){
			
			if (file_exists($file_path)) {
				$this->logProgress("The file $file_path exists");
				if(filesize($file_path) > 0){
					$this->logProgress("The file $file_path contains data");
					return true;
				}				
			} else {
				$this->logProgress("The file $file_path does not exist...");
				return false;
			}			
		}
		function LoadOptionsFrom12Twenty($option){
			$this->logProgress("Loading ".$option." options from 12Twenty...");		
			return json_decode($this->helper12Twenty->GetOptions($option), true);			
		}
		
		function logProgress(string $info){
			echo gmdate("MdYH_i_s")."  ".$info.PHP_EOL;
		}
			
		function SendLog(){
			$to1      = 'haihguo@iu.edu';
			$to2      = 'lhadleyk@iu.edu';
			$to3      = 'kmahome@iu.edu';
			$subject = '12Twenty log';
			$message = file_get_contents('/groups/iuieapi/log.txt', false);
			$headers = 'From: 12TwentyApp@iu.edu'       . "\r\n" .
						 'Reply-To: 12TwentyApp@iu.edu' . "\r\n" .
						 'X-Mailer: PHP/' . phpversion();
			mail($to1, $subject, $message, $headers);
			mail($to2, $subject, $message, $headers);
			mail($to3, $subject, $message, $headers);			
		}
		
		function CombineDuplicateStudentMajor($student_list){
			$this->logProgress("Merging duplicate student majors...");			
			$studentList_new = array();
			
			for($i=0; $i<count($student_list); $i++){
				
				$match = false;
				for($j=0; $j < count($studentList_new); $j++){
					
					if($studentList_new[$j]->StudentId == $student_list[$i]->StudentId){
						if($studentList_new[$j]->DegreeLevel == $student_list[$i]->DegreeLevel){							
							$studentList_new[$j]->AddMajor($student_list[$i]);
							$studentList_new[$j]->AddCollege($student_list[$i]);
							$studentList_new[$j]->AddDegree($student_list[$i]);
							$studentList_new[$j]->AddDivision($student_list[$i]);
							$match = true;
							break;
						}						
					}				
				}
				
				if($match != true){					
					array_push($studentList_new, $student_list[$i]);
				}
			}
			return $studentList_new;
		}
		
		function AddStudentCertificateAndMinor($student_list, $certificate_minor_list){	
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
	}

	class NewStudentJsonModel{
		public bool $IsMultipleEnrollmentLinkedAccount;
		public string $MultipleEnrollmentLinkedAccountUserId;
		public int $RoleId;
		public string $FirstName;
		public string $MiddleName;
		public string $LastName;
		public string $PreferredName;
		public string $EmailAddress;
		public int $GraduationYearId;
		public string $GraduationTerm;
		public int $GraduationClass;
		public string $StudentId;
		public string $SsoId;
		public string $Gender;
		public bool $IsAlumni;
		public bool $IncludeInResumeBook;
		public string $PreferredEmailAddress;
		public string $Phone1;
		public string $Phone2;
		public bool $IsEnrolled;
		public bool $IsFerpa;
		public Country $CountryOfCitizenship;
		public DegreeLevel $DegreeLevel;
		public Ethnicity $Ethnicity1;
		public WorkAuth $WorkAuthorization;
		public CustomAttributeValues $CustomAttributeValues;
		public MilitaryBackground $MilitaryBackground;
		public College $College;
		public College $College2;
		public College $College3;
		public Department $Department1;
		public Department $Department2;
		public Department $Department3;
		public Major $UndergradMajor;
		public Major $UndergradMajor2;
		public Major $UndergradMajor3;
		public Major $UndergradMajor4;
		public Major $UndergradMajor5;
		public Minor $UndergradMinor;
		public Minor $UndergradMinor2;
		public Minor $UndergradMinor3;
		public Minor $UndergradMinor4;
		public Minor $UndergradMinor5;
		public Degree $Degree1;
		public Degree $Degree2;
		public Degree $Degree3;
		
		public function __construct($student) {
			$this->IsMultipleEnrollmentLinkedAccount = $student->IsMultipleEnrollmentLinkedAccount; 
			$this->MultipleEnrollmentLinkedAccountUserId = $student->MultipleEnrollmentLinkedAccountUserId; 
			$this->RoleId = $student->RoleId;
			$this->FirstName = $student->FirstName;
			$this->MiddleName = $student->MiddleName;
			$this->LastName = $student->LastName;
			$this->PreferredName = $student->PreferredName;
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
		public bool $IsMultipleEnrollmentLinkedAccount;
		public string $MultipleEnrollmentLinkedAccountUserId;
		public int $RoleId;
		public string $FirstName;
		public string $MiddleName;
		public string $LastName;
		public string $PreferredName;
		public string $EmailAddress;
		public int $GraduationYearId;
		public string $GraduationTerm;
		public int $GraduationClass;
		public string $StudentId;
		public string $SsoId;
		public string $Gender;
		public bool $IsAlumni;
		public bool $IncludeInResumeBook;
		public string $PreferredEmailAddress;
		public string $Phone1;
		public string $Phone2;
		public bool $IsEnrolled;
		public bool $IsFerpa;
		public Country $CountryOfCitizenship;
		public DegreeLevel $DegreeLevel;
		public Ethnicity $Ethnicity1;
		public WorkAuth $WorkAuthorization;
		public CustomAttributeValues $CustomAttributeValues;
		public MilitaryBackground $MilitaryBackground;
		public College $College;
		public College $College2;
		public College $College3;
		public Department $Department1;
		public Department $Department2;
		public Department $Department3;
		public Major $UndergradMajor;
		public Major $UndergradMajor2;
		public Major $UndergradMajor3;
		public Major $UndergradMajor4;
		public Major $UndergradMajor5;
		public Minor $UndergradMinor;
		public Minor $UndergradMinor2;
		public Minor $UndergradMinor3;
		public Minor $UndergradMinor4;
		public Minor $UndergradMinor5;
		public Degree $Degree1;
		public Degree $Degree2;
		public Degree $Degree3;
		
		public function __construct($student) {
			$this->IsMultipleEnrollmentLinkedAccount = $student->IsMultipleEnrollmentLinkedAccount; 
			$this->MultipleEnrollmentLinkedAccountUserId = $student->MultipleEnrollmentLinkedAccountUserId; 
			$this->RoleId = $student->RoleId;
			$this->FirstName = $student->FirstName;
			$this->MiddleName = $student->MiddleName;
			$this->LastName = $student->LastName;
			$this->PreferredName = $student->PreferredName;
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
	
	
	class Student12twentyModel{
		public string $StudentId;
		public string $SystemId;
		public string $DegreeLevel;
		public bool $IsMultipleEnrollmentLinkedAccount;
		
		public function __construct($studentId, $systemId, $degreeLevel, $islinked) {
			$this->StudentId = $studentId;
			$this->SystemId = $systemId;
			$this->DegreeLevel = $degreeLevel;	
			$this->IsMultipleEnrollmentLinkedAccount = $islinked;			
		}		
	}
	new MainApp;
?>
