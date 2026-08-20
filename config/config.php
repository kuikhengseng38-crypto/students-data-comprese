<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH.'/uploads');
define('EXPORT_DIR', BASE_PATH.'/exports');
define('HISTORY_DIR', BASE_PATH.'/data/history');
define('LOG_DIR', BASE_PATH.'/logs');
define('SETTINGS_FILE', BASE_PATH.'/data/settings.json');
define('MAX_UPLOAD_BYTES', 100 * 1024 * 1024);
date_default_timezone_set('Asia/Kuala_Lumpur');
foreach ([UPLOAD_DIR,EXPORT_DIR,HISTORY_DIR,LOG_DIR] as $d) if (!is_dir($d)) mkdir($d,0750,true);
