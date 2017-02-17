<?php
 
if(_SERVER("REQUEST_METHOD"))
    exit; // avoid php execution via http request

include_once "/lib/sd_340.php";
include_once "/lib/sc_envu.php";
include_once "/lib/sn_tcp_ws.php";
include_once "/lib/vd_nec_infrared.php";
include_once "/lib/vd_uart_camera.php";

//Global constant
define("CMD_GET_CONFIG",         0x01); // client to server
define("CMD_CONTROL",            0x02);
define("CMD_UPDATE",             0x03);
define("CMD_SCAN",               0x04);
define("CMD_TEST",               0x05);
define("CMD_SCAN_CANCEL",        0x06);
define("CMD_MODE",               0x07);
define("CMD_REMOVE",             0x08);

define("_CMD_CONFIG_DATA",       0x11); // server to client
define("_CMD_SCANNED",           0x12); 
define("_CMD_UPDATED",           0x13);
/* move to vd_uart_camera 
define("_CMD_CAMERA_DATA_START", 0x14); 
define("_CMD_CAMERA_DATA",       0x15); 
define("_CMD_CAMERA_DATA_STOP",  0x16);
*/
define("_CMD_REQUEST_DENY",           0x17);
 
define("MODE_SETTING",     0x02); 
define("MODE_CAMERA_CTRL",      0x03); 

define("APP_CONN_NUM",      5); 

$nvp_list = "";

/**
	This function is used to load data from flash memory right after bootup.
**/
function load_data()
{
	global $nvp_list;
	
	$envu =  envu_read("envu");
	if( $envu === "")
	{
		$envu = "A=>0=>0=>0=>0\r\nB=>0=>0=>0=>0\r\nC=>0=>0=>0=>0\r\nD=>0=>0=>0=>0\r\nE=>0=>0=>0=>0\r\nF=>0=>0=>0=>0\r\nG=>0=>0=>0=>0\r\nH=>0=>0=>0=>0\r\nI=>0=>0=>0=>0";
		envu_write("envu", $envu, strlen($envu), 0);
		$envu =  envu_read("envu");
	}
	
	$nvp_list = explode("\r\n", $envu, 9);	
	
	for($i = 0; $i < count($nvp_list); $i++)
	{
		$nvp = explode("=>", $nvp_list[$i], 5);
		$nvp_list[$i] = $nvp;
	}
}

/**
	This function get button state and format it to json string.
**/
function get_config($cmd = _CMD_CONFIG_DATA)
{
	global $nvp_list;
			
	$json_str = '{"cmd":' . sprintf("%d", $cmd, 1)  . ', "data":{';
	
	for($i = 0; $i < count($nvp_list); $i++)
	{
		$nvp = $nvp_list[$i];
		//$nvp[0] is button name, $nvp[1] is buton's state: 0->unset,  1:->set
		$json_str .= ('"' . $nvp[0]. '":' .$nvp[1]. ',');
	}
	
	$json_str = rtrim($json_str, ","); // remove the last comma
	$json_str .= "}}";
	
	return  $json_str;
}

function control($button)
{
	global $nvp_list;
		
	for($i = 0; $i < count($nvp_list); $i++)
	{
		$nvp = $nvp_list[$i];

		if( ($nvp[0] == $button) && ((int)$nvp[1] == 1))
		{
			infrared_emit($nvp[4], (int)$nvp[2], (float)$nvp[3]);  
			break;
		}
	}
}

function update($btn_name, $btn_state, $count_buf, $count, $unit)
{
	global $nvp_list;
	
	$btn_name  = ltrim(rtrim($btn_name));
	$btn_state = sprintf("%d", $btn_state, 1);
	$count = sprintf("%d", $count);
	$unit = sprintf("%.1f", $unit);			

	$envu = "";

	for($i = 0; $i < count($nvp_list); $i++)
	{		
		if($nvp_list[$i][0] == $btn_name)
		{						
			$nvp_list[$i][0] = $btn_name; // button name
			$nvp_list[$i][1] = $btn_state; // button state: 0->unset, 1->set
			$nvp_list[$i][2] = $count;
			$nvp_list[$i][3] = $unit;
			$nvp_list[$i][4] = $count_buf;						
		}	
		
		$envu .= ($nvp_list[$i][0] . "=>" . $nvp_list[$i][1] . "=>" . $nvp_list[$i][2] . "=>" . $nvp_list[$i][3] . "=>" . $nvp_list[$i][4]."\r\n");
	}	
	
	$envu = rtrim($envu, "\r\n"); 
	
	envu_write("envu", $envu, strlen($envu), 0);
	
	envu_read("envu");
}

function ws_write_all($data)
{
	for($tcp_id = 0; $tcp_id < APP_CONN_NUM; $tcp_id++)
	{
		if(ws_state($tcp_id) == TCP_CONNECTED)
		{
			ws_write($tcp_id, $data);
		}
	}
}

