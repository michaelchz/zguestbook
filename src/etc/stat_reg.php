<?php
/*--------------------------------------------------------*\
 zChain GuestBook v4.00e
 
 Created: zChain, 2004.05.05
\*--------------------------------------------------------*/
require_once "setup.php";
require_once "bin/class_basic_record_file.php";
require_once "bin/class_book_list.php";

// --- Main Begin ---

define('TMPDIR',BASEDIR.'tmp/');

$oBooks=new CBookList;

if ($oBooks->open())
{
  $used = $oBooks->getRecordCount();
  $free = $reglimit - $used;
  $fp = fopen(TMPDIR."stat-reg.js", "wb");
  fwrite($fp, "document.write('（留言本总数：$reglimit 空闲可申请数：$free ）')");
  fclose($fp);

  $oBooks->close();
}

?>