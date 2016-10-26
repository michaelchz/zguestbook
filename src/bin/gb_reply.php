<?php
// global $OPTS,$copyright,$gburl,$ck_pass;
// global $oBooks,$oMsgs;

// ����ģ�崦������
define('BASEDIR', './');
include(BASEDIR.'/lib/xingTemplate/xingTemplate.php');

if (!$oMsgs->setAbsolutePosition($_REQUEST['mid'])) {
	errorview('���Բ����ڻ��ѱ�ɾ��!');
	exit;
}

if ($_SESSION['action'] == 'reply') {
	if ($oMsgs->reply && !$ck_pass) { errorview('�Բ���,�����ǰ��������޸Ļظ���'); exit; }
	gb_reply_form($oBooks,$oMsgs,$id,$mid);
} elseif ($_SESSION['action'] == 'replycommit') {
	if (!$ck_pass) { errorview('�Բ���,�����ǰ������ܻظ����ԣ�'); exit; }
	gb_reply_commit($oBooks,$oMsgs,$id,$mid);
} else {
	exit('gb_reply: Wrong action code');
}

function gb_reply_form($oBooks,$oMsgs,$id,$mid) {
	global $OPTS,$copyright,$gburl,$ck_pass,$imgurl;

	// ׼�����Ա������Ϣ
	$bookInfo = array(
		'url'=>$oBooks->url,
		'urlname'=>$oBooks->urlname,
		'title'=>$oBooks->title,
		'id'=>$id,
		'btnurl'=>"$imgurl/$OPTS[btn]"
	);
	
	// ׼�����ظ�������Ϣ

	// auto detect http link
	$pattern = "(http|https|ftp):(\/\/|\\\\\\\\)[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*"
   		."((\/|\\\\)[~_a-zA-Z0-9-]+)*(\.[~_a-zA-Z0-9-]+(#[~_a-zA-Z0-9-]+){0,1}){0,1}"
   		."((\/|\\\\)|(\?[~_a-zA-Z0-9-]+=[~_a-zA-Z0-9-]+(\&amp;[~_a-zA-Z0-9-]+=[~_a-zA-Z0-9-]+)*)){0,1}";
 	
   	$comment = $oMsgs->comment;
   	$comment = eregi_replace($pattern, " <a href='\\0' target=_blank>\\0</a> ", $comment);
   	$reply = $oMsgs->reply;
	$reply = eregi_replace($pattern, " <a href='\\0' target=_blank>\\0</a> ", $reply);
	
	$msg = array(
		'mid'=>$mid,
		'comment'=>$comment,
		'reply'=>$reply,
		'time'=>$oMsgs->time,
		'replytime'=>$oMsgs->replytime,
		'email'=>$oMsgs->email,
		'user'=>$oMsgs->user,
		'url'=>$oMsgs->url,
		'ip'=>$oMsgs->ip,
		'secret'=>$oMsgs->secret,
		'replysecret'=>$oMsgs->replysecret	
	);
	
	// ׼�����༭����
	$content = str_replace("<br>","\n",$oMsgs->reply);
	
	// ���ģ��gba_reply_form
	global $xingTemplate;
	$xingTemplate->assign('content',$content);
	$xingTemplate->assign('bookInfo',$bookInfo);
	$xingTemplate->assign('msg',$msg);
	$xingTemplate->display('gba_reply_form');
	
}

function gb_reply_commit(&$oBooks, &$oMsgs, $id, $mid) {
	global $copyright,$prgurl,$gburl,$hosturl,$hostname;
	global $thistime;

	$comment=$_POST['f_comment'];
	if($comment == ""){errorview('�ظ����ݲ���Ϊ�գ�');exit;}

	$rawcomment=stripslashes($comment);
	$comment=htmlspecialchars($rawcomment);
	$comment=str_replace("\r","",$comment);
	$comment=str_replace("\t","--",$comment);
	$comment=str_replace("\n","<br>",$comment);
	if ($oMsgs->reply) {
		$comment=$comment."<br><br>[�ظ��޸���: $thistime]";
	}

	if (!$oMsgs->replytime) {
		$oMsgs->replytime=$thistime;
	}

	$oMsgs->reply=$comment;
	$oMsgs->replysecret=$_POST['f_secret'];
	$oMsgs->update();
 
	if ($oMsgs->email != "") {
		$oldcomment = str_replace("<br>","\n",$oMsgs->comment);
		$oldcomment = html_entity_decode($oldcomment);

		$emsg = "���� � $thistime �ظ�����������\n\n";
		$emsg .= "�ظ�����: \n$rawcomment\n\n";
		$emsg .= "ԭ��������: \n$oldcomment\n\n";
		$emsg .= "$oBooks->title($prgurl/$gburl?id=$id)\n\n\n";
		$emsg .= "����������zChain.net(http://www.zchain.net)\n";
		$emsg .= "������Բ������� $hostname($hosturl) �ṩ\n\n";
		@mail("$oMsgs->email","Subject: {$oBooks->title} �ظ�����֪ͨ",$emsg,"From $oBooks->title");
	}
}

?>