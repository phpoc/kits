<?php
 
if(_SERVER("REQUEST_METHOD"))
    exit; // avoid php execution via http request

include_once "/lib/sd_340.php";
include_once "/lib/sc_envu.php";
include_once "/lib/sn_tcp_ws.php";
include_once "/lib/vd_nec_infrared.php";
include_once "/lib/vd_uart_camera.php";

//Global constant
define("CMD_GET_CONFIG",		0x01); // client to server
define("CMD_CONTROL",			0x02);
define("CMD_UPDATE_BTN",		0x03);
define("CMD_SCAN",				0x04);
define("CMD_TEST",				0x05);
define("CMD_SCAN_CANCEL",		0x06);
define("CMD_MODE",				0x07);
define("CMD_REMOVE_BTN",		0x08);
define("CMD_ADD_SCHE",			0x09);
define("CMD_DELETE_SCHE",		0x0A);

define("_CMD_CONFIG_DATA",		0x11); // server to client
define("_CMD_SCANNED",			0x12);
define("_CMD_UPDATED_BTN",		0x13);
/* move to vd_uart_camera 
define("_CMD_CAMERA_DATA_START", 0x14); 
define("_CMD_CAMERA_DATA",       0x15); 
define("_CMD_CAMERA_DATA_STOP",  0x16);
*/
define("_CMD_REQUEST_DENY",		0x17);
define("_CMD_UPDATED_SCHE",		0x18);
define("_CMD_IMG_NOT_FOUND",	0x19);

define("MODE_SETTING",			0x02);
define("MODE_CAMERA_CTRL",		0x03);
define("MODE_SCHEDULE",			0x04);

define("APP_CONN_NUM",			5);
define("IR_BASE_UNIT",			112.5);

/**
	This function is used to load data from flash memory right after bootup.
**/

function load_data()
{
	global $btn_str, $btn_list;
	global $sche_str, $sche_list;
	
	$envu = envu_read("envu");
	if( $envu === "")
	{
		$btn_str = "A=>0=>0=>0=>0\r\nB=>0=>0=>0=>0\r\nC=>0=>0=>0=>0\r\nD=>0=>0=>0=>0\r\nE=>0=>0=>0=>0\r\nF=>0=>0=>0=>0\r\nG=>0=>0=>0=>0\r\nH=>0=>0=>0=>0\r\nI=>0=>0=>0=>0";
		$sche_str = "";
		$envu = "$btn_str\r\n\r\n$sche_str";
		
		envu_write("envu", $envu, strlen($envu), 0);
		$envu =  envu_read("envu");
	}
	
	$config = explode("\r\n\r\n", $envu, 2);
	$btn_str = $config[0];
	$sche_str = $config[1];
	
	$btn_list = explode("\r\n", $btn_str, 9);
	
	for($i = 0; $i < count($btn_list); $i++)
	{
		$btn = explode("=>", $btn_list[$i], 5);
		$btn_list[$i] = $btn;
	}
	
	if($sche_str != "")
	{
		$sche_list = explode("\r\n", $sche_str);
		
		for($i = 0; $i < count($sche_list); $i++)
		{
			$sche = explode(":", $sche_list[$i], 5);
			$sche_list[$i] = $sche;
		}
	}
	else
		$sche_list = array();
}

/**
	This function get button state and format it to json string.
**/
function get_config($cmd = _CMD_CONFIG_DATA)
{
	global $btn_list, $sche_list;
	
	$conf_btn = "";
	$conf_sche = "";
	
	for($i = 0; $i < count($btn_list); $i++)
	{
		$btn = $btn_list[$i];
		//$btn[0] is button name, $btn[1] is buton's state: 0->unset,  1:->set
		$conf_btn .= ('"' . $btn[0]. '":' .$btn[1]. ',');
	}
	
	for($i = 0; $i < count($sche_list); $i++)
	{
		$sche = $sche_list[$i];
		$day = $sche[0];
		$hour = $sche[1];
		$minute = $sche[2];
		$btn = $sche[3];
		
		$conf_sche .= "{\"day\":\"$day\",\"hour\":\"$hour\",\"minute\":\"$minute\",\"btn\":\"$btn\"},";
	}
	
	$conf_btn = rtrim($conf_btn, ","); // remove the last comma
	$conf_sche = rtrim($conf_sche, ","); // remove the last comma
	$data = "{\"btn\":{$conf_btn}, \"sche\":[$conf_sche]}";
	
	$cmd = sprintf("%d", $cmd, 1);
	
	$json_str = "{\"cmd\":$cmd, \"data\":$data}";
	
	return  $json_str;
}

