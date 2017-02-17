<?php
include_once "/lib/sd_340.php";
include_once "config.php";
include_once "/lib/sc_envu.php";

set_time_limit(30);

if((int)ini_get("init_net0"))
	$pid_net = pid_open("/mmap/net0");
else
	$pid_net = pid_open("/mmap/net1");
$ipaddr = pid_ioctl($pid_net, "get ipaddr");

$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

// init nm0
if(!($nm_use = envu_find($envu, "nm_use"))) 
{
	$fill = " ";
	$temp60 = str_repeat($fill, 300);
	nm_write(0, 0, $temp60, 300);
	
	envu_update($envu, "nm_use", "1");
}

if(!($title_home = envu_find($envu, "title_home")))
	$title_home = "Temperature";
if(!($title_graph = envu_find($envu, "title_graph")))
	$title_graph = "Temperature Graph";
if(!($title_console = envu_find($envu, "title_console")))
	$title_console = "Temperature Web Console";

if(!($email_opt = envu_find($envu, "email_opt")))
	$email_opt = "0";
if(!($email_min_temp = envu_find($envu, "email_min_temp")))
	$email_min_temp = "0";
if(!($email_max_temp = envu_find($envu, "email_max_temp")))
	$email_max_temp = "50";

if(!($email_address = envu_find($envu, "email_address")))
	$email_address = "";
if(!($email_server = envu_find($envu, "email_server")))
	$email_server = "";
if(!($email_server_port = envu_find($envu, "email_server_port")))
	$email_server_port = "";
if(!($email_server_id = envu_find($envu, "email_server_id")))
	$email_server_id = "";
if(!($email_server_pw = envu_find($envu, "email_server_pw")))
	$email_server_pw = "";

if(!($interval = envu_find($envu, "interval")))
	$interval = "1";

