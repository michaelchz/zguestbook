<?php
/*--------------------------------------------------------*\
 ������Բ����û��� zChain GuestBook v4.00
 
 ���ߣ�zChain (http://www.zchain.net)
 ��Ȩ����(c) 2001-2003

 ������Ϊ����������������� GNUͨ�ù�����Ȩ����涨������
 �޸ġ�ʹ����ɢ�������򣬵����뱣����������վ�����ӡ�����
 ��μ� readme.txt ��
\*--------------------------------------------------------*/

class CFormMessage {
	var $user, $email, $icon, $url, $save;
	var $comment, $ip, $time, $secret;
	var $reply, $replysecret;
}

header("Cache-Control: private, must-revalidate");

function getmicrotime(){ 
    list($usec, $sec) = explode(" ",microtime()); 
    return ((float)$usec + (float)$sec); 
} 

$__start=0;
function timer_start(){
 global $__start, $__mark;
 $__start = $__mark = getmicrotime();
} 

function timer_stop($name=''){ 
 global $filepath, $__start;
 $time = getmicrotime() - $__start;
 echo "<BR><FONT style='font-size:10px; color:white;'>$name Page execution time: $time second.</FONT>";
} 

timer_start();
ob_start();

require "setup.php";
require "bin/class_basic_record_file.php";
require "bin/class_book_list.php";
require "bin/class_message_list.php";

/*
$notices=@file("$filepath/notice");
list($upstp,$notice)=split("\t",$notices[0]);
if (($timestamp-$upstp) > (3600*24*7)){
 $notices=@file("http://www.zchain.net/zgb/orgnote");
 $notice=$notices[0];
 if($TMP=fopen("$filepath/notice","w")) {
  fwrite($TMP,"$timestamp\t$notice");
  fclose($TMP);
 }
}
*/
$notice = '';

$id = $_REQUEST['id'];
$id = str_replace(".","",$id);
if ($id == "") {errorview("δָ��id��");exit;}

$oBooks=new CBookList;
if(!$oBooks->open()){errorview("book list file open error!");exit;}
if(!$oBooks->find($id)){errorview("id(=$id)����");exit;}
$oBooks->getOptions($OPTS);

getlocaltime($OPTS['timesft']);

$oMsgs=new CMessageList;
if(!$oMsgs->open($id)){errorview("guest book open error!");exit;};

// HTML translation
$id=htmlspecialchars($id);
$oBooks->title=htmlspecialchars($oBooks->title);

// admin authorization
switch ($_REQUEST['action']) {
	case 'logout':	$ck_pass = ""; break;
	case 'login':	$ck_pass = $_REQUEST['f_pass']; break;
	default:	$ck_pass = ($_REQUEST['f_pass']) ? $_REQUEST['f_pass'] : $_COOKIE['ck_pass']; break;
}
if (!validpass($ck_pass,$oBooks->pass)){ $ck_pass = ""; }
setcookie("ck_pass",$ck_pass);
setcookie("ck_supported",'true');

$rawcomment=stripslashes($f_comment);
$f_comment=htmlspecialchars($f_comment);
$f_comment=stripslashes($f_comment);
$f_comment=str_replace("\t","--",$f_comment);
$f_comment=str_replace("\n","<br>",$f_comment);
$f_comment=str_replace("\r","",$f_comment);

//
// ׼����������Ҫ��ȫ�ֻ���

// ����ģ�崦������
define('BASEDIR', './');
include(BASEDIR.'/lib/xingTemplate/xingTemplate.php');

// ��װȫ�ֻ�������
$context = array(
	'xingTemplate'=>$xingTemplate,
	'id'=>$id,
	'page'=>$page
);

