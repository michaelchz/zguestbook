<?php
// ver 4.0 2003/09/05-2003/09/13

//require_once("../bin/class_basic_record_file.php");

if (!defined('SP')) { define('SP',"\r"); }
else { (SP == "\r") or die('CBookList: Incompatible constant SP'); }
if (!defined('SP1')){ define('SP1',"\x07"); }
else { (SP1 == "\x07") or die('CBookList: Incompatible constant SP1'); }

class CBookList extends CBasicRecordFile {
 public $name='',$pass='',$email='',$url='',$title='',$urlname='';
 public $regtime='',$htmlt='',$htmlb='',$desc='',$flags='',$_opts=array();

 private function _explodeRecord($a_line){
  $tmp='';
  list($this->name,$this->pass,$this->email,$this->url,$this->title,$this->urlname,
       $this->regtime,$this->htmlt,$this->htmlb,$this->desc,$this->flags,$tmp)
       = explode(SP,$a_line);
  $this->_opts=explode(SP1,$tmp);
 }

 private function _composeRecord($a_line){
  $tmp=implode(SP1,$this->_opts);
  $a_line=$this->name.SP.$this->pass.SP.$this->email.SP.$this->url.SP.$this->title
    .SP.$this->urlname.SP.$this->regtime.SP.$this->htmlt.SP.$this->htmlb.SP
    .$this->desc.SP.$this->flags.SP.$tmp.SP;
 }

 private function _compareRecord($a_key){
  return ($this->name==$a_key);
 }

 public function create(){
  global $filepath;
  return parent::createFile("$filepath/book.lst",256);  
 }

 public function open(){
  global $filepath;
  return parent::open("$filepath/book.lst");  
 }

 public function checkSystem(){
  global $filepath;

  if (!file_exists($filepath)) mkdir("$filepath",0777);
  if (!file_exists("$filepath/book.lst")){
    $this->create();
  }
 }

 public function getOptions(&$opts){
  if ($this->_opts[0] != '') $opts['timesft'] = $this->_opts[0]; # time-zone shift, hours
  if ($this->_opts[1] != '') $opts['perpage'] = $this->_opts[1]; # msg per page
  if ($this->_opts[2] != '') $opts['notify']  = $this->_opts[2]; # email notify, 1-enable; 0-diable
  if ($this->_opts[3] != '') $opts['showdlg'] = $this->_opts[3]; # msg form, 1-enable; 0-diable
  if ($this->_opts[4] != '') $opts['useicon'] = $this->_opts[4]; # avatar, 1-enable; 0-diable
  if ($this->_opts[5] != '') $opts['numicon'] = $this->_opts[5]; # avatar number
  if ($this->_opts[6] != '') $opts['css']   = $this->_opts[6];   # CSS filename
  if ($this->_opts[7] != '') $opts['btn']   = $this->_opts[7];   # button style
 }

 public function setOptions($opts){
  $this->_opts[0] = $opts['timesft'];
  $this->_opts[1] = $opts['perpage'];
  $this->_opts[2] = $opts['notify'];
  $this->_opts[3] = $opts['showdlg'];
  $this->_opts[4] = $opts['useicon'];
  $this->_opts[5] = $opts['numicon'];
  $this->_opts[6] = $opts['css'];
  $this->_opts[7] = $opts['btn'];
 }

}

?>