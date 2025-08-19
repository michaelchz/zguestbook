<?php



class xingTemplate
{
	// 引擎选项
	private $arrayConfig = array();
	
	/* 模板编译信息提示 默认中文 ( 如果特定语言包存在,将自动读取 ) */
	public $xingTemplate_Class_Lang = array(
																			' 模板文件不存在或读取失败',
																			' 模板文件大小超出限制',
																			' 模板文件没有正常加载',
																			'严重错误',
																			'程序警告',
																			'语法错误',
																			'文件名称',
																			'错误等级',
																			'错误所在',
																			'错误信息',
																			'缓存文件路径',
																			'错误源产生在',
																			'行'
																		);
	
	// 类实例化 (进行数组设置)
	public function __construct($arrayConfig = array())
	{		
		/* 获取当前类所在目录  */  
		$this->arrayConfig['classDir'] = dirname(str_replace('\\','/',__FILE__));

		/* 载入配置 */
		$this->arrayConfig += $arrayConfig;
		
		/* 获取当前类名称 (防止类名称修改,导致报错机制失败) */  
		$this->arrayConfig['ClassName'] = __CLASS__;
		
		/* 类被初始化时 自动读取语言包 (并判断语言包是否有效)  */
		global $_xingTemplate_Class_Lang;
		if (is_array($_xingTemplate_Class_Lang)) 
		{
			$this->xingTemplate_Class_Lang += $_xingTemplate_Class_Lang;
		}
		
		/* 载入扩展功能文件 */
		$Plugins = $this->get_Template_Plugins();
		if (is_array($Plugins))
		{
			foreach ($Plugins as $p_path){ include $p_path; }
		}
			
	}
	
	/* 设置引擎 */
	public function setConfig($key, $value = null)
	{
		if (is_array($key))
		{
			$this->arrayConfig += $key;
		}else{
			$this->arrayConfig[$key] = $value;
		}
	}
	
	/* 获取当前模板引擎配置 */
	public function getConfig($key = null)
	{
		if ($key)
		{
			return $this->arrayConfig[$key];
		}
		return $this->arrayConfig;
	}
	
	/* 向模板引擎中注入变量 */
	public function assign($key,$val = null)
	{
		if (empty($key)) return '';
		if (is_array($key))
		{
			foreach ($key as $k=>$v)
			{
				$this->arrayConfig['GLOBALS'][$k] = $v;
			}
		}else{
			$this->arrayConfig['GLOBALS'][$key] = $val;	
		}
	}
	
	/* 取得变量值 */
	function &get_Value($key)
	{
		if (!isset($this->arrayConfig['GLOBALS'][$key]))
		{
			$val = isset($GLOBALS[$key]) ? $GLOBALS[$key] : null;
			$this->assign($key, $val);
		}
			
		return $this->arrayConfig['GLOBALS'][$key];
	}
	
	/* 取得模板路径 */
	private function get_Template_Path($templateName)
	{
		if (is_string($this->arrayConfig['templateDir'])) $this->arrayConfig['templateDir'] = array('default'=>$this->arrayConfig['templateDir']);
		
		$path = $this->arrayConfig['templateDir'][(empty($this->arrayConfig['template_Name']) ? 'default' : $this->arrayConfig['template_Name'])].'/'.$templateName.$this->arrayConfig['templateExt'];

		if (file_exists($path))
		{
			return $path;
		}else{
			return $this->arrayConfig['templateDir']['default'].'/'.$templateName.$this->arrayConfig['templateExt'];
		}
	}
	
	/* 获取模板编译路径 */
	private function get_Template_Compile_Path($templateName)
	{
		return $this->arrayConfig['classDir'].'/'.$this->arrayConfig['templateCompileDir'].'/'.md5($this->get_Template_Path($templateName)).$this->arrayConfig['templateCompileExt'];
	}
	
	/* 获取模板缓存路径 */
	private function get_Template_Cache_Path($templateName, $all = false)
	{
		/* 多级缓存目录 */
		$templateName = $this->arrayConfig['templateCacheODir'].'/'.($templateName ? md5($templateName) : '');
		
		if ($all)
		{
			if ($all === true)
			{
				$tmp_path = $templateName.'*';
			}else{
				$tmp_path = $templateName.$all;
			}
		}else{
			$tmp_path = $templateName.$this->arrayConfig['cacheId'];
		}
		return $this->arrayConfig['classDir'].'/'.$this->arrayConfig['templateCacheDir'].'/'.$tmp_path.$this->arrayConfig['templateCacheExt'];
	}
	
