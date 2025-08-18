<?php
// global $OPTS,$copyright,$gburl,$ck_pass;
// global $oBooks,$oMsgs;

// 引入模板处理引擎
define('BASEDIR', './');
include(BASEDIR.'/lib/xingTemplate/xingTemplate.php');

if (!$oMsgs->setAbsolutePosition($_REQUEST['mid'])) {
	errorview('留言不存在或已被删除!');
	exit;
}

if ($_SESSION['action'] == 'reply') {
	if ($oMsgs->reply && !$ck_pass) { errorview('对不起,您不是版主不能修改回复！'); exit; }
	gb_reply_form($oBooks,$oMsgs,$id,$mid);
} elseif ($_SESSION['action'] == 'replycommit') {
	if (!$ck_pass) { errorview('对不起,您不是版主不能回复留言！'); exit; }
	gb_reply_commit($oBooks,$oMsgs,$id,$mid);
} else {
	exit('gb_reply: Wrong action code');
}

function gb_reply_form($oBooks,$oMsgs,$id,$mid) {
	global $OPTS,$copyright,$gburl,$ck_pass,$imgurl;

	// 准备留言本相关信息
	$bookInfo = array(
		'url'=>$oBooks->url,
		'urlname'=>$oBooks->urlname,
		'title'=>$oBooks->title,
		'id'=>$id,
		'btnurl'=>"$imgurl/$OPTS[btn]"
	);
	
	// 准备待回复留言信息

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
	
	// 准备待编辑内容
	$content = str_replace("<br>","\n",$oMsgs->reply);
	
	// 输出模板gba_reply_form
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
	if($comment == ""){errorview('回复内容不能为空！');exit;}

	$rawcomment=stripslashes($comment);
	$comment=htmlspecialchars($rawcomment);
	$comment=str_replace("\r","",$comment);
	$comment=str_replace("\t","--",$comment);
	$comment=str_replace("\n","<br>",$comment);
	if ($oMsgs->reply) {
		$comment=$comment."<br><br>[回复修改于: $thistime]";
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

		$emsg = "版主 於 $thistime 回复了您的留言\n\n";
		$emsg .= "回复内容: \n$rawcomment\n\n";
		$emsg .= "原留言内容: \n$oldcomment\n\n";
		$emsg .= "$oBooks->title($prgurl/$gburl?id=$id)\n\n\n";
		$emsg .= "程序制作：zChain.net(http://www.zchain.net)\n";
		$emsg .= "免费留言簿服务由 $hostname($hosturl) 提供\n\n";
		@mail("$oMsgs->email","Subject: {$oBooks->title} 回复留言通知",$emsg,"From $oBooks->title");
	}
}

?>