?>
<!DOCTYPE html>
<html>
<head>
	<title>PHPoC</title>
	<meta content="initial-scale=0.5, maximum-scale=1.0, minimum-scale=0.5, width=device-width, user-scalable=yes" name="viewport">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 

	<link type="text/css" rel="stylesheet" href="common.css">
	<script src="aes.js"></script>
	<script src="core.js"></script>
	
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
	
	//AES
	var key = CryptoJS.enc.Hex.parse('06a9214036b8a15b512e03d534120006');
	var iv = CryptoJS.enc.Hex.parse('3dafba429d9eb430b422da802c9fac41');
		
	function chkEmailOpt()
	{
		var phpoc_setup = document.phpoc_setup;	
		
		if(phpoc_setup.email_opt[1].checked) //email 사용안함
		{
			phpoc_setup.email_min_temp.disabled = "true";
			phpoc_setup.email_max_temp.disabled = "true";
			phpoc_setup.email_address.disabled = "true";
			phpoc_setup.email_server.disabled = "true";
			phpoc_setup.email_server_port.disabled = "true";
			phpoc_setup.email_server_id.disabled = "true";
			phpoc_setup.email_server_pw.disabled = "true";
		}
		else
		{
			phpoc_setup.email_min_temp.disabled = "";
			phpoc_setup.email_max_temp.disabled = "";
			phpoc_setup.email_address.disabled = "";
			phpoc_setup.email_server.disabled = "";
			phpoc_setup.email_server_port.disabled = "";
			phpoc_setup.email_server_id.disabled = "";
			phpoc_setup.email_server_pw.disabled = "";
		}
	
		var email_server_pw = "<?php echo $email_server_pw;?>";
		var email_server_pw_des = CryptoJS.AES.decrypt(email_server_pw, key, { iv: iv });
		
		phpoc_setup.email_server_pw.value = email_server_pw_des.toString(CryptoJS.enc.Utf8).trim();
	}
	
	function excSubmit()
	{			
		var phpoc_setup = document.phpoc_setup;	
		
		var interval = phpoc_setup.interval.value;
		if (interval < 1)
		{
			alert("Please check the Time interval. (Over 1 second)");	
			phpoc_setup.interval.focus();
			return;
		}
			
		if(phpoc_setup.email_opt[0].checked) //email 사용함
		{
			
			var email_min_temp = phpoc_setup.email_min_temp.value;
			if( email_min_temp == "" || email_min_temp < '0' || email_min_temp > '50' || email_min_temp > email_max_temp )
			{
				alert("Please check the minimum temperature. (0℃ ~ 50 ℃)");	
				phpoc_setup.email_min_temp.focus();
				return;
			}
			
			var email_max_temp = phpoc_setup.email_max_temp.value;
			if( email_max_temp == "" || email_max_temp <= '0' || email_max_temp > '50' )
			{
				alert("Please check the maximum temperature. (0℃ ~ 50 ℃)");	
				phpoc_setup.email_max_temp.focus();
				return;
			}	
			
			var email_address = phpoc_setup.email_address.value;
			if( email_address == "" )
			{
				alert("Please check your E-mail address.");	
				phpoc_setup.email_address.focus();
				return;
			}
			
			var email_server = phpoc_setup.email_server.value;
			if( email_server == "" )
			{
				alert("Please check the E-mail server address.");	
				phpoc_setup.email_server.focus();
				return;
			}
			
			var email_server_port = phpoc_setup.email_server_port.value;
			if( email_server_port == "" )
			{
				alert("Please check E-mail server port number.");	
				phpoc_setup.email_server_port.focus();
				return;
			}
			
			var email_server_id = phpoc_setup.email_server_id.value;
			if( email_server_id == "" )
			{
				alert("Please check your E-mail Account ID.");	
				phpoc_setup.email_server_id.focus();
				return;
			}
			var email_server_pw = phpoc_setup.email_server_pw.value;
			if( email_server_pw != "" )
			{	
				//var pad = " ".repeat(32);  repeat() supports over IE ver. 12, Chrome ver. 41
				var pad = "                                "; 
				email_server_pw += pad;
				email_server_pw = email_server_pw.substring(0, 32);
				var email_server_pw_enc = CryptoJS.AES.encrypt(email_server_pw, key, { iv: iv });	
				phpoc_setup.email_server_pw.value = email_server_pw_enc;
			}
			else
			{
				alert("Please check your E-mail Account Password.");	
				phpoc_setup.email_server_pw.focus();
				return;
			}
			
			phpoc_setup.email_min_temp.disabled = "";
			phpoc_setup.email_max_temp.disabled = "";
			phpoc_setup.email_address.disabled = "";
			phpoc_setup.email_server.disabled = "";
			phpoc_setup.email_server_port.disabled = "";
			phpoc_setup.email_server_id.disabled = "";
			phpoc_setup.email_server_pw.disabled = "";
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
				<td width="40%" class="theader">Home</td>	
				<td><input type="text" class="formtext" name="title_home" value="<? echo $title_home?>" size="30" maxlength="20"></td>
			</tr>
			<tr>
				<td class="theader">Graph page</td>	
				<td><input type="text" class="formtext" name="title_graph" value="<? echo $title_graph?>" size="30" maxlength="20"></td>
			</tr>
			<tr>
				<td class="theader">Web console page</td>	
				<td><input type="text" class="formtext" name="title_console" value="<? echo $title_console?>" size="30" maxlength="20"></td>
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
		<h1>E-mail Notification</h1>
		
		<table>
			<tr>
				<td width="50%" class="theader" colspan="2">E-mail Notification</td>	
				<td>
					<input type="radio" value="1" name="email_opt" onclick="chkEmailOpt();" <? if($email_opt == "1") echo "checked" ?> /> Enable &emsp;
					<input type="radio" value="0" name="email_opt" onclick="chkEmailOpt();" <? if($email_opt == "0") echo "checked" ?> /> Disable
				</td>
			</tr>
			<tr>
				<td width="50%" class="theader" colspan="2">Normal Temperature Range</td>	
				<td>
					<input type="text" class="formtext" name="email_min_temp" value="<? echo $email_min_temp?>" size="2" maxlength="2">℃&emsp;~&emsp;
					<input type="text" class="formtext" name="email_max_temp" value="<? echo $email_max_temp?>" size="2" maxlength="2">℃
				</td>
			</tr>
			<tr>
				<td class="theader" colspan="2">E-mail Address</td>	
				<td><input type="text" class="formtext" name="email_address" maxlength="40" value="<? echo $email_address?>"></td>
			</tr>			
			<tr>
				<td width="15%" class="theader" rowspan="4">E-mail Server</td>
				<td class="theader">Server address</td>	
				<td><input type="text" class="formtext" name="email_server" maxlength="40" value="<? echo $email_server?>"></td>
			</tr>
			<tr>
				<td class="theader">Server port</td>	
				<td><input type="text" class="formtext" name="email_server_port" maxlength="10" value="<? echo $email_server_port?>" size="10"></td>
			</tr>
			<tr>
				<td class="theader">Account ID</td>	
				<td><input type="text" class="formtext" name="email_server_id" maxlength="40" value="<? echo $email_server_id?>"></td>
			</tr>
			<tr>
				<td class="theader">Account Password</td>	
				<td><input type="password" class="formtext" name="email_server_pw" maxlength="32" value="<? echo $email_server_pw?>"></td>
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

