<?php
/*--------------------------------------------------------*\
 zChain GuestBook v4.00e

 Created: zChain, 2003.09.03
 \*--------------------------------------------------------*/

$perpage=20;

require "setup.php";
require "bin/class_basic_record_file.php";
require "bin/class_book_list.php";
require "bin/class_message_list.php";

// 引入模板处理引擎
include(BASEDIR.'/lib/xingTemplate/xingTemplate.php');

##########
$oBooks=new CBookList;
$oBooks->open();
$size=$oBooks->getRecordCount();
$pages=ceil($size/$perpage);

$oMsgs=new CMessageList;

if ($page == "") {$page=$pages;}
$firstitem=($page-1)*$perpage;
$lastitem=$firstitem+$perpage;
if($lastitem>$size){$lastitem=$size;}
$oBooks->moveTo($firstitem);

//获取需要显示的留言本信息
$bookInfos = array();

for($i=$firstitem; $i<$lastitem; $i++) {

	$id=urlencode($oBooks->name);
	$name=htmlspecialchars($oBooks->name);
	$title=htmlspecialchars($oBooks->title);
	$desc=htmlspecialchars($oBooks->desc);
	$regtime=$oBooks->regtime;
	$email=$oBooks->email;
	
	$oMsgs->open($oBooks->name);
	$lys=$oMsgs->getRecordCount();
	$oMsgs->close();

	$bookInfos[$i] = array(
		'id'=>$id,'name'=>$name,'title'=>$title,
		'desc'=>$desc,'regtime'=>$regtime,'lys'=>$lys,
		'email'=>$email
	);

	$oBooks->moveNext();
}

$oBooks->close();

// 输出模板
$xingTemplate->display('gb_list');

?>