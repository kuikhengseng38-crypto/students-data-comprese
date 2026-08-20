<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/*
|--------------------------------------------------------------------------
| Only POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

try {

    verify_csrf(
        $_POST['csrf'] ?? null
    );

} catch (Throwable $e) {

    $_SESSION['error'] = $e->getMessage();

    redirect('index.php');
}

/*
|--------------------------------------------------------------------------
| Mode
|--------------------------------------------------------------------------
*/

$mode = $_POST['mode'] ?? 'smart';

if (!in_array($mode, ['smart', 'exact'], true)) {
    $mode = 'smart';
}

/*
|--------------------------------------------------------------------------
| Check files
|--------------------------------------------------------------------------
*/

if (
    !isset($_FILES['file_a']) ||
    !isset($_FILES['file_b'])
) {

    $_SESSION['error'] =
        'Please upload both File A and File B.';

    redirect('index.php');
}

$fileA = $_FILES['file_a'];
$fileB = $_FILES['file_b'];

/*
|--------------------------------------------------------------------------
| Upload errors
|--------------------------------------------------------------------------
*/

if ($fileA['error'] !== UPLOAD_ERR_OK) {

    $_SESSION['error'] =
        'File A upload failed. Error code: ' .
        $fileA['error'];

    redirect('index.php');
}

if ($fileB['error'] !== UPLOAD_ERR_OK) {

    $_SESSION['error'] =
        'File B upload failed. Error code: ' .
        $fileB['error'];

    redirect('index.php');
}

/*
|--------------------------------------------------------------------------
| File size
|--------------------------------------------------------------------------
*/

$maxSize = 100 * 1024 * 1024;

if ((int)$fileA['size'] > $maxSize) {

    $_SESSION['error'] =
        'File A is larger than 100MB.';

    redirect('index.php');
}

if ((int)$fileB['size'] > $maxSize) {

    $_SESSION['error'] =
        'File B is larger than 100MB.';

    redirect('index.php');
}

/*
|--------------------------------------------------------------------------
| Extension
|--------------------------------------------------------------------------
*/

$allowedExtensions = [
    'xlsx',
    'xls',
    'csv'
];

$extensionA = strtolower(
    pathinfo(
        (string)$fileA['name'],
        PATHINFO_EXTENSION
    )
);

$extensionB = strtolower(
    pathinfo(
        (string)$fileB['name'],
        PATHINFO_EXTENSION
    )
);

if (!in_array($extensionA, $allowedExtensions, true)) {

    $_SESSION['error'] =
        'File A must be XLSX, XLS or CSV.';

    redirect('index.php');
}

if (!in_array($extensionB, $allowedExtensions, true)) {

    $_SESSION['error'] =
        'File B must be XLSX, XLS or CSV.';

    redirect('index.php');
}

/*
|--------------------------------------------------------------------------
| Upload directory
|--------------------------------------------------------------------------
*/

$uploadDir = BASE_PATH . '/storage/uploads';

if (!is_dir($uploadDir)) {

    if (
        !mkdir(
            $uploadDir,
            0775,
            true
        )
        &&
        !is_dir($uploadDir)
    ) {

        $_SESSION['error'] =
            'Unable to create upload directory.';

        redirect('index.php');
    }
}

/*
|--------------------------------------------------------------------------
| Temporary files
|--------------------------------------------------------------------------
*/

$tempA =
    $uploadDir .
    '/' .
    bin2hex(random_bytes(16)) .
    '.' .
    $extensionA;

$tempB =
    $uploadDir .
    '/' .
    bin2hex(random_bytes(16)) .
    '.' .
    $extensionB;

/*
|--------------------------------------------------------------------------
| Move File A
|--------------------------------------------------------------------------
*/

if (
    !move_uploaded_file(
        $fileA['tmp_name'],
        $tempA
    )
) {

    $_SESSION['error'] =
        'Unable to save File A.';

    redirect('index.php');
}

/*
|--------------------------------------------------------------------------
| Move File B
|--------------------------------------------------------------------------
*/

