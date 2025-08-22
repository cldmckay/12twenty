<?php

/**
 * Minimal XLSX reader using built‑in PHP extensions only.
 * Reads the first worksheet and returns data as a two‑dimensional array.
 */
function readXlsx(string $filename): array {
    $zip = new ZipArchive();
    if ($zip->open($filename) !== true) {
        return [];
    }

    $sharedStrings = [];
    if (($idx = $zip->locateName('xl/sharedStrings.xml')) !== false) {
        $xml = simplexml_load_string($zip->getFromIndex($idx));
        foreach ($xml->si as $si) {
            $sharedStrings[] = (string) $si->t;
        }
    }

    $sheet = [];
    if (($idx = $zip->locateName('xl/worksheets/sheet1.xml')) !== false) {
        $xml = simplexml_load_string($zip->getFromIndex($idx));
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $c) {
                $value = (string) $c->v;
                if ((string) $c['t'] === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                }
                $col = preg_replace('/\d+/', '', (string) $c['r']);
                $rowData[columnIndexFromString($col)] = $value;
            }
            ksort($rowData);
            $sheet[] = array_values($rowData);
        }
    }

    $zip->close();
    return $sheet;
}

function columnIndexFromString(string $letters): int {
    $letters = strtoupper($letters);
    $col = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $col = $col * 26 + (ord($letters[$i]) - 64);
    }
    return $col - 1; // zero based
}

function GetMajorTable(): array {
    $rows = readXlsx('/groups/iuieapi/bin/iuie_majors.xlsx');
    if (empty($rows)) {
        return [];
    }

    $headers = array_shift($rows);
    $data = [];
    foreach ($headers as $h) {
        $data[$h] = [];
    }
    foreach ($rows as $row) {
        foreach ($headers as $i => $h) {
            $data[$h][] = $row[$i] ?? null;
        }
    }

    $major_list = [];
    $count = count($data['Program'] ?? []);
    for ($i = 0; $i < $count; $i++) {
        $major_list[] = new MajorItem(
            $data['Career'][$i] ?? null,
            $data['Program'][$i] ?? null,
            $data['Program Description'][$i] ?? null,
            $data['Major Code'][$i] ?? null,
            $data['Major Description'][$i] ?? null,
            $data['Division'][$i] ?? null,
            $data['Degree Level'][$i] ?? null,
            $data['Degree'][$i] ?? null
        );
    }
    return $major_list;
}

function GetGraduationTermTable(): array {
    $rows = readXlsx('/groups/iuieapi/bin/12Twenty/Graduation_Term_Table.xlsx');
    if (empty($rows)) {
        return [];
    }

    $headers = array_shift($rows);
    $data = [];
    foreach ($headers as $h) {
        $data[$h] = [];
    }
    foreach ($rows as $row) {
        foreach ($headers as $i => $h) {
            $data[$h][] = $row[$i] ?? null;
        }
    }

    $term_list = [];
    $count = count($data['Admit Term'] ?? []);
    for ($i = 0; $i < $count; $i++) {
        $term_list[] = new TermItem(
            $data['Admit Term'][$i] ?? null,
            $data['Graduation Term'][$i] ?? null,
            $data['Graduation Year'][$i] ?? null
        );
    }
    return $term_list;
}

class TermItem {
    public ?string $admitTerm;
    public ?string $graduationTerm;
    public ?string $graduationYear;

    public function __construct($admitTerm, $graduationTerm, $graduationYear) {
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

    public function __construct($career, $program, $programdesc, $majorcode, $majordesc, $division, $degree_level, $degree) {
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

