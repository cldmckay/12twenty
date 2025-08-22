<?PHP
	class HelperDenodo{
		// constructor
		public string $username;
		public string $password;		
		public string $test;
		public string $debug;	
		
		public array $opts;
		
		public function __construct() {
			$this->username = "lhadleyk";
			$this->password = "";
			$this->opts = array(
			  'http'=>array(
				'method'=>"GET",
				'header' => "Authorization: Basic " . base64_encode("$this->username:$this->password")                 
			  )
			);
		}		
		
		function SaveStudentDataToFile(){
			$studentArray = array();
			//$remote_url = 'https://ebidvt-dev.uits.iu.edu/server/iu_webtech_iuieapi/testa/views/testa?$format=XML';
			$remote_url = 'https://ebidvt.uits.iu.edu/server/iu_webtech_iuieapi/iuieapi_main/views/iuieapi_main?$format=XML';			
			try{
				$context = stream_context_create($this->opts);// Open the file using the HTTP headers set above
				$file = file_get_contents($remote_url, false, $context);
				if(strlen($file) > 100){
					file_put_contents("/groups/iuieapi/bin/DenodoFiles/students.txt", $file);	
					$file = file_get_contents("/groups/iuieapi/bin/DenodoFiles/students.txt", false);	
					$xml = simplexml_load_string($file);
					
					$student_count = 0;
					foreach($xml->{'iuieapi_main'} as $student){
						//echo $student_count++; echo '<br />';
						$json = json_encode($student);
						array_push($studentArray, json_decode($json, true));
					}					
				}
				$this->logProgress(count($studentArray)." student entries saved to file!");
				
			}catch(Exception $e){
				$this->logProgress("Caught exception: ".$e->getMessage());
			}			
		}
		function GetStudentDataFromFile(){
			$studentArray = array();
			try{
				$file = file_get_contents("/groups/iuieapi/bin/DenodoFiles/students.txt", false);
				$xml = simplexml_load_string($file);
				foreach($xml->{'iuieapi_main'} as $student){
					$json = json_encode($student);
					array_push($studentArray, json_decode($json, true));
				}
				$this->logProgress(count($studentArray)." student entries imported from file!");
			}catch(Exception $e){
				$this->logProgress("Caught exception: ".$e->getMessage());
			}
			return $studentArray;
		}
		
		function SaveCertificateDataToFile(){
			$certificateArray = array();
			$remote_url = 'https://ebidvt.uits.iu.edu/server/iu_webtech_iuieapi/iuieapi_certificate/views/iuieapi_certificate?$format=XML';
			try{			
				$context = stream_context_create($this->opts);// Open the file using the HTTP headers set above
				$file = file_get_contents($remote_url, false, $context);
				if(strlen($file) > 100){
					file_put_contents("/groups/iuieapi/bin/DenodoFiles/certificate.txt", $file);	
					$file = file_get_contents("/groups/iuieapi/bin/DenodoFiles/certificate.txt", false);			
					$xml = simplexml_load_string($file);
					foreach($xml->{'iuieapi_certificate'} as $certificate){
						$json = json_encode($certificate);
						array_push($certificateArray, json_decode($json, true));
					}
				}
				
				$this->logProgress(count($certificateArray)." certificate entries saved to file!");
			}catch(Exception $e){
				$this->logProgress("Caught exception: ".$e->getMessage());
			}
		}
		function GetCertificateDataFromFile(){
			$certificateArray = array();
			try{	
				$file = file_get_contents("/groups/iuieapi/bin/DenodoFiles/certificate.txt", false);				
				$xml = simplexml_load_string($file);				
				
				foreach($xml->{'iuieapi_certificate'} as $certificate){
					$json = json_encode($certificate);
					array_push($certificateArray, json_decode($json, true));
				}
				$this->logProgress(count($certificateArray)." certificate entries imported from file!");
			}catch(Exception $e){
				$this->logProgress("Caught exception: ".$e->getMessage());
			}
			return $certificateArray;
		}
		
		function SaveMinorDataToFile(){
			$minorArray = array();
			$remote_url = 'https://ebidvt.uits.iu.edu/server/iu_webtech_iuieapi/iuieapi_minor/views/iuieapi_minor?$format=XML';
			try{	
				$context = stream_context_create($this->opts);// Open the file using the HTTP headers set above
				$file = file_get_contents($remote_url, false, $context);
				if(strlen($file) > 100){
					file_put_contents("/groups/iuieapi/bin/DenodoFiles/minor.txt", $file);	
					$file = file_get_contents("/groups/iuieapi/bin/DenodoFiles/minor.txt", false);			
					$xml = simplexml_load_string($file);		
					
					foreach($xml->{'iuieapi_minor'} as $minor){
						$json = json_encode($minor);
						array_push($minorArray, json_decode($json, true));
					}
				}
				
				$this->logProgress(count($minorArray)." minor entries saved to file!");
			}catch(Exception $e){
				$this->logProgress("Caught exception: ".$e->getMessage());
			}
		}
		function GetMinorDataFromFile(){
			$minorArray = array();
			try{	
				$file = file_get_contents("/groups/iuieapi/bin/DenodoFiles/minor.txt", false);	
				$xml = simplexml_load_string($file);
				
				
				foreach($xml->{'iuieapi_minor'} as $minor){
					$json = json_encode($minor);
					array_push($minorArray, json_decode($json, true));
				}
				$this->logProgress(count($minorArray)." minor entries imported from file!");
			}catch(Exception $e){
				$this->logProgress("Caught exception: ".$e->getMessage());
			}
			return $minorArray;
		}
		
		public function logProgress(string $info){
			echo gmdate("MdYH_i_s")."  ".$info.PHP_EOL;
		}	
	}
?>