<?php
/*--------------------------------------------------------*\
 零点留言簿多用户版 zChain GuestBook v3.50
 
 作者：zChain (http://www.zchain.net)
 版权所有(c) 2001-2003

 本程序为自由软件，您可以在 GNU通用公共授权条款规定下自由
 修改、使用与散播本程序，但必须保留作者与网站的链接。详情
 请参见 readme.txt 。
\*--------------------------------------------------------*/
// ver 1.0 2002/08/09,2002/12/31
define('NULLPTR',0);
define('PTR_SIZE',8);
define('PTRFIELD_SIZE',PTR_SIZE*3);
define('HEADER_SIZE',PTR_SIZE*8);
// PtrSeg: ptrNextSeg(8),[ptrPrev(8),ptrNext(8)]
// Header: magic(4),recSize(8),recNum(8),segNum(8),ptrHead(8),ptrTail(8),ptrFree(8),resv(12)

class CBasicRecordFile {
 private $_fp=NULLPTR, $_magic, $_recSize, $_recNum, $_segNum, $_ptrHead, $_ptrTail, $_ptrFree;
 private $_curId=NULLPTR, $_ptrPrev=NULLPTR, $_ptrNext=NULLPTR;
 public $recordBuffer;

 function _explodeRecord($a_line){}
 function _composeRecord($a_line){}
 function _compareRecord($a_key){/*return true/false*/}

 function _readHeader(){
  fseek($this->_fp, 0); $buf=fread($this->_fp, HEADER_SIZE);
  list($this->_magic, $this->_recSize, $this->_recNum, $this->_segNum,
    $this->_ptrHead, $this->_ptrTail, $this->_ptrFree)=explode('|',$buf);
 }

 function _writeHeader(){
  $buf='ZC10|'.$this->_recSize.'|'.$this->_recNum.'|'.$this->_segNum
    .'|'.$this->_ptrHead.'|'.$this->_ptrTail.'|'.$this->_ptrFree.'|';
  fseek($this->_fp, 0); fwrite($this->_fp, str_pad($buf,HEADER_SIZE), HEADER_SIZE);
 }

 function _readPointer($a_recId, $a_offset, &$a_ptrVal){
  fseek($this->_fp, $a_recId*$this->_recSize+HEADER_SIZE+$a_offset*PTR_SIZE);
  $a_ptrVal=fread($this->_fp, PTR_SIZE);
 }

 function _writePointer($a_recId, $a_offset, $a_ptrVal){
  fseek($this->_fp, $a_recId*$this->_recSize+HEADER_SIZE+$a_offset*PTR_SIZE);
  fwrite($this->_fp, str_pad($a_ptrVal,PTR_SIZE,' ',STR_PAD_LEFT), PTR_SIZE);
 }

 function _allocSegment(){
  if($this->_ptrFree > NULLPTR){
   $ptrNew=$this->_ptrFree;
   $this->_readPointer($ptrNew, 0, &$this->_ptrFree);
  }else{
   $ptrNew=$this->_segNum+1;
   $this->_writePointer($ptrNew, 0, NULLPTR);
   fwrite($this->_fp, str_repeat(' ',$this->_recSize));
  }
  $this->_segNum++;
  return $ptrNew;
 }

 function _allocSegmentList($a_size){
  $size=$this->_recSize - PTR_SIZE;
  $rest=$a_size; $recId=NULLPTR;
  while($rest > 0) {
   $segId=$this->_allocSegment();
   $this->_writePointer($segId, 0, $recId);
   $recId=$segId; $rest-=$size;
  }
  return $recId;
 }

 function _reallocSegmentList($a_idx, $a_size){
  $size=$this->_recSize - PTR_SIZE;
  $ptrSeg=$a_idx; $rest=$a_size;
  do{
   $lastSeg=$ptrSeg; $rest-=$size;
   $this->_readPointer($lastSeg, 0, &$ptrSeg);
  }while($rest > 0 && $ptrSeg > NULLPTR);
  if($rest>0){
   $ptrSeg=$this->_allocSegmentList($rest);
   $this->_writePointer($lastSeg, 0, $ptrSeg);
  }elseif($ptrSeg > NULLPTR){
   $this->_freeSegmentList($ptrSeg);
   $this->_writePointer($lastSeg, 0, NULLPTR);
  }
  return $a_idx;
 }