$_SESSION['action'] = $_REQUEST['action'];
switch ($_SESSION['action']) {
	case 'addmsg':
		AddMessage($id); JumpTo("$gburl?id=$id");
		break;
	case 'delmsg':
		DelMessage($id,$mid);ShowBook($id,$search,$page);
		break;
	case 'find':
		ShowBook($id,$search,$page);
		break;
	case 'reply':
		include 'bin/gb_reply.php';
		break;
	case 'replycommit':
		include 'bin/gb_reply.php'; JumpTo("$gburl?id=$id");
		break;
	case 'editform':
		include 'bin/gb_edit.php';
		break;
	case 'editcommit':
		include 'bin/gb_edit.php'; JumpTo("$gburl?id=$id");
		break;
	case 'iconlist':
		include 'bin/gb_iconlist.php';
		break;
	case 'login':
		if (!$ck_pass) {
			$msg = "��¼ʧ�ܣ�"; $delay = 2; 
		} elseif (!$_COOKIE['ck_supported']) {
			$msg = '��¼ʧ�ܣ����������֧��Cookie������ѡ���޷�����!��'; $delay = 5;
		} else {
			$msg = "������¼�ɹ������й���ѡ���Ѽ��"; $delay = 1;
		} 
		JumpToEx("$gburl?id=$id", $msg, $delay);
		break;
	default:
		include 'bin/controller_show_book.php';
		CShowBookController::getController()->execute($context);
		break;
}

$oBooks->close();
$oMsgs->close();

timer_stop('Before ob_end_flush()');
ob_end_flush();
timer_stop();

exit;

##########
function AddMessage($id){
 global $filepath,$OPTS,$f_user,$rawcomment,$f_comment,$f_email,$f_url,$userip,$f_secret,$f_icon,$f_save;
 global $f_authcode, $f_authmd5;
 global $prgurl,$gburl,$hostname,$hosturl,$thistime,$ftime;
 global $oBooks,$oMsgs; 

 if($f_user == ""){errorview("�������ֲ���û����дŶ��");exit;}
 if($f_comment == ""){errorview("�����������ݲ���û����дŶ��");exit;}
 if(strlen($rawcomment) > 2000 ){errorview("�����������ݲ��ܴ���2000���ַ���");exit;}
 if($f_email != ""){
   if(!eregi(".*\@.*\..*",$f_email)){errorview("����Email�������");exit;}
 }

 if(($f_authcode == '') || (md5($f_authcode) != $f_authmd5)){errorview("��֤��������������ԣ�");exit;}
 if(CheckRepeat($f_user.$f_comment)){errorview("ͬһ�����Կɲ��ܷ�2������Ŷ��");exit;}
 if(CheckFlood($id, $userip, $f_comment)){errorview("�벻Ҫ���͹�ˮ���ԣ�");exit;}

 $f_user=stripslashes($f_user);
 $f_user=htmlspecialchars($f_user);
 $f_url=stripslashes($f_url);
 $f_url=htmlspecialchars($f_url);

 $oFormMsg = new CFormMessage();
 $oMsgs->appendNew();

 $oFormMsg->user  = $oMsgs->user  = $f_user;
 $oFormMsg->email = $oMsgs->email = $f_email;
 $oFormMsg->icon  = $oMsgs->icon  = $f_icon;
 $oFormMsg->url   = $oMsgs->url   = (trim($f_url)!="http://") ? $f_url : "";

 $oMsgs->comment=$f_comment; $oMsgs->ip=$userip; $oMsgs->time=$thistime;
 $oMsgs->secret=$f_secret; $oMsgs->reply=""; $oMsgs->replysecret="";

 $oMsgs->update();

 $oFormMsg->save = $f_save;
 SetFormCookie($oFormMsg);
  
 if (($oBooks->email != "") && ($OPTS['notify'] == 1)) {
  $emsg = "����: $f_user � $thistime\n";
  if ($f_email != "") $emsg .= "����: $f_email\n";
  if ($oMsgs->url != "") $emsg .= "��ҳ: $f_url\n";
  $emsg .= "��������: \n$rawcomment\n\n";
  $emsg .= "$oBooks->title($prgurl/$gburl?id=$id)\n\n\n";
  $emsg .= "����������zChain.net(http://www.zchain.net)\n";
  $emsg .= "������Բ������� $hostname($hosturl) �ṩ\n\n";
  @mail($oBooks->email,"Subject: $oBooks->title ������֪ͨ",$emsg,"From: $f_email");
 }
}

