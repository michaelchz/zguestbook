<?php

print "<HTML><BODY><TABLE cellspacing=3>";

$cols=5;
$numicon=$OPTS['numicon'];
$rows=ceil($numicon/$cols);

for($i=0;$i<$rows;$i++){
 print "<TR>";
 for($j=0;$j<$cols;$j++){
  $iconid=$i*$cols+$j+1;
  if($iconid<=$numicon){
   print "<TD align=center><IMG border='0' src='$imgurl/icon{$iconid}.gif'><br><br>Í·Ïñ{$iconid}</TD>";
  }
 }
 print "</TR>";
}

print "</TABLE></BODY></HTML>";

?>