function update_btn($btn_name, $btn_state, $count_buf, $count, $unit)
{
	global $btn_list;
	global $update_flag;
	
	$btn_name  = ltrim(rtrim($btn_name));
	$btn_state = sprintf("%d", $btn_state, 1);
	$count = sprintf("%d", $count);
	$unit = sprintf("%.1f", $unit);
	
	for($i = 0; $i < count($btn_list); $i++)
	{		
		if($btn_list[$i][0] == $btn_name)
		{
			$btn_list[$i][0] = $btn_name; // button name
			$btn_list[$i][1] = $btn_state; // button state: 0->unset, 1->set
			$btn_list[$i][2] = $count;
			$btn_list[$i][3] = $unit;
			$btn_list[$i][4] = $count_buf;
		}
	}
	
	$update_flag = true;
	//memory_update();
}

function add_sche($sche)
{
	global $sche_str, $sche_list;
	global $update_flag;
	
	if(strpos($sche_str, $sche) !== false)
		return;
	
	$sche .= ":0";
	
	if($sche_str == "")
		$sche_str = "$sche";
	else
		$sche_str .= "\r\n$sche";
	
	$sche_list = explode("\r\n", $sche_str);
	
	for($i = 0; $i < count($sche_list); $i++)
	{
		$sche = explode(":", $sche_list[$i], 5);
		$sche_list[$i] = $sche;
	}
	
	$update_flag = true;
	//memory_update();
}

function delete_sche($sche)
{
	global $sche_str, $sche_list;
	global $update_flag;
	
	if(count($sche_list))
	{
		$sche_str = "";
		
		for($i = 0; $i < count($sche_list); $i++)
		{
			$cur_sche = $sche_list[$i];
			$cur_sche_str = $cur_sche[0] . ":" . $cur_sche[1] . ":" . $cur_sche[2] . ":" . $cur_sche[3] . ":" . $cur_sche[4];
			
			if(strpos($cur_sche_str, $sche) === false)
				$sche_str .= $cur_sche_str . "\r\n";
		}
		
		$sche_str = rtrim($sche_str, "\r\n"); // remove the last \r\n
		
		if($sche_str != "")
		{
			$sche_list = explode("\r\n", $sche_str);
			
			for($i = 0; $i < count($sche_list); $i++)
			{
				$sche = explode(":", $sche_list[$i], 5);
				$sche_list[$i] = $sche;
			}
		}
		else
			$sche_list = array();
	}
	else
		$sche_list = array();
	
	$update_flag = true;
	//memory_update();
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

function IR_scan()
{
	global $count_buf, $cnt_buf_len;
	global $settup_tcp_id;
	
	$repc = 160; 
	$lower_bound = 4200; 
	$upper_bound = 11000;
	$min_count = 30;
	
	infrared_capture_start(IR_BASE_UNIT, $repc);
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

function IR_emit($btn_name)
{
	global $btn_list;
		
	for($i = 0; $i < count($btn_list); $i++)
	{
		$btn = $btn_list[$i];

		if( ($btn[0] == $btn_name) && ((int)$btn[1] == 1))
		{
			infrared_emit($btn[4], (int)$btn[2], (float)$btn[3]);
			echo "$btn_name\r\n";
			break;
		}
	}
}

function memory_loop()
{
	global $btn_str, $btn_list;
	global $sche_str, $sche_list;
	global $update_flag, $last_update_time;
	
	if(!$update_flag)
		return;
	
	$cur_time = st_free_get_count(1);
	
	if(($cur_time - $last_update_time) < 2200)
		return;
	echo "update\r\n";
	$update_flag = false;
	$last_update_time = $cur_time;
	
	$btn_str = "";
	$sche_str = "";
	
	for($i = 0; $i < count($btn_list); $i++)
	{
		$btn = $btn_list[$i];
		$btn_str .= ($btn[0] . "=>" . $btn[1] . "=>" . $btn[2] . "=>" . $btn[3] . "=>" . $btn[4]."\r\n");
	}
	
	for($i = 0; $i < count($sche_list); $i++)
	{
		$sche = $sche_list[$i];
		$day = $sche[0];
		$hour = $sche[1];
		$minute = $sche[2];
		$btn = $sche[3];
		$state = $sche[4];
		
		$sche_str .= "$day:$hour:$minute:$btn:$state\r\n";
	}
	
	$btn_str = rtrim($btn_str, "\r\n");
	$sche_str = rtrim($sche_str, "\r\n");
	
	$envu = "$btn_str\r\n\r\n$sche_str";
	
	envu_write("envu", $envu, strlen($envu), 0);
	
	envu_read("envu");
}

function schedule_loop()
{
	global $sche_list;
	global $reset_day;
	global $update_flag;
	
	$today = date("D");
	$hour = (int)date("H");
	$minute = (int)date("i");
	$current_time = $hour*60 + $minute;//in minute
	
	$count = count($sche_list);
	
	if($count == 0)
		return;
	
	//reset for all schedules
	if($today != $reset_day)
	{
		for($i = 0; $i < $count; $i++)
		{
			if($sche_list[$i][0] != $today && $sche_list[$i][4] == "1")
			{
				$sche_list[$i][4] = "0"; // set state to 0;
				$update_flag = true;
			}
		}
		
		$reset_day = $today;
	}
	
	for($i = 0; $i < $count; $i++)
	{
		if($sche_list[$i][0] != $today || $sche_list[$i][4] == "1")
			continue; // if schedule day is not today state == 1, already done
		
		$schedule_time = ((int)$sche_list[$i][1])*60 + (int)$sche_list[$i][2];//in minute
		
		$time = $current_time - $schedule_time;
		if($time >= 0 && $time <= 1)
		{
			IR_emit($sche_list[$i][3]);
			$sche_list[$i][4] = "1"; // set state to 1, already done.
			$update_flag = true;
		}
	}
	
	//memory_update();
}

function network_loop()
{
	global $settup_tcp_id;
	global $is_scanning;
	global $modes;
	global $count_buf, $cnt_buf_len;
	
	$rbuf = "";
	
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
						IR_emit($data[1]);
						break;
						
					case CMD_TEST:
						infrared_emit($count_buf, $cnt_buf_len, IR_BASE_UNIT);  
						break;
					
					case CMD_UPDATE_BTN:
						//update
						update_btn($data[1], 1, $count_buf, $cnt_buf_len, IR_BASE_UNIT);
						ws_write_all(get_config(_CMD_UPDATED_BTN));
						
						$is_scanning = false;
						break;
					
					case CMD_ADD_SCHE:
						add_sche($data[1]);
						ws_write_all(get_config(_CMD_UPDATED_SCHE));
						break;
					
					case CMD_REMOVE_BTN:
						//update
						update_btn($data[1], 0, "0", 0, 0.0);
						ws_write_all(get_config(_CMD_UPDATED_BTN));
						
						$is_scanning = false;
						break;
					
					case CMD_DELETE_SCHE:
						delete_sche($data[1]);
						ws_write_all(get_config(_CMD_UPDATED_SCHE));
						
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
								$alert = '{"cmd":' . sprintf("%d", _CMD_REQUEST_DENY, 1) . ', "code":0, "msg": "Only one person can do setting at one time"}';
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
						else if($mode == MODE_SCHEDULE)
						{
							$modes[$tcp_id] = $mode;
							
							if($tcp_id == $settup_tcp_id)
								$settup_tcp_id = -1;
						}
						break;
					
				}
			}
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
}