function ShowBook($id,$keyword,$page) {
 global $copyright,$OPTS,$gburl,$ck_pass,$notice;
 global $oBooks, $oMsgs; 

 if($keyword) {$lines=$oMsgs->search($keyword);}
 $size=($keyword) ? count($lines) : $oMsgs->getRecordCount();

 PrintHeader();

 $perpage = $OPTS['perpage'];
 $pages = ceil($size/$perpage);
 if($pages < 1){$pages=1;}
 if($page == ""){$page=1;}elseif($page>$pages){$page=$pages;}
 $firstitem=($page-1)*$perpage;
 $lastitem=$firstitem+$perpage;
 if($lastitem>$size){$lastitem=$size;}
 if(!$keyword){$oMsgs->moveTo($firstitem, false);}

 for($i=$firstitem; $i<$lastitem; $i++) {
  if($keyword){
   $oMsgs->setAbsolutePosition($lines[$i]);
   PrintMessage($size-$i, &$oMsgs, $keyword, $page);
  }else{
   PrintMessage($size-$i, &$oMsgs, $keyword, $page);
   if($i<($lastitem-1)){$oMsgs->movePrev();}
  }
 }

 $promptMsg=($keyword!="") ? "�������" : "��������";
 $prompt=($keyword!="") ? "&action=find&search=".urlencode($keyword) : "";
 $prevPg=($page>1)?$page-1:0;
 $prevMsg=($prevPg>0)?"href=$gburl?id=$id&page=$prevPg{$prompt}":"";
 $nextPg=($page<$pages)?$page+1:0;
 $nextMsg=($nextPg>0)?"href=$gburl?id=$id&page=$nextPg{$prompt}":"";
 print <<<EOT
<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bookNavigator">
<tr><td><form method=post action=$gburl?action=find&id=$id>
�����ؼ��֣�<input name="search" size="20" class="plainInput">
<input class="plainButton" type="submit" value="����" name="submit"></form></td>
<td align="right">
 <form action=$gburl?id=$id{$prompt} method=post>
 <a $prevMsg>&lt;&lt;</a>
 �� <input size="2" value="$page" name="page" class="plainInput"> / $pages ҳ
 <a $nextMsg>&gt;&gt;</a>
 [{$promptMsg}��<em>$size</em>]</form></td></tr>
</table>
</div>
$oBooks->htmlb
<div class="bookFrame">
<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bookNavigator">
<tr><td><center>$notice<br>$copyright</center></td></tr></table>
</div>
</body></html>
EOT;

}

function DelMessage($id,$msgid) {
 global $ck_pass, $oMsgs;

 if(!$ck_pass){errorview("�����������");exit;}

 if(!$oMsgs->setAbsolutePosition($msgid)){errorview("ָ�����Դ���");exit;}
 if(!$oMsgs->delete()){errorview("����ɾ������");exit;}
}

