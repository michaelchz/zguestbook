<?php
// global $OPTS,$copyright,$gburl,$ck_pass;
// global $oBooks,$oMsgs;

if (!$oMsgs->setAbsolutePosition($_REQUEST['mid'])) {
	errorview('留言不存在或已被删除!');
	exit;
} elseif (!$ck_pass) {
	if($oMsgs->ip != $userip){
		errorview('无权修改, IP地址不匹配!');
		exit;
	}elseif(($timestamp-strtotime($oMsgs->time))>(3600*12)){
		errorview('无权修改, 距离留言时间已大于12小时!');
		exit;
	}
}

if ($_SESSION['action'] == 'editform') {
	gb_edit_form($oBooks,$oMsgs,$id,$mid);
} elseif ($_SESSION['action'] == 'editcommit') {
	gb_edit_commit($oBooks,$oMsgs);
} else {
	exit('gb_edit: Wrong action code');
}

function gb_edit_form($oBooks,$oMsgs,$id,$mid) {
	global $OPTS,$copyright,$gburl,$imgurl;

	if(!$oMsgs->setAbsolutePosition($mid)){
		errorview("消息不存在或已被删除!");
		exit;
	}

	$sturl="$imgurl/{$OPTS['btn']}";
	
	$check_1 = ($oMsgs->secret) ? 'checked' : '';
	$check_2 = ($oMsgs->secret) ? '' : 'checked';
	$comment=str_replace("<br>","\n",$oMsgs->comment);
	
	print <<<EOT
<HTML>
<HEAD>
<TITLE>$oBooks->title【编辑留言】</TITLE>
<META content="text/html; charset=UTF-8" http-equiv=Content-Type>
<link rel="stylesheet" type="text/css" href="style/{$OPTS['css']}">
<script language="javascript" src="bin/gb.js"></script>
</HEAD>

<BODY class="book">
$oBooks->htmlt

<DIV class="bookFrame">

<FORM NAME="signForm" ACTION="$gburl" METHOD="post" onsubmit="validate_signForm();">
<input type="hidden" name="action" value="editcommit">
<input type="hidden" name="id" value="$id">
<input type="hidden" name="mid" value="$mid">
<TABLE ID="fullForm" cellPadding=0 cellSpacing=1 width=100% class="msgForm">
<TR><TD>
 <TABLE border=0 width="100%" class="msgFormCaption">
 <TR><TD>&gt;&gt;&gt; <a href="$oBooks->url">$oBooks->urlname</a>
 &gt;&gt; <a href="$gburl?id=$id">$oBooks->title</a>
 &gt;&gt; 编辑留言</TD></TR></TABLE>
</TD></TR>
<TR><TD vAlign=top width="50%">
 <TABLE width="100%" class="msgFormBody">
 <TR><TD vAlign=top>
  <TABLE class="msgFormBody">
  <TR><TD><IMG src="$sturl/name.gif" width=16 height=16></TD>
  <TD>姓名</TD>
  <TD colspan="2"><INPUT class=plainInput maxLength=20 name=f_user value="$oMsgs->user" onmouseover="set_at_end(this);"> *</TD></TR>
  <TR><TD><IMG border=0 src="$sturl/email.gif" width=16 height=16></TD>
  <TD>Email</TD>
  <TD colspan="2"><INPUT class=plainInput maxLength=40 name=f_email value="$oMsgs->email" size=28 onmouseover="set_at_end(this);"></TD></TR>
  <TR><TD><IMG border=0 src="$sturl/home.gif" width=16 height=16></TD>
  <TD>主页</TD>
  <TD colspan="2"><INPUT class=plainInput maxLength=40 name=f_url value="$oMsgs->url" size=28 onmouseover="set_at_end(this);"></TD></TR>
  <TR><TD><IMG border=0 src="$sturl/private.gif" width=16 height=16></TD>
  <TD>悄悄话</TD>
  <TD><INPUT $check_1 name=f_secret type=radio value=1>是 <INPUT $check_2 name=f_secret type=radio value=0>否</TD>
EOT;

	if($OPTS['useicon']==1){
		print "<TD rowspan=2> <IMG id=idface src='$imgurl/icon{$oMsgs->icon}.gif' height=48 width=48>
			<A target=_blank href='bin/iconlist.php'>头像列表</A></TD>";
		print "</TR>";

		print "<TR><TD align=left><IMG src='$sturl/face.gif' height=16 width=16></TD>";
		print "<TD>选头像</TD><TD><SELECT class=plainInput name=f_icon size=1
			onChange=\"document.images['idface'].src='$imgurl/icon'+options[selectedIndex].value+'.gif';\">";
		for($i=1;$i<=$OPTS['numicon'];$i++){
			$selected = ($oMsgs->icon == $i) ? 'selected' : '';
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
   <IMG height=15 width=15 src="$sturl/pen.gif"> 留言 *<BR>
   <TEXTAREA class=plainInput cols=50 name=f_comment rows=5 title=最大留言字数1000>$comment</TEXTAREA>
  </TD></TR></TABLE>
 </TD></TR>
 <TR><TD>
  <TABLE align=center border=0 cellPadding=0 cellSpacing=0 width="100%" class="msgFormCaption">
  <TR><TD align=center>
   <INPUT class=plainButton name=Submit type=submit value="确认修改">
   &nbsp;&nbsp;&nbsp; <INPUT class=plainButton name=Submit2 type=reset value="恢复留言">
  </TD></TR></TABLE>
</TD></TR></TABLE></FORM>
</DIV>
$oBooks->htmlb

<DIV class="bookFrame">
<table border=0 cellpadding=0 cellspacing=0 width=100% class=bookNavigator>
<tr><td align=center>$copyright</td></tr></table>
</DIV>

</BODY></HTML>
EOT;

}

function gb_edit_commit($oBooks, $oMsgs){
	global $thistime;

	if($_POST['f_user'] == ""){errorview("姓名不能为空！");exit;}
	$comment=$_POST['f_comment'];
	if($comment == ""){errorview("留言内容不能为空！");exit;}
	if(strlen($comment) > 2000 ){errorview("留言内容不能大于1000个汉字！");exit;}
	$email=$_POST['f_email'];
	if($email != ""){
		if(!preg_match("/.*\@.*\..*/i",$email)){errorview("Email格式错误！");exit;}
	}

	$comment=htmlspecialchars($comment);
	$comment=stripslashes($comment);
	$comment=str_replace("\r","",$comment);
	$comment=str_replace("\t","--",$comment);
	$comment=str_replace("\n","<br>",$comment);
	$comment=$comment."<br><br>[留言修改于: $thistime]";

	$user=stripslashes($_POST['f_user']);
	$user=htmlspecialchars($user);
	$url=stripslashes($_POST['f_url']);
	$url=(trim($url)!="http://") ? $url : "";
	$url=htmlspecialchars($url);

	$oMsgs->user=$user; $oMsgs->email=$email; $oMsgs->icon=$_POST['f_icon'];
	$oMsgs->url=$url;
	$oMsgs->comment=$comment;
	$oMsgs->secret=$_POST['f_secret'];
	$oMsgs->update();
}

?>