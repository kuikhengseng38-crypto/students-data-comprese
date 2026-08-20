<?php
declare(strict_types=1); namespace App;
final class Normalizer{
 public function text(mixed $v):string{$s=trim((string)$v);$s=preg_replace('/\s+/u',' ',$s)??$s;return mb_strtolower($s,'UTF-8');}
 public function smart(mixed $v):string{$s=$this->text($v);return preg_replace('/[\p{P}\p{S}\s]+/u','',$s)??$s;}
 public function ic(mixed $v):string{return preg_replace('/\D+/u','',(string)$v)??'';}
 public function email(mixed $v):string{return $this->text($v);}
 public function phone(mixed $v):string{return preg_replace('/\D+/u','',(string)$v)??'';}
}
