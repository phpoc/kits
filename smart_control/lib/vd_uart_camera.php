<?php

// camera command
define("CAM_CMD_PREFIX",      0xAA);
define("CAM_CMD_SYNC",        0x0D);
define("CAM_CMD_ACK",         0x0E);
define("CAM_CMD_NAK",         0x0F);
define("CAM_CMD_INITIAL",     0x01);
define("CAM_CMD_DATA",        0x0A);
define("CAM_CMD_RESET",       0x08);
define("CAM_CMD_POWEROFF",    0x09);
define("CAM_CMD_BAUDRATE",    0x07);
define("CAM_CMD_PACKAGESIZE", 0x06);
define("CAM_CMD_SNAPSHOT",    0x05);
define("CAM_CMD_GETPICTURE",  0x04);
define("CAM_CMD_LIGHTFREQ",   0x13);

//Color Type  
define("CT_GRAYSCALE_2", 0x01); 
define("CT_GRAYSCALE_4", 0x02); 
define("CT_GRAYSCALE_8", 0x03); 
define("CT_COLOR_12", 0x05); 
define("CT_COLOR_16", 0x06); 
define("CT_JPEG", 0x07); 

//Preview Resolution
define("PR_80x60", 0x01);
define("PR_160x120", 0x03);

// JPEG Resolution
define("JR_80x64",   0x01);
define("JR_160x128", 0x03);
define("JR_320x240", 0x05);
define("JR_640x480", 0x07);

define("PIC_PKT_LEN",    512);        //data length of each read, dont set this too big because ram is limited

// server to client
define("_CMD_CAMERA_DATA_START", 0x14); 
define("_CMD_CAMERA_DATA",       0x15); 
define("_CMD_CAMERA_DATA_STOP",  0x16);

$uart_id = 0; // default uart0, it can be change by using set_uart() function


function set_uart($id)
{
	global $uart_id;
	
	$uart_id = $id;
}

function clear_rx_buf()
{
	global $uart_id;
	
	$rbuf = "";
	$len = uart_read($uart_id, $rbuf);
	
	//log
	if($len)
	{
		echo "clear:<<", bin2hex($rbuf), "\r\n";
	}
	
}

function ws_send($ws_modes, $data)
{
	$ws_num = count($ws_modes);
	
	for($tcp_id = 0; $tcp_id < $ws_num; $tcp_id++)
	{
		if($ws_modes[$tcp_id] == 0x03)
		{
			if(ws_state($tcp_id) == TCP_CONNECTED)
				ws_write($tcp_id, $data);
		}
	}
}

function send_cmd($cmd, $CAM_CMD_len)
{
	global $uart_id;
	
	for ($i = 0; $i < $CAM_CMD_len; $i++) 
	{
		$wbuf = int2bin(($cmd[$i]&0xff),1);
		uart_write($uart_id, $wbuf, 1);
	}
}

function read_bytes(&$dest, $len, $timeout)
{
	global $uart_id;
	
	$pid_st0 = pid_open("/mmap/st0");
	pid_ioctl($pid_st0, "set mode free");
	pid_ioctl($pid_st0, "set dir up");
	pid_ioctl($pid_st0, "set div ms");
	pid_ioctl($pid_st0, "start");
	$tick = pid_ioctl($pid_st0, "get count");
	
	$rlen = 0;
	
	while($tick < $timeout) 
	{
		if(($rlen = uart_readn($uart_id, $dest, $len)) == $len) 
		{
			break;
		}
		$tick = pid_ioctl($pid_st0, "get count");
	}
	
	pid_close($pid_st0);
	return $rlen;
}

function camera_wait_for_ACK($time_out, $command)
{
	$resp = "";
	if (read_bytes($resp, 6, $time_out) == 6)
	{
		if (bin2int($resp, 0, 1) == CAM_CMD_PREFIX 
			&& bin2int($resp, 1, 1) == (CAM_CMD_ACK ) 
			&& bin2int($resp, 2, 1) == $command )
		{
			//echo "camera: received ACK\r\n";
			return true;
		}
	}
	//echo "camera: not receive ACK\r\n";
	return false;
}

function camera_sync()
{   
	$cmd = array(CAM_CMD_PREFIX, CAM_CMD_SYNC, 0x00, 0x00, 0x00, 0x00);  
	$resp = "";

	echo "camera: CAM_CMD_SYNC...\r\n";
	
	$retry_cnt = 0;
	
	while (1) 
	{
		clear_rx_buf();
		send_cmd($cmd,6);
		if (camera_wait_for_ACK(100, CAM_CMD_SYNC))
			break;
		
		$retry_cnt++;
		
		if($retry_cnt > 20)
			return false;
			//system("reboot php 100");
		
		usleep(10000);
	}  
	$cmd[1] = CAM_CMD_ACK ;
	$cmd[2] = CAM_CMD_SYNC;
	send_cmd($cmd, 6); 
	
	return true;
}

function camera_setup($color_type, $preview_resolution, $jpeg_resolution)
{
	//initial 
	echo "camera: CAM_CMD_INITIAL...\r\n";
	$cmd = array( CAM_CMD_PREFIX, CAM_CMD_INITIAL , 0x00, $color_type, $preview_resolution, $jpeg_resolution );  
	$resp = "";
	
	$retry_cnt = 0;
	
	while (1)
	{
		clear_rx_buf();
		send_cmd($cmd, 6);
		
		if (camera_wait_for_ACK(100, CAM_CMD_INITIAL))
			break;
		
		$retry_cnt++;
		
		if($retry_cnt > 20)
			return false;
			//system("reboot php 100");
	}
	// set packet size
	//echo "camera: set CAM_CMD_PACKAGESIZE\r\n";
	$cmd = array( CAM_CMD_PREFIX, CAM_CMD_PACKAGESIZE , 0x08, PIC_PKT_LEN & 0xff, (PIC_PKT_LEN>>8) & 0xff ,0); 
	
	$retry_cnt = 0;
	
	while (1)
	{
		clear_rx_buf();
		send_cmd($cmd, 6);
		
		if (camera_wait_for_ACK(100, CAM_CMD_PACKAGESIZE))
			break;
		
		$retry_cnt++;
		
		if($retry_cnt > 20)
			return false;
			//system("reboot php 100");
	}
	
	return true;
}

