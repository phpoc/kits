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
	function select(ch)
	{
		window.opener.parent.document.phpoc_setup.channel.value = ch;
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
			<div class="headerTitle">Channel List</div>

			<div class="headerMenu">
				<div class="right">
					<a href="javascript:search();">SEARCH</a>			
				</div>
			</div>	
		</div>	
		<br /><br /><br /><br />
		<table>
		
		<?php
		for($ch = 1; $ch <= 14; $ch++)
		{
			
		?>			
			<tr>
		<?
			$n = pid_ioctl($pid, "scan result $ch");

			$total_ssid = "";
			for($id = 0; $id < $n; $id++)
			{	
				
				$scan = pid_ioctl($pid, "scan result $ch $id");
				if($scan)
				{
					$scan = explode(" ", $scan);
					
					$ch    = (int)$scan[0];
					$ssid  = hex2bin($scan[3]);
					
					if(!is_printable($ssid))
						continue;
			
					$total_ssid = $ssid . ", " . $total_ssid;
				}
				else	
					break;
			}

			$total_ssid = substr($total_ssid, 0, strlen($total_ssid)-2); //remove last comma.
		?>
		
				<td class="theader" width="90px"><?php echo "Channel $ch";?></td>	
				<td><?php echo $total_ssid;?></td>	
				<td align="center" width="50px"><button type="button" onclick="select('<?php echo $ch?>')";>Select</button></td>
			</tr>		
		<?
		
		}
		pid_close($pid);
		
		?>		


		</table>
		<br>
	</center>	
	</form>
</body>
</html>
