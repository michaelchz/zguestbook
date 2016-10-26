<?php


/* 加载xingTemplate类  */
include 'core.xingTemplate_class.php';

 /* 加载xingTemplate 报错功能 */
include 'core.xingTemplate_debug.php';

/* 设置模板引擎配置(数组) */

$xingTemplate_set = array(

/* 模板语法前后标示符 */
	'left_tag' => '{',
	'right_tag' => '}',

/* 模板路径，以default为默认模板 (详细使用方法，请查看官方手册) */
	'templateDir' => array(
			'default' => 'template',
			'default2' => 'template2'
			),

/* 默认使用模板,此与模板路径键对应  */
	'template_Name' => 'default',

/* 模板文件后缀名 */
	'templateExt' => '.html',

/* 是否持续编译模板 (用于调试时用) */
	'force_compile' => false,

/* 是否开启直接插入PHP代码 */
	'PHP_off' => false,

/* 定义模板编译目录,结尾不要加斜杠 '/' */
	'templateCompileDir' => '../../tmp/template_compile',

/* 模板编译文件的后缀名 */
	'templateCompileExt' => '.phpc',

/* 是否使用输出缓存 */
	'cache_is' => false,

/* 输出缓存标示符 默认为 当前URL 的MD5值 */
/*
 * 此功能,在您在调用输出缓存时,需要指定的,以防止模板缓存重复,以影响您的程序输出
 * 可在使用时,自行定义
 *
 */
	'cacheId' => md5($_SERVER['REQUEST_URI']),

/* 输出缓存时间 单位秒 */
	'cache_time' => 5,
			 	
/* 输出缓存目录,结尾不要加斜杠 '/' */
	'templateCacheDir' => 'xingTemplate_Cache',

/* 缓存子目录,相对缓存目录的相对路径 (多级子目录存放缓存),结尾不要加斜杠 '/' ,例如: dir1/dir2 */
/* 如果您的缓存量上万级别，可以使用，在每个模板使用 setConfig 调用前设置，模板缓存就会根据目录分类存放。*/
	'templateCacheODir' => '',

/* 输出缓存文件后缀名 */
	'templateCacheExt' => '.phpo',

/* 扩展功能(Function)插件存放路径,结尾不要加斜杠 '/' */
	'templatePluginsDir' => 'xingTemplate_Plugins',

/* 由本程序所创建的目录权限 代码 */
	'dir_mode' => 0777,

/* 被编译模板文件的大小限制 单位 M */
	'file_max' => 1,

/* 开启Gzip传输,提高传输速度 (此功能只在使用display是适用) */
	'gzip_off' => false,

/* 兼容选项，如果模板引擎输出空白，请开启此项 */
	'compatible' => false,

/* 此为调试时开启，可自动弹出一个窗口，窗口里为 xingTemplate 模板引擎的所有配置文件，包括注入的资源 (方便调试) */
	'debug' => false,

/* 是否存在已定义的 error_reporting */
	'error_reporting' => false

/* 默认的提示语言为中文, 您可以编写简单的语言包,为此程序增加提示语言的可读性 */

	);


/* 类实例化，引入配置 $xingTemplate_set / 亦可以使用 $xingTemplate->setConfig($xingTemplate_set) */
$xingTemplate = new xingTemplate($xingTemplate_set);

?>