	/* 获取扩展功能文件列表 */
	private function get_Template_Plugins()
	{
		if (is_dir($this->arrayConfig['classDir'].'/'.$this->arrayConfig['templatePluginsDir'].'/'))
		{
			return glob($this->arrayConfig['classDir'].'/'.$this->arrayConfig['templatePluginsDir'].'/*.php');
		}
	}
	
	/* 判断缓存输出是否有效/是否开启缓存输出  */
	public function is_cached($templateName)
	{
		$_PATH = $this->get_Template_Cache_Path($templateName);
		if (!file_exists($_PATH))
		{
			return false;
		}elseif (filemtime($_PATH) + $this->arrayConfig['cache_time'] < time()){
			return false;
		}else{
			return true;
		}
	}
	
	/* 读取文件 */
	private function template_Read($PATH)
	{
		if (function_exists('file_get_contents'))
		{
			return file_get_contents($PATH);
		}else{
			$fopen = fopen($PATH,'r');
			$template_Content = '';
			do {
				$data = fread($fopen,1024);
				if (strlen($data)===0) break;
				$template_Content .= $data;
			}while(1);
			fclose($fopen);
			
			return $template_Content;
		}
	}
	
	/* 写入文件 */
	private function template_Write($PATH,$String)
	{
		/* 调用递归创建目录  */
		$this->template_CreateDir(dirname($PATH));

			/* 以写入方式打开文件句柄,开启 flock  */
			$fopen = fopen($PATH,'w');
				flock($fopen, LOCK_EX + LOCK_NB);
				$fwrite = fwrite($fopen,$String);
					/* 失败重新尝试写入  */
					if (!$fwrite) $fwrite = fwrite($fopen,$String);
				flock($fopen, LOCK_UN + LOCK_NB);
			fclose($fopen);

		return $fwrite;
	}
	
	/* 循环创建目录 */
	private function template_CreateDir($Dir)
	{
		if (is_dir($Dir))
		 return true;
		
		if (@ mkdir($Dir, $this->arrayConfig['dir_mode']))
			return true;

		if (!$this->template_CreateDir(dirname($Dir)))
		 return false;
		
		return mkdir($Dir, $this->arrayConfig['dir_mode']);
	}
	
	/* 循环删除目录 */
	private function xingTemplate_rmDir($dir,$to = false)
	{
		if ($list = glob($dir.'/*'))
		{
			foreach ($list as $file)
			{
				is_dir($file) ? $this->xingTemplate_rmDir($file) : unlink($file);
			}
		}
		
		if ($to === false) rmdir($dir);
	}

	public function getMicrotime()
	{
		list($microtime_1,$microtime_2) = explode(' ',microtime());
		return $microtime_1 + $microtime_2;
	}

	/* 输出模板 */
	public function display($templateName, $key = '',$Clean = 0)
	{
		$this->xingTemplate_Display($templateName,$Clean,'display',$key);
	}
	
	/* 返回输出模板 */
	public function fetch($templateName, $key = '')
	{
		return $this->xingTemplate_Display($templateName,$Clean,'output',$key);
	}
	
