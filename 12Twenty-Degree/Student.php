<?php

	class Student {
		
		    // Simple scalars / flags
		public bool $InvalidData;
		public string $RoleId;
		public string $FirstName;
		public string $MiddleName;
		public string $LastName;
		public string $EmailAddress;
		public string $GraduationYearId;
		public string $GraduationTerm;
		public string $GraduationClass;
		public string $StudentId;
		public string $SsoId;
		public string $Gender;
		public bool $IsAlumni;
		public bool $IncludeInResumeBook;
		public string $PreferredEmailAddress;
		public string $Phone2;
		public string $Phone1;
		public string $IsEnrolled;
		public bool $IsFerpa;
		public string $SystemID;
		
		public Program $Program;
		public Country $CountryOfCitizenship;
		public Ethnicity $Ethnicity1;
		public Workauth $WorkAuthorization;
		public CustomAttributeValues $CustomAttributeValues;
		public MilitaryBackground $MilitaryBackground;
		
		public array $collegeArr;
		public array $majorArr;
		public array $minorArr;
		public array $degree;
		public array $department;
		
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
		
		public ?DegreeLevel $DegreeLevel = null;
		
		public string $MultipleEnrollmentLinkedAccountUserId;
		public bool $IsMultipleEnrollmentLinkedAccount;
		
		public string $APIMethod;
		
		public function __construct($db, $major_list) {
			//STU_ADMT_TERM_DESC missing
			$this->InvalidData = false;
			
			$this->RoleId = '3';  //3 -> full access
			
			$this->FirstName = !is_array($db['prsn_pref_1st_nm']) ? $db['prsn_pref_1st_nm'] : '';
			$this->MiddleName = !is_array($db['prsn_pref_mid_nm']) ? $db['prsn_pref_mid_nm'] : '';
			$this->LastName = !is_array($db['prsn_pref_last_nm']) ? $db['prsn_pref_last_nm'] : '';
			
			if(is_array($db['prsn_ntwrk_id'])){
				$this->InvalidData = true;
				$this->logProgress("Invalid prsn_ntwrk_id. name ".$db['prsn_pref_1st_nm']." ".$db['prsn_pref_last_nm']);	
			}else{
				$this->EmailAddress = $db['email'];
			}			
			//prsn_ntwrk_id@iu.edu
			
			$this->GraduationYearId = "";
			$this->GraduationTerm = "";				
			$this->GraduationClass = "";
			
			//apply all term
			if(isset($db['stu_degr_cmpltn_term_desc']) and !is_array($db['stu_degr_cmpltn_term_desc'])){				
				$termArr = explode(" ", $db['stu_degr_cmpltn_term_desc']);
				$this->GraduationTerm = $termArr[0];
				if($this->GraduationTerm != "Spring"){
					$termArr[1] = strval(intval($termArr[1])+1);
				}			
				$this->GraduationYearId = $termArr[1];
				$this->GraduationClass = $termArr[1];
			}
			//check if it is June
			if (isset($db['confer_date']) && !is_array($db['confer_date'])) {
				// Check if confer_date matches the pattern '06-30-20xx'
				if (preg_match('/^06-30-(\d{4})$/', $db['confer_date'], $matches)) {
					$this->GraduationTerm = "June";
					$this->GraduationYearId = $matches[1];
					$this->GraduationClass = $matches[1];
				}
			}
			if($this->GraduationTerm == ""){
				$this->InvalidData = true;
				$this->logProgress("Invalid GraduationTerm. id ".$db['prsn_ntwrk_id']);
			}
			
			$this->StudentId = $db['prsn_univ_id'];	
			
			if(!is_array($db['prsn_ntwrk_id']))
				$this->SsoId = $db['prsn_ntwrk_id'];				
			
			$this->Gender = $db['gender'];
			
			$this->IsAlumni = false;
			$this->IncludeInResumeBook = false;
			
			$this->PreferredEmailAddress = !is_array($db['prsn_othr_email_id']) ? $db['prsn_othr_email_id'] : '';		
			$this->Phone2 = !is_array($db['prsn_cell_phn_nbr']) ? $db['prsn_cell_phn_nbr'] : '';
			$this->Phone1 = !is_array($db['prsn_hm_phn_nbr']) ? $db['prsn_hm_phn_nbr'] : '';
			
			$this->IsEnrolled = 'Yes';//missing
			$this->Program = new Program();//missing
			
			$ferpa = false;
			if(!is_array($db['prsn_ferpa_rstrct_c_email_ind'])){
				if($db['prsn_ferpa_rstrct_c_email_ind'] == "Y"){
					$ferpa = true;
				}else{
					$ferpa = false;
				}
			}			
			$this->IsFerpa = $ferpa;
			$this->CountryOfCitizenship = new Country(!is_array($db['prsn_2nd_ctzn_cntry_desc']) ? $db['prsn_2nd_ctzn_cntry_desc'] : 'United States (USA)');
			
			
			
			
			
			$this->collegeArr = array();
			$this->majorArr = array();
			$this->minorArr = array();		
			$this->degree = array();
			$this->department = array();
			
			//add major, college, minor/cert, college, division
			for ($i = 1; $i <= 3; $i++) {
				// Construct variable names
				//major code 
				$major_code_key = "acad_plan_mjr{$i}_cd";
				//major 
				$major_key = "acad_plan_mjr{$i}_desc";
				
				//college
				$college_key = "acad_plan_mjr{$i}_grp_desc";
				
				//minor
				$mnr_desc_key = "acad_plan_mnr{$i}_desc";
				
				//program code
				$program_key = "acad_plan_mjr{$i}_pgm_cd";
				
				// Process major
				if (isset($db[$major_key]) && !is_array($db[$major_key])) {
					$newMajor = new Major($db[$major_key]);
					array_push($this->majorArr, $newMajor);
				}
				
				// Process minor
				if (isset($db[$mnr_desc_key]) && !is_array($db[$mnr_desc_key])) {
					$newMinor = new Minor($db[$mnr_desc_key]);
					array_push($this->minorArr, $newMinor);
				}
				
				// Process college
				if (isset($db[$college_key]) && !is_array($db[$college_key])) {
					$new_college = new College($db[$college_key]);
					array_push($this->collegeArr, $new_college);
				}
				
				// Process division and degree from file data
				if (isset($db[$major_code_key]) && !is_array($db[$major_code_key])) {
					$major_from_file = MajorFilter($major_list, $db[$major_code_key]);				
					if(!isset($major_from_file)){
						$this->InvalidData = true;
						$this->logProgress("Invalid Degree: id ".$db['prsn_ntwrk_id']." major code ".$db[$major_code_key]);
						$this->DegreeLevel = new DegreeLevel('Update');
					}else{
						$new_degree = isset($major_from_file) ?  new Degree($major_from_file->degree) : new Degree('Update');	
						$new_department = isset($major_from_file) ?  new Degree($major_from_file->division) : new Department('Update');
						array_push($this->degree, $new_degree);
						array_push($this->department, $new_department);
						//load degree level from file			
						$this->DegreeLevel = $major_from_file->degree_level != null ? new DegreeLevel($major_from_file->degree_level) : new DegreeLevel('Update');
					}
				}
			}
			
			
			$this->Ethnicity1 = new Ethnicity($db['ethnicity']);		
			$this->WorkAuthorization = new Workauth(!is_array($db['prsn_vprmt_typ_cd']) ? $db['prsn_vprmt_typ_cd'] : '');
			//CustomAttributeValues
			//customattr_50081/Home City
			//customattr_50082/Home State
			//customattr_10888805111851/Opt Out of Emails
			//customattr_106905112/First Generation
			//customattr_804/Preferred Name
			//customattr_50083/Residency Status
			$this->CustomAttributeValues = new CustomAttributeValues($db);
			$this->MilitaryBackground = new MilitaryBackground($db['mil_status']);			
			
			$this->College  = new College();
			$this->College2  = new College();
			$this->College3  = new College();
			
			$this->Department1  = new Department();
			$this->Department2  = new Department();
			$this->Department3  = new Department();
			
			$this->UndergradMajor  = new Major();
			$this->UndergradMajor2  = new Major();
			$this->UndergradMajor3  = new Major();
			$this->UndergradMajor4  = new Major();
			$this->UndergradMajor5 = new Major();
			
			$this->UndergradMinor = new Minor();
			$this->UndergradMinor2 = new Minor();
			$this->UndergradMinor3 = new Minor();
			$this->UndergradMinor4 = new Minor();
			$this->UndergradMinor5 = new Minor();
			
			$this->Degree1 = new Degree();
			$this->Degree2 = new Degree();
			$this->Degree3 = new Degree();
			
			$this->MultipleEnrollmentLinkedAccountUserId = "";
			$this->IsMultipleEnrollmentLinkedAccount = false;
			
			$this->APIMethod = "";			
		}
		function logProgress(string $info){
			echo gmdate("MdYH_i_s")."  ".$info.PHP_EOL;
		}
		function SetAPIMethod($method){
			$this->APIMethod = $method;
		}
		
		function SetLinkedAccount($isLinked, $linkedUserId){
			$this->MultipleEnrollmentLinkedAccountUserId = $linkedUserId;
			$this->IsMultipleEnrollmentLinkedAccount = $isLinked;
		}
		
		function AddMajor($student){
			$major_num = count($this->majorArr);
			for($i = 0; $i< count($student->majorArr); $i++){
				$this->majorArr[$major_num + $i] = $student->majorArr[$i];
			}			
		}
		function AddCertificateAndMinor($student){
			$minor_num = count($this->minorArr);
			for($i = 0; $i< count($student->minorArr); $i++){
				$this->minorArr[$minor_num + $i] = $student->minorArr[$i];
			}
		}
		function AddCollege($student){
			$college_num = count($this->collegeArr);
			//only accept 3 items
			if($college_num >= 3){
				return;
			}
			for($i = 0; $i< count($student->collegeArr); $i++){
				if($college_num + $i > 3){
					return;
				}
				$this->collegeArr[$college_num + $i] = $student->collegeArr[$i];
			}
		}
		function AddDivision($student){
			$department_num = count($this->department);
			//only accept 3 items
			if($department_num >= 3){
				return;
			}
			for($i = 0; $i< count($student->department); $i++){
				if($department_num + $i > 3){
					return;
				}
				$this->department[$department_num + $i] = $student->department[$i];
			}
		}
		function AddDegree($student){
			$degree_num = count($this->degree);
			//only accept 3 items
			if($degree_num >= 3){
				return;
			}
			for($i = 0; $i< count($student->degree); $i++){
				if($degree_num + $i > 3){
					return;
				}
				$this->degree[$degree_num + $i] = $student->degree[$i];
			}
		}
		function SetParameters(){
			$this->SetCollege();
			$this->SetMajor();
			$this->SetMinor();
			$this->SetDegree();
			$this->SetDepartment();			
		}
		function SetCollege(){
			$college_count = count($this->collegeArr);	
			for($i = 0; $i< $college_count; $i++){
				if($i != 0){
					$this->{'College'.($i+1)}->name = $this->collegeArr[$i]->name;
					$this->{'College'.($i+1)}->id = $this->collegeArr[$i]->id;
					$this->{'College'.($i+1)}->attribute_id = $this->collegeArr[$i]->attribute_id;
				}else{
					$this->College->name = $this->collegeArr[0]->name;
					$this->College->id = $this->collegeArr[0]->id;
					$this->College->attribute_id = $this->collegeArr[0]->attribute_id;
				}				
			}
		}
		function SetMajor(){
			$major_count = count($this->majorArr);
			for($i = 0; $i< $major_count; $i++){
				if($i != 0){
					$this->{'UndergradMajor'.($i+1)}->name = $this->majorArr[$i]->name;
					$this->{'UndergradMajor'.($i+1)}->id = $this->majorArr[$i]->id;
					$this->{'UndergradMajor'.($i+1)}->attribute_id = $this->majorArr[$i]->attribute_id;
				}else{
					$this->UndergradMajor->name = $this->majorArr[0]->name;
					$this->UndergradMajor->id = $this->majorArr[0]->id;
					$this->UndergradMajor->attribute_id = $this->majorArr[0]->attribute_id;
				}				
			}
		}
		function SetMinor(){
			$minor_count = count($this->minorArr);
			for($i = 0; $i< $minor_count; $i++){
				if($i != 0){
					$this->{'UndergradMinor'.($i+1)}->name = $this->minorArr[$i]->name;
					$this->{'UndergradMinor'.($i+1)}->id = $this->minorArr[$i]->id;
					$this->{'UndergradMinor'.($i+1)}->attribute_id = $this->minorArr[$i]->attribute_id;
				}else{
					$this->UndergradMinor->name = $this->minorArr[0]->name;
					$this->UndergradMinor->id = $this->minorArr[0]->id;
					$this->UndergradMinor->attribute_id = $this->minorArr[0]->attribute_id;
				}				
			}				
		}
		function SetDegree(){
			$degree_count = count($this->degree);
			for($i = 0; $i< $degree_count; $i++){
				$this->{'Degree'.($i+1)} ->name= $this->degree[$i]->name;
				$this->{'Degree'.($i+1)}->id = $this->degree[$i]->id;
				$this->{'Degree'.($i+1)}->attribute_id = $this->degree[$i]->attribute_id;				
			}		
		}
		function SetDepartment(){
			$department_count = count($this->department);
			for($i = 0; $i< $department_count; $i++){
				$this->{'Department'.($i+1)}->name = $this->department[$i]->name;	
				$this->{'Department'.($i+1)}->id = $this->department[$i]->id;
				$this->{'Department'.($i+1)}->attribute_id = $this->department[$i]->attribute_id;
			}
		}
	}
	
	class DegreeLevel{
		public ?string $Id;
        public ?string $AttributeId;
		public ?string $Name;
		public function __construct($name = ""){
			$this->Name = $name;
			$this->AttributeId='1000017';
			$this->Id=''; 
		}
		public function SetID($array){			
			foreach($array as $item){
			
				if($item['Name'] == $this->Name)					
					$this->Id = $item['Id'];
			}
		}
	}
	class Program{
		public ?string $Id;
        public ?string $AttributeId;
		public ?string $Name;
		public function __construct(){
			$this->Name = "Centralized Univ";
			$this->AttributeId='1000082';
			$this->Id='10006905110001';
		}
	}
	function MajorFilter($major_array, $major_code){
		//var_dump($major_code);
		foreach($major_array as $major){			
			if($major->major_code == $major_code)
				return $major;
		}
	}
	class CustomAttributeValues{
		public string $custom_attribute_50081;
        public string $custom_attribute_50082;
		public string $custom_attribute_10888805126337;
		
		public function __construct($db){
			$this->custom_attribute_50081 = gettype($db['prsn_hm_cty_nm']) != 'array' ? $db['prsn_hm_cty_nm'] : '';
			$this->custom_attribute_50082 = gettype($db['prsn_hm_st_desc']) != 'array' ? $db['prsn_hm_st_desc'] : '';
			//confer_date
			
			$date = DateTime::createFromFormat('m-d-Y', $db['confer_date']);    
			// Check if the date was created successfully
			if ($date) {
				// Format the date as "yyyy-mm-dd" and return it
				$this->custom_attribute_10888805126337 = $date->format('Y-m-d');
			}
			//$this->custom_attribute_10888805111851 = "N";
			//$this->custom_attribute_106905112 = '';
			//$this->custom_attribute_804 = '';
			//$this->custom_attribute_50083 = '50083002';
			/*if($db['residency'] == 'R'){
				$this->custom_attribute_50083 = '50083001';
			}else{
				$this->custom_attribute_50083 = '50083002';
			}*/
		}
	}
	class Major{
		public ?string $id;
        public ?string $attribute_id;
		public ?string $name;
		public function __construct($name = "") {
			$this->id = null;//get from 1220 api
			$this->name = $name;
			$this->attribute_id = '1000102';
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->name)					
					$this->id = $item['Id'];
			}
		}
	}
	class Minor{
		public ?string $id;
        public ?string $attribute_id;
		public ?string $name;
		public function __construct($name = "") {
			$this->id = null;//get from 1220 api
			$this->name = $name;
			$this->attribute_id = '1000103';
			//ACAD_PLAN_TYP_CD = 'MIN'  OR ( ACAD_PLAN_TYP_CD = 'MAJ' AND ACAD_PGM_CD IN ('COLL0','MSCH0','SGIS0'))
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->name)					
					$this->id = $item['Id'];
			}
		}
	}
	class College{
		public ?string $id;
        public ?string $attribute_id;
		public ?string $name;
		public function __construct($name = "") {
			$this->id = null;//get from 1220 api
			$this->name = $name;
			$this->attribute_id = '1000007';
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->name)					
					$this->id = $item['Id'];
			}
		}
	}
	
	class Ethnicity{
		public ?string $id;
        public ?string $attribute_id;
		public ?string $name;
		public function __construct($name="") {
			$this->id = null;//get from 1220 api
			$this->attribute_id = '1000027';
			$this->name = $name;
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->name)					
					$this->id = $item['Id'];
			}
		}
	}
	
	class Country{
		public ?string $id;
        public ?string $attribute_id;
		public ?string $name;
		public function __construct($name="") {
			$this->id = null;//get from 1220 api
			$this->attribute_id = '1000011';
			$this->name = $name;
			if($this->name == "China")
				$this->name = "China Mainland";
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->name)					
					$this->id = $item['Id'];
			}
		}
	}
	
	class Degree{
		public ?string $id;
        public ?string $attribute_id;
		public ?string $name;
		public function __construct($name="") {
			$this->id = null;//get from 1220 api
			$this->attribute_id = '1000016';
			$this->name = $name;
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->name)					
					$this->id = $item['Id'];
			}
		}
	}	
	class Department{
		//only match major id
		public ?string $id;
        public ?string $attribute_id;
		public ?string $name;
		public function __construct($name ="") {
			$this->id = null;//get from 1220 api
			$this->attribute_id = '1000018';
			$this->name = $name;
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->name)					
					$this->id = $item['Id'];
			}
		}
	}
	class MilitaryBackground{
		public ?string $id;
        public ?string $attribute_id;
		public ?string $name;
		public function __construct($name="") {
			$this->id = null;
			$this->attribute_id = '1000038';
			switch($name){
				case 'N':
				$name = 'No';
				break;
				case 'Y':
				$name = 'Yes';
				break;
				default:
				$name = 'No';
			}
			$this->name = $name;
			$this->setID();
		}
		public function SetID(){					
			$this->name == 'Yes' ? $this->id = '1' : $this->id = '2';
		}
	}
	class Workauth{
		public ?string $id;
        public ?string $attribute_id;
		public ?string $name;
		public function __construct($name="") {
			$this->id = null;//get from 1220 api
			$this->attribute_id = '1000108';
			switch($name){
				case 'A1':
				$this->name = 'A1 Diplomat and Family';
				break;
				case 'A2':
				$this->name = 'A2 - Other Foreign Government Official';
				break;
				case 'AA':
				$this->name = 'EA Education Abroad';
				break;
				case 'B1':
				$this->name = 'B1 Temporary Business Visitor';
				break;
				case 'B2':
				$this->name = 'B2 Temp Visitor-Pleas';
				break;
				case 'DA':
				$this->name = 'Deferred Action Employment Authorization (DACA)';
				break;
				case 'E1':
				$this->name = 'E1 Treaty Trader';
				break;
				case 'E2':
				$this->name = 'E2 Treaty Investor';
				break;
				case 'E3':
				$this->name = 'E-3 Certain Specialty Occupation Professionals from Australia';
				break;				
				case 'F-1' :
				$this->name = 'F1 Student';
				break;
				case 'G4':
				$this->name = 'G4 Employee of International Org and Family';
				break;
				case 'H1B':
				$this->name = 'H1b Temp Spec Worker';
				break;
				case 'H4':
				$this->name = 'H4 Family of Temp Spec Worker';
				break;
				case 'I-9':
				$this->name = 'Non-Permanent Work Authorization';
				break;
				case 'II':
				$this->name = 'II Intending Immigrant';
				break;
				case 'IM':
				$this->name = 'IM Immigrant';
				break;
				case 'J-1':
				$this->name = 'J1 Exchange Visitor';
				break;
				case 'J-2':
				$this->name = 'J2 Exchange Visitor';
				break;
				case 'LPR':
				$this->name = 'US Citizen or Permanent Resident';
				break;
				case 'NULL':
				$this->name = 'US Citizen or Permanent Resident';
				break;
				case 'O':
				$this->name = 'Not Applicable / Unknown';
				break;
				case 'O1':
				$this->name = 'O-1 Visa: Individuals with Extraordinary Ability or Achievement';
				break;
				case 'TD':
				$this->name = 'TD Spouse of Child of TN';
				break;
				case 'TN':
				$this->name = 'Mex-NAFTA TN Can.';
				break;
				case 'TPA':
				$this->name = 'TPS Temporary Protected Status';
				break;
				default:
				$this->name = 'US Citizen or Permanent Resident';
			}
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->name)					
					$this->id = $item['Id'];
			}
		}		
	}
	
	

?>