<?php
declare(strict_types=1);
session_start();
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/vendor/autoload.php';
spl_autoload_register(function(string $class): void {
    $prefix='App\\'; if (strncmp($class,$prefix,strlen($prefix))!==0) return;
    $file=BASE_PATH.'/classes/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_file($file)) require_once $file;
});
if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
function csrf_token(): string{return $_SESSION['csrf'];}
function verify_csrf(?string $t): void{if(!$t||!hash_equals($_SESSION['csrf']??'',$t)) throw new RuntimeException('Invalid security token. Refresh and try again.');}
function e(mixed $v): string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function redirect(string $p): never{header('Location: '.$p);exit;}