function PrintHeader()
{
 global $imgurl,$gburl,$OPTS,$id,$oBooks,$ck_pass;

 // generate authcode, store it to session to be used by authimg.php 
 srand((double)microtime()*1000000);
 while(($authcode=rand()%10000)<1000);
 $_SESSION['authcode'] = $authcode;
 $authmd5 = md5($authcode);

 $sturl="$imgurl/$OPTS[btn]";

 $oFormMsg = new CFormMessage();
 GetFormCookie($oFormMsg);

 $mgrPrompt=($ck_pass)?"<a href='$gburl?action=logout&id=$id'>[ע��]</a>":"<a onclick='showDlg(); return false;' href='#'>[����]</a>";

 $ctrl_save="<INPUT name=f_save type=checkbox value=1".($oFormMsg->save?' CHECKED':'').">�����ҵ���Ϣ";
 
 print <<<EOT
<HTML><HEAD>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312">
<link rel="stylesheet" type="text/css" href="style/$OPTS[css]">
<script language="javascript" src="bin/gb.js"></script>
<title>$oBooks->title</title>
</HEAD>
<BODY class="book">
$oBooks->htmlt

<DIV ID="passForm" align="center" class="manageForm">
<form method=post action='$gburl?action=login&id=$id'>
<table cellspacing=1 bgcolor=#000000 cellpadding=3>
<tr><td width=100% bgcolor=#F0F0F0 align=center>
����������룺<input type=password class="plainInput" name=f_pass size=15><br><br>
<input type=submit value="ȷ��" class="plainButton">
<input type="button" onclick="hideDlg();" value="�ر�" class="plainButton">
</td></tr></table></form>
</DIV>

<div class="bookFrame">

<TABLE ID="simpForm" cellPadding=0 cellSpacing=1 width=100% class="msgForm">
<TR><TD>
 <TABLE border=0 width="100%" class="msgFormCaption">
 <TR><TD>&gt;&gt;&gt; <a href="$oBooks->url">$oBooks->urlname</a>
 &gt;&gt; <a href="$gburl?id=$id">$oBooks->title</a></TD>
 <TD align=right>
  $mgrPrompt
  <a target="_blank" href="/?op=regedit">[�޸�]</a>
  <a target=_blank href="/?op=reg">[����]</a>
  <a href="#" onclick="showForm(1);return false;">&darr;[����]</a>
 </TD></TR></TABLE>
</TD></TR>
</TABLE>

<FORM NAME="signForm" ACTION="$gburl?id=$id" METHOD="post" onsubmit="validate_signForm();">
<input type="hidden" name="action" value="addmsg">
<TABLE ID="fullForm" cellPadding=0 cellSpacing=1 width=100% class="msgForm">
<TR><TD>
 <TABLE border=0 width="100%" class="msgFormCaption">
 <TR><TD align=left vAlign=center>&gt;&gt;&gt; <a href="$oBooks->url">$oBooks->urlname</a>
 &gt;&gt; <a href="$gburl?id=$id">$oBooks->title</a></TD>
 <TD align=right class=fgfont vAlign=center>
 $mgrPrompt
 <a target="_blank" href="/?op=regedit">[�޸�]</a>
 <a target=_blank href="/?op=reg">[����]</a>
 <a href="#" onclick="showForm(0);return false;">&uarr;[����]</a>
</TD></TR></TABLE></TD></TR>
<TR><TD vAlign=top width="50%">
 <TABLE width="100%" class="msgFormBody">
 <TR><TD vAlign=top>
  <TABLE class="msgFormBody">
  <TR><TD><IMG src="$sturl/name.gif" width=16 height=16></TD>
  <TD>����</TD>
  <TD colspan="2"><INPUT class=plainInput maxLength=20 name=f_user value="$oFormMsg->user" onmouseover="set_at_end(this);"> *</TD></TR>
  <TR><TD><IMG border=0 src="$sturl/email.gif" width=16 height=16></TD>
  <TD>Email</TD>
  <TD colspan="2"><INPUT class=plainInput maxLength=40 name=f_email value="$oFormMsg->email"  size=28 onmouseover="set_at_end(this);"></TD></TR>
  <TR><TD><IMG border=0 src="$sturl/home.gif" width=16 height=16></TD>
  <TD>��ҳ</TD>
  <TD colspan="2"><INPUT class=plainInput maxLength=40 name=f_url value="$oFormMsg->url" value="http://" size=28 onmouseover="set_at_end(this);"></TD></TR>
  <TR><TD><IMG border=0 src="$sturl/private.gif" width=16 height=16></TD>
  <TD>���Ļ�</TD>
  <TD><INPUT name=f_secret type=radio value=1>�� <INPUT CHECKED name=f_secret type=radio value=0>��</TD>
EOT;

 if($OPTS['useicon']==1){
  $deficon=($oFormMsg->icon) ? "icon{$oFormMsg->icon}.gif" : "icon1.gif";
  print "<TD rowspan=2> <IMG id=idface src='$imgurl/$deficon' height=48 width=48>
   <A target=_blank href='?action=iconlist&id=$id'>ͷ���б�</A></TD>";
  print "</TR>";

  print "<TR><TD align=left><IMG src='$sturl/face.gif' height=16 width=16></TD>";
  print "<TD>ѡͷ��</TD><TD><SELECT class=plainInput name=f_icon size=1
   onChange=\"document.images['idface'].src='$imgurl/icon'+options[selectedIndex].value+'.gif';\">";
  for($i=1;$i<=$OPTS['numicon'];$i++){
   $selected = ($oFormMsg->icon == $i) ? 'selected' : '';
   print "<OPTION value=$i $selected>ͷ��{$i}</OPTION>";
  }
  print "</SELECT></TD></TR>";
 } else {
  print "<TD rowspan=2></TD>";
  print "</TR>";
 }

 print <<<EOT
  </TABLE></TD>
  <TD align=left valign=top>
   <IMG height=15 width=15 src="$sturl/pen.gif"> ���� * &nbsp;&nbsp;�����2000�����ã�
   <input class=plainInput type=text name=used size=3 maxlength=4 value="0" disabled>��
   <BR>
   <TEXTAREA class=plainInput cols=50 name=f_comment rows=5 title=�����������2000
    onKeyDown="count_char(this,this.form.used);"
    onKeyUp="count_char(this,this.form.used);"></TEXTAREA>
   <BR>
��������ͼ�е���֤�룺<INPUT class=plainInput size=10 name=f_authcode>
<img src=authimg.php?authcode=$authcode align=absbottom>
<input type=hidden name=f_authmd5 value=$authmd5>
  </TD></TR></TABLE>
 </TD></TR>
 <TR><TD>
  <TABLE align=center border=0 cellPadding=0 cellSpacing=0 width="100%" class="msgFormCaption">
  <TR><TD align=center>
   $ctrl_save&nbsp;&nbsp;&nbsp;&nbsp;
   <INPUT class=plainButton name=Submit type=submit value="��������">
   &nbsp;&nbsp;&nbsp; <INPUT class=plainButton name=Submit2 type=reset value="�������">
  </TD></TR></TABLE>
</TD></TR></TABLE></FORM>
<script language=JavaScript>
var disp=get_cookie("disp"); if(disp=="")disp=$OPTS[showdlg];
showForm(disp);</script>
EOT;
}

