<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


function GetMajorTable(){
	//echo '<br /><br /><br />get major table<br /><br /><br />';
	$inputFileType = 'Xlsx';
	$inputFileName = '/groups/iuieapi/bin/iuie_majors.xlsx';

	$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

	$worksheetData = $reader->listWorksheetInfo($inputFileName);

	$reader->setReadDataOnly(true);
	/**  Load $inputFileName to a Spreadsheet Object  **/
	$spreadsheet = $reader->load($inputFileName);
	$worksheet = $spreadsheet->getActiveSheet();

	$data = array();
	$row_names = array();
	$row_num = 1;
	foreach ($worksheet->getRowIterator() AS $row) {
		//echo '$row_num'.$row_num;
		if($row_num == 1){
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(FALSE); // This loops through all cells,
			$column = 1;
			foreach ($cellIterator as $cell) {
				$row_names[$column] = $cell->getValue();
				$data[$row_names[$column]] = array();
				$column++;
			}
		}else{
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(FALSE); // This loops through all cells,
			$cells = [];
			$column = 1;
			foreach ($cellIterator as $cell) {
				//echo '<br />'.$row_names[$column].' --> '.$cell->getValue().'<br />';
				array_push($data[$row_names[$column]], $cell->getValue());
				$column++;
			}		
		}
		$row_num++;
	}
	$major_list = [];
	for($i = 0; $i < Count($data['Program']); $i++){
		//var_dump($data['Program'][i]);
		$major_list[] = new MajorItem($data['Career'][$i],$data['Program'][$i],$data['Program Description'][$i],$data['Major Code'][$i],$data['Major Description'][$i],$data['Division'][$i], $data['Degree Level'][$i], $data['Degree'][$i]);
	}
	//var_dump($major_list);
	return $major_list;
}

function GetGraduationTermTable(){
	//echo '<br /><br /><br />get major table<br /><br /><br />';
	$inputFileType = 'Xlsx';
	$inputFileName = '/groups/iuieapi/bin/12Twenty/Graduation_Term_Table.xlsx';

	$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

	$worksheetData = $reader->listWorksheetInfo($inputFileName);

	$reader->setReadDataOnly(true);
	/**  Load $inputFileName to a Spreadsheet Object  **/
	$spreadsheet = $reader->load($inputFileName);
	$worksheet = $spreadsheet->getActiveSheet();

	$data = array();
	$row_names = array();
	$row_num = 1;
	foreach ($worksheet->getRowIterator() AS $row) {
		//echo '$row_num'.$row_num;
		if($row_num == 1){
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(FALSE); // This loops through all cells,
			$column = 1;
			foreach ($cellIterator as $cell) {
				$row_names[$column] = $cell->getValue();
				$data[$row_names[$column]] = array();
				$column++;
			}
		}else{
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(FALSE); // This loops through all cells,
			$cells = [];
			$column = 1;
			foreach ($cellIterator as $cell) {
				//echo '<br />'.$row_names[$column].' --> '.$cell->getValue().'<br />';
				array_push($data[$row_names[$column]], $cell->getValue());
				$column++;
			}		
		}
		$row_num++;
	}
	$term_list = [];
	for($i = 0; $i < Count($data['Admit Term']); $i++){
		//var_dump($data['Program'][i]);
		$term_list[] = new TermItem($data['Admit Term'][$i],$data['Graduation Term'][$i],$data['Graduation Year'][$i]);
	}
	//var_dump($term_list);
	return $term_list;
}

class TermItem {
	public ?string $admitTerm;
    public ?string $graduationTerm;
    public ?string $graduationYear;
	public function __construct($admitTerm, $graduationTerm, $graduationYear) {
		//$UNIX_DATE = ($beginDate - 25569) * 86400;		
		$this->admitTerm = $admitTerm;
		$this->graduationTerm = $graduationTerm;
		$this->graduationYear = $graduationYear;
	}
}

class MajorItem {
    public ?string $career;
    public ?string $program;
    public ?string $program_description;
    public ?string $major_code;
    public ?string $major_description;
    public ?string $division;
    public ?string $degree_level;
    public ?string $degree;
	public function __construct($career, $program, $programdesc, $majorcode, $majordesc, $division, $degree_level,  $degree) {
		$this->career = $career;
		$this->program = $program;
		$this->program_description = $programdesc;
		$this->major_code = $majorcode;
		$this->major_description = $majordesc;
		$this->division = $division;
		$this->degree_level = $degree_level;
		$this->degree = $degree;
	}
}



?>