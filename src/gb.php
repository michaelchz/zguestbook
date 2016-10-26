<?php
/*--------------------------------------------------------*\
 零点留言簿多用户版 zChain GuestBook v4.00
 
 作者：zChain (http://www.zchain.net)
 版权所有(c) 2001-2003

 本程序为自由软件，您可以在 GNU通用公共授权条款规定下自由
 修改、使用与散播本程序，但必须保留作者与网站的链接。详情
 请参见 readme.txt 。
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
if ($id == "") {errorview("未指定id！");exit;}

$oBooks=new CBookList;
if(!$oBooks->open()){errorview("book list file open error!");exit;}
if(!$oBooks->find($id)){errorview("id(=$id)错误！");exit;}
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
// 准备各功能需要的全局环境

// 引入模板处理引擎
define('BASEDIR', './');
include(BASEDIR.'/lib/xingTemplate/xingTemplate.php');

// 组装全局环境变量
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
			$msg = "登录失败！"; $delay = 2; 
		} elseif (!$_COOKIE['ck_supported']) {
			$msg = '登录失败！（浏览器不支持Cookie，管理选项无法激活!）'; $delay = 5;
		} else {
			$msg = "版主登录成功，所有管理选项已激活！"; $delay = 1;
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

 if($f_user == ""){errorview("您的名字不能没有填写哦！");exit;}
 if($f_comment == ""){errorview("您的留言内容不能没有填写哦！");exit;}
 if(strlen($rawcomment) > 2000 ){errorview("您的留言内容不能大于2000个字符！");exit;}
 if($f_email != ""){
   if(!eregi(".*\@.*\..*",$f_email)){errorview("您的Email输入错误！");exit;}
 }

 if(($f_authcode == '') || (md5($f_authcode) != $f_authmd5)){errorview("验证码输入错误，请重试！");exit;}
 if(CheckRepeat($f_user.$f_comment)){errorview("同一则留言可不能发2次以上哦！");exit;}
 if(CheckFlood($id, $userip, $f_comment)){errorview("请不要发送灌水留言！");exit;}

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
  $emsg = "姓名: $f_user 於 $thistime\n";
  if ($f_email != "") $emsg .= "邮箱: $f_email\n";
  if ($oMsgs->url != "") $emsg .= "主页: $f_url\n";
  $emsg .= "留言内容: \n$rawcomment\n\n";
  $emsg .= "$oBooks->title($prgurl/$gburl?id=$id)\n\n\n";
  $emsg .= "程序制作：zChain.net(http://www.zchain.net)\n";
  $emsg .= "免费留言簿服务由 $hostname($hosturl) 提供\n\n";
  @mail($oBooks->email,"Subject: $oBooks->title 新留言通知",$emsg,"From: $f_email");
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

 $promptMsg=($keyword!="") ? "搜索结果" : "留言总数";
 $prompt=($keyword!="") ? "&action=find&search=".urlencode($keyword) : "";
 $prevPg=($page>1)?$page-1:0;
 $prevMsg=($prevPg>0)?"href=$gburl?id=$id&page=$prevPg{$prompt}":"";
 $nextPg=($page<$pages)?$page+1:0;
 $nextMsg=($nextPg>0)?"href=$gburl?id=$id&page=$nextPg{$prompt}":"";
 print <<<EOT
<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bookNavigator">
<tr><td><form method=post action=$gburl?action=find&id=$id>
搜索关键字：<input name="search" size="20" class="plainInput">
<input class="plainButton" type="submit" value="搜索" name="submit"></form></td>
<td align="right">
 <form action=$gburl?id=$id{$prompt} method=post>
 <a $prevMsg>&lt;&lt;</a>
 第 <input size="2" value="$page" name="page" class="plainInput"> / $pages 页
 <a $nextMsg>&gt;&gt;</a>
 [{$promptMsg}：<em>$size</em>]</form></td></tr>
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

 if(!$ck_pass){errorview("您的密码错误！");exit;}

 if(!$oMsgs->setAbsolutePosition($msgid)){errorview("指定留言错误！");exit;}
 if(!$oMsgs->delete()){errorview("留言删除错误！");exit;}
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

 $mgrPrompt=($ck_pass)?"<a href='$gburl?action=logout&id=$id'>[注销]</a>":"<a onclick='showDlg(); return false;' href='#'>[管理]</a>";

 $ctrl_save="<INPUT name=f_save type=checkbox value=1".($oFormMsg->save?' CHECKED':'').">保存我的信息";
 
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
输入管理密码：<input type=password class="plainInput" name=f_pass size=15><br><br>
<input type=submit value="确认" class="plainButton">
<input type="button" onclick="hideDlg();" value="关闭" class="plainButton">
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
  <a target="_blank" href="/?op=regedit">[修改]</a>
  <a target=_blank href="/?op=reg">[申请]</a>
  <a href="#" onclick="showForm(1);return false;">&darr;[留言]</a>
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
 <a target="_blank" href="/?op=regedit">[修改]</a>
 <a target=_blank href="/?op=reg">[申请]</a>
 <a href="#" onclick="showForm(0);return false;">&uarr;[留言]</a>
</TD></TR></TABLE></TD></TR>
<TR><TD vAlign=top width="50%">
 <TABLE width="100%" class="msgFormBody">
 <TR><TD vAlign=top>
  <TABLE class="msgFormBody">
  <TR><TD><IMG src="$sturl/name.gif" width=16 height=16></TD>
  <TD>姓名</TD>
  <TD colspan="2"><INPUT class=plainInput maxLength=20 name=f_user value="$oFormMsg->user" onmouseover="set_at_end(this);"> *</TD></TR>
  <TR><TD><IMG border=0 src="$sturl/email.gif" width=16 height=16></TD>
  <TD>Email</TD>
  <TD colspan="2"><INPUT class=plainInput maxLength=40 name=f_email value="$oFormMsg->email"  size=28 onmouseover="set_at_end(this);"></TD></TR>
  <TR><TD><IMG border=0 src="$sturl/home.gif" width=16 height=16></TD>
  <TD>主页</TD>
  <TD colspan="2"><INPUT class=plainInput maxLength=40 name=f_url value="$oFormMsg->url" value="http://" size=28 onmouseover="set_at_end(this);"></TD></TR>
  <TR><TD><IMG border=0 src="$sturl/private.gif" width=16 height=16></TD>
  <TD>悄悄话</TD>
  <TD><INPUT name=f_secret type=radio value=1>是 <INPUT CHECKED name=f_secret type=radio value=0>否</TD>
EOT;

 if($OPTS['useicon']==1){
  $deficon=($oFormMsg->icon) ? "icon{$oFormMsg->icon}.gif" : "icon1.gif";
  print "<TD rowspan=2> <IMG id=idface src='$imgurl/$deficon' height=48 width=48>
   <A target=_blank href='?action=iconlist&id=$id'>头像列表</A></TD>";
  print "</TR>";

  print "<TR><TD align=left><IMG src='$sturl/face.gif' height=16 width=16></TD>";
  print "<TD>选头像</TD><TD><SELECT class=plainInput name=f_icon size=1
   onChange=\"document.images['idface'].src='$imgurl/icon'+options[selectedIndex].value+'.gif';\">";
  for($i=1;$i<=$OPTS['numicon'];$i++){
   $selected = ($oFormMsg->icon == $i) ? 'selected' : '';
   print "<OPTION value=$i $selected>头像{$i}</OPTION>";
  }
  print "</SELECT></TD></TR>";
 } else {
  print "<TD rowspan=2></TD>";
  print "</TR>";
 }

 print <<<EOT
  </TABLE></TD>
  <TD align=left valign=top>
   <IMG height=15 width=15 src="$sturl/pen.gif"> 留言 * &nbsp;&nbsp;（最大：2000；已用：
   <input class=plainInput type=text name=used size=3 maxlength=4 value="0" disabled>）
   <BR>
   <TEXTAREA class=plainInput cols=50 name=f_comment rows=5 title=最大留言字数2000
    onKeyDown="count_char(this,this.form.used);"
    onKeyUp="count_char(this,this.form.used);"></TEXTAREA>
   <BR>
请输入右图中的验证码：<INPUT class=plainInput size=10 name=f_authcode>
<img src=authimg.php?authcode=$authcode align=absbottom>
<input type=hidden name=f_authmd5 value=$authmd5>
  </TD></TR></TABLE>
 </TD></TR>
 <TR><TD>
  <TABLE align=center border=0 cellPadding=0 cellSpacing=0 width="100%" class="msgFormCaption">
  <TR><TD align=center>
   $ctrl_save&nbsp;&nbsp;&nbsp;&nbsp;
   <INPUT class=plainButton name=Submit type=submit value="发送留言">
   &nbsp;&nbsp;&nbsp; <INPUT class=plainButton name=Submit2 type=reset value="清除留言">
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

 $indicator=($msgNo>0)?"第 $msgNo 条留言":"待回复留言";
 print <<<EOT
<table border="0" cellpadding="0" cellspacing="1" width="100%" class="msg">
<tr><td>
 <table border="0" cellpadding="0" cellspacing="0" width="100%" class="msgCaption">
 <tr><td width="100" align="center" valign="middle">$indicator</td>
 <td valign="middle">发表于 $oMsg->time</td>
 <td align="right" valign="middle">
EOT;

 if($msgNo > 0){
  echo "<a href=$gburl?action=find&id=$id&search=$urluser><img src=$sturl/find.gif style='border: 0px' alt='搜索{$oMsg->user}所写过的留言'></a> \n";
 }
 if($oMsg->email != ""){
  $memail=($ck_pass) ? "href=mailto:$oMsg->email" : "";
  $malt=($ck_pass) ? $oMsg->email : "保密";
  echo "<a $memail><img src=$sturl/email2.gif style='border: 0px' alt={$oMsg->user}的email：$malt></a> \n";
 }
 if($oMsg->url != ""){
  echo "<a href=$oMsg->url target=_blank><img src=$sturl/home2.gif style='border: 0px' alt=主页></a> \n";
 }
 $mip=($ck_pass) ? $oMsg->ip : "保密";
 echo "<img src=$sturl/ip.gif alt='{$oMsg->user}的IP地址：$mip'></a> \n";
 if($msgNo > 0){
  echo "<a href=$gburl?action=reply&id=$id&mid=$oMsg->msgid><img src=$sturl/reply.gif style='border: 0px' alt=回复留言></a> \n";
  if($ck_pass){
   echo "<a href=$gburl?action=delmsg&id=$id&mid=$oMsg->msgid&search=$keyword&page=$page onClick='return confirm_del();'><img src=$sturl/del.gif style='border: 0px' alt=删除此留言></a>\n";
  }
 }

	$tmpMsg1=(($oMsg->secret != 1) || ($ck_pass)) ? $oMsg->comment : '悄悄话留言...';
	$tmpTip1=(($oMsg->secret == 1) && ($ck_pass)) ? '<font color=red>&lt;悄悄话留言&gt;</font><BR>' : '';
	if (($oMsg->replysecret != 1) || ($ck_pass)) {
		$tmpMsg2=($oMsg->reply) ? $oMsg->reply : '';
		if (($tmpMsg2!='') && ($oMsg->replytime!='')) {
			$tmpMsg2="<font color=#ab00ac>版主回复</font> - "
				."<i><FONT color=#777777>$oMsg->replytime</font></i><br>".$tmpMsg2;
		}
	} else {
		$tmpMsg2='悄悄话回复...';
	}
	$tmpTip2=(($oMsg->replysecret == 1) && ($ck_pass)) ? '<font color=red>&lt;悄悄话回复&gt;</font><BR>' : '';
	if ($tmpMsg2) { $tmpTip2 = '<BR><BR>'.$tmpTip2; }

 // filter content
 if (!$ck_pass) {
  $tmpMsg1=str_replace('陈昕峰', '---', $tmpMsg1);
  $tmpMsg2=str_replace('陈昕峰', '---', $tmpMsg2);
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
  $cmdLine="<a href=$gburl?action=editform&id=$id&mid=$oMsg->msgid>[修改留言]</a><BR><BR>";
 } elseif (($oMsg->ip == $userip) && (($timestamp-strtotime($oMsg->time))<(3600*12))) {
  $cmdLine="<a href=$gburl?action=editform&id=$id&mid=$oMsg->msgid>[修改我的留言]</a><BR><BR>";
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
<html><head><title>留 言 完 成 !</title>
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
<span style="font-size: 10.5pt;line-height: 13pt">- 留 言 完 成 -</span><BR></td></tr>
<tr><td align=center><a href=$url>[ 回 留 言 簿 ]</a>&nbsp;&nbsp;&nbsp;<a href=$oBooks->url>[ 回 到 主 页 ]</a></td></tr>
</table>2秒钟后自动返回......<br><br><br>
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
$delay 秒钟后自动跳转，点击 <a href=$url>此处</a> 立即跳转
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