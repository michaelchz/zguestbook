<?php
/*--------------------------------------------------------*\
 管理用留言本列表记录类

 在CBookList类的基础上，为留言本记录增加“最后更新时间”属性
 因更新时间属性需要访问多个留言本文件，且仅被管理功能调用
 故增加此类，仅供管理功能访问，其它正常留言本操作不受影响
 \*--------------------------------------------------------*/
// ver 1.0 2009/12/22

//require_once("../bin/class_book_list_admin.php");

class CBookListAdmin extends CBookList {

	var $uptime='';
	
	function _explodeRecord($a_line){
		//调用父类方法设置父类中定义的属性
		parent::_explodeRecord($a_line);

		//获取本条记录对应的留言本文件的最后更新时间
		global $filepath;
		$this->uptime = filemtime("$filepath/{$this->name}.bok");
		if ($this->uptime == false) {
			//访问文件错误,未获取更新时间
			$this->uptime='N/A';
		} else {
			$this->uptime=strftime("%Y-%m-%d", $this->uptime);
		}
	}

}

?>