function camera_loop()
{
	global $modes;
	global $camera_state;
	global $is_scanning;
	
	if($is_scanning)
		return;
	
	$pre_state = $camera_state;
	
	if(!$camera_state)
	{
		$camera_state = camera_sync();
		if($camera_state)
			$camera_state = camera_setup(CT_JPEG, PR_160x120, JR_320x240);
	}
	else
	{
		for($tcp_id = 0; $tcp_id < APP_CONN_NUM; $tcp_id++)
		{
			if(ws_state($tcp_id) == TCP_CONNECTED)
			{
				if($modes[$tcp_id] == MODE_CAMERA_CTRL)
				{
					$camera_state = camera_get_picture($modes);
					break;
				}
			}
		}
	}
	
	if(!$pre_state && $camera_state)
		echo "camera: connected\r\n";
	
	if($pre_state && !$camera_state)
	{
		$data = '{"cmd":' . sprintf("%d", _CMD_IMG_NOT_FOUND, 1). '}';
		ws_write_all($data);
		echo "camera: disconnected\r\n";
	}
}

function IR_scan_loop()
{
	global $is_scanning;
	
	if($is_scanning)
		IR_scan();
}

$btn_str = "";
$btn_list = "";
$sche_str = "";
$sche_list = array();

$reset_day = "";
$update_flag = false;
$last_update_time = st_free_get_count(1);

$count_buf = "";
$cnt_buf_len = 0;

$is_scanning = false;
$settup_tcp_id = -1;

$camera_state = false;

$modes = array( MODE_CAMERA_CTRL, MODE_CAMERA_CTRL, MODE_CAMERA_CTRL, MODE_CAMERA_CTRL, MODE_CAMERA_CTRL );

ws_setup(0, "phpoc_infrared", "csv.phpoc");
ws_setup(1, "phpoc_infrared", "csv.phpoc");
ws_setup(2, "phpoc_infrared", "csv.phpoc");
ws_setup(3, "phpoc_infrared", "csv.phpoc");
ws_setup(4, "phpoc_infrared", "csv.phpoc");

uart_setup(0, 115200);

$camera_state = camera_sync();
if($camera_state)
	$camera_state = camera_setup(CT_JPEG, PR_160x120, JR_320x240);

load_data();

while(1)
{
	schedule_loop();
	network_loop();
	camera_loop();
	IR_scan_loop();
	memory_loop();
}
?>