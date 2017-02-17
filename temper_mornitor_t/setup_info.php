<?php  
include_once "config.php";
include_once "/lib/sc_envu.php";

$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

if(!($title_home = envu_find($envu, "title_home")))
	$title_home = "PHPoC X ThingSpeak";

if((int)ini_get("init_net0"))
	$pid_net = pid_open("/mmap/net0");
else
	$pid_net = pid_open("/mmap/net1");
$hwaddr = pid_ioctl($pid_net, "get hwaddr");
$ipaddr = pid_ioctl($pid_net, "get ipaddr");
$netmask = pid_ioctl($pid_net, "get netmask");
$gwaddr = pid_ioctl($pid_net, "get gwaddr");
$nsaddr = pid_ioctl($pid_net, "get nsaddr");

$ip6linklocal = pid_ioctl($pid_net, "get ipaddr6 0");
$ip6global = pid_ioctl($pid_net, "get ipaddr6 1");
$prefix6 = pid_ioctl($pid_net, "get prefix6");
$gw6addr = pid_ioctl($pid_net, "get gwaddr6");
$ns6addr = pid_ioctl($pid_net, "get nsaddr6");
pid_close($pid_net);

$pid_net1 = pid_open("/mmap/net1");
$wmode = pid_ioctl($pid_net1, "get mode");
$ssid = pid_ioctl($pid_net1, "get ssid");
$rssi = pid_ioctl($pid_net1, "get rssi");
$rsna = pid_ioctl($pid_net1, "get rsna");
$akm = pid_ioctl($pid_net1, "get akm");
$cipher = pid_ioctl($pid_net1, "get cipher");
pid_close($pid_net1);
?>
<!DOCTYPE html>
<html>
<head>
	<title>PHPoC</title>
	<meta name="viewport" content="width=device-width, initial-scale=0.5, maximum-scale=1.0, minimum-scale=0.5, user-scalable=yes">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
	<style type="text/css">
	body {
		font-family: calibri, verdana, Helvetica, Arial, sans-serif, gulim; 
		margin: 0; padding: 0;
	}
	h1 { font-weight: bold; font-size: 18pt; padding-bottom: 5px;}
	h3 { color: #3E3A39;}
	hr {width: 450px; margin: 30px auto; }
	.con_button { background-color: #A9A9A9; padding:5px 3px; color: #fff; width: 100px; font-size: 10pt; border:0px}
	.temperature_wrap{
		margin:0 auto;
	}	 
	.midHeader {color: white; background-color: rgb(6, 38, 111);  position:fixed; left:0; right:0; z-index:3;}
	.headerTitle {
	  font-size: 2em;
	  font-weight: bold;
		text-align:center;
	}
	.headerMenu{
		position:relative;
		width: 450px;
		margin:0 auto;
		padding: 5px;
	}
	.right {
	  color: white;
	  position: absolute;
	  right: 1px;
	  bottom: 4px;
	  font-size:10pt;		  
	}	
	.left {
	  color: white;
	  position: absolute;
	  left: 1px;
	  bottom: 4px;
	  font-size:10pt;		  
	}
	.right a, .left a
	{
		text-align:center;
	  color: white;
	  background-color: transparent;
	  text-decoration: none;
	  margin: 0;
	  padding:0 1ex 0 1ex;
	}			
	.right a:hover, .left a:hover 
	{
	  color: white;
	  text-decoration: underline;
	 }	
	.contents{
		text-align:center;
		padding-top : 100px; 
		color: #3E3A39; 
	}
	.index_contents{
		margin:0 auto;
		padding-top : 150px;
		text-align:center;
	}
	.index_contents a{
		text-decoration:none;
		color:gray;
		font-size:1.1em;
		line-height:4.5em;
	}
	.index_contents a:hover{
		text-decoration:underline;
	}
	table  {border-collapse:collapse; width:450px; font-size:12pt; text-align:center; margin:0 auto; color: #3E3A39;}
	.theader { font-weight: bold; border-right:solid 1px #ccc;}
	tr { height :35px;}
	td { padding-left: 10px; text-align: left; border-top:solid 1px #ccc; border-bottom:solid 1px #ccc;}
	input.formtext  { border:0; background-color: #eee; padding:5px }
	input.formtext:disabled  { border:0; background-color: #DBDBDB; padding:5px; color: #c6c6c6; }
	textarea { width:400px; height:400px; padding:10px; font-family:courier; font-size:14px; }
	.footer{margin:0 auto; height:auto !important; height:100%; margin-bottom:-100px;  }
	.superFooter {
		text-align: center;
		height: 2em; color: white; background-color: rgb(6, 38, 111); font-size:9pt; position:fixed; left:0; right:0; bottom:0; z-index:4;  font-size:10pt;
	}
	.superFooter a {
		text-decoration: none; color: white; margin:0 auto; position:relative; top:5px;
	}	
	.superFooter a:hover {
		text-decoration: underline;
	}
	</style>
</head>
<body>
<div class="temperature_wrap">
    <div class="header">
		<div class="midHeader">
			<h1 class="headerTitle"><?php echo $title_home;?></h1>
			<div class="headerMenu">
				<div class="left">
					<a href="index.php">HOME</a>| 
					<a href="setup_info.php">INFO</a>| 
					<a href="setup_net.php">SETUP</a>| 
					<a href="setup_app.php">APP</a>	
				</div>
				<div class="right">		
				</div>
			</div>
		</div>
		
		<div class="subHeader">
		</div>		
	</div>	
    <div class="contents">		
	<form name="phpoc_info">		
		<h1>System Information</h1>
		<table>
			<tr>
				<td width="45%" class="theader"><?php echo "Product name";?></td>	
				<td>
					<?php echo system("uname -m") . "\r\n";?>
				</td>
			</tr>
			<tr>
				<td class="theader"><?php echo "MAC address";?></td>	
				<td>
					<?php echo $hwaddr;?>
				</td>
			</tr>
			<tr>
				<td class="theader"><?php echo "Firmware name";?></td>	
				<td>
					<?php echo system("uname -f") . "\r\n";?>
				</td>
			</tr>
			<tr>
				<td class="theader"><?php echo "Firmware version";?></td>	
				<td>
					<?php echo system("uname -v") . "\r\n";?>
				</td>
			</tr>
		</table>
		
		<hr size="2" noshade>
		
		<h1>Network Information</h1>
		<table>
			<tr>
				<td width="10%" rowspan="4" class="theader">IPv4</td>
				<td width="35%" class="theader"><?php echo "IP address";?></td>	
				<td><?php echo $ipaddr;?></td>
			</tr>
			<tr>
				<td class="theader"><?php echo "Subnet mask";?></td>	
				<td><?php echo $netmask;?></td>
			</tr>
			<tr>
				<td class="theader"><?php echo "Gateway";?></td>	
				<td><?php echo $gwaddr;?></td>
			</tr>
			<tr>
				<td class="theader"><?php echo "DNS Server";?></td>
				<td><?php echo $nsaddr;?></td>
			</tr>			
		</table>
		<?php 
		if(ini_get("init_ip6") == "1")	
		{
		?>
		<br />
		<table>
			<tr>
				<td width="10%" rowspan="4" class="theader">IPv6</td>
				<td width="35%" class="theader"><?php echo "Link Local";?></td>
				<td><?php echo $ip6linklocal;?></td>	
			</tr>
			<tr>
				<td class="theader"><?php echo "Global";?></td>	
				<td><?php echo $ip6global , " / " , $prefix6;?></td>
			</tr>
			<tr>
				<td class="theader"><?php echo "Gateway";?></td>	
				<td><?php echo $gw6addr;?></td>
			</tr>
			<tr>
				<td class="theader"><?php echo "DNS Server";?></td>	
				<td><?php echo $ns6addr;?></td>
			</tr>			
		</table>	
		<?php 
		}
		?>
		
		<?php
		if ($wmode != "")
		{
		?>
		
		<hr size="2" noshade>
		
		<h1>Wireless LAN Information</h1>
		<table>
			<tr>
				<td width="45%" class="theader"><?php echo "WLAN mode";?></td>	
				<td>
					<?php  					
					switch($wmode)
					{
						case "INFRA":
							$wmode = "Infrastructure";
							break;
						case "IBSS":
							$wmode = "Ad-hoc";
							break;
						case "AP":
							$wmode = "Soft AP";
							break;
					}
					
					echo $wmode;
					?>
				</td>
			</tr>
			<tr>
				<td class="theader"><?php echo "SSID";?></td>	
				<td>
					<?php echo $ssid;?>
				</td>
			</tr>
			<tr>
				<td class="theader"><?php echo "Signal strength";?></td>	
				<td>
					<?php echo "-",$rssi,"dbm";?>
				</td>
			</tr>
			<tr>
				<td class="theader"><?php echo "Security";?></td>	
				<td>
					<?php  
					if($rsna == "")
						echo "NONE";
					else
						echo $rsna;			
					?>
				</td>
			</tr>
			<tr>
				<td class="theader"><?php echo "Key Management";?></td>	
				<td>
					<?php  
					if($akm == "")
						echo "-";
					else
						echo $akm;	
					?>
				</td>
			</tr>
			<tr>
				<td class="theader"><?php echo "Encryption";?></td>	
				<td>
					<?php  
					if($cipher == "")
						echo "-";
					else
						echo $cipher;	
					?>
				</td>
			</tr>
		</table>
		<?php 
		}
		?>	
	</form>
	<br /><br /><br /><br />
	</div>	
	<div id="footer">
		<div class="superFooter">
			<a href="http://www.sollae.co.kr/kr/home/" target="_blank">SOLLAE SYSTEMS</a>	
		</div>
	</div>	
</div>	
</body>
</html>
