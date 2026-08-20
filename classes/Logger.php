<?php
declare(strict_types=1); namespace App;
final class Logger{public function error(string $m,array $c=[]):void{file_put_contents(LOG_DIR.'/app.log','['.date('Y-m-d H:i:s').'] '.$m.' '.json_encode($c,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND|LOCK_EX);}}
