<?php
/*--------------------------------------------------------*\
 ������Բ����û��� zChain GuestBook v4.00
 
 ���ߣ�zChain (http://www.zchain.net)
 ��Ȩ����(c) 2001-2003

 ������Ϊ������������������������������������GNUͨ��
 ������Ȩ����涨���ͱ�������Ϊɢ����/���޸ģ�����������
 ���Ǳ���Ȩ�ĵڶ����������ѡ��ģ���һ�պ��еİ汾��

 ������ϵ����ʹ��Ŀ�Ķ�����ɢ����Ȼ�������κε������Σ�
 �಻�������Ի��ض�Ŀ�ĵ���������Ĭʾ���������������
 GNUͨ�ù�����Ȩ��

 ��Ӧ���յ��渽�ڱ������GNUͨ�ù�����Ȩ�ĸ��������ޣ���
 д���������������᣺
 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA

 ������Ϊ�����������������GNUͨ�ù�����Ȩ����涨������
 �޸ġ�ʹ����ɢ�������򣬵����뱣����������վ�����ӡ�
\*--------------------------------------------------------*/
######################## �趨���� ########################
error_reporting(0);

$admname   = "admin";        # վ������
$admpass   = "prcanada";        # վ������
$hostname  = "zChain���Ա�����";  # ��ҳ����
$hosturl   = "http://gb.zchain.com"; # ��ҳ��ַ

$imgurl    = "/img";       # ͼƬλ��(���治Ҫ��"/")
$prgurl    =  myurl();      # �����ļ��� URL·��(���治Ҫ��"/") myurl()=�Զ�̽��
$filepath  = "./ZDB_c3416034cf9f7cc8";     # �����ļ�������·��(���治Ҫ��"/")

$reglimit  = "100";           # ����ע������԰�������0 = ������

$smtphost = ""; # ʹ��SMTP, SMTP��������ַ
$smtpuser = ""; # SMTP�������û���
$smtppass = ""; # SMTP�������û�����

$OPTS['timesft']  = "8";  # Serverʱ��������Сʱ
$OPTS['perpage'] = "15";   # ÿҳ������
$OPTS['notify']  = "1";   # email֪ͨ����/�ظ���1 - ���ã�0 - ��ֹ
$OPTS['showdlg'] = "1";   # ��ʾ���ԶԻ���1 - ���ã�0 - ��ֹ
$OPTS['useicon'] = "1";   # ͷ��1 - ���ã�0 - ��ֹ
$OPTS['numicon'] = "22";  # ͷ����
$OPTS['css']   = "modern.css";  # CSS�ļ���
$OPTS['btn']   = "modern.btn";      # ��ť�������

######################## �趨���� ########################
## ���²��ֲ����޸� ##
######################
$gburl="gb.php";
$cginame="���Բ�";
$copyright = 
"<script type='text/JavaScript'>alimama_pid='mm_11098526_1049344_2302436'; alimama_titlecolor='0000FF';"
 ."alimama_descolor ='000000'; alimama_bgcolor='FFFFFF'; alimama_bordercolor='E6E6E6'; alimama_linkcolor='008000';"
 ."alimama_bottomcolor='FFFFFF'; alimama_anglesize='0'; alimama_bgpic='0'; alimama_icon='0'; alimama_sizecode='16'; "
 ."alimama_width=658; alimama_height=60; alimama_type=2; </script> "
 ."<script src='http://a.alimama.cn/inf.js' type=text/javascript> </script>"
 . "<br><br>���{$cginame}������ <b><a href=$hosturl target='_blank'>$hostname</a></b> �ṩ��"
 ."����������<b><a href='http://www.zchain.com' target='_blank'>zChain.com</a></b><br>"
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