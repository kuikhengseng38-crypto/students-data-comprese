<?php
declare(strict_types=1); namespace App;
final class MatchingEngine{
 public function __construct(private Normalizer $n){}
 public function value(string $field,mixed $v,string $mode):string{return match($field){'IC Number'=>$this->n->ic($v),'Email'=>$this->n->email($v),'Phone'=>$this->n->phone($v),default=>$mode==='smart'?$this->n->smart($v):$this->n->text($v)};}
 public function key(array $r,array $fields,string $mode):string{$p=[];foreach($fields as $f)$p[]=$this->value($f,$r[$f]??'',$mode);return implode('|',$p);}
 public function same(array $a,array $b,array $fields,string $mode):bool{foreach($fields as $f)if($this->value($f,$a[$f]??'',$mode)!==$this->value($f,$b[$f]??'',$mode))return false;return true;}
}
