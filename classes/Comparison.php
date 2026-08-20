<?php
declare(strict_types=1); namespace App;
final class Comparison{
 public function __construct(private ExcelReader $reader,private MatchingEngine $matcher,private DuplicateDetector $dupes,private Normalizer $normalizer){}
 public function run(string $a,string $b,array $mapA,array $mapB,array $fields,string $mode,int $headerA=1,int $headerB=1):array{
  $ra=$this->load($a,$mapA,$headerA);$rb=$this->load($b,$mapB,$headerB);$idx=[];foreach($rb as $r){$k=$this->matcher->key($r,$fields,$mode);if($k!=='')$idx[$k][]=$r;}
  $used=[];$rows=[];$matched=0;$modified=0;$missingB=0;$missingA=0;
  foreach($ra as $x){$k=$this->matcher->key($x,$fields,$mode);$y=null;foreach($idx[$k]??[] as $c){if(!isset($used[$c['_row']])){$y=$c;break;}}
   if($y){$used[$y['_row']]=true;$same=$this->matcher->same($x,$y,$fields,$mode);$same?$matched++:$modified++;$rows[]=$this->row($x,$y,$same?'Match':'Modified',$same?'':$this->diff($x,$y,$fields));}
   else{$missingB++;$rows[]=$this->row($x,null,'Missing in B','No matching record in File B');}
  }
  foreach($rb as $y)if(!isset($used[$y['_row']])){$missingA++;$rows[]=$this->row(null,$y,'Missing in A','No matching record in File A');}
  $dupA=$this->dupes->detect($ra,$fields,$mode);$dupB=$this->dupes->detect($rb,$fields,$mode);$invalidA=$this->invalid($ra);$invalidB=$this->invalid($rb);$totalA=count($ra);$totalB=count($rb);
  return ['totalA'=>$totalA,'totalB'=>$totalB,'matched'=>$matched,'modified'=>$modified,'missingA'=>$missingA,'missingB'=>$missingB,'duplicate'=>count($dupA)+count($dupB),'invalid'=>count($invalidA)+count($invalidB),'percentage'=>$totalA?round($matched/$totalA*100,2):0,'duplicatesA'=>$dupA,'duplicatesB'=>$dupB,'invalidA'=>$invalidA,'invalidB'=>$invalidB,'results'=>$rows];
 }
 private function load(string $file,array $map,int $header):array{$out=[];foreach($this->reader->rows($file,$header) as $rn=>$row){$r=['_row'=>$rn];foreach($map as $f=>$i)$r[$f]=trim((string)($row[$i]??''));$out[]=$r;}return $out;}
 private function invalid(array $rows):array{$o=[];foreach($rows as $r)if(($r['IC Number']??'')!==''){ $d=$this->normalizer->ic($r['IC Number']);if(strlen($d)!==12)$o[]=['row'=>$r['_row'],'value'=>$r['IC Number'],'reason'=>'Invalid IC number'];}return $o;}
 private function diff(array $a,array $b,array $fields):string{$d=[];foreach($fields as $f)if(trim((string)($a[$f]??''))!==trim((string)($b[$f]??'')))$d[]=$f;return implode(', ',$d)?:'Modified record';}
 private function row(?array $a,?array $b,string $status,string $diff):array{return ['nameA'=>$a['Student Name']??'','nameB'=>$b['Student Name']??'','icA'=>$a['IC Number']??'','icB'=>$b['IC Number']??'','idA'=>$a['Student ID']??'','idB'=>$b['Student ID']??'','courseA'=>$a['Course']??'','courseB'=>$b['Course']??'','status'=>$status,'difference'=>$diff,'remarks'=>$status==='Match'?'All selected fields match.':$diff];}
}
