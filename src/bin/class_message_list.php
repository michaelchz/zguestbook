<?php
// ver 4.0 2002/08/09,2002/12/31,2003/09/03-2003/09/13

//require_once("../bin/class_basic_record_file.php");

if (!defined('SP')) { define('SP',"\r"); }
else { (SP == "\r") or die('CMessageList: Incompatible constant SP'); }

class CMessageList extends CBasicRecordFile {
 var $msgid,$searchmode=false;
 var $user,$email,$url,$comment,$ip,$time,$secret,$reply,$replysecret,$icon,$replytime;

 function _explodeRecord($a_line){
  if($this->searchmode){return;}
  list($this->user,$this->email,$this->url,$this->comment,$this->ip,
       $this->time,$this->secret,$this->reply,$this->replysecret,$this->icon,$this->replytime)
       = explode(SP,rtrim($a_line));
  $this->msgid=$this->getAbsolutePosition();
 }

 function _composeRecord(&$a_line){
  $a_line=$this->user.SP.$this->email.SP.$this->url.SP.$this->comment.SP.$this->ip
    .SP.$this->time.SP.$this->secret.SP.$this->reply.SP.$this->replysecret.SP.$this->icon
	.SP.$this->replytime.SP;
 }

 function _compareRecord($a_key){return false;}

 function create($a_bname){
  global $filepath;
  if(file_exists("$filepath/$a_bname.bok")){
   return false;
  } elseif (file_exists("$filepath/$a_bname.bak")){
  } else {
   return parent::createFile("$filepath/$a_bname.bok",512);
  }
 }

 function destroy($a_bname){
  global $filepath;
  if(file_exists("$filepath/$a_bname.bok")){  
   return rename("$filepath/$a_bname.bok", "$filepath/$a_bname.bak");
  }else{
   return false;
  }
 }

 function open($a_bname){
  global $filepath;
  if(parent::open("$filepath/$a_bname.bok")){  
   return true;
  }else{
   return false;
  }
 }

 function search($a_keyword) {
  $this->searchmode=true;
  $tmparr=array();

  $flag=$this->moveLast();
  while ($flag){
   if(strstr($this->recordBuffer,$a_keyword)){
    $tmparr[]=$this->getAbsolutePosition();
   }
   $flag=$this->movePrev();
  }

  $this->searchmode=false;
  return $tmparr;
 }

}

?>