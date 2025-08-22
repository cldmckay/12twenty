<?php

	class Student {
		// constructor
		public bool $InvalidData;
		public string $RoleId;
		public string $FirstName;
		public string $MiddleName;
		public string $LastName;
		public string $PreferredName;
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
		
		public function __construct($db, $major_list, $term_list) {
			//STU_ADMT_TERM_DESC missing
			$this->InvalidData = false;
			
			$this->RoleId = '3';  //3 -> full access
			
			$this->FirstName = !is_array($db['prsn_pref_1st_nm']) ? $db['prsn_pref_1st_nm'] : '';
			$this->MiddleName = !is_array($db['prsn_pref_mid_nm']) ? $db['prsn_pref_mid_nm'] : '';
			$this->LastName = !is_array($db['prsn_pref_last_nm']) ? $db['prsn_pref_last_nm'] : '';
			$this->PreferredName = !is_array($db['prsn_pref_full_nm']) ? $db['prsn_pref_full_nm'] : '';
			
			if(is_array($db['prsn_ntwrk_id'])){
				$this->InvalidData = true;
				$this->logProgress("Invalid prsn_ntwrk_id. name ".$db['prsn_pref_1st_nm']." ".$db['prsn_pref_last_nm']);	
			}else{
				$this->EmailAddress = $db['email'].'@iu.edu';
			}				
			//prsn_ntwrk_id@iu.edu
			
			$this->GraduationYearId = "";
			$this->GraduationTerm = "";				
			$this->GraduationClass = "";
			
			if(isset($db['stu_expct_grad_term_desc']) and !is_array($db['stu_expct_grad_term_desc'])){				
				$termArr = explode(" ", $db['stu_expct_grad_term_desc']);
				$this->GraduationTerm = $termArr[0];
				if($this->GraduationTerm != "Spring"){
					$termArr[1] = strval(intval($termArr[1])+1);
				}			
				$this->GraduationYearId = $termArr[1];
				$this->GraduationClass = $termArr[1];
			}else{
				for($i = 0; $i < Count($term_list); $i++){
					if(strval($term_list[$i]->admitTerm) == $db['stu_pgm_stk_admt_trm_cd']){
						$this->GraduationYearId = $term_list[$i]->graduationYear;
						$graduationTermArr = explode(" ", $term_list[$i]->graduationTerm);
						//$term['graduationTerm'] example:  Spring 2022
						$this->GraduationTerm = $graduationTermArr[0];						
						$this->GraduationClass = $graduationTermArr[1];
					}
				}				
			}
			
			if($this->GraduationTerm == ""){
				$this->InvalidData = true;
				//echo "invalid GraduationTerm";
			}
			
			$this->StudentId = $db['prsn_univ_id'];			
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
			$tmpArray = array("COLL0","MSCH0","SGIS0");
			if($db['acad_plan_typ_cd'] == 'MIN'  OR ( $db['acad_plan_typ_cd'] == 'MAJ' AND in_array($db['acad_pgm_cd'], $tmpArray))){
			}else{
				$newMajor = new Major($db['acad_plan_desc']);
			}
			array_push($this->majorArr, $newMajor);
			//array_push($this->minor, $newMinor);
			
			$this->collegeArr = array();
			$new_college = new College($db['acad_grp_desc']);
			array_push($this->collegeArr, $new_college);
			
			
			
			
			
			//
			
			$this->degree = array();
			$this->department = array();
			$temp_major = MajorFilter($major_list, $db['acad_plan_cd']);
			//if($this->DegreeLevel != 'Undergraduate'){ ///change to not equal   
			//	$new_degree = new Degree('Other');
			//}else{			
			
				//if nothing, change to "Update"
			//}
			
			if(!isset($temp_major)){
				$this->InvalidData = true;
				//echo "invalid major";
			}
			
			$new_degree = isset($temp_major) ?  new Degree($temp_major->degree) : new Degree('Update');	
			$new_department = isset($temp_major) ?  new Department($temp_major->division) : new Department('Update');
			array_push($this->degree, $new_degree);
			array_push($this->department, $new_department);
			$this->DegreeLevel =  isset($temp_major->degree_level) ? new DegreeLevel($temp_major->degree_level) : new DegreeLevel('Update');	
			
			
			
			//IR_INTND_DEGR_ABBREV_CD for Undergraduates for Graduate level must be derived from ACAD_PLAN_DESC (MBA, MA, MS, PHD, etc.)    do not understand*			
			
			
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
			$this->majorArr[$major_num] = $student->majorArr[0];
		}
		function AddCertificateAndMinor($student){
			$minor_num = count($this->minorArr);
			$this->minorArr[$minor_num] = $student->minorArr[0];
		}
		function AddCollege($student){
			$college_num = count($this->collegeArr);
			$this->collegeArr[$college_num] = $student->collegeArr[0];
		}
		function AddDivision($student){
			$department_num = count($this->department);
			$this->department[$department_num] = $student->department[0];
		}
		function AddDegree($student){
			$degree_num = count($this->degree);
			$this->degree[$degree_num] = $student->degree[0];
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
				if($i != 0 && $i < 3){
					$this->{'College'.($i+1)} = $this->collegeArr[$i];
				}else{
					$this->College = $this->collegeArr[0];
				}				
			}
		}
		function SetMajor(){
			$major_count = count($this->majorArr);
			for($i = 0; $i< $major_count; $i++){
				if($i != 0){
					$this->{'UndergradMajor'.($i+1)} = $this->majorArr[$i];
				}else{
					$this->UndergradMajor = $this->majorArr[0];
				}				
			}
		}
		function SetMinor(){
			$minor_count = count($this->minorArr);
			for($i = 0; $i< $minor_count; $i++){
				if($i != 0){
					$this->{'UndergradMinor'.($i+1)} = $this->minorArr[$i];
				}		
			}				
		}
		function SetDegree(){
			$degree_count = count($this->degree);
			for($i = 0; $i< $degree_count; $i++){
				if($i < 3){
					$this->{'Degree'.($i+1)}= $this->degree[$i];	
				}				
			}		
		}
		function SetDepartment(){
			$department_count = count($this->department);
			for($i = 0; $i< $department_count; $i++){
				if($i < 3){
					$this->{'Department'.($i+1)} = $this->department[$i];	
				}
			}
		}
	}
	
	class DegreeLevel{
		public ?int $Id;
        public ?int $AttributeId;
		public ?string $Name;
		public function __construct($name = ""){
			$this->Name = $name;
			$this->AttributeId=1000017;
			$this->Id=null; 
		}
		public function SetID($array){			
			foreach($array as $item){
			
				if($item['Name'] == $this->Name)					
					$this->Id = $item['Id'];
			}
		}
	}
	class Program{
		public ?int $Id;
        public ?int $AttributeId;
		public ?string $Name;
		public function __construct(){
			$this->Name = "Centralized Univ";
			$this->AttributeId=1000082;
			$this->Id=10006905110001;
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
		public ?string $custom_attribute_50081;
		public ?string $custom_attribute_50082;
		public ?string $custom_attribute_10888805111851;
		public ?string $custom_attribute_106905112;
		public ?string $custom_attribute_50083;
		
		public function __construct($db){
			$this->custom_attribute_50081 = gettype($db['prsn_hm_cty_nm']) != 'array' ? $db['prsn_hm_cty_nm'] : '';
			$this->custom_attribute_50082 = gettype($db['prsn_hm_st_desc']) != 'array' ? $db['prsn_hm_st_desc'] : '';
			$this->custom_attribute_10888805111851 = "N";
			$this->custom_attribute_106905112 = $db['ir_frst_gen_ind'];			
			if($db['residency'] == 'R'){
				$this->custom_attribute_50083 = '50083001';
			}else{
				$this->custom_attribute_50083 = '50083002';
			}
		}
	}
	class Major{
		public ?int $Id;
        public ?int $AttributeId;
		public ?string $Name;
		public function __construct($name = "") {
			$this->Id = null;//get from 1220 api
			$this->Name = $name;
			$this->AttributeId = 1000102;
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->Name)					
					$this->Id = $item['Id'];
			}
		}
	}
	class Minor{
		public ?int $Id;
        public ?int $AttributeId;
		public ?string $Name;
		public function __construct($name = "") {
			$this->Id = null;//get from 1220 api
			$this->Name = $name;
			$this->AttributeId = 1000103;
			//ACAD_PLAN_TYP_CD = 'MIN'  OR ( ACAD_PLAN_TYP_CD = 'MAJ' AND ACAD_PGM_CD IN ('COLL0','MSCH0','SGIS0'))
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->Name)					
					$this->Id = $item['Id'];
			}
		}
	}
	class College{
		public ?int $Id;
        public ?int $AttributeId;
		public ?string $Name;
		public function __construct($name = "") {
			$this->Id = null;//get from 1220 api
			$this->Name = $name;
			$this->AttributeId = 1000007;
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->Name)					
					$this->Id = $item['Id'];
			}
		}
	}
	
	class Ethnicity{
		public ?int $Id;
        public ?int $AttributeId;
		public ?string $Name;
		public function __construct($name="") {
			$this->Id = null;//get from 1220 api
			$this->AttributeId = '1000027';
			$this->Name = $name;
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->Name)					
					$this->Id = $item['Id'];
			}
		}
	}
	
	class Country{
		public ?int $Id;
        public ?int $AttributeId;
		public ?string $Name;
		public function __construct($name="") {
			$this->Id = null;//get from 1220 api
			$this->AttributeId = 1000011;
			$this->Name = $name;
			if($this->Name == "China")
				$this->Name = "China Mainland";
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->Name)					
					$this->Id = $item['Id'];
			}
		}
	}
	
	class Degree{
		public ?int $Id;
        public ?int $AttributeId;
		public ?string $Name;
		public function __construct($name="") {
			$this->Id = null;//get from 1220 api
			$this->AttributeId = 1000016;
			$this->Name = $name;
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->Name)					
					$this->Id = $item['Id'];
			}
		}
	}	
	class Department{
		//only match major id
		public ?int $Id;
        public ?int $AttributeId;
		public ?string $Name;
		public function __construct($name ="") {
			$this->Id = null;//get from 1220 api
			$this->AttributeId = 1000018;
			$this->Name = $name;
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->Name)					
					$this->Id = $item['Id'];
			}
		}
	}
	class MilitaryBackground{
		public ?int $Id;
        public ?int $AttributeId;
		public ?string $Name;
		public function __construct($name="") {
			$this->Id = null;
			$this->AttributeId = 1000038;
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
			$this->Name = $name;
			$this->setID();
		}
		public function SetID(){					
			$this->Name == 'Yes' ? $this->Id = 1 : $this->Id = 2;
		}
	}
	class Workauth{
		public ?int $Id;
        public ?int $AttributeId;
		public ?string $Name;
		public function __construct($name="") {
			$this->Id = null;//get from 1220 api
			$this->AttributeId = 1000108;
			switch($name){
				case 'A1':
				$this->Name = 'A1 Diplomat and Family';
				break;
				case 'A2':
				$this->Name = 'A2 - Other Foreign Government Official';
				break;
				case 'AA':
				$this->Name = 'EA Education Abroad';
				break;
				case 'B1':
				$this->Name = 'B1 Temporary Business Visitor';
				break;
				case 'B2':
				$this->Name = 'B2 Temp Visitor-Pleas';
				break;
				case 'DA':
				$this->Name = 'Deferred Action Employment Authorization (DACA)';
				break;
				case 'E1':
				$this->Name = 'E1 Treaty Trader';
				break;
				case 'E2':
				$this->Name = 'E2 Treaty Investor';
				break;
				case 'E3':
				$this->Name = 'E-3 Certain Specialty Occupation Professionals from Australia';
				break;				
				case 'F-1' :
				$this->Name = 'F1 Student';
				break;
				case 'G4':
				$this->Name = 'G4 Employee of International Org and Family';
				break;
				case 'H1B':
				$this->Name = 'H1b Temp Spec Worker';
				break;
				case 'H4':
				$this->Name = 'H4 Family of Temp Spec Worker';
				break;
				case 'I-9':
				$this->Name = 'Non-Permanent Work Authorization';
				break;
				case 'II':
				$this->Name = 'II Intending Immigrant';
				break;
				case 'IM':
				$this->Name = 'IM Immigrant';
				break;
				case 'J-1':
				$this->Name = 'J1 Exchange Visitor';
				break;
				case 'J-2':
				$this->Name = 'J2 Exchange Visitor';
				break;
				case 'LPR':
				$this->Name = 'US Citizen or Permanent Resident';
				break;
				case 'NULL':
				$this->Name = 'US Citizen or Permanent Resident';
				break;
				case 'O':
				$this->Name = 'Not Applicable / Unknown';
				break;
				case 'O1':
				$this->Name = 'O-1 Visa: Individuals with Extraordinary Ability or Achievement';
				break;
				case 'TD':
				$this->Name = 'TD Spouse of Child of TN';
				break;
				case 'TN':
				$this->Name = 'Mex-NAFTA TN Can.';
				break;
				case 'TPA':
				$this->Name = 'TPS Temporary Protected Status';
				break;
				default:
				$this->Name = 'US Citizen or Permanent Resident';
			}
		}
		public function SetID($array){
			foreach($array as $item){
				if($item['Name'] == $this->Name)					
					$this->Id = $item['Id'];
			}
		}		
	}
	
	

?>