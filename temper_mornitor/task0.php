<?php
include_once "/lib/sd_340.php";
include_once "/lib/sn_tcp_ws.php";
include_once "/lib/sn_dns.php";
include_once "/lib/sn_esmtp.php";
include_once "config.php";
include_once "/lib/sc_envu.php";

$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

if(!($email_opt = envu_find($envu, "email_opt")))
	$email_opt = "0";
if(!($email_min_temp = envu_find($envu, "email_min_temp")))
	$email_min_temp = "0";
if(!($email_max_temp = envu_find($envu, "email_max_temp")))
	$email_max_temp = "50";
if(!($interval = envu_find($envu, "interval")))
	$interval = "1";

for ($i = 1; $i < 5; $i++ )
{
    ws_setup($i, "temperature", "text.phpoc");
}  

$email_address = "";

$adc_value = 0;
$pid = pid_open("/mmap/adc0"); // 0번 ADC 열기
pid_ioctl($pid, "set ch 0"); // 0번 채널로 설정

$email_send_flag = 0;

$temp60 = "";
nm_read(0, 0, $temp60, 300);
 
if ((Bool)strpos($temp60, ",") == FALSE)	
	$temp60 = str_repeat("--.-,", 60);


function setup_mail()
{
	global $email_address;
	
	$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

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

	$tv_cbc_key = "\x06\xa9\x21\x40\x36\xb8\xa1\x5b\x51\x2e\x03\xd5\x34\x12\x00\x06";
	$tv_cbc_iv  = "\x3d\xaf\xba\x42\x9d\x9e\xb4\x30\xb4\x22\xda\x80\x2c\x9f\xac\x41";
		
	$email_server_pw_base64 =  system("base64 dec %1", $email_server_pw);

	$aes = system("aes init cbc dec %1 %2", $tv_cbc_key, $tv_cbc_iv);
	$email_server_pw = substr(system("aes crypt %1 %2", $aes, $email_server_pw_base64), 0, 32);
	$email_server_pw = str_replace(" ", "", $email_server_pw);  

	esmtp_account($email_server_id, "From PHPoC");
	esmtp_auth($email_server_id, $email_server_pw);
	esmtp_msa($email_server, (int) $email_server_port);

}

function read_temper()
{
	global $pid;
	global $adc_value;
	
	pid_read($pid, $adc_value); // ADC 값 읽기
	
	$resistance   	= (float) $adc_value * 10000.0 /(4096.0 - $adc_value);
	$temp 			= 1 / (log($resistance / 10000.0)/3950.0 + 1.0 / 298.15);
	$temp 			= round($temp - 273.15, 1);
	
	return $temp;
}
	
if ($email_opt == "1")
{
	esmtp_setup(0, 0, "");
	setup_mail();
}
						
while(1)
{   
	global $email_address;
	
	$temp = 0.0;
	$temp_split = explode(",", $temp60, 2);
	
	$temp_oldest_len = strlen($temp_split[0]) + 1;
	$temp60 = substr($temp60, $temp_oldest_len);
	
	$total_tmp = 0.0;
	
	//온도 측정 명령 수행	 
	for ($i=0; $i<100; $i++)
	{	
		$temp = read_temper();
		$total_tmp += $temp; 
	}	
	
	$temp = round($total_tmp / 100, 1);
 	echo "Temperature: $temp 'C\r\n";

	if (is_nan((float)$temp) != TRUE && (float)$temp >= 0 && (float)$temp <= 50 )
	{
		$temp = sprintf("%.01f", $temp);
		
		if ( strlen($temp) == 3  )
			$temp = "0" . $temp; //글자 자리수 맞추기
		
		if ( strlen(rtrim(ltrim($temp))) == 4 && is_nan((float)$temp) != TRUE )
		{
			$temp60 = $temp60 . (string)$temp . ",";
			nm_write(0, 0, $temp60); 
		}	
	
		if ($email_opt == "1")
		{
			if (( (float) $email_min_temp > (float) $temp || (float) $temp > (float) $email_max_temp ) )
			{
				if ( $email_send_flag == 0 )
				{
					Echo "email\r\n";
					$subject = "Temperature Notification";
					$message = "Current Temperature : ". $temp ."'C\r\n";
					echo "email_min_temp : $email_min_temp\r\n";
					echo "temp : $temp\r\n";
					echo "email_max_temp : $email_max_temp\r\n";
					
					$msg = esmtp_send($email_address, "Noti Reciever", $subject, $message);
					
					if($msg == "221"){
						echo "send mail successful\r\n";
						$email_send_flag = 1;
						echo "email_send_flag : 1\r\n";
					}
					else
						echo "send mail failed\r\n";
				}
			}
			else 
			{
				$email_send_flag = 0;
				
				echo "email_send_flag : 0\r\n";
				
			}
		}
	}	
	else
	{		
		$temp60 = $temp60 . "--.-,";
		nm_write(0, 0, $temp60); 
		$temp = "--.-"; 
	}
		
	for($i = 1; $i < 5; $i++ )
    {
        if(ws_state($i) == TCP_CONNECTED)
            ws_write($i, "$temp");
        
    }
	
	sleep((int) $interval);
}		
//타이머 종료
pid_close($pid_ht_in);
pid_close($pid_ht_out);
 
?>