	/* 输出模板 */
	private function xingTemplate_Display($templateName,$Clean = 0,$display = '',$key = '')
	{
		
		/* 定义错误信息 */
		if (!$this->arrayConfig['error_reporting'])
		{
			$xingTemplate_old_err = error_reporting();
				error_reporting(E_ERROR | E_WARNING | E_PARSE);
		}
		
		/* 设定模板 */
		if ($key)
		{
			$tmp_key = $this->arrayConfig['template_Name'];
			$this->arrayConfig['template_Name'] = $key;
		}
		
		/* 引擎运行时间统计  */
		$this->arrayConfig['Runtime'] = $this->getMicrotime();
		
		if ($this->arrayConfig['cache_is'])
			$_PATH = $this->get_Template_Cache_Path($templateName);
		
		if (!$this->is_cached($templateName) || $Clean)
		{	
			/* 将错误载入特定函数处理  */
			if ($this->arrayConfig['compatible'])
			{
				ob_start();
			}else{

				// 载入Debug类
				$class_debug = new xingTemplate_debug($this);
				
				ob_start(array($class_debug,'xingTemplate_xError'));
			}
					/* 进行模板编译   */
					include $this->xingTemplate_compile($templateName,$Clean);
					
					$xingTemplate_Content = ob_get_contents();
				ob_end_clean();

		}else{
			/* 读取缓存输出文件 */
			$xingTemplate_Content = $this->template_Read($_PATH);
		}

			 /* 判断是否可以写入缓存输出内容  */
			if ($this->arrayConfig['cache_is'])
			{
				/* 判断缓存输出是否有效 */
				if (!$this->is_cached($templateName))
					$this->template_write($_PATH,$xingTemplate_Content);
			}
		
			if ($this->arrayConfig['debug'])
			{
				unset($this->arrayConfig['GLOBALS'][$this->arrayConfig['ClassName']]);
				ob_start(); print_r($this->arrayConfig); $debug = ob_get_contents(); ob_end_clean();
				$xingTemplate_Content .= '<div id="Me" style="display:none;">'.highlight_string($debug,1).'</div>'.'<script type="text/javascript">var code=document.getElementById("Me").innerHTML;var newwin=window.open("","","height=600 ,width=500,scrollbars=yes");  newwin.opener = null ;newwin.document.write(code); newwin.document.close();</script>';		
			}
			
			switch ($display)
			{
				case 'display':
						echo $xingTemplate_Content;
						
						$xingTemplate_Content = null;
				break;
			}
			
			/* 返回执行时间 */
		$this->template_Runtime();
		
		if (isset($tmp_key)) $this->arrayConfig['template_Name'] = $tmp_key;

		if ($this->arrayConfig['gzip_off'] && str_contains($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'))
		{
			ob_start('ob_gzhandler');
		}
		
		/* 定义错误信息 */
		if (!$this->arrayConfig['error_reporting'])
			error_reporting($xingTemplate_old_err);
		
		return $xingTemplate_Content;
	}
	
	/*******************************************************************/
	/* 编译开始
	/*******************************************************************/
	
	
		/* 转换标示符 */
	private function ConverTag($Tag)
	{
		$_count = strlen($Tag);
		$new_array = array('{','}','[',']','$','(',')','*','+','.','?','\\','^','|');
		$Tag_ = '';
		for ($i=0;$i<$_count;$i++)
		{
			$Tag_ .= (in_array($Tag[$i],$new_array)?'\\':'').$Tag[$i];
		}
		return $Tag_;
	}

	/* 模板引擎编译 */
	private function xingTemplate_compile($templateName,$Clean = 0)
	{
		$_PATH = array();

		/* 取得有效模板路径  */
		$_PATH['From'] =  $this->get_Template_Path($templateName);
		$_PATH['Save'] =  $this->get_Template_Compile_Path($templateName);
		
		/* 判断模板文件是否存在 */
		if (!file_exists($_PATH['From'])) return $_PATH['From'].' {'.$templateName.$this->xingTemplate_Class_Lang[0].'}';

		/* 判断模板缓存文件是否需要更新  */
		if ($this->arrayConfig['force_compile']) $Clean = 1;
		
		if (!$Clean)
		{
			$_fromt = 0;
			if (file_exists($_PATH['From']))
				$_fromt = filemtime($_PATH['From']);
			$_savet = 0;
			if (file_exists($_PATH['Save']))
				$_savet = filemtime($_PATH['Save']);

			if ($_fromt <= $_savet)
			{
				return $_PATH['Save'];
			}
		}
		
		/* 判断模板文件大小限制 */
		if (filesize($_PATH['From']) > $this->arrayConfig['file_max'] * 1024 * 1024) return $this->xingTemplate_Error($templateName.$this->xingTemplate_Class_Lang[1].' ('.$this->arrayConfig['file_max'].' M)');
		
		/* 取得有效标示  */
		$_Left = '(?<!!)'.$this->ConverTag($this->arrayConfig['left_tag']);
		$_Right = '((?<![!]))'.$this->ConverTag($this->arrayConfig['right_tag']);
		
		/* 取得模板源 */
		$xingTemplate_Conver = $this->template_read($_PATH['From']);

		/* 如果模板为空,不进行编译 */
		if (empty($xingTemplate_Conver))
		{
			$this->template_Write($_PATH['Save'],$xingTemplate_Conver);
			return $_PATH['Save'];
		}
		
		/* **************模板进行相关编译起始******************* */
			/*
				Start// write by xbantu 2009-06-06
			*/
		$xingTemplate_Conver = trim($xingTemplate_Conver);
		
		preg_match_all('/'.$_Left.'xingTemplate (([\w|-|\/]{1,})|(\$([_a-zA-Z][\w]*)))'.$_Right.'/',$xingTemplate_Conver,$Include_);
		$Include_count = count($Include_[0]);
		
			/* 模板文件嵌套调用处理  */
		for ($i=0;$i< $Include_count;$i++)
		{
			/* 编译相应调入模板文件 */
			$xingTemplate_Conver = str_replace($Include_[0][$i],$this->arrayConfig['left_tag'].'eval include $this->xingTemplate_compile("'.$Include_[1][$i].'")'.$this->arrayConfig['right_tag'],$xingTemplate_Conver);
			
			/* 2009-06-07 放弃使用模板状态提示 */
			/*
				// 提示模板文件加载状态  
				$Include_Tmp_Name = $this->get_xingTemplate_path($Include_[1][$i]);	
				$xingTemplate_Conver = str_replace($Include_[0][$i],$this->xingTemplate_Left.' '.$Include_[1][$i].$this->xingTemplate_Class_Lang[2].$this->xingTemplate_Right,$xingTemplate_Conver);				
			*/
		}
		unset($Include_);
			
		 /* 获取模板所使用变量 */
		preg_match_all('/\$([_a-zA-Z][\w]*)/',$xingTemplate_Conver,$Global_var);

		if (is_array($Global_var[1]))
		{	
			$Global_var[1] = array_unique($Global_var[1]);

			$Global_var_Im = array('this','_GET','_POST','_COOKIE','_SERVER','_SESSION','_FILES','_ENV');
			$Global_var_out = '';

			foreach ($Global_var[1] as $val)
			{
				if (!in_array($val,$Global_var_Im))
				{
					$Global_var_out .= '$'.$val.' =& $this->get_Value(\''.$val.'\'); ';

				}
			}
		}

		 /* 相关标签转换 */
		$Template_preg = array();
		$Template_Replace = array();

		 /* 判断是否允许插入PHP  */
		if ($this->arrayConfig['PHP_off'] === false)
		{
			$xingTemplate_Preg[] = '/<\?(=|php|)(.+?)\?>/is';
			
			$xingTemplate_Replace[] = '&lt;?\\1\\2?&gt;';
		}

			/*
			此类编译的语法
			_if 
			_elseif
			_else
			_for
			_while
			_foreach
			_eval
			_echo
			_print_r
			_变量输出
			*/ 
			
		$xingTemplate_Preg[] = '/'.$_Left.'(else if|elseif) (.*?)'.$_Right.'/i'; 
		$xingTemplate_Preg[] = '/'.$_Left.'for (.*?)'.$_Right.'/i'; 
		$xingTemplate_Preg[] = '/'.$_Left.'while (.*?)'.$_Right.'/i'; 
		$xingTemplate_Preg[] = '/'.$_Left.'(loop|foreach) (.*?)'.$_Right.'/i'; 
		$xingTemplate_Preg[] = '/'.$_Left.'if (.*?)'.$_Right.'/i'; 
		$xingTemplate_Preg[] = '/'.$_Left.'else'.$_Right.'/i'; 
		$xingTemplate_Preg[] = '/'.$_Left."(eval|_)( |[\r\n])(.*?)".$_Right.'/is';
		$xingTemplate_Preg[] = '/'.$_Left.'_e (.*?)'.$_Right.'/is';
		$xingTemplate_Preg[] = '/'.$_Left.'_p (.*?)'.$_Right.'/i';
		$xingTemplate_Preg[] = '/'.$_Left.'\/(if|for|loop|foreach|eval|while)'.$_Right.'/i'; 
		$xingTemplate_Preg[] = '/'.$_Left.'((( *(\+\+|--) *)*?(([_a-zA-Z][\w]*\(.*?\))|\$((\w+)((\[|\()(\'|")?\$*\w*(\'|")?(\)|\]))*((->)?\$?(\w*)(\((\'|")?(.*?)(\'|")?\)|))){0,})( *\.?[^ \.]*? *)*?){1,})'.$_Right.'/i'; 
		$xingTemplate_Preg[] = "/(	| ){0,}(\r\n){1,}\";/"; 
		$xingTemplate_Preg[] = '/'.$_Left.'(\#|\*)(.*?)(\#|\*)'.$_Right.'/'; 
		$xingTemplate_Preg[] = '/'.$_Left.'\%([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)'.$_Right.'/';


			/* 编译为相应的PHP文件语法 _所产生错误在运行时提示  */
		$xingTemplate_Replace[] = '<?php }else if (\\2){ ?>';
		$xingTemplate_Replace[] = '<?php for (\\1) { ?>';
		$xingTemplate_Replace[] = '<?php while (\\1) { ?>';
		$xingTemplate_Replace[] = '<?php $__i = 0; foreach ((array)\2) { $__i++; ?>';
		$xingTemplate_Replace[] = '<?php if (\\1){ ?>';
		$xingTemplate_Replace[] = '<?php }else{ ?>';
		$xingTemplate_Replace[] = '<?php \\3; ?>';
		$xingTemplate_Replace[] = '<?php echo \\1; ?>';
		$xingTemplate_Replace[] = '<?php print_r(\\1); ?>';
		$xingTemplate_Replace[] = '<?php } ?>';
		$xingTemplate_Replace[] = '<?php echo \\1;?>';
		$xingTemplate_Replace[] = '';
		$xingTemplate_Replace[] = '';
		$xingTemplate_Replace[] = '<?php echo $this->lang_array[\'\\1\'];?>';

		
		  /* 在有必要时 开启 */  
		//ksort($xingTemplate_Preg);
		//ksort($xingTemplate_Replace);
				
			/* 执行正则分析编译 */
		$xingTemplate_Conver=preg_replace($xingTemplate_Preg,$xingTemplate_Replace,$xingTemplate_Conver);
		
			/* 过滤敏感字符 */ 
		$xingTemplate_Conver = str_replace(array('!'.$this->arrayConfig['right_tag'],'!'.$this->arrayConfig['left_tag'],'?><?php'),array($this->arrayConfig['right_tag'],$this->arrayConfig['left_tag'],''),$xingTemplate_Conver);

			/* 整理输出缓存内容  */
		if ($Global_var_out)
		{
			$xingTemplate_Conver = "<?php $Global_var_out ?>\r\n".$xingTemplate_Conver;
		}
		
		$this->template_write($_PATH['Save'],$xingTemplate_Conver);
		
		/*
			End conver;
		*/
		/* ***************模板进行相关编译结束******************** */
		
		return $_PATH['Save'];

	}


	/* 类错误信息输出 */
	private function xingTemplate_Error($Msg)
	{
		echo $Msg;
		
		/*exit;*/
	}
	

	
	/* 清理缓存输出 或缓存 */
	public function xingTemplate_clean($type = 'cache')
	{
		
		switch ($type)
		{
			/* 判断是否是输出缓存 */
			case 'cache':
			/* 2009.8.22 预丢弃
				echo $_PATH = dirname($this->get_Template_Cache_Path('_'));
				$_END = $this->arrayConfig['templateCacheExt'];
			
				$_PATH_ = glob($_PATH.'/*'.$_END);
			*/
				$this->xingTemplate_rmDir($this->arrayConfig['classDir'].'/'.$this->arrayConfig['templateCacheDir'], true);
				return true;
			break;
			
			/* 判断是否是模板缓存 */
			case 'compile':
			/* 2009.8.22 预丢弃
				$_PATH = dirname($this->get_Template_Compile_Path('_'));
				$_END = $this->arrayConfig['templateCompileExt'];
			
				$_PATH_ = glob($_PATH.'/*'.$_END);
			*/
				$this->xingTemplate_rmDir($this->arrayConfig['classDir'].'/'.$this->arrayConfig['templateCompileDir'], true);
				return true;
			break;
		}
		
		 /* 判断是否是输出缓存 */
		if (!empty($type) && empty($_PATH))
		{
			$_PATH = $this->get_Template_Cache_Path($type, true);
			
			$_PATH_ = glob($_PATH);
		}
		
		if (is_array($_PATH_))
		{
			$j = 0;
			foreach ($_PATH_ as $val)
			{
				if (file_exists($val))
				{
					unlink($val);
					$j ++;
				}
			}
				
			return $j;
		}else{
			return false;
		}
		
		return false;
	}
	
	/* 获取实时模板引擎运行时间 */
	public function template_Runtime()
	{
		/* 返回执行时间 */
		return round($this->getMicrotime() - $this->arrayConfig['Runtime'],5);
	}

  /* 释放资源 */ 
	public function __destruct()
	{
		$this->arrayConfig = null;
	}
	
	//类结束

}




?>