<?php

declare(strict_types=1);

namespace App;

use PhpOffice\PhpSpreadsheet\IOFactory;

class Comparator
{
    /**
     * Compare two Excel / CSV files.
     */
    public function compare(
        string $fileA,
        string $fileB,
        string $mode = 'student_id'
    ): array {

        $rowsA = $this->readFile($fileA);
        $rowsB = $this->readFile($fileB);

        $totalA = count($rowsA);
        $totalB = count($rowsB);

        $matched  = 0;
        $modified = 0;
        $missingA = 0;
        $missingB = 0;
        $duplicate = 0;
        $invalid = 0;

        $indexA = [];
        $indexB = [];

        /*
        |--------------------------------------------------------------------------
        | Build File A index
        |--------------------------------------------------------------------------
        */

        foreach ($rowsA as $row) {

            $key = $this->getCompareKey($row, $mode);

            if ($key === '') {
                $invalid++;
                continue;
            }

            if (isset($indexA[$key])) {
                $duplicate++;
            }

            $indexA[$key] = $row;
        }

        /*
        |--------------------------------------------------------------------------
        | Build File B index
        |--------------------------------------------------------------------------
        */

        foreach ($rowsB as $row) {

            $key = $this->getCompareKey($row, $mode);

            if ($key === '') {
                $invalid++;
                continue;
            }

            if (isset($indexB[$key])) {
                $duplicate++;
            }

            $indexB[$key] = $row;
        }

        /*
        |--------------------------------------------------------------------------
        | Compare all students
        |--------------------------------------------------------------------------
        */

        $results = [];

        $allKeys = array_unique(
            array_merge(
                array_keys($indexA),
                array_keys($indexB)
            )
        );

        foreach ($allKeys as $key) {

            $a = $indexA[$key] ?? null;
            $b = $indexB[$key] ?? null;

            /*
            |--------------------------------------------------------------------------
            | Missing from File B
            |--------------------------------------------------------------------------
            */

            if ($a !== null && $b === null) {

                $missingB++;

                $results[] = [
                    'nameA'     => $this->getName($a),
                    'nameB'     => '',
                    'icA'       => $this->getIC($a),
                    'icB'       => '',
                    'idA'       => $this->getStudentId($a),
                    'idB'       => '',
                    'status'    => 'Missing',
                    'difference'=> 'Missing in File B',
                    'remarks'   => 'Student exists in File A only'
                ];

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Missing from File A
            |--------------------------------------------------------------------------
            */

            if ($a === null && $b !== null) {

                $missingA++;

                $results[] = [
                    'nameA'     => '',
                    'nameB'     => $this->getName($b),
                    'icA'       => '',
                    'icB'       => $this->getIC($b),
                    'idA'       => '',
                    'idB'       => $this->getStudentId($b),
                    'status'    => 'Missing',
                    'difference'=> 'Missing in File A',
                    'remarks'   => 'Student exists in File B only'
                ];

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Both files contain student
            |--------------------------------------------------------------------------
            */

            $nameA = $this->getName($a);
            $nameB = $this->getName($b);

            $icA = $this->getIC($a);
            $icB = $this->getIC($b);

            $idA = $this->getStudentId($a);
            $idB = $this->getStudentId($b);

            $differences = [];

            /*
            |--------------------------------------------------------------------------
            | Compare Name
            |--------------------------------------------------------------------------
            */

            if (
                $this->normalize($nameA)
                !==
                $this->normalize($nameB)
            ) {
                $differences[] = 'Name';
            }

            /*
            |--------------------------------------------------------------------------
            | Compare IC
            |--------------------------------------------------------------------------
            */

            if (
                $this->normalize($icA)
                !==
                $this->normalize($icB)
            ) {
                $differences[] = 'IC';
            }

            /*
            |--------------------------------------------------------------------------
            | Compare Student ID
            |--------------------------------------------------------------------------
            */

            if (
                $this->normalize($idA)
                !==
                $this->normalize($idB)
            ) {
                $differences[] = 'Student ID';
            }

            /*
            |--------------------------------------------------------------------------
            | Match
            |--------------------------------------------------------------------------
            */

            if (empty($differences)) {

                $matched++;

                $results[] = [
                    'nameA'      => $nameA,
                    'nameB'      => $nameB,
                    'icA'        => $icA,
                    'icB'        => $icB,
                    'idA'        => $idA,
                    'idB'        => $idB,
                    'status'     => 'Match',
                    'difference' => '',
                    'remarks'    => 'All information matched'
                ];

            } else {

                /*
                |--------------------------------------------------------------------------
                | Modified
                |--------------------------------------------------------------------------
                */

                $modified++;

                $results[] = [
                    'nameA'      => $nameA,
                    'nameB'      => $nameB,
                    'icA'        => $icA,
                    'icB'        => $icB,
                    'idA'        => $idA,
                    'idB'        => $idB,
                    'status'     => 'Modified',
                    'difference' => implode(', ', $differences),
                    'remarks'    => 'Information is different'
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Matching percentage
        |--------------------------------------------------------------------------
        */

        $totalCompared =
            $matched +
            $modified +
            $missingA +
            $missingB;

        $percentage =
            $totalCompared > 0
                ? round(
                    ($matched / $totalCompared) * 100,
                    2
                )
                : 0;

        return [
            'totalA'    => $totalA,
            'totalB'    => $totalB,
            'matched'   => $matched,
            'modified'  => $modified,
            'missingA'  => $missingA,
            'missingB'  => $missingB,
            'duplicate' => $duplicate,
            'invalid'   => $invalid,
            'percentage'=> $percentage,
            'results'   => $results
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Read Excel / CSV
    |--------------------------------------------------------------------------
    */

    private function readFile(string $file): array
    {
        if (!is_file($file)) {
            throw new \RuntimeException(
                'File not found: ' . $file
            );
        }

        $spreadsheet = IOFactory::load($file);

        $sheet = $spreadsheet->getActiveSheet();

        /*
        | Use formatted values.
        |
        | This is important for IC / Student ID because
        | Excel may contain values such as:
        |
        | 010203-10-1234
        | ST001
        | 000123
        |
        */

        $data = $sheet->toArray(
            null,
            true,
            true,
            true
        );

        if (empty($data)) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | First row = headers
        |--------------------------------------------------------------------------
        */

        $headerRow = array_shift($data);

        $headers = [];

        foreach ($headerRow as $column => $header) {

            $normalizedHeader =
                $this->normalizeHeader(
                    (string)$header
                );

            $headers[$column] = $normalizedHeader;
        }

        $rows = [];

        foreach ($data as $row) {

            $item = [];

            foreach ($headers as $column => $header) {

                if ($header === '') {
                    continue;
                }

                $value = $row[$column] ?? '';

                /*
                | Convert value to string safely.
                */

                if (is_float($value)) {

                    /*
                    | Prevent values such as:
                    | 12345.0
                    |
                    | from appearing as:
                    | 12345
                    */

                    $value = rtrim(
                        rtrim(
                            sprintf('%.15f', $value),
                            '0'
                        ),
                        '.'
                    );
                }

                $item[$header] =
                    trim((string)$value);
            }

            /*
            |--------------------------------------------------------------------------
            | Ignore empty rows
            |--------------------------------------------------------------------------
            */

            $hasData = false;

            foreach ($item as $value) {

                if (
                    trim((string)$value) !== ''
                ) {

                    $hasData = true;
                    break;
                }
            }

            if (!$hasData) {
                continue;
            }

            $rows[] = $item;
        }

        return $rows;
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Header
    |--------------------------------------------------------------------------
    |
    | Examples:
    |
    | "Student ID"       -> "student_id"
    | "Student ID No."   -> "student_id_no"
    | "Student No."      -> "student_no"
    | "IC No."           -> "ic_no"
    | "I.C. Number"      -> "ic_number"
    | "NRIC No"          -> "nric_no"
    |
    */

    private function normalizeHeader(string $header): string
    {
        $header = trim($header);

        /*
        | Convert Unicode / special spaces
        */

        $header = preg_replace(
            '/[\x{00A0}\x{2000}-\x{200B}]+/u',
            ' ',
            $header
        ) ?? $header;

        /*
        | Lowercase
        */

        $header = strtolower($header);

        /*
        | Remove dots
        |
        | I.C. -> IC
        */

        $header = str_replace(
            ['.', ',', ':', ';', '/', '\\', '(', ')', '[', ']', '{', '}'],
            ' ',
            $header
        );

        /*
        | Convert hyphen / underscore to space
        */

        $header = str_replace(
            ['-', '_'],
            ' ',
            $header
        );

        /*
        | Remove multiple spaces
        */

        $header = preg_replace(
            '/\s+/',
            ' ',
            $header
        ) ?? $header;

        $header = trim($header);

        /*
        | Convert spaces to underscore
        */

        $header = str_replace(
            ' ',
            '_',
            $header
        );

        return trim(
            $header,
            '_'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Get Student Name
    |--------------------------------------------------------------------------
    */

    private function getName(array $row): string
    {
        $keys = [

            'student_name',
            'studentname',
            'name',
            'full_name',
            'fullname',
            'student_full_name',
            'student_fullname',
            'student',
            'student_name_full',
            'student_fullname_name',

            '姓名',
            '学生姓名',
            '学生名字',
            '名字'
        ];

        foreach ($keys as $key) {

            $key = $this->normalizeHeader($key);

            if (
                isset($row[$key]) &&
                trim((string)$row[$key]) !== ''
            ) {

                return trim(
                    (string)$row[$key]
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Search any header containing name
        |--------------------------------------------------------------------------
        */

        foreach ($row as $key => $value) {

            $normalizedKey =
                $this->normalizeHeader(
                    (string)$key
                );

            if (
                str_contains(
                    $normalizedKey,
                    'name'
                ) &&
                trim((string)$value) !== ''
            ) {

                return trim(
                    (string)$value
                );
            }
        }

        return '';
    }


    /*
    |--------------------------------------------------------------------------
    | Get IC / NRIC
    |--------------------------------------------------------------------------
    */

    private function getIC(array $row): string
    {
        $keys = [

            'ic',
            'ic_no',
            'ic_number',
            'icno',
            'icnumber',

            'nric',
            'nric_no',
            'nric_number',
            'nricno',
            'nricnumber',

            'identity_card',
            'identity_card_no',
            'identity_card_number',

            'identification_number',
            'identification_no',

            'no_ic',
            'ic_no_',

            'kad_pengenalan',
            'no_kad_pengenalan',

            'mykad',
            'mykad_no',
            'mykad_number'
        ];

        foreach ($keys as $key) {

            $key = $this->normalizeHeader($key);

            if (
                isset($row[$key]) &&
                trim((string)$row[$key]) !== ''
            ) {

                return trim(
                    (string)$row[$key]
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback:
        | Look for IC / NRIC / Identity / Kad
        |--------------------------------------------------------------------------
        */

        foreach ($row as $key => $value) {

            $normalizedKey =
                $this->normalizeHeader(
                    (string)$key
                );

            $value = trim((string)$value);

            if ($value === '') {
                continue;
            }

            if (
                str_contains($normalizedKey, 'nric') ||
                str_contains($normalizedKey, 'mykad') ||
                str_contains($normalizedKey, 'identity_card') ||
                str_contains($normalizedKey, 'identification') ||
                str_contains($normalizedKey, 'kad_pengenalan')
            ) {

                return $value;
            }

            /*
            | "ic", "ic_no", "ic_number"
            */

            if (
                preg_match(
                    '/(^|_)ic($|_)/',
                    $normalizedKey
                )
            ) {

                return $value;
            }

            /*
            | Handle:
            | ic_no
            | ic_number
            | no_ic
            */

            if (
                str_contains(
                    $normalizedKey,
                    'ic_no'
                ) ||
                str_contains(
                    $normalizedKey,
                    'ic_number'
                ) ||
                str_contains(
                    $normalizedKey,
                    'no_ic'
                )
            ) {

                return $value;
            }
        }

        return '';
    }


    /*
    |--------------------------------------------------------------------------
    | Get Student ID
    |--------------------------------------------------------------------------
    */

    private function getStudentId(array $row): string
    {
        $keys = [

            'student_id',
            'studentid',

            'student_id_no',
            'student_id_number',

            'student_no',
            'student_number',

            'student_no_',
            'student_number_',

            'id',
            'sid',

            'student_code',
            'student_code_no',

            'matric',
            'matric_no',
            'matric_number',
            'matricno',
            'matricnumber',

            'matric_id',
            'matric_id_no'
        ];

        foreach ($keys as $key) {

            $key = $this->normalizeHeader($key);

            if (
                isset($row[$key]) &&
                trim((string)$row[$key]) !== ''
            ) {

                return trim(
                    (string)$row[$key]
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback
        |--------------------------------------------------------------------------
        */

        foreach ($row as $key => $value) {

            $normalizedKey =
                $this->normalizeHeader(
                    (string)$key
                );

            $value = trim((string)$value);

            if ($value === '') {
                continue;
            }

            /*
            | Student ID
            */

            if (
                str_contains(
                    $normalizedKey,
                    'student_id'
                )
            ) {

                return $value;
            }

            /*
            | Student Number
            */

            if (
                str_contains(
                    $normalizedKey,
                    'student_no'
                )
            ) {

                return $value;
            }

            /*
            | Student Number
            */

            if (
                str_contains(
                    $normalizedKey,
                    'student_number'
                )
            ) {

                return $value;
            }

            /*
            | Matric Number
            */

            if (
                str_contains(
                    $normalizedKey,
                    'matric'
                ) &&
                (
                    str_contains(
                        $normalizedKey,
                        'id'
                    ) ||
                    str_contains(
                        $normalizedKey,
                        'no'
                    ) ||
                    str_contains(
                        $normalizedKey,
                        'number'
                    )
                )
            ) {

                return $value;
            }

            /*
            | SID
            */

            if ($normalizedKey === 'sid') {
                return $value;
            }
        }

        return '';
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Comparison Key
    |--------------------------------------------------------------------------
    */

    private function getCompareKey(
        array $row,
        string $mode
    ): string {

        $mode = strtolower(
            trim($mode)
        );

        /*
        |--------------------------------------------------------------------------
        | Compare by IC
        |--------------------------------------------------------------------------
        */

        if ($mode === 'ic') {

            return $this->normalize(
                $this->getIC($row)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Compare by Name
        |--------------------------------------------------------------------------
        */

        if (
            $mode === 'name' ||
            $mode === 'student_name'
        ) {

            return $this->normalize(
                $this->getName($row)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Default = Student ID
        |--------------------------------------------------------------------------
        */

        $id = $this->getStudentId($row);

        if ($id !== '') {

            return $this->normalize(
                $id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback = IC
        |--------------------------------------------------------------------------
        */

        $ic = $this->getIC($row);

        if ($ic !== '') {

            return $this->normalize(
                $ic
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback = Name
        |--------------------------------------------------------------------------
        */

        return $this->normalize(
            $this->getName($row)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Comparison Value
    |--------------------------------------------------------------------------
    */

    private function normalize(string $value): string
    {
        $value = trim($value);

        /*
        | Remove spaces
        */

        $value = preg_replace(
            '/\s+/',
            '',
            $value
        ) ?? $value;

        /*
        | Remove common IC separators
        |
        | 900101-01-1234
        | becomes
        | 900101011234
        */

        $value = str_replace(
            ['-', '_', '.', '/'],
            '',
            $value
        );

        return strtolower($value);
    }
}