uart_setup(0, 115200);

camera_sync();
camera_setup(CT_JPEG, PR_160x120, JR_320x240);

$rbuf = "";
$base_unit = 112.5;
$repc = 160; 
$lower_bound = 4200; 
$upper_bound = 11000;
$min_count = 30;

$count_buf = "";
$cnt_buf_len = 0;

$is_scanning = false;

$modes = array( MODE_CAMERA_CTRL, MODE_CAMERA_CTRL, MODE_CAMERA_CTRL, MODE_CAMERA_CTRL, MODE_CAMERA_CTRL );

$settup_tcp_id = -1;

ws_setup(0, "phpoc_infrared", "csv.phpoc");
ws_setup(1, "phpoc_infrared", "csv.phpoc");
ws_setup(2, "phpoc_infrared", "csv.phpoc");
ws_setup(3, "phpoc_infrared", "csv.phpoc");
ws_setup(4, "phpoc_infrared", "csv.phpoc");

load_data();

while(1)
{
	$camera_in_use = false;
	
	for($tcp_id = 0; $tcp_id < APP_CONN_NUM; $tcp_id++)
	{
		if(ws_state($tcp_id) == TCP_CONNECTED)
		{
			$rlen = ws_read_line($tcp_id, $rbuf);

			if($rlen)
			{
				$data = explode(" ", $rbuf);
				$cmd = (int)$data[0];
				
				switch($cmd)
				{
					case CMD_GET_CONFIG:
						ws_write($tcp_id, get_config());
						break;	
						
					case CMD_CONTROL:
						control($data[1]);
						break;
						
					case CMD_TEST:
						infrared_emit($count_buf, $cnt_buf_len, $base_unit);  
						break;
					
					case CMD_UPDATE:									
						//update
						update($data[1], 1, $count_buf, $cnt_buf_len, $base_unit);					
						ws_write_all(get_config(_CMD_UPDATED));		
						
						$is_scanning = false;					
						break;
					case CMD_REMOVE:									
						//update
						update($data[1], 0, "0", 0, 0.0);					
						ws_write_all(get_config(_CMD_UPDATED));		
						
						$is_scanning = false;					
						break;
						
					case CMD_SCAN:
						$is_scanning = true;					
						break;
						
					case CMD_SCAN_CANCEL:
						$is_scanning = false;					
						break;	
						
					case CMD_MODE:
						$mode = (int)$data[1];
						
						if($mode == MODE_SETTING)
						{
							if($settup_tcp_id == -1 || $settup_tcp_id == $tcp_id)
							{
								$modes[$tcp_id] = $mode;
								$settup_tcp_id = $tcp_id;
							}
							else
							{
								//Send alert
								$alert = '{"cmd":' . sprintf("%d", _CMD_REQUEST_DENY, 1) . ', "code":0, "msg": "Only one persion can do setting at the same time"}';
								ws_write($tcp_id, $alert);
								$modes[$tcp_id] = MODE_CAMERA_CTRL;
							}
						}
						else if($mode == MODE_CAMERA_CTRL)
						{
							$modes[$tcp_id] = $mode;
							
							if($tcp_id == $settup_tcp_id)
								$settup_tcp_id = -1;
						}
						break;
					
				}
			}
			
			if($modes[$tcp_id] == MODE_CAMERA_CTRL)
				$camera_in_use = true;
		}
		else 
		{
			$modes[$tcp_id] = MODE_CAMERA_CTRL;
			
			if($tcp_id == $settup_tcp_id)
			{
				$is_scanning = false;
				$settup_tcp_id = -1;
			}
		}
	}
	
	if($camera_in_use)
		camera_get_picture($modes);
	
	if($is_scanning)
	{
		infrared_capture_start($base_unit, $repc);
		usleep(300000);    
		infrared_recv_stop();
    
		if(infrared_available($lower_bound, $upper_bound))
		{
			echo "\r\n";
			$count_buf = int2bin(1, 2);    // first value is dummy.
			$cnt_buf_len = 1;
			
			for($i = 0; $i <= $repc; $i++)
			{
				//get width of pulses
				$count = infrared_get_raw_count($i);
				
				$min = 2;
				$max = 100;
				
				if($count > $min && $count < $max)
				{
					$count_buf .= int2bin($count, 2);  
					$cnt_buf_len++;
					echo "$count, ";
				}
				if($count > $max)
					break;
			}
			
			if( $cnt_buf_len >= $min_count)
			{
				if(ws_state($settup_tcp_id) == TCP_CONNECTED)
				{
					ws_write($settup_tcp_id, '{"cmd":' . sprintf("%d", _CMD_SCANNED, 1) . '}');
				}
				
				$is_scanning = false;
			}
		}   
	}	
	
}

?>