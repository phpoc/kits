<?php 
include_once "/lib/sd_340.php";
include_once "/lib/sn_tcp_ws.php";
include_once "config.php";
include_once "/lib/sc_envu.php";

set_time_limit(30);

$ws_host = _SERVER("HTTP_HOST");

$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

if(!($title_home = envu_find($envu, "title_home")))
	$title_home = "Temperature";
?>

<!DOCTYPE html>
<html>
<head>
	<title>PHPoC / <?echo system("uname -i")?></title>
	<meta content="initial-scale=0.5, maximum-scale=1.0, minimum-scale=0.5, width=device-width" name="viewport">
	<link type="text/css" rel="stylesheet" href="common.css">
</head>
<body>
<div class="temperature_wrap">
	
		<div class="midHeader">
			<center>
				<h1 class="headerTitle"><?php echo $title_home;?></h1>
				<div class="headerMenu">
					<div class="left">
					</div>
					<div class="right">
					</div>
				</div>
			</center>
		</div>
		
		<div class="subHeader">
		</div>		
	
    <div class="index_contents">
        <a href="setup_net.php">Setup</a><br/>
        <a href="index_graph.php">Realtime Graph</a><br/>
        <a href="index_webconsole.php">Realtime Web Console</a>
    </div>
   
	<div class="footer">
		<div class="superFooter">
			<a href="http://www.sollae.co.kr/kr/home/" target="_blank">SOLLAE SYSTEMS</a>	
		</div>
	</div>
</div>	
</body>
</html>
