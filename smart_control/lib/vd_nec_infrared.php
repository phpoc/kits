<?php
 
if(_SERVER("REQUEST_METHOD"))
    exit; // avoid php execution via http request

define("BASIC_CLOCK",   42000000); // basic clock of PHPoC 42MHz

$recv_ht_id = 0; // timer id which connect to an infrared receiver to capture data
$emit_control_ht_id = 1; // timer id which control an infrared emitter
$emit_carrier_ht_id = 2; // timer id which create the carier for infrared modulation

function infrared_setup($rec_ht_id, $control_ht_id, $carrier_ht_id)
{
	global $recv_ht_id;
	global $emit_control_ht_id;
	global $emit_carrier_ht_id;
	
	$recv_ht_id = $rec_ht_id;
	$emit_control_ht_id = $control_ht_id;
	$emit_carrier_ht_id = $carrier_ht_id;
}
/*
This function make timer to start capturing signal from an infrared receiver. 
It is used to analyze the pulse chain, so it it set to capture toggle mode.
Paramerer: 
	-$unit (microsecond);
	-$repc: the number of pulse need to be captured
*/
function infrared_capture_start($unit, $repc)
{
	global $recv_ht_id;
	
	$repc++; // plus one dummy pulse
	$unit = $unit * BASIC_CLOCK / 1000000;
	// setup capture timer
	ht_ioctl($recv_ht_id, "reset");
	ht_ioctl($recv_ht_id, "set div $unit");
	ht_ioctl($recv_ht_id, "set mode capture toggle");
	ht_ioctl($recv_ht_id, "set trigger from pin fall");
	ht_ioctl($recv_ht_id, "set repc $repc");
	ht_ioctl($recv_ht_id, "start"); // start trigger pulse	
}

/*
This function make timer to start capturing signal from an infrared receiver. 
It is used to get data, so it it set to capture fall mode.
Paramerer: 
	-$unit (microsecond);
	-$repc: the number of pulse need to be captured
*/
function infrared_recv_start($unit, $repc)
{
	global $recv_ht_id;
	
	$repc++; // plus one dummy pulse
	$unit = $unit * BASIC_CLOCK / 1000000;
	// setup capture timer
	ht_ioctl($recv_ht_id, "reset");
	ht_ioctl($recv_ht_id, "set div $unit");
	ht_ioctl($recv_ht_id, "set mode capture fall");
	ht_ioctl($recv_ht_id, "set trigger from pin fall");
	ht_ioctl($recv_ht_id, "set repc $repc");
	ht_ioctl($recv_ht_id, "start"); // start trigger pulse	
}

function infrared_recv_stop()
{
	global $recv_ht_id;
	
	ht_ioctl($recv_ht_id, "stop");
}

function infrared_carrier_start($freq)
{
	global $emit_carrier_ht_id;
	
    $div = BASIC_CLOCK / ($freq * 2);
 
	ht_ioctl($emit_carrier_ht_id, "reset");
    ht_ioctl($emit_carrier_ht_id, "set div $div"); // div 13.14us
    ht_ioctl($emit_carrier_ht_id, "set mode output pwm");
    ht_ioctl($emit_carrier_ht_id, "set output od");
    ht_ioctl($emit_carrier_ht_id, "set count 1 1");
    ht_ioctl($emit_carrier_ht_id, "start");
}

function infrared_carrier_stop()
{
	global $emit_carrier_ht_id;
	
    ht_ioctl($emit_carrier_ht_id, "stop");
	ht_ioctl($emit_carrier_ht_id, "set output high");
}