 function _freeSegmentList($a_idx){
  $ptrSeg=$a_idx;
  do {
   $lastSeg=$ptrSeg; $this->_segNum--;
   $this->_readPointer($lastSeg, 0, &$ptrSeg);
  }while($ptrSeg > NULLPTR);
  $this->_writePointer($lastSeg, 0, $this->_ptrFree);
  $this->_ptrFree=$a_idx;
 }

 function _readRecordPointer($a_recId, &$a_ptrPrev, &$a_ptrNext){
  fseek($this->_fp, $a_recId * $this->_recSize + HEADER_SIZE);
  $buf=fread($this->_fp, PTRFIELD_SIZE);
  $a_ptrPrev=substr($buf,PTR_SIZE,PTR_SIZE);
  $a_ptrNext=substr($buf,PTR_SIZE*2);
 }

 function _readRecord($a_recId, &$a_ptrPrev, &$a_ptrNext, &$a_buf){
  $buf=""; $ptrSeg=$a_recId;
  do {
   fseek($this->_fp, $ptrSeg * $this->_recSize + HEADER_SIZE);
   $segBuf=fread($this->_fp, $this->_recSize);
   $ptrSeg=substr($segBuf,0,PTR_SIZE);
   $buf.=substr($segBuf,PTR_SIZE);
  } while ($ptrSeg > NULLPTR);
  if($buf[PTR_SIZE*2]=="\x07"){
   $a_ptrPrev=substr($buf,0,PTR_SIZE);
   $a_ptrNext=substr($buf,PTR_SIZE,PTR_SIZE);
   $a_buf=substr($buf,PTR_SIZE*2+1);
   return true;
  }else{
   return false;
  }
 }

 function _writeRecord($a_recId, $a_ptrPrev, $a_ptrNext, $a_buf){
  $buf=str_pad($a_ptrPrev,PTR_SIZE,' ',STR_PAD_LEFT)
    .str_pad($a_ptrNext,PTR_SIZE,' ',STR_PAD_LEFT)."\x07".$a_buf;
  $this->_reallocSegmentList($a_recId, strlen($buf));
  $ptrSeg=$a_recId;
  do{
   $tmpbuf=substr($buf,0,$this->_recSize-PTR_SIZE);
   $buf=substr($buf,$this->_recSize-PTR_SIZE);
   if(!$buf){$tmpbuf=str_pad($tmpbuf,$this->_recSize-PTR_SIZE);}
   fseek($this->_fp, $ptrSeg * $this->_recSize + HEADER_SIZE + PTR_SIZE);
   fwrite($this->_fp, $tmpbuf, $this->_recSize-PTR_SIZE);
   $this->_readPointer($ptrSeg, 0, $ptrSeg);
  }while ($buf && $ptrSeg > NULLPTR);
 }

 function _destroyRecord($a_recId){
  $buf=str_pad(NULLPTR,PTR_SIZE).str_pad(NULLPTR,PTR_SIZE)."\x08";
  fseek($this->_fp, $a_recId * $this->_recSize + HEADER_SIZE + PTR_SIZE);
  fwrite($this->_fp, $buf, $this->_recSize-PTR_SIZE);
 }

 function _validateRecord($a_recId){
  if($a_recId <= NULLPTR){return false;}
  fseek($this->_fp, $a_recId*$this->_recSize + HEADER_SIZE + PTRFIELD_SIZE);
  $flag=fread($this->_fp, 1);
  return ($flag == "\x07");
 }

 function readMemo(){
  fseek($this->_fp, HEADER_SIZE);
  return fread($this->_fp, $this->_recSize);
 }

 function writeMemo($a_buf){
  fseek($this->_fp, HEADER_SIZE);
  return fwrite($this->_fp, 
    str_pad($a_buf,$this->_recSize), $this->_recSize);
 }

 function open($a_filename){
  if(!($this->_fp=fopen($a_filename, "r+b"))){return false;}
  $this->_readHeader();
  return ($this->_magic=='ZC10');
 }

 function close(){
  fclose($this->_fp);
 }

 function getRecordCount() {return (integer)$this->_recNum;}

 function getAbsolutePosition() {return (integer)$this->_curId;}

