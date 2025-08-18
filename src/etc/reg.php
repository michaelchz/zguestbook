<?php
/*--------------------------------------------------------*\
 zChain GuestBook v4.00e

 Created: zChain, 2003.09.04
 \*--------------------------------------------------------*/
require_once "setup.php";
require_once "bin/class_basic_record_file.php";
require_once "bin/class_book_list.php";
require_once "bin/class_message_list.php";

// 引入模板处理引擎
include(BASEDIR.'/lib/xingTemplate/xingTemplate.php');

// --- Main Begin ---

define('CSSDIR',BASEDIR."style/");
define('BTNDIR',BASEDIR."img/");

$oBooks=new CBookList;
// 检查并建立留言本存储目录结构
$oBooks->checkSystem(); //check and create file structures
$oBooks->open();

if(($reglimit>0)&&($oBooks->getRecordCount()>=$reglimit)){
	errorview("对不起，已超过系统设定的留言板注册上限，无法注册！");
}

if($action == "regcommit"){
	regCommit();
} else {
	regForm();
}

$oBooks->close();

// --- Main End ---

function regForm()
{
	global $prgurl,$gburl,$copyright,$cginame,$cgiurl;

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
	
	// 输出模板reg_form
	global $xingTemplate;
	$xingTemplate->assign('csslist',$csslist);
	$xingTemplate->assign('btnlist',$btnlist);
	$xingTemplate->display('reg_form');

}

function regCommit()
{
	global $OPTS,$copyright,$hostname,$hosturl,$cginame,$prgurl,$gburl,$thisdate,$oBooks;

	$f_title = trim($_REQUEST['f_title']);
	$f_name  = trim($_REQUEST['f_name']);
	$f_pass  = $_REQUEST['f_pass'];
	$f_email = trim($_REQUEST['f_email']);
	$f_url   = trim($_REQUEST['f_url']);
	$f_urlname = trim($_REQUEST['f_urlname']);
	$f_htmlt = stripslashes(str_replace("\r","",$_REQUEST['f_htmlt']));
	$f_htmlb = stripslashes(str_replace("\r","",$_REQUEST['f_htmlb']));
	$f_desc  = stripslashes(str_replace("\r","",$_REQUEST['f_desc']));

	if(($f_pass == "")||($f_name == "")||($f_email == "")||($f_title == "")){
		errorview("显示标题　名字　密码　信箱　必须填写，请重新输入！");
	}
	if(!eregi(".*\@.*\..*",$f_email)){errorview("您的Email输入错误！");}
	if(!eregi("^[_a-zA-Z0-9-]+$",$f_name)){errorview("您的管理员名称包含非法字符！");}
	if(strlen($f_desc) > 400 ){errorview("您的{$cginame}简介不能大于200个汉字！");}

	if(($f_url == "")||($f_url == "http://")){
		errorview("主页URL必须填写，请重新输入！");
	}
	if($f_urlname == ""){
		$f_urlname = $f_url;
	}

	$oMsgs=new CMessageList;
	if(!$oMsgs->create($f_name)){
		errorview("对不起，用户名已被别人注册! 换一个吧！");
	}

	$regdate=strftime("%Y-%m-%d", time());

	$OPTS['css'] = $_REQUEST['f_css'];
	$OPTS['btn'] = $_REQUEST['f_btn'];

	$bid=$oBooks->appendNew();
	$oBooks->setOptions($OPTS);
	$oBooks->name  = $f_name;
	$oBooks->pass  = md5($f_pass);
	$oBooks->title = $f_title;
	$oBooks->email = $f_email;
	$oBooks->url   = $f_url;
	$oBooks->urlname = $f_urlname;
	$oBooks->regtime = $regdate;
	$oBooks->htmlt   = $f_htmlt;
	$oBooks->htmlb   = $f_htmlb;
	$oBooks->desc    = $f_desc;
	$oBooks->update();
	
	$emsg = "亲爱的{$f_name}, 您好!\n\n";
	$emsg .= "　　恭喜! 您已经成功的申请了$hostname($hosturl)的{$cginame}系统, 非常感谢您使用{$hostname}的服务!\n\n";
	$emsg .= "　* 我们免费为您的{$cginame}提供了一个比较好记的地址,请您试试\n";
	$emsg .= "URL: $prgurl/$gburl?id=$f_name\n\n";
	$emsg .= "　* 您的帐号是:{$f_name}　密码是:$f_pass\n\n";
	$emsg .= "　* 最后, 有几点注意事项请您牢记\n";
	$emsg .= "1、不得使用本{$cginame}系统建立任何包含色情、非法、以及危害国家安全的内容的{$cginame};\n";
	$emsg .= "2、不得在本系统用户所拥有的{$cginame}内发布任何色情、非法、或者危害国家安全的{$cginame};\n";
	$emsg .= "3、以上规则违者责任自负，本站有权删除该类用户或者内容，并追究其法律责任。\n\n\n";
	$emsg .= "程序制作：zChain.com(http://www.zchain.com)\n";
	$emsg .= "免费留言簿服务由 $hostname($hosturl) 提供\n\n";
	@mail($f_email, "Subject: {$cginame}系统开通通知！", $emsg);
	
	//准备输出的留言本信息
	$bookInfo = array(
		'title'=>$f_title,
		'name'=>$f_name,
		'pass'=>$f_pass,
		'email'=>$f_email,
		'url'=>$f_url,
		'regdate'=>$regdate
	);
	
	// 输出模板reg_form
	global $xingTemplate;
	$xingTemplate->assign('bookInfo',$bookInfo);
	$xingTemplate->display('reg_completed');

}
?>