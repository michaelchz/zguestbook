<?php
/*--------------------------------------------------------*\
 零点留言簿多用户版 zChain GuestBook v4.00
 
 作者：zChain (http://www.zchain.net)
 版权所有(c) 2001-2003

 本程序为自由软件；您可依据自由软件基金会所发表的GNU通用
 公共授权条款规定，就本程序再为散播与/或修改；无论您依据
 的是本授权的第二版或（您自行选择的）任一日后发行的版本。

 本程序系基于使用目的而加以散布，然而不负任何担保责任；
 亦不对适售性或特定目的的适用性作默示担保。详情请参照
 GNU通用公共授权。

 您应已收到随附于本程序的GNU通用公共授权的副本；如无，请
 写信至自由软件基金会：
 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA

 本程序为自由软件，您可以在GNU通用公共授权条款规定下自由
 修改、使用与散播本程序，但必须保留作者与网站的链接。
\*--------------------------------------------------------*/
######################## 设定部分 ########################
error_reporting(0);

$admname   = "admin";        # 站长名字
$admpass   = "prcanada";        # 站长密码
$hostname  = "zChain留言本服务";  # 主页名称
$hosturl   = "http://gb.zchain.com"; # 主页地址

$imgurl    = "/img";       # 图片位置(后面不要加"/")
$prgurl    =  myurl();      # 程序文件的 URL路径(后面不要加"/") myurl()=自动探测
$filepath  = "./ZDB_c3416034cf9f7cc8";     # 数据文件的物理路径(后面不要加"/")

$reglimit  = "100";           # 允许注册的留言板数量，0 = 无限制

$smtphost = ""; # 使用SMTP, SMTP服务器地址
$smtpuser = ""; # SMTP服务器用户名
$smtppass = ""; # SMTP服务器用户密码

$OPTS['timesft']  = "8";  # Server时区调整，小时
$OPTS['perpage'] = "15";   # 每页留言数
$OPTS['notify']  = "1";   # email通知留言/回复，1 - 启用；0 - 禁止
$OPTS['showdlg'] = "1";   # 显示留言对话框，1 - 启用；0 - 禁止
$OPTS['useicon'] = "1";   # 头像，1 - 启用；0 - 禁止
$OPTS['numicon'] = "22";  # 头像数
$OPTS['css']   = "modern.css";  # CSS文件名
$OPTS['btn']   = "modern.btn";      # 按钮风格名称

######################## 设定结束 ########################
## 以下部分不需修改 ##
######################
$gburl="gb.php";
$cginame="留言簿";
$copyright = 
"<script type='text/JavaScript'>alimama_pid='mm_11098526_1049344_2302436'; alimama_titlecolor='0000FF';"
 ."alimama_descolor ='000000'; alimama_bgcolor='FFFFFF'; alimama_bordercolor='E6E6E6'; alimama_linkcolor='008000';"
 ."alimama_bottomcolor='FFFFFF'; alimama_anglesize='0'; alimama_bgpic='0'; alimama_icon='0'; alimama_sizecode='16'; "
 ."alimama_width=658; alimama_height=60; alimama_type=2; </script> "
 ."<script src='http://a.alimama.cn/inf.js' type=text/javascript> </script>"
 . "<br><br>免费{$cginame}服务由 <b><a href=$hosturl target='_blank'>$hostname</a></b> 提供　"
 ."程序制作：<b><a href='http://www.zchain.com' target='_blank'>zChain.com</a></b><br>"
 ."Powered by zChain GuestBook v4.00a";

$cgiurl=$PHP_SELF;

$userip=$GLOBALS['REMOTE_ADDR'];

function getlocaltime($timesft)
{
 global $timestamp, $thistime, $thisdate, $ftime;

 $timestamp=time()+(3600*$timesft);
 $thistime=gmstrftime("%Y-%m-%d %H:%M:%S", $timestamp);
 $thisdate=gmstrftime("%Y-%m-%d", $timestamp);
 $ftime=gmstrftime("%Y%m%d%H%M%S", $timestamp);
}

function errorview($msg)
{
 print "<html><head></head><body><script>alert('$msg');history.back();</script></body></html>";
 exit;
}

function myurl()
{
  $server_port = ":".$GLOBALS['SERVER_PORT'];
  $server_name = $GLOBALS['SERVER_NAME'];
  $script_name = $GLOBALS['PHP_SELF'];
  if ($server_port == ":80") { $server_port="";}
  $fullcgiurl = "http://$server_name$server_port$script_name";
  return substr($fullcgiurl,0,strrpos($fullcgiurl,"/"));
}

function validpass($input, $saved)
{
 if (strlen($saved)==32) {
  return (md5($input)==$saved);
 } else {
  return ($input==$saved);
 }
}

?>