function PrintMessage($msgNo, $oMsg, $keyword, $page) {
 global $imgurl,$id,$gburl,$ck_pass,$OPTS,$userip,$timestamp;

 $sturl="$imgurl/$OPTS[btn]";

 $urluser=urlencode($oMsg->user);

 $indicator=($msgNo>0)?"�� $msgNo ������":"���ظ�����";
 print <<<EOT
<table border="0" cellpadding="0" cellspacing="1" width="100%" class="msg">
<tr><td>
 <table border="0" cellpadding="0" cellspacing="0" width="100%" class="msgCaption">
 <tr><td width="100" align="center" valign="middle">$indicator</td>
 <td valign="middle">������ $oMsg->time</td>
 <td align="right" valign="middle">
EOT;

 if($msgNo > 0){
  echo "<a href=$gburl?action=find&id=$id&search=$urluser><img src=$sturl/find.gif style='border: 0px' alt='����{$oMsg->user}��д��������'></a> \n";
 }
 if($oMsg->email != ""){
  $memail=($ck_pass) ? "href=mailto:$oMsg->email" : "";
  $malt=($ck_pass) ? $oMsg->email : "����";
  echo "<a $memail><img src=$sturl/email2.gif style='border: 0px' alt={$oMsg->user}��email��$malt></a> \n";
 }
 if($oMsg->url != ""){
  echo "<a href=$oMsg->url target=_blank><img src=$sturl/home2.gif style='border: 0px' alt=��ҳ></a> \n";
 }
 $mip=($ck_pass) ? $oMsg->ip : "����";
 echo "<img src=$sturl/ip.gif alt='{$oMsg->user}��IP��ַ��$mip'></a> \n";
 if($msgNo > 0){
  echo "<a href=$gburl?action=reply&id=$id&mid=$oMsg->msgid><img src=$sturl/reply.gif style='border: 0px' alt=�ظ�����></a> \n";
  if($ck_pass){
   echo "<a href=$gburl?action=delmsg&id=$id&mid=$oMsg->msgid&search=$keyword&page=$page onClick='return confirm_del();'><img src=$sturl/del.gif style='border: 0px' alt=ɾ��������></a>\n";
  }
 }

	$tmpMsg1=(($oMsg->secret != 1) || ($ck_pass)) ? $oMsg->comment : '���Ļ�����...';
	$tmpTip1=(($oMsg->secret == 1) && ($ck_pass)) ? '<font color=red>&lt;���Ļ�����&gt;</font><BR>' : '';
	if (($oMsg->replysecret != 1) || ($ck_pass)) {
		$tmpMsg2=($oMsg->reply) ? $oMsg->reply : '';
		if (($tmpMsg2!='') && ($oMsg->replytime!='')) {
			$tmpMsg2="<font color=#ab00ac>�����ظ�</font> - "
				."<i><FONT color=#777777>$oMsg->replytime</font></i><br>".$tmpMsg2;
		}
	} else {
		$tmpMsg2='���Ļ��ظ�...';
	}
	$tmpTip2=(($oMsg->replysecret == 1) && ($ck_pass)) ? '<font color=red>&lt;���Ļ��ظ�&gt;</font><BR>' : '';
	if ($tmpMsg2) { $tmpTip2 = '<BR><BR>'.$tmpTip2; }

 // filter content
 if (!$ck_pass) {
  $tmpMsg1=str_replace('��꿷�', '---', $tmpMsg1);
  $tmpMsg2=str_replace('��꿷�', '---', $tmpMsg2);
 }

 // auto detect http link
 $pattern = "(http|https|ftp):(\/\/|\\\\\\\\)[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*"
   ."((\/|\\\\)[~_a-zA-Z0-9-]+)*(\.[~_a-zA-Z0-9-]+(#[~_a-zA-Z0-9-]+){0,1}){0,1}"
   ."((\/|\\\\)|(\?[~_a-zA-Z0-9-]+=[~_a-zA-Z0-9-]+(\&amp;[~_a-zA-Z0-9-]+=[~_a-zA-Z0-9-]+)*)){0,1}";
 $tmpMsg1 = eregi_replace($pattern, " <a href='\\0' target=_blank>\\0</a> ", $tmpMsg1);
 $tmpMsg2 = eregi_replace($pattern, " <a href='\\0' target=_blank>\\0</a> ", $tmpMsg2);

 $userMsg=($OPTS['useicon']==1)?
  "$oMsg->user<br><img border=0 src='$imgurl/icon{$oMsg->icon}.gif'>"
  :"<br>$oMsg->user<br><br>";

 if ($ck_pass) {
  $cmdLine="<a href=$gburl?action=editform&id=$id&mid=$oMsg->msgid>[�޸�����]</a><BR><BR>";
 } elseif (($oMsg->ip == $userip) && (($timestamp-strtotime($oMsg->time))<(3600*12))) {
  $cmdLine="<a href=$gburl?action=editform&id=$id&mid=$oMsg->msgid>[�޸��ҵ�����]</a><BR><BR>";
 } else {
  $cmdLine='';
 }

 print <<<EOT
 </td></tr>
 </table>
</td></tr>
<tr><td>
 <table width=100% class=msgBody>
  <tr><td width=100 align=center valign=top>$userMsg</td>
  <td align=left valign=top class=msgText>
  $cmdLine
  $tmpTip1$tmpMsg1
  $tmpTip2$tmpMsg2
  </td></tr>
 </table></td></tr>
</table>
EOT;

}

