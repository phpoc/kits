<?php
set_time_limit(30);

function is_printable($ssid)
{
	$ssid_len = strlen($ssid);

	for($i = 0; $i < $ssid_len; $i++)
	{
		$code = bin2int($ssid, $i, 1);
		if($code == 0x00)
			return false;
	}

	return true;
}

$pid = pid_open("/mmap/net1");

pid_ioctl($pid, "scan qsize 64");

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
		body { font-family: verdana, Helvetica, Arial, sans-serif, gulim; }		
		.midHeader {
			color: white; 
			background-color: rgb(6, 38, 111); 
			z-index:3; 
			margin-bottom:20px 
		}
		.headerTitle { 
			font-size: 200%;
			font-weight: normal;
			font-family: impact; 
			padding: 10px;
		}
		.headerMenu{
			position:relative;
			width: 430px;
		}
		.right {
			color: white;
			position: absolute;
			right: 1px;
			bottom: 4px;
			font-size:9pt;		  
		}		
		.right a
		{
			color: white;
			background-color: transparent;
			text-decoration: none;
			margin: 0;
			padding:0 2ex 0 2ex;
		}		
		.right a:hover
		{
			color: white;
			text-decoration: underline;
		}	
		table {width:480px; font-size:10pt;}
		.theader { font-weight: bold; width:100px;}
		tr { height :28px;}
		td { padding: 10px;}
		.zebra {background-color : #ECECEC;}
	</style>
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
		<table>
		
		<?php
		for($ch = 1; $ch <= 14; $ch++)
		{
			if ($ch % 2 == 1)
				$tr_class = "zebra";
			else
				$tr_class = "";
			
		?>			
			<tr class="<?php echo $tr_class;?>">
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
		
				<td class="theader"><?php echo "Channel $ch";?></td>	
				<td width="300px"><?php echo $total_ssid;?></td>	
				<td align="center"><button type="button" onclick="select('<?php echo $ch?>')";>Select</button></td>
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