/*
see nec_infrared_send($data, $bit_length) function to know how to use this function
*/
function infrared_emit($count_buf, $cnt_buf_len, $unit)
{
	global $emit_control_ht_id;
	
	$unit = $unit * BASIC_CLOCK / 1000000;
	
	infrared_carrier_start(38000); //enable 38KHz PWM signal - carrier frequency
	
	//ht_ioctl($emit_control_ht_id, "reset");
	ht_ioctl($emit_control_ht_id, "set div $unit");
	ht_ioctl($emit_control_ht_id, "set mode output toggle"); // set mode: toggle
	ht_ioctl($emit_control_ht_id, "set output od");
	
	$pid_ht = pid_open("/mmap/ht$emit_control_ht_id");
    pid_write($pid_ht, $count_buf);
    pid_ioctl($pid_ht, "set repc $cnt_buf_len");
    pid_close($pid_ht);
	
	ht_ioctl($emit_control_ht_id, "start"); // start HT
	
	while(ht_ioctl($emit_control_ht_id, "get state"));
	
	ht_ioctl($emit_control_ht_id, "stop");
	
	infrared_carrier_stop(); // stop to save energy.
}

/* 
Paramerer: 
	-$lower_bound: minimum value of the first captured pulse in microsecond
	-$upper_bound: minimum value of the first captured pulse in microsecond
Return:
	-true: value of the first captured pulse varies from $lower_bound to $upper_bound
	-false: otherwise.
*/
function infrared_available($lower_bound, $upper_bound)
{
	global $recv_ht_id;
	
	$unit = ht_ioctl($recv_ht_id, "get div"); // in number clock stick
	$unit = $unit * 1000000 / BASIC_CLOCK; // in microsecond
	$lower_bound /= $unit;
	$upper_bound /= $unit;
	
	$count = ht_ioctl($recv_ht_id, "get count 1");
	
	if( ($count >= $lower_bound) && ($count <= $upper_bound))
		return true;
	
	return false;
}

/* 
Paramerer: $count_id start from 0.
Return: width of captured pulse in microsecond
*/
function infrared_get_count($count_id)
{
	global $recv_ht_id;
	
	$count_id++; // due to the dummy value (first value)
	$unit = ht_ioctl($recv_ht_id, "get div");
	$count = ht_ioctl($recv_ht_id, "get count $count_id");
	$reval = $unit * $count * 1000000 / BASIC_CLOCK;
	
	return $reval;
}

/* 
Paramerer: $count_id start from 0.
Return: width of captured pulse in unit
*/
function infrared_get_raw_count($count_id)
{
	global $recv_ht_id;
	
	$count_id++; // due to the dummy value (first value)
	$count = ht_ioctl($recv_ht_id, "get count $count_id");
	
	return $count;
}

function nec_infrared_send($data, $bit_length)
{
	
	$unit = 562.5;// 562.5µs
	
	$count_buf = int2bin(1, 2);    // first value is dummy.
	$count_buf .= int2bin(16, 2);   // 9ms leading pulse burst (16 * 562.5us)
	$count_buf .= int2bin(8, 2);    //  4.5ms space (8 * 562.5us)
	$cnt_buf_len = 3;

			
	$mask = 1 << ($bit_length-1);
	
	while($mask)
	{
		$count_buf .= int2bin(1, 2);    // 562.5µs pulse burst
		$cnt_buf_len++;
		
		if($data & $mask)
		{
			$count_buf .= int2bin(3, 2);    // logical 1: 1.6875ms space
		}
		else 
		{
			 $count_buf .= int2bin(1, 2);    // Logical 0 562.5µs space
		}
		$cnt_buf_len++;
		
		$mask = $mask >> 1;
	}
	
	$count_buf .= int2bin(1, 2);    // 562.5µs stop code
	$cnt_buf_len++;
	
	infrared_emit($count_buf, $cnt_buf_len, $unit);	
}

function nec_infrared_recv($bit_length)
{	
	$data = 0;
	$mask = 1 << ($bit_length -1);
	for($i = 1; $i <= $bit_length; $i++)
	{
		$us = infrared_get_count($i);
		if($us < 2500)
		if($us > 1500)
		{
			$data |= ($mask>> ($i-1));
		}
	}
	return $data;
}

?>