function CheckRepeat($a_identity){
 global $filepath;
 $lasttxts=@file("$filepath/chklast");
 if($TMP=fopen("$filepath/chklast","w")){
  fwrite($TMP,$a_identity); fclose($TMP);
 }
 return ($lasttxts[0] == $a_identity);
}

function CheckFlood($a_id, $a_ip, $a_comment){
	global $filepath;

	// check messge content to avoid ad message
	$pattern = "(http|https|ftp):(\/\/|\\\\\\\\)[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*"
		."((\/|\\\\)[~_a-zA-Z0-9-]+)*(\.[~_a-zA-Z0-9-]+(#[~_a-zA-Z0-9-]+){0,1}){0,1}"
		."((\/|\\\\)|(\?[~_a-zA-Z0-9-]+=[~_a-zA-Z0-9-]+(\&amp;[~_a-zA-Z0-9-]+=[~_a-zA-Z0-9-]+)*)){0,1}";
	$tmpMsg = eregi_replace($pattern, " <<URL>> ", $a_comment);
	$urlcount = substr_count($tmpMsg, " <<URL>> ");
	if ($urlcount > 5) {
		// too much url in the message, consider it as flood
		return true;
	}

	$lasttxts=@file("$filepath/chkflood.$a_id");
	list($last_ip, $last_time) = explode(" ",$lasttxts[0]); 
	
	if (($last_ip == $a_ip) && (($last_time + 60) > getmicrotime())) {
		// same IP send message within 1 mins, consider it as flood
		return true;
	}
	
	// not flood, record the information
	if($TMP=fopen("$filepath/chkflood.$a_id","w")){
		fwrite($TMP,$a_ip." ".getmicrotime()); fclose($TMP);
	}	
	return false;
}

