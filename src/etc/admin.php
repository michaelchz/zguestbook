<?php
/*--------------------------------------------------------*\
 zChain GuestBook v4.00e

 Created: zChain, 2003.09.05
 \*--------------------------------------------------------*/
require_once "setup.php";
require_once "bin/class_basic_record_file.php";
require_once "bin/class_book_list.php";
require_once "bin/class_book_list_admin.php";
require_once "bin/class_message_list.php";

// 引入模板处理引擎
include(BASEDIR.'/lib/xingTemplate/xingTemplate.php');

// --- Main Begin ---

$perpage=10;

$adm_user = $_COOKIE['adm_user'];
$adm_pass = $_COOKIE['adm_pass'];
if($f_user != ""){$adm_user=$f_user; setcookie("adm_user",$adm_user);}
if($f_pass != ""){$adm_pass=$f_pass; setcookie("adm_pass",$adm_pass);}

if (($admname != $adm_user)||($admpass != $adm_pass)){ login_form(); }
elseif ($action == "killBook"){ killBook($f_bookname);}
elseif ($action == "review"){ review($f_bookname);}
else{ show_list($page); }

// --- Main End ---

function login_form(){

	// 输出模板admin_login
	global $xingTemplate;
	$xingTemplate->display('admin_login');
	
}

function show_list($page){
	global $gburl,$perpage;

	$oList=new CBookListAdmin;
	$oList->open();
	$size = $oList->getRecordCount();
	$pages=ceil($size/$perpage);

	$oMsgList=new CMessageList;

	if($page == ""){$page=$pages;}
	$firstitem=($page-1)*$perpage;
	$lastitem=$firstitem+$perpage;
	if($lastitem>$size){$lastitem=$size;}
	$oList->moveTo($firstitem);

	//获取需要显示的留言本信息
	$bookInfos = array();

	for($i=$firstitem; $i<$lastitem; $i++) {

		$oMsgList->open($oList->name);
		$lys = $oMsgList->getRecordCount();
		$oMsgList->close();

		$id=urlencode($oList->name);
		$name=htmlspecialchars($oList->name);
		$title=htmlspecialchars($oList->title);
		$desc=htmlspecialchars($oList->desc);
		$regtime=$oList->regtime;
		$email=$oList->email;
		$uptime=$oList->uptime;

		$bookInfos[$i] = array(
			'id'=>$id,'name'=>$name,'title'=>$title,
			'desc'=>$desc,'regtime'=>$regtime,'lys'=>$lys,
			'email'=>$email,'uptime'=>$uptime
		);

		$oList->moveNext();
	}

	$oList->close();

	// 输出模板
	global $xingTemplate;
	$xingTemplate->assign('size',$size);
	$xingTemplate->assign('pages',$pages);
	$xingTemplate->assign('page',$page);
	$xingTemplate->assign('bookInfos',$bookInfos);
	$xingTemplate->display('admin_list');

}

function killBook($bookname){

	global $cgiurl;

	$oList=new CBookList;
	$oList->open();
	if($oList->find($bookname)){$oList->delete();}
	$oList->close();

	$oBook=new CMessageList;
	$oBook->destroy($bookname);

	// 输出模板 admin_kill
	global $xingTemplate;
	$xingTemplate->assign('cgiurl',$cgiurl);
	$xingTemplate->assign('bookname',$bookname);
	$xingTemplate->display('admin_kill');

}

function Review($bookname){
	global $imgurl,$cginame,$cgiurl,$gburl,$copyright;

	$oBooks=new CBookList;
	$oBooks->open();
	if (!$oBooks->find($bookname)){errorview("对不起，此版主没有注册！");}
	$priv_opts=array();
	$oBooks->getOptions($priv_opts);

	// 输出模板 admin_review
	global $xingTemplate;
	$xingTemplate->assign('oBooks',$oBooks);
	$xingTemplate->assign('priv_opts',$priv_opts);
	$xingTemplate->display('admin_review');
	
	$oBooks->close();
}
?>