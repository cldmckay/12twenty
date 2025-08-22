<?php
	require_once('/groups/pagriet/lib/iet/CURLClient.php');
	//require_once('CURLClient.php');
	class Helper12Twenty {
		// constructor
		public function __construct() {
			$this->client = new CURLClient;
			$this->key = array(
				'key' => 'MP9U~!xDW^%268*y[3',
			);
			$this->getTokenUrl = "https://indiana.admin.12twenty.com/api/client/generateAuthenticationToken?";
			$this->token = $this->client->get($this->getTokenUrl, $this->key);
			$this->authorization = "Authorization: Bearer ".$this->token;
			$this->header = array('Content-Type: application/json', $this->authorization);
		}
		
		function GetOptions(string $opt){
			$optionVar = '';
			switch($opt){
				case 'College list':
					echo 'getting College list... <br />';
					$optionVar = '119';
				break;
				case 'Academic term':
					echo 'getting Academic term list... <br />';
					$optionVar = '102';
				break;
				case 'Country':
					echo 'getting Country list... <br />';
					$optionVar = '176';
				break;
				case 'Ethnicity':
					echo 'getting Ethnicity list... <br />';
					$optionVar = '150';
				break;
				case 'Department':
					echo 'getting Department list... <br />';
					$optionVar = '144';
				break;
				case 'Major':
					echo 'getting Undergrad Major list... <br />';
					$optionVar = '277';
				break;
				case 'Minor':
					echo 'getting Undergrad Minor list... <br />';
					$optionVar = '278';
				break;
				case 'Degree':
					echo 'getting Degree list... <br />';
					$optionVar = '141';
				break;
				case 'Degree Level':
					echo 'getting Degree level list... <br />';
					$optionVar = '142';
				break;
				case 'Work Auth':
					echo 'getting Work Authorization list... <br />';
					$optionVar = '285';
				break;
			}
			if($optionVar != ''){
				return $this->GetOptionData($optionVar);
			}
		}		
		function GetStudentsIDPair(){
			echo 'getting Students in 12 Twenty DB... <br />';
			$full_list = array();
			$pair_list = array();
			
			$page_num = 1;
			$page_size = 500;
			$url = "https://indiana.admin.12twenty.com/api/v2/students?PageSize=".$page_size."&PageNumber=".$page_num;
			$this->result = $this->client->get($url, '', $this->header);
			$returned = json_decode($this->result, true);
			$full_list = $returned["Items"];
			
			$total = 0;
			$total = $returned["Total"];
			$total_page_num = ceil($total/$page_size);
			//for($i=2; $i<$total_page_num+1; $i++){
			for($i=2; $i<3; $i++){
				$url = "https://indiana.admin.12twenty.com/api/v2/students?PageSize=".$page_size."&PageNumber=".$i;
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
			$url = "https://indiana.admin.12twenty.com/api/v2/lookups/".$optionVar."/options";
			$this->result = $this->client->get($url, '', $this->header);
			return $this->result;
		}
		
		function PostStudent(string $student){
			$url = "https://indiana.admin.12twenty.com/api/v2/students";
			$this->result = $this->client->post($url, $student, $this->header);
			return $this->result;
		}
		
		function PutStudent(string $student, string $id){
			$url = "https://indiana.admin.12twenty.com/api/v2/students/".$id;
			$this->result = $this->client->put($url, $student, $this->header);
			return $this->result;
		}
		
	}
?>