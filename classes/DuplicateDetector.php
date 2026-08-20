<?php
declare(strict_types=1); namespace App;
final class DuplicateDetector{
 public function __construct(private MatchingEngine $m){}
 public function detect(array $records,array $fields,string $mode):array{$seen=[];$out=[];foreach($records as $r){$k=$this->m->key($r,$fields,$mode);if($k==='')continue;if(isset($seen[$k]))$out[]=['row'=>$r['_row'],'value'=>$k,'first_row'=>$seen[$k]];else$seen[$k]=$r['_row'];}return $out;}
}
