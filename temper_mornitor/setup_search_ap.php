<?php
set_time_limit(30);

function is_printable($ssid)
{
	$ssid_len = strlen($ssid);

	for($i = 0; $i < $ssid_len; $i++)
	{
		$code = bin2int($ssid, $i, 1);
		if(($code < 0x20) || ($code > 0x7e))
			return false;
	}

	return true;
}

$pid = pid_open("/mmap/net1");

pid_ioctl($pid, "scan qsize 32");


pid_ioctl($pid, "scan start");
while(pid_ioctl($pid, "scan state"))
	;

?>
<!DOCTYPE html>
<html>
<head>
	<title>PHPoC</title>
	<meta content="initial-scale=0.7, maximum-scale=1.0, minimum-scale=0.5, width=device-width, user-scalable=yes" name="viewport">
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
	<script type="text/javascript">

	function select(ssid, security)
	{
		window.opener.parent.document.phpoc_setup.ssid.value = ssid;
		
		if (security == "None")
		{
			window.opener.parent.document.phpoc_setup.shared_key.value = "";
			window.opener.parent.document.phpoc_setup.shared_key.disabled = true;
			window.opener.parent.document.phpoc_setup.hide_key.disabled = true;
		}
		else
		{
			window.opener.parent.document.phpoc_setup.shared_key.disabled = false;
			window.opener.parent.document.phpoc_setup.hide_key.disabled = false;
		}	
		window.close();
	}
	
	function search()
	{
		window.location.reload();
	}
	</script>
</head>
<body>

	<form name="searchap" method="post">		
	<center>	
		<div class="midHeader">
			<div class="headerTitle">AP List</div>

				<div class="headerMenu">
					<div class="right">
						<a href="javascript:search();">SEARCH</a>			
					</div>
				</div>	
		</div>	
		<br /><br /><br /><br />
		<table>
		
		<?php
		$i = 0;
		for($ch = 1; $ch <= 14; $ch++)
		{
			$n = pid_ioctl($pid, "scan result $ch");

			for($id = 0; $id < $n; $id++)
			{	
				
				$scan = pid_ioctl($pid, "scan result $ch $id");
				
				if($scan)
				{
					$scan = explode(" ", $scan);
					
					$ch    = (int)$scan[0];
					$rssi  = (int)$scan[1];
					$flags = bin2int(hex2bin($scan[2]), 0, 1);
					$ssid  = hex2bin($scan[3]);
					
					if($flags & 0x20)
						$security = "IBSS";
					else
						$security = "BSS";

					if($flags & 0x04)
						$security = "WPA2";
					else
					if($flags & 0x02)
						$security = "WPA";
					else
					if($flags & 0x01)
						$security = "WEP";
					else
						$security = "None";

					if(!is_printable($ssid))
						continue;
					
					$i++;

		?>
			<tr>
				<td width="170px" class="theader"><?php echo $ssid;?></td>	
				<td><?php echo $security;?></td>
				<td><?php echo "-",$rssi,"dBm";?></td>
				<td align="center"><button type="button" onclick="select('<?php echo $ssid?>', '<?php echo $security?>')";>Select</button></td>
			</tr>
		<?php	

				}
				else	
					break;
			}
		}
		pid_close($pid);
		
		?>			
		</table>
		<br>
	</center>	
	</form>
</body>
</html>