function JumpTo($url)
{
 global $oBooks;

 print <<<EOT
<html><head><title>�� �� �� �� !</title>
<META HTTP-EQUIV="REFRESH" CONTENT="2; URL=$url">
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=gb2312">
<style><!--
{font-size: 12px;}
p {font-size:12px;}
a { text-decoration: none; color: rgb(40,40,180) }
a:hover {color:'#FF9900';text-decoration:none}
table {font-size:12px;}
td {font-size:12px;}
--></style>
</head>
<body><CENTER><br><br><br>
<table width=335 cellspacing=1 cellpadding=5>
<tr><td width=100% bgcolor='#ffffff' align=center>
<span style="font-size: 10.5pt;line-height: 13pt">- �� �� �� �� -</span><BR></td></tr>
<tr><td align=center><a href=$url>[ �� �� �� �� ]</a>&nbsp;&nbsp;&nbsp;<a href=$oBooks->url>[ �� �� �� ҳ ]</a></td></tr>
</table>2���Ӻ��Զ�����......<br><br><br>
</body></html>
EOT;
}

function JumpToEx($url,$msg,$delay)
{
 global $oBooks;

 print <<<EOT
<HTML><HEAD>
<META HTTP-EQUIV="REFRESH" CONTENT="$delay; URL=$url">
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=gb2312">
<title>$oBooks->title</title>
<style><!--
a { text-decoration: none; color: rgb(40,40,180) }
a:hover {color:'#FF9900';text-decoration:none}
table {font-size:14px;}
--></style>
</HEAD>
<BODY>
<CENTER>
<br><br><br>
<table width=335 cellspacing=1 cellpadding=5>
<tr><td align=center>
$msg<br><br><br>
$delay ���Ӻ��Զ���ת����� <a href=$url>�˴�</a> ������ת
</td></tr>
</table>
</body></html>
EOT;
}

function SetFormCookie($formMsg)
{
	$expire = time() + 3600 * 24 * 30;
	if ($formMsg->save) {
		setcookie('zgb_user', $formMsg->user, $expire);
		setcookie('zgb_email', $formMsg->email, $expire);
		setcookie('zgb_url', $formMsg->url, $expire);
		setcookie('zgb_icon', $formMsg->icon, $expire);
		setcookie('zgb_save', $formMsg->save, $expire);
	} else {
		setcookie('zgb_user');
		setcookie('zgb_email');
		setcookie('zgb_url');
		setcookie('zgb_icon');
		setcookie('zgb_save');
	}
}

function GetFormCookie(&$formMsg)
{
	$formMsg->user = $_COOKIE['zgb_user'];
	$formMsg->email = $_COOKIE['zgb_email'];
	$formMsg->url = $_COOKIE['zgb_url'];
	$formMsg->icon = $_COOKIE['zgb_icon'];
	$formMsg->save = $_COOKIE['zgb_save'];
}

?>