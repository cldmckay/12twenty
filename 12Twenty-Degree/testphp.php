<?php
	require_once('/groups/pagriet/lib/iet/CURLClient.php');
	//require_once('CURLClient.php');
	class Helper12Twenty {
		// constructor
		public function __construct(bool $test = false, bool $debug = false) {
			$this->client = new CURLClient;
			$this->key = array(
				'key' => 'MP9U~!xDW^%268*y[3',
			);
			$this->test = $test;
			$this->debug = $debug;
			
			
			if($this->test){
				$this->getTokenUrl = "https://indiana.admin.sandbox-12twenty.com/api/client/generateAuthenticationToken?";
			
			}else{
				$this->getTokenUrl = "https://indiana.admin.12twenty.com/api/client/generateAuthenticationToken?";
			}

			$this->token = $this->client->get($this->getTokenUrl, $this->key);
			$this->authorization = "Authorization: Bearer ".$this->token;
			//$this->authorization = "Authorization: Bearer q0HOT7Ziug41w8m6fs9bexS5TKwSidHhfVsoaz5X9R3ZPVReF6re95BK9T1gMc6dhl2Is4Abg54mDY2xkAZZTNvZ6UwnQtPlWFXlYNRYSoSFW2hqark8KpNo/ByaE0ZgXLh/upB7rPs=";
			$this->header = array('Content-Type: application/json', $this->authorization);
			
			
		}
		
		function GetOptions(string $opt){
			$optionVar = '';
			switch($opt){
				case 'College list':
					$optionVar = '119';
				break;
				case 'Academic term':
					$optionVar = '102';
				break;
				case 'Country':
					$optionVar = '176';
				break;
				case 'Ethnicity':
					$optionVar = '150';
				break;
				case 'Department':
					$optionVar = '144';
				break;
				case 'Major':
					$optionVar = '277';
				break;
				case 'Minor':
					$optionVar = '278';
				break;
				case 'Degree':
					$optionVar = '141';
				break;
				case 'Degree Level':
					$optionVar = '142';
				break;
				case 'Work Auth':
					$optionVar = '285';
				break;
			}
			if($optionVar != ''){
				return $this->GetOptionData($optionVar);
			}
		}		
		
		function GetStudentsIDPair(){
			
			$full_list = array();
			$pair_list = array();
			
			$page_num = 1;
			$page_size = 500;
			if($this->test){
				$url = "https://indiana.admin.sandbox-12twenty.com/api/v2/students?PageSize=".$page_size."&PageNumber=".$page_num;
			
			}else{
				$url = "https://indiana.admin.12twenty.com/api/v2/students?PageSize=".$page_size."&PageNumber=".$page_num;
			}
			$this->result = $this->client->get($url, '', $this->header);
			$returned = json_decode($this->result, true);
			$full_list = $returned["Items"];
			
			$total = 0;
			$total = $returned["Total"];
			$total_page_num = ceil($total/$page_size);
			
			if($this->debug == true){				
				$total_page_num = 3;
			}
			
			for($i=2; $i<$total_page_num+1; $i++){
			//for($i=2; $i<3; $i++){
				if($this->test){
					$url = "https://indiana.admin.sandbox-12twenty.com/api/v2/students?PageSize=".$page_size."&PageNumber=".$i;
				
				}else{
					$url = "https://indiana.admin.12twenty.com/api/v2/students?PageSize=".$page_size."&PageNumber=".$i;
				}
				
				$this->result = $this->client->get($url, '', $this->header);
				$returned = json_decode($this->result, true);			
				$full_list = array_merge($full_list, $returned["Items"]);
			}
			
			foreach($full_list as $item){
				//echo $item["StudentId"].'<br />';
				//echo $item["Id"].'<br />';
				$pair_list[$item["StudentId"]]=$item["Id"];
			}
			//echo count($pair_list).'student id pair imported!<br/>';
			return $pair_list;
		}		
		
		function GetOptionData(string $optionVar){	
			if($this->test){
				$url = "https://indiana.admin.sandbox-12twenty.com/api/v2/lookups/".$optionVar."/options";
			
			}else{
				$url = "https://indiana.admin.12twenty.com/api/v2/lookups/".$optionVar."/options";
			}		
			
			$this->result = $this->client->get($url, '', $this->header);
			return $this->result;
		}
		
		function GetStudentIn12Twenty(string $studentID){
			
			if($this->test){
				$url = "https://indiana.admin.sandbox-12twenty.com/api/V2/students/?StudentId=".$studentID;
			
			}else{
				$url = "https://indiana.admin.12twenty.com/api/V2/students/?StudentId=".$studentID;
			}
				
			$pair_list = array();
			$full_list = array();
			
			try{				
			
				$this->result = $this->client->get($url, '', $this->header);
				$returned = json_decode($this->result, true);
				if(isset($returned["Items"])){
					$full_list = $returned["Items"];
				}	
				if(count($full_list) > 0){
					foreach($full_list as $item){
						if(isset($item["DegreeLevel"]["Name"])){
							if($item["IsMultipleEnrollmentLinkedAccount"] == 'true'){
								$newStudent = new Student12twentyModel($item["StudentId"], $item["Id"], $item["DegreeLevel"]["Name"], true);
							}else{
								$newStudent = new Student12twentyModel($item["StudentId"], $item["Id"], $item["DegreeLevel"]["Name"], false);
							}	
							array_push($pair_list, $newStudent);					
						}				
					}	
				}
			
			}catch (Exception $e) {
				return 'Caught exception getting student from 12twenty: '.$e->getMessage()." with student".$student->Id;
			}
			return $pair_list;
		}
		
		function PostStudent(string $student, string $studentID){
			
			if($this->test){
				$url = "https://indiana.admin.sandbox-12twenty.com/api/v2/students";
			
			}else{
				$url = "https://indiana.admin.12twenty.com/api/v2/students";
			}			
			
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => $url,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_POSTFIELDS => $student,
			  CURLOPT_HTTPHEADER => $this->header,
			));


			try{
				$response = curl_exec($curl);
			
				curl_close($curl);
				
				$response = json_decode($response, true);
				if(isset($response["Id"])){
					return $response["Id"];
				}else{
					var_dump($student);
					return "Error with student data! Method: POST, ID:".$studentID;	
				}				
			}catch (Exception $e) {
				return 'Caught exception post: '.$e->getMessage()." with student".$student->Id;
			}
		}
		
		function PutStudent(string $student, string $id, string $studentID){			
			if($this->test){
				$url = "https://indiana.admin.sandbox-12twenty.com/api/v2/students/".$id;
			
			}else{
				$url = "https://indiana.admin.12twenty.com/api/v2/students/".$id;
			}		
			
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => $url,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'PATCH',
			  CURLOPT_POSTFIELDS => $student,
			  CURLOPT_HTTPHEADER => $this->header,
			));

			try{
				$response = curl_exec($curl);
			
				curl_close($curl);
				
				$response = json_decode($response, true);
				if(isset($response["Id"])){
					return $response["Id"];
				}else{
					var_dump($student);
					return "Error with student data! Method: PUT, ID:".$studentID;					
				}
			}catch (Exception $e) {
				return 'Caught exception put: '.$e->getMessage()." with student".$student->Id;
			}
		}
		
		public function logProgress(string $info){
			echo gmdate("MdYH_i_s")."  ".$info.PHP_EOL;
		}
	}
	class Student12twentyModel{
		public function __construct($studentId, $systemId, $degreeLevel, $islinked) {
			$this->StudentId = $studentId;
			$this->SystemId = $systemId;
			$this->DegreeLevel = $degreeLevel;	
			$this->IsMultipleEnrollmentLinkedAccount = $islinked;			
		}		
	}
	
	
	
	$helper = new Helper12Twenty(true, true);
	var_dump($helper->GetStudentIn12Twenty("0009999999"));
?>