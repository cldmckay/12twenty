<?php
	class CertificateMinor {
		// constructor
		public function __construct($db) {
			$this->StudentId = $db['prsn_univ_id'];
			$newMinor = new Minor($db['acad_plan_desc']);
			$this->minorArr= array();
			array_push($this->minorArr, $newMinor);
			$this->collegeArr = array();
			$new_college = new College($db['acad_pgm_desc']);
			array_push($this->collegeArr, $new_college);
		}
	}
?>