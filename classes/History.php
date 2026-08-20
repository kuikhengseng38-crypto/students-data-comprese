<?php
declare(strict_types=1); namespace App;
final class History{
 public function save(array $d):string{$h=hash('sha256',json_encode($d,JSON_UNESCAPED_UNICODE).microtime(true));$d['hash']=$h;$d['created_at']=date('c');file_put_contents(HISTORY_DIR.'/'.$h.'.json',json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),LOCK_EX);return $h;}
 public function all():array{$o=[];foreach(glob(HISTORY_DIR.'/*.json')?:[] as $f){$d=json_decode((string)file_get_contents($f),true);if(is_array($d))$o[]=$d;}usort($o,fn($a,$b)=>strcmp($b['created_at']??'',$a['created_at']??''));return $o;}
}