function camera_reset($reset = 0x01)
{
	//initial 
	$cmd = array( CAM_CMD_PREFIX, CAM_CMD_RESET , 0x00, 0x00, 0x00, 0x00 );  
	$resp = "";
	
	$retry_cnt = 0;
  
	while (1)
	{
		clear_rx_buf();
		send_cmd($cmd, 6);
		
		if (camera_wait_for_ACK(100, CAM_CMD_RESET))
			break;
		
		$retry_cnt++;
		
		if($retry_cnt > 20)
			system("reboot php 100");
	}
}

function camera_capture()
{	
	//snapshot. 
	//echo "camera: CAM_CMD_SNAPSHOT...\r\n";
	$cmd = array( CAM_CMD_PREFIX, CAM_CMD_SNAPSHOT , 0x00, 0x00, 0x00 ,0x00); 
	$resp = "";
	
	$retry_cnt = 0;
	
	while (1)
	{
		clear_rx_buf();
		send_cmd($cmd, 6);
	
		if (camera_wait_for_ACK(100, CAM_CMD_SNAPSHOT))
			break;
		
		$retry_cnt++;
		
		if($retry_cnt > 20)
			system("reboot php 100");
	}
}

function camera_get_picture($ws_modes)
{	
	// send get picture command and get total size
	//echo "camera: get CAM_CMD_GETPICTURE...\r\n";
	$cmd = array( CAM_CMD_PREFIX, CAM_CMD_GETPICTURE , 0x01, 0x00, 0x00 ,0x00); 
	$resp = "";
	$retry_cnt = 0;
	
	while (1) 
	{
		clear_rx_buf();
		$retry_cnt++;
		send_cmd($cmd, 6);
		
		if($retry_cnt > 5)
			return false;
			//system("reboot php 100");
		
		if (camera_wait_for_ACK(100, CAM_CMD_GETPICTURE))
		{
			if (read_bytes($resp, 6, 1000) != 6)
			{
				continue;
			}
			if (bin2int($resp, 0, 1) == CAM_CMD_PREFIX 
				&& bin2int($resp, 1, 1) == (CAM_CMD_DATA ) 
				&& bin2int($resp, 2, 1) == 0x01)
			{
				$pic_total_len = (bin2int($resp, 3, 1)) | (bin2int($resp, 4, 1) << 8) | (bin2int($resp, 5, 1) << 16); 
				//echo "\r\npic_total_len: ", $pic_total_len, "\r\n";
				break;
			}
		}
	} 
	
	// get data
	$pktCnt = ($pic_total_len) / (PIC_PKT_LEN - 6);
	$last_pkt_len = PIC_PKT_LEN;
	
	$mod = $pic_total_len % (PIC_PKT_LEN-6);
	
	if ($mod != 0) 
	{
		$pktCnt += 1;
		$last_pkt_len = $mod + 6;
	}
  
	$cmd = array( CAM_CMD_PREFIX, CAM_CMD_ACK , 0x00, 0x00, 0x00, 0x00 );  
	$pkt = "";
  
	// send start code to websocket client
	$wbuf = '{"cmd":' . sprintf("%d", _CMD_CAMERA_DATA_START, 1) . '}';
	ws_send($ws_modes, $wbuf);
	
	for ($i = 0; $i < $pktCnt; $i++)
	{
		$cmd[4] = $i & 0xff;
		$cmd[5] = ($i >> 8) & 0xff;
	  
		$retry_cnt = 0;
		$retry = true;
		
		if($i < ($pktCnt-1))
			$len = PIC_PKT_LEN ;
		else
		{
			$len = $last_pkt_len;
		}
		
		while($retry)
		{
			usleep(6000);
			clear_rx_buf(); 
			$retry_cnt++;
			send_cmd($cmd, 6); 
			$cnt = read_bytes($pkt, $len, 1200);			
			
			if($cnt)
			{ 
				//checksum funtion
				/*
				$sum = 0; 
				
				for ($y = 0; $y < $cnt - 2; $y++)
				{
					$sum += bin2int($pkt, $y, 1);
				}
				
				$sum &= 0xff;
				
				if ($sum != bin2int($pkt, ($cnt-2), 1))
				{
					echo "checksum error";
					if ($retry_cnt < 100) $retry = true;
					else 
					{
						break;
					}
				}
				else */
				{
					$retry = false;
				}
			}
			
			if($retry_cnt > 5)
				return false;
				//system("reboot php 100");
		} 
		
		if($retry) break; 
		
		$data = bin2hex(substr($pkt, 4, $cnt-6));
		
		// send data to websocket client
		$wbuf = '{"cmd":' . sprintf("%d", _CMD_CAMERA_DATA, 1) . ',"data":"'. $data . '"}';
		ws_send($ws_modes, $wbuf);
	}
	$cmd[4] = 0xf0;
	$cmd[5] = 0xf0; 
	send_cmd($cmd, 6);
	
	// send finish code to websocket client
	$wbuf = '{"cmd":' . sprintf("%d", _CMD_CAMERA_DATA_STOP, 1) . '}';
	ws_send($ws_modes, $wbuf);
	
	return true;
}
?>