if (
    !move_uploaded_file(
        $fileB['tmp_name'],
        $tempB
    )
) {

    @unlink($tempA);

    $_SESSION['error'] =
        'Unable to save File B.';

    redirect('index.php');
}

/*
|--------------------------------------------------------------------------
| Compare
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | IMPORTANT
    |--------------------------------------------------------------------------
    | Comparator.php must be:
    |
    | classes/Comparator.php
    |
    */

    $comparator = new App\Comparator();

    /*
    |--------------------------------------------------------------------------
    | Compare
    |--------------------------------------------------------------------------
    */

    $result = $comparator->compare(
        $tempA,
        $tempB,
        'student_id'
    );

    /*
    |--------------------------------------------------------------------------
    | Make sure results exists
    |--------------------------------------------------------------------------
    */

    if (!isset($result['results'])) {
        $result['results'] = [];
    }

    /*
    |--------------------------------------------------------------------------
    | Make sure every row contains:
    |
    | nameA
    | nameB
    | icA
    | icB
    | idA
    | idB
    | status
    | difference
    | remarks
    |--------------------------------------------------------------------------
    */

    foreach ($result['results'] as &$row) {

        $row['nameA'] =
            $row['nameA']
            ?? $row['name_a']
            ?? '';

        $row['nameB'] =
            $row['nameB']
            ?? $row['name_b']
            ?? '';

        $row['icA'] =
            $row['icA']
            ?? $row['ic_a']
            ?? '';

        $row['icB'] =
            $row['icB']
            ?? $row['ic_b']
            ?? '';

        $row['idA'] =
            $row['idA']
            ?? $row['id_a']
            ?? '';

        $row['idB'] =
            $row['idB']
            ?? $row['id_b']
            ?? '';

        $row['status'] =
            $row['status']
            ?? 'Invalid';

        $row['difference'] =
            $row['difference']
            ?? '';

        $row['remarks'] =
            $row['remarks']
            ?? '';
    }

    unset($row);

    /*
    |--------------------------------------------------------------------------
    | SAVE RESULT TO SESSION
    |--------------------------------------------------------------------------
    */

    $_SESSION['result'] = $result;

    /*
    |--------------------------------------------------------------------------
    | SAVE META
    |--------------------------------------------------------------------------
    */

    $_SESSION['meta'] = [

        'file_a' =>
            (string)$fileA['name'],

        'file_b' =>
            (string)$fileB['name'],

        'mode' =>
            $mode,

        'created_at' =>
            date('Y-m-d H:i:s')
    ];

    /*
    |--------------------------------------------------------------------------
    | Remove temporary files
    |--------------------------------------------------------------------------
    */

    @unlink($tempA);
    @unlink($tempB);

    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    |
    | 这里就是你要的地方。
    |
    | 成功后：
    |
    | upload.php
    |       ↓
    | result.php
    |
    |--------------------------------------------------------------------------
    */

    redirect('result.php');

} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Remove temp files
    |--------------------------------------------------------------------------
    */

    @unlink($tempA);
    @unlink($tempB);

    /*
    |--------------------------------------------------------------------------
    | Show real error
    |--------------------------------------------------------------------------
    |
    | 暂时不要 redirect。
    | 这样如果 Comparator 有问题，
    | 你可以直接看到真正的错误。
    |
    |--------------------------------------------------------------------------
    */

    http_response_code(500);

    echo '<!doctype html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<title>Comparison Error</title>';
    echo '<style>';
    echo 'body{font-family:Arial;padding:40px;background:#f5f5f5;}';
    echo '.box{background:white;padding:25px;border-radius:10px;max-width:900px;margin:auto;}';
    echo '.error{color:#b00020;background:#ffecec;padding:15px;border-radius:6px;}';
    echo 'a{display:inline-block;margin-top:20px;}';
    echo '</style>';
    echo '</head>';
    echo '<body>';

    echo '<div class="box">';

    echo '<h2>Comparison Failed</h2>';

    echo '<div class="error">';
    echo htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );
    echo '</div>';

    echo '<a href="index.php">← Back to Upload</a>';

    echo '</div>';

    echo '</body>';
    echo '</html>';

    exit;
}

