<?php
/*--------------------------------------------------------*\
 ���������Ա��б��¼��

 ��CBookList��Ļ����ϣ�Ϊ���Ա���¼���ӡ�������ʱ�䡱����
 �����ʱ��������Ҫ���ʶ�����Ա��ļ����ҽ��������ܵ���
 �����Ӵ��࣬���������ܷ��ʣ������������Ա���������Ӱ��
 \*--------------------------------------------------------*/
// ver 1.0 2009/12/22

//require_once("../bin/class_book_list_admin.php");

class CBookListAdmin extends CBookList {

	var $uptime='';
	
	function _explodeRecord($a_line){
		//���ø��෽�����ø����ж��������
		parent::_explodeRecord($a_line);

		//��ȡ������¼��Ӧ�����Ա��ļ���������ʱ��
		global $filepath;
		$this->uptime = filemtime("$filepath/{$this->name}.bok");
		if ($this->uptime == false) {
			//�����ļ�����,δ��ȡ����ʱ��
			$this->uptime='N/A';
		} else {
			$this->uptime=strftime("%Y-%m-%d", $this->uptime);
		}
	}

}

?>