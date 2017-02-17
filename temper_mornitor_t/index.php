<?php 
set_time_limit(30);

include_once "/lib/sd_340.php";
include_once "config.php";
include_once "/lib/sc_envu.php";

$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

if(!($title_home = envu_find($envu, "title_home")))
	$title_home = "PHPoC X ThingSpeak";
if(!($channel_id = envu_find($envu, "channel_id")))
	$channel_id = "";
if(!($days = envu_find($envu, "days")))
	$days = "";
else
	$days = "&days=" . $days;
if(!($results = envu_find($envu, "results")))
	$results = "";
else
	$results = "&results=" . $results;
if(!($ymin = envu_find($envu, "ymin")))
	$ymin = "";
else
	$ymin = "&yaxismin=" . $ymin;
if(!($ymax = envu_find($envu, "ymax")))
	$ymax = "";
else
	$ymax = "&yaxismax=" . $ymax;
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
						<a href="index.php">HOME</a>| 
						<a href="setup_net.php">SETUP</a>
					</div>
					<div class="right">
					</div>
				</div>
			</center>
		</div>
		<div class="subHeader">
		</div>		
	
    <div class="index_contents">
		<iframe width="450" height="260" style="border: 1px solid #cccccc;" src="https://thingspeak.com/channels/<?echo $channel_id?>/charts/1?bgcolor=%23ffffff&color=%23d62020&dynamic=true&type=line<?echo $days?><?echo $results?><?echo $ymax?><?echo $ymin?>"></iframe>
    </div>
   
	<div class="footer">
		<div class="superFooter">
			<a href="http://www.sollae.co.kr/kr/home/" target="_blank">SOLLAE SYSTEMS</a>	
		</div>
	</div>
</div>	
</body>
</html>
