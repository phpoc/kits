<?php
include_once "/lib/sd_340.php";
include_once "config.php";
include_once "/lib/sc_envu.php";
include_once "/lib/vd_thingspeak.php";

$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

if(!($channel_id = envu_find($envu, "channel_id")))
	$channel_id = "";
if(!($write_api_key = envu_find($envu, "write_api_key")))
	$write_api_key = "";
if(!($interval = envu_find($envu, "interval")))
	$interval = "15";

thingspeak_setup(0, $channel_id, $write_api_key);

$email_address = "";

$adc_value = 0;

// AD0번 핀 설정
adc_setup(0, 0); 

function read_temper()
{
	global $adc_value;
	
	// ADC 값 읽기
	$adc_value = adc_in(0); 
	
	$resistance   	= (float) $adc_value * 10000.0 /(4096.0 - $adc_value);
	$temp 			= 1 / (log($resistance / 10000.0)/3950.0 + 1.0 / 298.15);
	$temp 			= round($temp - 273.15, 1);
	
	return $temp;
}
					
while(1)
{   	
	$temp = 0.0;
	$total_tmp = 0.0;
	
	// 온도 측정 명령 수행	 
	for ($i = 0; $i < 100; $i++)
	{	
		$temp = read_temper();
		$total_tmp += $temp; 
	}	
	
	$temp = round($total_tmp / 100, 1);
 	echo "Temperature: $temp 'C\r\n";

	$result = thingspeak_write("field1=".(string)$temp);
	
	if($result > 0)
		echo "Write succesfully\r\n";
	else
		echo "Write unsuccesfully\r\n";

	sleep((int) $interval);
}	
	
// 타이머 종료
pid_close($pid_ht_in);
pid_close($pid_ht_out);
 
?>