 function setAbsolutePosition($a_idx) {
  if(!$this->_validateRecord($a_idx)){return false;}
  if($this->_readRecord($a_idx, $this->_ptrPrev, $this->_ptrNext, $this->recordBuffer)){
   $this->_curId=$a_idx;
   $this->_explodeRecord($this->recordBuffer);
   return true;
  }else{
   return false;
  }
 }

 function moveTo($a_offset, $a_forward=true) {
  $tmpIdx=($a_forward) ? $this->_ptrHead : $this->_ptrTail;
  $count=0;
  while(($count<$a_offset)&&($tmpIdx>NULLPTR)){
   $this->_readRecordPointer($tmpIdx,$tmpPrev,$tmpNext);
   $tmpIdx=($a_forward) ? $tmpNext : $tmpPrev;
   $count++;
  }
  return $this->setAbsolutePosition($tmpIdx);
 }

 function moveFirst(){return $this->setAbsolutePosition($this->_ptrHead);}
 function moveLast(){return $this->setAbsolutePosition($this->_ptrTail);}
 function moveNext(){return $this->setAbsolutePosition($this->_ptrNext);}
 function movePrev(){return $this->setAbsolutePosition($this->_ptrPrev);}

 function find($a_key) {
  $flag=$this->moveFirst();
  while ($flag){
   if($this->_compareRecord($a_key)){return true;}
   $flag=$this->moveNext();
  }
  return false;
 }

 function update() {
  flock($this->_fp, 2);
  $this->_readHeader();
  if(!$this->_validateRecord($this->_curId)){
   flock($this->_fp, 3); return false;
  }

  $this->_composeRecord($this->recordBuffer);
  $this->_writeRecord($this->_curId, $this->_ptrPrev, $this->_ptrNext, $this->recordBuffer);
  $this->_writeHeader();

  fflush($this->_fp);
  flock($this->_fp, 3);
  return true;
 }

 function appendNew() {
  flock($this->_fp, 2);
  $this->_readHeader();

  $ptrNew=$this->_allocSegmentList(1);
  if($this->_ptrTail > NULLPTR){
   $this->_ptrPrev=$this->_ptrTail;
   $this->_writePointer($this->_ptrTail, 2, $ptrNew);
   $this->_ptrTail=$ptrNew;
  }else{
   $this->_ptrPrev= NULLPTR;
   $this->_ptrTail=$this->_ptrHead=$ptrNew;
  }
  $this->_ptrNext= NULLPTR;
  $this->_curId=$ptrNew;
  $this->_writeRecord($ptrNew, $this->_ptrPrev, $this->_ptrNext, "XXXXXXXXXXXXXYYY");
  $this->_recNum++;
  $this->_writeHeader();

  fflush($this->_fp);
  flock($this->_fp, 3);
  return $this->_curId;
 }

 function delete() {
  flock($this->_fp, 2);
  $this->_readHeader();
  if(!$this->_validateRecord($this->_curId)){
   flock($this->_fp, 3); return false;
  }

  if($this->_ptrPrev > NULLPTR){
   $this->_writePointer($this->_ptrPrev, 2, $this->_ptrNext);
  }else{
   $this->_ptrHead=$this->_ptrNext;
  }
  if($this->_ptrNext > NULLPTR){
   $this->_writePointer($this->_ptrNext, 1, $this->_ptrPrev);
  }else{
   $this->_ptrTail=$this->_ptrPrev;
  }
  $this->_destroyRecord($this->_curId);
  $this->_freeSegmentList($this->_curId);
  $this->_recNum--;
  $this->_writeHeader();

  fflush($this->_fp);
  flock($this->_fp, 3);
  return true;
 }

 function createFile($a_filename, $a_recsize){
  $this->_fp=fopen($a_filename,"wb");
  $this->_recSize=($a_recsize>PTRFIELD_SIZE) ? $a_recsize : PTRFIELD_SIZE+1;
  $this->_recNum=$this->_segNum=0;
  $this->_ptrHead=$this->_ptrTail=$this->_ptrFree=NULLPTR;
  $this->_writeHeader();
  fwrite($this->_fp, str_repeat('*',$this->_recSize));
  fclose($this->_fp);
  return true;
 }

 function destroyFile($a_filename){
  if(!is_file($a_filename)){return false;}
  return unlink($a_filename);
 }
}

?>