function trim(inputString) {
 s=inputString.toString();
 s=s.replace(/^ +/, '').replace(/ +$/, '');
 return s;
}

function is_empty(inputString) {
 return (trim(inputString)=='');
}

function set_at_end(field) {
  if (field.createTextRange) {
    var r = field.createTextRange();
    r.moveStart('character', field.value.length);
    r.collapse();
    r.select();
  }
}

function get_cookie(Name) {
 var search = Name + "="
 var returnvalue = "";
 if (document.cookie.length > 0) {
  offset = document.cookie.indexOf(search)
  if (offset != -1) { 
   offset += search.length
   end = document.cookie.indexOf(";", offset);
   if (end == -1) end = document.cookie.length;
   returnvalue=unescape(document.cookie.substring(offset, end))
  }
 }
 return returnvalue;
}

function count_char(message,used) {
 used.value = message.value.length;
}

function confirm_del()
{
  return confirm("删除留言无法恢复，确认删除?");
}

function showDlg() {
 if(document.layers){
  document.layers['passForm'].visibility="show";
 } else if(document.getElementById){
  var obj = document.getElementById('passForm');
  obj.style.visibility = "visible";
 } else if(document.all){
  document.all.passForm.style.visibility="visible";
 }

 document.all.passForm.style.left=document.body.scrollLeft+window.event.clientX-10;
 document.all.passForm.style.top=document.body.scrollTop+window.event.clientY+10;
}

function hideDlg() {
 if(document.layers){
  document.layers['passForm'].visibility="hide";
 } else if(document.getElementById){
  var obj = document.getElementById('passForm');
  obj.style.visibility = "hidden";
 } else if(document.all){
  document.all.passForm.style.visibility="hidden";
 }
}

function showForm(disp) {
 if(document.layers){
  document.layers['simpForm'].visibility=(disp==0)?"show":"hide";
  document.layers['fullForm'].visibility=(disp==0)?"hide":"show";
 } else if(document.getElementById){
  var obj = document.getElementById('simpForm');
  obj.style.display = disp ? "none" : "";
  obj = document.getElementById('fullForm');
  obj.style.display = disp ? "" : "none";
 } else if(document.all){
  document.all.simpForm.style.display=(disp==0)?"":"none";
  document.all.fullForm.style.display=(disp==0)?"none":"";
 }
 document.cookie="ck_disp="+disp;
}

function validate_signForm() {
 if(is_empty(signForm.f_user.value) || is_empty(signForm.f_comment.value)){
  alert('您的姓名／留言内容没有填写！');
  event.returnValue=false;
  return;
 }
 if(signForm.f_comment.value.length > 2000) {
  alert('您的留言内容超过了字数限制！');
  event.returnValue=false;
 }
 if(signForm.f_secret[0].checked && is_empty(signForm.f_email.value)){
  event.returnValue=confirm("您没有留email地址，将无法接收版主回复，确认发送留言?");
 }
}