<?php
declare(strict_types=1); namespace App;
use PhpOffice\PhpSpreadsheet\Spreadsheet; use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
final class Exporter{
 private array $headers=['No','Student Name A','Student Name B','IC A','IC B','Student ID A','Student ID B','Status','Difference','Remarks'];
 public function xlsx(array $r):string{$s=new Spreadsheet();$sh=$s->getActiveSheet();foreach($this->headers as $i=>$h)$sh->setCellValueByColumnAndRow($i+1,1,$h);$row=2;foreach($r['results'] as $i=>$x){$v=[$i+1,$x['nameA'],$x['nameB'],$x['icA'],$x['icB'],$x['idA'],$x['idB'],$x['status'],$x['difference'],$x['remarks']];foreach($v as $c=>$z)$sh->setCellValueByColumnAndRow($c+1,$row,$z);$row++;}foreach(range(1,10) as $c)$sh->getColumnDimensionByColumn($c)->setAutoSize(true);$f=EXPORT_DIR.'/comparison_'.date('Ymd_His').'.xlsx';(new Xlsx($s))->save($f);$s->disconnectWorksheets();return $f;}
 public function csv(array $r):string{$f=EXPORT_DIR.'/comparison_'.date('Ymd_His').'.csv';$p=fopen($f,'wb');fputcsv($p,$this->headers);foreach($r['results'] as $i=>$x)fputcsv($p,[$i+1,$x['nameA'],$x['nameB'],$x['icA'],$x['icB'],$x['idA'],$x['idB'],$x['status'],$x['difference'],$x['remarks']]);fclose($p);return $f;}
}
