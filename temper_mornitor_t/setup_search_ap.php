<?php
set_time_limit(30);

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
		table {width:430px; font-size:10pt;}
		.theader { font-weight: bold;}
		tr { height :28px;}
		td { padding: 0 10px;}
		.zebra {background-color : #ECECEC;}
	</style>
	<script type="text/javascript">

	function select(ssid, ssid_raw, security)
	{
		window.opener.parent.document.phpoc_setup.ssid.value = ssid;
		window.opener.parent.document.phpoc_setup.ssid_raw.value = ssid_raw;
		
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
		<table>
		
		<?php
		$result = "";
		$item_count = 0;
		$delimiter = ";";		
		$total_len = 0;
		$overflow = false;
		$j = 1;
								
		for($ch = 1; $ch <= 13; $ch++)
		{
			// check buffer status
			if($overflow == true)
			{
				break;
			}
			
			$n = pid_ioctl($pid, "scan result $ch");
			
			for($id = 0; $id < $n; $id++)
			{
				$value = pid_ioctl($pid, "scan result $ch $id");
				
				// check buffer size (overflow check) 
				if(MAX_STRING_LEN < $total_len + strlen($value))
				{
					$overflow = true;
					break;
				}
				
				// update total length
				$total_len = $total_len + strlen($value);
				
				if($value)
				{
					$add = false;
					$compare = "";
					$buf = "";
					$result_len = strlen($result);
			
					// compare
					$pos1 = 0;
					$pos2 = 0;
					
					while(1)
					{
						$pos2 = strpos($result, $delimiter, $pos1);
						
						if($pos2 === FALSE)
						{
							break;
						}
				
						$compare = substr($result, $pos1, $pos2 - $pos1);
					
						$scan = explode(" ", $value);
						$value1  = (int)$scan[1];
			
						$scan = explode(" ", $compare);
						$value2  = (int)$scan[1];
			
						if($value1 < $value2)
						{
							$add = true;
							$temp = $buf. $value. $delimiter. $compare. substr($result, $pos2);
							$result = $temp;
							break;
						}
						$buf = $buf. $compare. $delimiter;
						$compare = "";

						$pos1 = $pos2 + 1;
					}
					
					// add tail
					if($add == false) 
					{
						$result = $result. $value. $delimiter;
					}
					$value = "";
					$item_count++;
				}
				else
				{
					break;
				}
			}
		}

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
		
		function get_item($src, $index, $delimiter)
		{
			$len = strlen($src);
			$item = "";
			$item_count = 0;
			
			$pos1 = 0;
			$pos2 = 0;

			while(1)
			{
				$pos2 = strpos($src, $delimiter, $pos1);
				
				if($pos2 === FALSE)
				{
					return "";
				}
				
				if($item_count == $index)
				{
					$value = substr($src, $pos1, $pos2 - $pos1);
					return $value;
				}
				else
				{
					$pos1 = $pos2 + 1;
					$item_count++;
				}
			}
		}

		for($i = 0; $i < $item_count; $i++)
		{
			$value = get_item($result, $i, $delimiter);
			
			if($value)
			{
				$scan = explode(" ", $value);
				
				$ch    = (int)$scan[0];
				$rssi  = (int)$scan[1];
				$flags = bin2int(hex2bin($scan[2]), 0, 1);
				$ssid_raw  = $scan[3];
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
				else 
					$j++;
				
				if ($j % 2 == 0)
					$tr_class = "zebra";
				else
					$tr_class = "";

		?>
			<tr class="<?php echo $tr_class?>">
				<td width="170px" class="theader"><?php echo $ssid;?></td>	
				<td><?php echo $security;?></td>
				<td><?php echo "-",$rssi,"dBm";?></td>
				<td align="center"><button type="button" onclick="select('<?php echo $ssid?>', '<?php echo $ssid_raw?>', '<?php echo $security?>')";>Select</button></td>
			</tr>
		<?php	

			}
			else	
				break;			
		}		
			
			pid_close($pid);	
?>
		</table>
		<br>
	</center>	
	</form>
</body>
</html>
