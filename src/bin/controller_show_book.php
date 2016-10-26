<?php

class CShowBookController {
	
	private $xingTemplate;
	
	static function getController() {
		return new CShowBookController();
	}

	function execute($context) {

		$this->xingTemplate = $context[xingTemplate];
		$this->showBook($context[id],"",$context[page]);

	}

	function showBook($id,$keyword,$page) {
		global $copyright,$OPTS,$gburl,$ck_pass,$notice;
		global $oBooks, $oMsgs;

		if($keyword) {$lines=$oMsgs->search($keyword);}
		$size=($keyword) ? count($lines) : $oMsgs->getRecordCount();

		if ($ck_pass) {
			// ���������¼���ҳ��ͷ��
			$this->printHeaderAdmin();
		} else {
			// �����ͨ�û���ҳ��ͷ��
			$this->printHeader();
		}

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
				$this->printMessage($size-$i, &$oMsgs, $keyword, $page);
			}else{
				$this->printMessage($size-$i, &$oMsgs, $keyword, $page);
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

	function printHeader()
	{
		global $imgurl,$gburl,$OPTS,$id,$oBooks,$ck_pass;

		// generate authcode, store it to session to be used by authimg.php
		srand((double)microtime()*1000000);
		while(($authcode=rand()%10000)<1000);
		$_SESSION['authcode'] = $authcode;
		$authmd5 = md5($authcode);

		$oFormMsg = new CFormMessage();
		GetFormCookie($oFormMsg);

		// ׼��ģ����Ҫ�Ĵ������
		$bookInfo = array (
			'title'=>$oBooks->title,
			'htmlt'=>$oBooks->htmlt,
			'url'=>$oBooks->url,
			'urlname'=>$oBooks->urlname,
			'id'=>$id,
		);
		
		$formVal = array (
			'user'=>$oFormMsg->user,
			'email'=>$oFormMsg->email,
			'url'=>$oFormMsg->url,
			'icon'=>$oFormMsg->icon,
			'save'=>$oFormMsg->save
		);
		
		// ���ģ�� gb_header
		$xingTemplate = $this->xingTemplate;		
		$xingTemplate->assign('authcode',$authcode);
		$xingTemplate->assign('authmd5',$authmd5);
		$xingTemplate->assign('btnurl',"$imgurl/$OPTS[btn]");
		$xingTemplate->assign('OPTS',$OPTS);
		$xingTemplate->assign('formVal',$formVal);
		$xingTemplate->assign('bookInfo',$bookInfo);
		$xingTemplate->display('gb_header');

	}

	function printHeaderAdmin()
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

		// ׼��ģ����Ҫ�Ĵ������
		$bookInfo = array (
			'title'=>$oBooks->title,
			'htmlt'=>$oBooks->htmlt,
			'id'=>$id,
		);
		
		// ���ģ�� gb_header_admin
		$xingTemplate = $this->xingTemplate;		
		$xingTemplate->assign('OPTS',$OPTS);
		$xingTemplate->assign('bookInfo',$bookInfo);
		$xingTemplate->display('gb_header_admin');
		
		print <<<EOT

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
	
	function printMessage($msgNo, $oMsg, $keyword, $page) {
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

}

?>