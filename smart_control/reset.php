<?php
include_once "/lib/sc_envu.php";

$envu = envu_read("envu");
$btn_str = "A=>0=>0=>0=>0\r\nB=>0=>0=>0=>0\r\nC=>0=>0=>0=>0\r\nD=>0=>0=>0=>0\r\nE=>0=>0=>0=>0\r\nF=>0=>0=>0=>0\r\nG=>0=>0=>0=>0\r\nH=>0=>0=>0=>0\r\nI=>0=>0=>0=>0";
$sche_str = "";
$envu = "$btn_str\r\n\r\n$sche_str";
envu_write("envu", $envu, strlen($envu), 0);
$envu =  envu_read("envu");
system("reboot php 100");
?>
<html>
<head>
<script>
function pageReload()
{
 	window.location.href = "index.php?mode=2";
}
window.setTimeout("pageReload()", 1000);
</script>
</head>
<body>
</body>
</html>