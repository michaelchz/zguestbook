<?php

include('setup.php');

echo "<HTML><HEAD><TITLE>Long time execution log</TITLE></HEAD><BODY>\n\n";

$result = file("$filepath/longtime.log");

foreach($result as $line) {
	$line = chop($line);
	$p = strstr($line, "ip=");
	$p = substr($p, 3);
	echo $line;
	echo " <a target=ipxml href=http://ip.flush.com.cn/searchbyXML.aspx?ip=$p>Locate</a><BR>";
}

echo "</BODY></HTML>";

?>