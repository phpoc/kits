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
	<link type="text/css" rel="stylesheet" href="common.css">
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
