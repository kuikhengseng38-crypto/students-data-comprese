<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$r = $_SESSION['result'] ?? null;
$m = $_SESSION['meta'] ?? [];

if (!$r) {
    redirect('index.php');
}


function getValue(array $data, array $keys, string $default = ''): string
{
    foreach ($keys as $key) {

        if (
            array_key_exists($key, $data) &&
            $data[$key] !== null &&
            $data[$key] !== ''
        ) {
            return (string)$data[$key];
        }

    }

    return $default;
}

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Comparison Results</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- DataTables -->

    <link
        href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css"
        rel="stylesheet"
    >


    <!-- App CSS -->

    <link
        href="assets/css/app.css"
        rel="stylesheet"
    >


    <!-- Chart.js -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>


    <!-- jQuery -->

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>


    <!-- DataTables JS -->

    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>

    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">

    <div class="brand">
        Student Compare
    </div>


    <a
        class="active"
        href="index.php"
    >
        Compare
    </a>


    <a href="history.php">
        History
    </a>


    <a href="settings.php">
        Settings
    </a>

</aside>



<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main class="content">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="d-flex flex-wrap justify-content-between gap-2 mb-4">


        <div>

            <h2>
                Comparison Results
            </h2>


            <small class="text-secondary">

                <?= e($m['file_a'] ?? '') ?>

                vs

                <?= e($m['file_b'] ?? '') ?>

            </small>

        </div>



        <div>


            <a
                class="btn btn-success"
                href="export.php?type=xlsx"
            >
                Excel
            </a>


            <a
                class="btn btn-outline-secondary"
                href="export.php?type=csv"
            >
                CSV
            </a>


            <button
                type="button"
                class="btn btn-outline-dark"
                onclick="window.print()"
            >
                Print
            </button>


            <a
                class="btn btn-primary"
                href="index.php"
            >
                New
            </a>

        </div>

    </div>



    <!-- =====================================================
         SUMMARY
    ====================================================== -->

    <div class="row g-3 mb-4">


        <?php

        $metrics = [

            [
                'Total A',
                $r['totalA'] ?? 0,
                'primary'
            ],

            [
                'Total B',
                $r['totalB'] ?? 0,
                'secondary'
            ],

            [
                'Matched',
                $r['matched'] ?? 0,
                'success'
            ],

            [
                'Modified',
                $r['modified'] ?? 0,
                'warning'
            ],

            [
                'Missing A',
                $r['missingA'] ?? 0,
                'danger'
            ],

            [
                'Missing B',
                $r['missingB'] ?? 0,
                'danger'
            ],

            [
                'Duplicate',
                $r['duplicate'] ?? 0,
                'info'
            ],

            [
                'Invalid',
                $r['invalid'] ?? 0,
                'dark'
            ]

        ];


        foreach ($metrics as $x):

        ?>


        <div class="col-6 col-md-3">

            <div
                class="metric border-start border-4 border-<?= e($x[2]) ?>"
            >

                <small>
                    <?= e($x[0]) ?>
                </small>


                <h3>
                    <?= e($x[1]) ?>
                </h3>

            </div>

        </div>


        <?php endforeach; ?>


    </div>



    <!-- =====================================================
         CHARTS
    ====================================================== -->

    <div class="row g-4 mb-4">


        <!-- Matching Percentage -->

        <div class="col-lg-5">

            <div class="card border-0 shadow-sm">

                <div class="card-body">


                    <h5>
                        Matching Percentage
                    </h5>


                    <canvas id="pie"></canvas>


                    <h2 class="text-center mt-2">

                        <?= e($r['percentage'] ?? 0) ?>%

                    </h2>


                </div>

            </div>

        </div>



        <!-- Status -->

        <div class="col-lg-7">

            <div class="card border-0 shadow-sm">

                <div class="card-body">


                    <h5>
                        Status
                    </h5>


                    <canvas id="bar"></canvas>


                </div>

            </div>

        </div>


    </div>



    <!-- =====================================================
         RESULTS TABLE
    ====================================================== -->

    <div class="card border-0 shadow-sm">


        <div class="card-body table-responsive">


            <table
                id="results"
                class="table table-hover"
            >


                <thead>


                    <tr>

                        <th>No</th>

                        <th>
                            Student Name A
                        </th>

                        <th>
                            Student Name B
                        </th>

                        <th>
                            IC A
                        </th>

                        <th>
                            IC B
                        </th>

                        <th>
                            Student ID A
                        </th>

                        <th>
                            Student ID B
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Difference
                        </th>

                        <th>
                            Remarks
                        </th>

                    </tr>


                </thead>



                <tbody>


                <?php

                $results = $r['results'] ?? [];

                ?>


                <?php foreach ($results as $i => $x): ?>


                    <?php

                    /*
                    |--------------------------------------------------------------------------
                    | STUDENT NAME A
                    |--------------------------------------------------------------------------
                    |
                    | Check multiple possible field names.
                    |
                    */

                    $nameA = getValue(
                        $x,
                        [
                            'nameA',
                            'name_a',
                            'student_name_a',
                            'studentNameA',
                            'student_name',
                            'studentName',
                            'name'
                        ]
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | STUDENT NAME B
                    |--------------------------------------------------------------------------
                    */

                    $nameB = getValue(
                        $x,
                        [
                            'nameB',
                            'name_b',
                            'student_name_b',
                            'studentNameB',
                            'student_name_2',
                            'name2'
                        ]
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | IC A
                    |--------------------------------------------------------------------------
                    */

                    $icA = getValue(
                        $x,
                        [
                            'icA',
                            'ic_a',
                            'student_ic_a',
                            'studentIcA',
                            'ic'
                        ]
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | IC B
                    |--------------------------------------------------------------------------
                    */

                    $icB = getValue(
                        $x,
                        [
                            'icB',
                            'ic_b',
                            'student_ic_b',
                            'studentIcB'
                        ]
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | STUDENT ID A
                    |--------------------------------------------------------------------------
                    */

                    $idA = getValue(
                        $x,
                        [
                            'idA',
                            'id_a',
                            'student_id_a',
                            'studentIdA',
                            'studentID_A',
                            'id'
                        ]
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | STUDENT ID B
                    |--------------------------------------------------------------------------
                    */

                    $idB = getValue(
                        $x,
                        [
                            'idB',
                            'id_b',
                            'student_id_b',
                            'studentIdB',
                            'studentID_B'
                        ]
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | STATUS
                    |--------------------------------------------------------------------------
                    */

                    $status = getValue(
                        $x,
                        [
                            'status'
                        ]
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | DIFFERENCE
                    |--------------------------------------------------------------------------
                    */

                    $difference = getValue(
                        $x,
                        [
                            'difference',
                            'diff'
                        ]
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | REMARKS
                    |--------------------------------------------------------------------------
                    */

                    $remarks = getValue(
                        $x,
                        [
                            'remarks',
                            'remark'
                        ]
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | STATUS CLASS
                    |--------------------------------------------------------------------------
                    */

                    if ($status === 'Match') {

                        $cls = 'match';

                    } elseif ($status === 'Modified') {

                        $cls = 'modified';

                    } else {

                        $cls = 'missing';

                    }


                    ?>


                    <tr class="<?= e($cls) ?>">


                        <!-- No -->

                        <td>
                            <?= $i + 1 ?>
                        </td>


                        <!-- =================================================
                             NAME A
                        ================================================== -->

                        <td>

                            <?php if ($nameA !== ''): ?>

                                <?= e($nameA) ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    -
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- =================================================
                             NAME B
                        ================================================== -->

                        <td>

                            <?php if ($nameB !== ''): ?>

                                <?= e($nameB) ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    -
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- IC A -->

                        <td>
                            <?= e($icA) ?>
                        </td>


                        <!-- IC B -->

                        <td>
                            <?= e($icB) ?>
                        </td>


                        <!-- STUDENT ID A -->

                        <td>
                            <?= e($idA) ?>
                        </td>


                        <!-- STUDENT ID B -->

                        <td>
                            <?= e($idB) ?>
                        </td>


                        <!-- STATUS -->

                        <td>
                            <?= e($status) ?>
                        </td>


                        <!-- DIFFERENCE -->

                        <td>
                            <?= e($difference) ?>
                        </td>


                        <!-- REMARKS -->

                        <td>
                            <?= e($remarks) ?>
                        </td>


                    </tr>


                <?php endforeach; ?>


                </tbody>


            </table>


        </div>


    </div>


</main>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>


/*
|--------------------------------------------------------------------------
| DataTable
|--------------------------------------------------------------------------
*/

new DataTable(
    '#results',
    {
        pageLength: 25
    }
);



/*
|--------------------------------------------------------------------------
| Doughnut Chart
|--------------------------------------------------------------------------
*/

const pieCanvas = document.getElementById('pie');


if (pieCanvas) {

    new Chart(
        pieCanvas,
        {

            type: 'doughnut',

            data: {

                labels: [
                    'Matched',
                    'Modified',
                    'Missing'
                ],


                datasets: [

                    {

                        data: [

                            <?= (int)($r['matched'] ?? 0) ?>,

                            <?= (int)($r['modified'] ?? 0) ?>,

                            <?= (int)(
                                ($r['missingA'] ?? 0)
                                +
                                ($r['missingB'] ?? 0)
                            ) ?>

                        ]

                    }

                ]

            }

        }
    );

}



/*
|--------------------------------------------------------------------------
| Bar Chart
|--------------------------------------------------------------------------
*/

const barCanvas = document.getElementById('bar');


if (barCanvas) {

    new Chart(
        barCanvas,
        {

            type: 'bar',

            data: {

                labels: [

                    'Matched',

                    'Modified',

                    'Missing A',

                    'Missing B',

                    'Duplicate',

                    'Invalid'

                ],


                datasets: [

                    {

                        label: 'Records',

                        data: [

                            <?= (int)($r['matched'] ?? 0) ?>,

                            <?= (int)($r['modified'] ?? 0) ?>,

                            <?= (int)($r['missingA'] ?? 0) ?>,

                            <?= (int)($r['missingB'] ?? 0) ?>,

                            <?= (int)($r['duplicate'] ?? 0) ?>,

                            <?= (int)($r['invalid'] ?? 0) ?>

                        ]

                    }

                ]

            }

        }
    );

}

</script>


</body>

</html>
```
