<?php
declare(strict_types=1); namespace App;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
final class ExcelReader{
 public function headers(string $path,int $headerRow=1):array{
  $r=IOFactory::createReaderForFile($path);$r->setReadDataOnly(true);$r->setReadFilter(new RangeFilter($headerRow,$headerRow));$s=$r->load($path)->getActiveSheet();
  $max=$s->getHighestColumn();$n=Coordinate::columnIndexFromString($max);$out=[];for($c=1;$c<=$n;$c++)$out[]=trim((string)$s->getCell(Coordinate::stringFromColumnIndex($c).$headerRow)->getValue());return $out;
 }
 public function rows(string $path,int $headerRow=1,int $chunk=5000):\Generator{
  $base=IOFactory::createReaderForFile($path);$base->setReadDataOnly(true);
  $probe=$base->load($path);$sheet=$probe->getActiveSheet();$last=$sheet->getHighestDataRow();$lastCol=$sheet->getHighestDataColumn();$probe->disconnectWorksheets();unset($probe);
  for($start=$headerRow+1;$start<=$last;$start+=$chunk){$end=min($last,$start+$chunk-1);$r=IOFactory::createReaderForFile($path);$r->setReadDataOnly(true);$r->setReadFilter(new RangeFilter($start,$end));$wb=$r->load($path);$s=$wb->getActiveSheet();$n=Coordinate::columnIndexFromString($lastCol);
   for($row=$start;$row<=$end;$row++){ $vals=[];$empty=true;for($c=1;$c<=$n;$c++){ $v=$s->getCell(Coordinate::stringFromColumnIndex($c).$row)->getValue();$vals[$c-1]=$v;if($v!==null&&trim((string)$v)!=='')$empty=false;}if(!$empty)yield $row=>$vals; }
   $wb->disconnectWorksheets();unset($wb,$r);
  }
 }
}
final class RangeFilter implements IReadFilter{public function __construct(private int $start,private int $end){}public function readCell(string $column,int $row,string $worksheetName=''):bool{return $row>=$this->start&&$row<=$this->end;}}
