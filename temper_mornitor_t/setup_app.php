<?php
set_time_limit(30);

include_once "/lib/sd_340.php";
include_once "config.php";
include_once "/lib/sc_envu.php";

if((int)ini_get("init_net0"))
	$pid_net = pid_open("/mmap/net0");
else
	$pid_net = pid_open("/mmap/net1");
$ipaddr = pid_ioctl($pid_net, "get ipaddr");

$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

if(!($title_home = envu_find($envu, "title_home")))
	$title_home = "PHPoC X ThingSpeak";

if(!($channel_id = envu_find($envu, "channel_id")))
	$channel_id = "";
if(!($write_api_key = envu_find($envu, "write_api_key")))
	$write_api_key = "";
if(!($days = envu_find($envu, "days")))
	$days = "";
if(!($results = envu_find($envu, "results")))
	$results = "";
if($days == "" && $results == "")
	$results = "50";

if(!($ymin = envu_find($envu, "ymin")))
	$ymin = "";
if(!($ymax = envu_find($envu, "ymax")))
	$ymax = "";

if(!($interval = envu_find($envu, "interval")))
	$interval = "15";

?>
<!DOCTYPE html>
<html>
<head>
	<title>PHPoC</title>
	<meta content="initial-scale=0.5, maximum-scale=1.0, minimum-scale=0.5, width=device-width, user-scalable=yes" name="viewport">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
	<link type="text/css" rel="stylesheet" href="common.css">	
	<script type="text/javascript">	
		
	function isNum(key)
	{
		var phpoc_setup = document.phpoc_setup;	
		
		var num_check = /^[0-9.]*$/;
		if(!num_check.test(key.value))
		{
			alert("Please input number only.");
			phpoc_setup.interval.value = "";
			phpoc_setup.interval.focus();
		}
	}
	
	
	function excSubmit()
	{			
		var phpoc_setup = document.phpoc_setup;	
		
		var ymin = phpoc_setup.ymin.value;
		var ymax = phpoc_setup.ymax.value;
		if (ymin != "" && ymax != "" && ymin >= ymax)
		{
			alert("Please check the Y-Asix values.");	
			phpoc_setup.ymin.focus();
			return;
		}	
		
		var interval = phpoc_setup.interval.value;
		if (interval < 15)
		{
			alert("Please check the Time interval. (Over 15 seconds)");	
			phpoc_setup.interval.focus();
			return;
		}			
					
		var result = confirm("Are you sure to save changes? Product will be restarted.");
		if(result == true)
			phpoc_setup.submit();		 
		else
			return;
	}
	</script>
</head>
<body onload="chkEmailOpt();">
<div class="temperature_wrap">
    <div class="header">
		<div class="midHeader">
			<center>
				<h1 class="headerTitle"><?php echo $title_home;?></h1>
				<div class="headerMenu">
					<div class="left">
						<a href="index.php">HOME</a>| 
						<a href="setup_info.php">INFO</a>| 
						<a href="setup_net.php">SETUP</a>| 
						<a href="setup_app.php">APP</a>	
					</div>
					<div class="right">
						<a href="javascript:excSubmit();">SAVE</a>				
					</div>
				</div>
			</center>
		</div>
	</div>
		
	<div class="subHeader">
	</div>	

    <div class="contents">		
	<form name="phpoc_setup" action="setup_app_ok.php" method="post">	
	
		<h1>Title</h1>
		
		<table>
			<tr>
				<td width="40%" class="theader">TOP</td>	
				<td><input type="text" class="formtext" name="title_home" value="<? echo $title_home?>" size="30" maxlength="20"></td>
			</tr>
		</table>
		
		<hr size="2" noshade>
		<h1>Mornitoring</h1>
		
		<table>
			<tr>
				<td width="40%" class="theader">Time Interval</td>	
				<td><input type="text" class="formtext" name="interval" size="2" maxlength="5" value="<? echo $interval?>" onkeyUp="isNum(this)"> (sec.)</td>
			</tr>
		</table>
		
		<hr size="2" noshade>
		<h1>ThingSpeak</h1>
		
		<table>
			<tr>
				<td class="theader">Channel ID</td>	
				<td><input type="text" class="formtext" name="channel_id" maxlength="10" size="25" value="<? echo $channel_id?>" size="10"></td>
			</tr>
			<tr>
				<td class="theader">Write API Key</td>	
				<td><input type="text" class="formtext" name="write_api_key" maxlength="16" size="25" value="<? echo $write_api_key?>"></td>
			</tr>	
			<tr>
				<td class="theader">Days</td>	
				<td><input type="text" class="formtext" name="days" maxlength="2" size="10" value="<? echo $days?>" size="10"></td>
			</tr>
			<tr>
				<td class="theader">Results</td>	
				<td><input type="text" class="formtext" name="results" maxlength="2" size="10" value="<? echo $results?>"></td>
			</tr>
			<tr>
				<td class="theader">Y-Axis Min</td>	
				<td><input type="text" class="formtext" name="ymin" maxlength="2" size="10" value="<? echo $ymin?>" size="10"></td>
			</tr>
			<tr>
				<td class="theader">Y-Axis Max</td>	
				<td><input type="text" class="formtext" name="ymax" maxlength="2" size="10" value="<? echo $ymax?>"></td>
			</tr>
		</table>			
	</form>
	<br /><br /><br /><br />
    </div>
	<div class="footer">
		<div class="superFooter">
			<a href="http://www.sollae.co.kr/kr/home/" target="_blank">SOLLAE SYSTEMS</a>	
		</div>
	</div>	
</div>	
</body>
</html>

