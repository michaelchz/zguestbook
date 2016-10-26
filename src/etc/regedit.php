<?php
/*--------------------------------------------------------*\
 zChain GuestBook v4.00e

 Created: zChain, 2003.09.04
 \*--------------------------------------------------------*/
require_once "setup.php";
require_once "bin/class_basic_record_file.php";
require_once "bin/class_book_list.php";

// 引入模板处理引擎
include(BASEDIR.'/lib/xingTemplate/xingTemplate.php');

// --- Main Begin ---

define('CSSDIR',BASEDIR."style/");
define('BTNDIR',BASEDIR."img/");

if($action == "editcommit"){editCommit();}
elseif($f_name == ""){editStart();}
else{editForm();}

// --- Main End ---

function editForm() {
	global $OPTS,$cgiurl,$cginame,$prgurl,$gburl,$hosturl,$hostname;
	global $f_name,$f_pass;

	$oBooks=new CBookList;
	$oBooks->open();
	if (!$oBooks->find($f_name)){errorview("对不起，此名字没有在本{$cginame}注册过!");}
	if (!validpass($f_pass,$oBooks->pass)){errorview("密码错误!");}
	
	// 获取留言本配置信息至$OPTS中
	$oBooks->getOptions($OPTS);

	// 计算配色选择列表
	$csslist = array();
	
	if ($dh = opendir(CSSDIR)) {

		while (($file = readdir($dh)) !== false) {

			if (!is_file(CSSDIR.$file) || (substr($file,-4) != '.css')) { continue; }
			$base=basename($file,'.css');
			$csslist[$base] = $file;
		}

		closedir($dh);
	}
	
	// 计算按钮选择列表
	$btnlist = array();
	
	if ($dh = opendir(BTNDIR)) {

		while (($file = readdir($dh)) !== false) {

			if (!is_dir(BTNDIR.$file) || (substr($file,-4) != '.btn')) { continue; }
			$base=basename($file,'.btn');
			$btnlist[$base] = $file;
		}

		closedir($dh);
	}

	// 生成时区列表
	$zonelist = array();
	
	for ($i=-13; $i<=13; $i++) {
		$name=($i>0) ? "+$i" : "$i";
		$zonelist[$name]=$i;
	}
	
	// 输出模板regedit_login
	global $xingTemplate;
	$xingTemplate->assign('bookInfo', $oBooks);
	$xingTemplate->assign('OPTS',$OPTS);
	$xingTemplate->assign('csslist',$csslist);
	$xingTemplate->assign('btnlist',$btnlist);
	$xingTemplate->assign('zonelist',$zonelist);
	$xingTemplate->display('regedit_form');
	
	$oBooks->close();

}

function editCommit()
{
	global $OPTS,$copyright,$cginame,$prgurl,$gburl;
	global $f_title,$f_name,$f_pass,$f_newpass,$f_email,$f_url,$f_urlname,$f_htmlt,$f_htmlb,$f_desc;
	global $f_css,$f_btn,$f_timesft,$f_perpage,$f_notify,$f_showdlg,$f_useicon;

	if(($f_pass == "")||($f_name == "")||($f_email == "")||($f_title == "")){
		errorview("显示标题　名字　密码　信箱　必顺要填写的，请重新输入！");
	}
	if(!eregi(".*\@.*\..*",$f_email)){errorview("您的Email输入错误！");}
	if(strlen($f_desc) > 400 ){errorview("您的{$cginame}简介不能大于200个汉字！");}

	$oBooks=new CBookList;
	$oBooks->open();
	if (!$oBooks->find($f_name)){errorview("对不起，此名字没有在本{$cginame}注册过!");}
	if ($f_pass != $oBooks->pass){errorview("密码错误!");}

	$OPTS['timesft'] = $f_timesft;
	$OPTS['perpage'] = $f_perpage;
	$OPTS['notify']  = $f_notify;
	$OPTS['showdlg'] = $f_showdlg;
	$OPTS['useicon'] = $f_useicon;
	$OPTS['css'] = $f_css;
	$OPTS['btn'] = $f_btn;
	$oBooks->setOptions($OPTS);
	if($f_newpass != ""){$oBooks->pass=md5($f_newpass);}
	$oBOoks->name=$f_name; $oBooks->title=$f_title; $oBooks->email=$f_email;
	$oBooks->url=$f_url;   $oBooks->urlname=$f_urlname;
	$oBooks->htmlt=stripslashes(str_replace("\r","",$f_htmlt));
	$oBooks->htmlb=stripslashes(str_replace("\r","",$f_htmlb));
	$oBooks->desc=stripslashes(str_replace("\r","",$f_desc));
	$oBooks->update();
	$oBooks->close();

	//准备输出的留言本信息
	$bookInfo = array(
		'title'=>$f_title,
		'name'=>$f_name,
		'pass'=>$f_newpass,
		'email'=>$f_email,
		'url'=>$f_url,
		'regdate'=>$regdate
	);
	
	// 输出模板reg_form
	global $xingTemplate;
	$xingTemplate->assign('bookInfo',$bookInfo);
	$xingTemplate->display('regedit_completed');
	
}

function editStart(){

	// 输出模板regedit_login
	global $xingTemplate;
	$xingTemplate->display('regedit_login');

}

?>