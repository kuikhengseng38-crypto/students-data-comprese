<?php
declare(strict_types=1); namespace App;
final class FileValidator{
 public function validate(array $f):string{
  $err=(int)($f['error']??UPLOAD_ERR_NO_FILE); if($err!==UPLOAD_ERR_OK) throw new \RuntimeException($this->err($err));
  if((int)$f['size']<=0||(int)$f['size']>MAX_UPLOAD_BYTES) throw new \RuntimeException('Each file must be larger than 0 bytes and no more than 100MB.');
  $ext=strtolower(pathinfo((string)$f['name'],PATHINFO_EXTENSION)); if(!in_array($ext,['xlsx','xls','csv'],true)) throw new \RuntimeException('Only XLSX, XLS and CSV files are supported.');
  if(!is_uploaded_file((string)$f['tmp_name'])) throw new \RuntimeException('Invalid upload.');
  return $ext;
 }
 private function err(int $c):string{return match($c){UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE=>'File exceeds the server upload limit.',UPLOAD_ERR_PARTIAL=>'Upload was incomplete.',UPLOAD_ERR_NO_FILE=>'Please select a file.',default=>'Upload failed.'};}
}
