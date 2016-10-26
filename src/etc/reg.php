<?php
/*--------------------------------------------------------*\
 zChain GuestBook v4.00e

 Created: zChain, 2003.09.04
 \*--------------------------------------------------------*/
require_once "setup.php";
require_once "bin/class_basic_record_file.php";
require_once "bin/class_book_list.php";
require_once "bin/class_message_list.php";

// ����ģ�崦������
include(BASEDIR.'/lib/xingTemplate/xingTemplate.php');

// --- Main Begin ---

define('CSSDIR',BASEDIR."style/");
define('BTNDIR',BASEDIR."img/");

$oBooks=new CBookList;
// ��鲢�������Ա��洢Ŀ¼�ṹ
$oBooks->checkSystem(); //check and create file structures
$oBooks->open();

if(($reglimit>0)&&($oBooks->getRecordCount()>=$reglimit)){
	errorview("�Բ����ѳ���ϵͳ�趨�����԰�ע�����ޣ��޷�ע�ᣡ");
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

	// ������ɫѡ���б�
	$csslist = array();
	
	if ($dh = opendir(CSSDIR)) {

		while (($file = readdir($dh)) !== false) {

			if (!is_file(CSSDIR.$file) || (substr($file,-4) != '.css')) { continue; }
			$base=basename($file,'.css');
			$csslist[$base] = $file;
		}

		closedir($dh);
	}
	
	// ���㰴ťѡ���б�
	$btnlist = array();
	
	if ($dh = opendir(BTNDIR)) {

		while (($file = readdir($dh)) !== false) {

			if (!is_dir(BTNDIR.$file) || (substr($file,-4) != '.btn')) { continue; }
			$base=basename($file,'.btn');
			$btnlist[$base] = $file;
		}

		closedir($dh);
	}
	
	// ���ģ��reg_form
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
		errorview("��ʾ���⡡���֡����롡���䡡������д�����������룡");
	}
	if(!eregi(".*\@.*\..*",$f_email)){errorview("����Email�������");}
	if(!eregi("^[_a-zA-Z0-9-]+$",$f_name)){errorview("���Ĺ���Ա���ư����Ƿ��ַ���");}
	if(strlen($f_desc) > 400 ){errorview("����{$cginame}��鲻�ܴ���200�����֣�");}

	if(($f_url == "")||($f_url == "http://")){
		errorview("��ҳURL������д�����������룡");
	}
	if($f_urlname == ""){
		$f_urlname = $f_url;
	}

	$oMsgs=new CMessageList;
	if(!$oMsgs->create($f_name)){
		errorview("�Բ����û����ѱ�����ע��! ��һ���ɣ�");
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
	
	$emsg = "�װ���{$f_name}, ����!\n\n";
	$emsg .= "������ϲ! ���Ѿ��ɹ���������$hostname($hosturl)��{$cginame}ϵͳ, �ǳ���л��ʹ��{$hostname}�ķ���!\n\n";
	$emsg .= "��* �������Ϊ����{$cginame}�ṩ��һ���ȽϺüǵĵ�ַ,��������\n";
	$emsg .= "URL: $prgurl/$gburl?id=$f_name\n\n";
	$emsg .= "��* �����ʺ���:{$f_name}��������:$f_pass\n\n";
	$emsg .= "��* ���, �м���ע�����������μ�\n";
	$emsg .= "1������ʹ�ñ�{$cginame}ϵͳ�����κΰ���ɫ�顢�Ƿ����Լ�Σ�����Ұ�ȫ�����ݵ�{$cginame};\n";
	$emsg .= "2�������ڱ�ϵͳ�û���ӵ�е�{$cginame}�ڷ����κ�ɫ�顢�Ƿ�������Σ�����Ұ�ȫ��{$cginame};\n";
	$emsg .= "3�����Ϲ���Υ�������Ը�����վ��Ȩɾ�������û��������ݣ���׷���䷨�����Ρ�\n\n\n";
	$emsg .= "����������zChain.com(http://www.zchain.com)\n";
	$emsg .= "������Բ������� $hostname($hosturl) �ṩ\n\n";
	@mail($f_email, "Subject: {$cginame}ϵͳ��֪ͨͨ��", $emsg);
	
	//׼����������Ա���Ϣ
	$bookInfo = array(
		'title'=>$f_title,
		'name'=>$f_name,
		'pass'=>$f_pass,
		'email'=>$f_email,
		'url'=>$f_url,
		'regdate'=>$regdate
	);
	
	// ���ģ��reg_form
	global $xingTemplate;
	$xingTemplate->assign('bookInfo',$bookInfo);
	$xingTemplate->display('reg_completed');

}
?>