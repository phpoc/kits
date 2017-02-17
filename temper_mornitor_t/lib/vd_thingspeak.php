<?php

include_once "/lib/vc_http.php";

define("THINGSPEAK_HOSTNAME", "api.thingspeak.com");

// Cache ip help reduce DNS query. It enable by default
$ip_cache = ""; 
$is_cache_ip = true;

$ts_write_api_key = "";
$ts_read_api_key = "";
$ts_channel_id = "";

$ts_tcp_id = 0;

function thingspeak_disable_cache_ip()
{
	global $is_cache_ip;
	global $ip_cache;
	
	$is_cache_ip = false;
	$ip_cache = "";
}

function thingspeak_enable_cache_ip()
{
	global $is_cache_ip;
	global $ip_cache;
	
	$host_addr = dns_lookup(THINGSPEAK_HOSTNAME, RR_A);

	if($host_addr == THINGSPEAK_HOSTNAME)
		exit "api.thingspeak.com : Not Found\r\n";
		
	$ip_cache = "[" . $host_addr . "]";
	$is_cache_ip = true;
}

function thingspeak_set_write_key($key)
{
	global $ts_write_api_key;
	
	$ts_write_api_key = $key;
}

function thingspeak_set_read_key($key)
{
	global $ts_read_api_key;
	
	$ts_read_api_key = $key;
}

function thingspeak_set_channel_id($id)
{
	global $ts_channel_id;
	
	$ts_channel_id = $id;
}

/*
Note: If Channel is public, we can set read_key = "";
*/
function thingspeak_setup($tcp_id, $channel, $write_key, $read_key = "", $cache_ip = true)
{
	global $ts_tcp_id;
	
	$ts_tcp_id = $tcp_id;
	
	thingspeak_set_channel_id($channel);
	thingspeak_set_write_key($write_key);
	thingspeak_set_read_key($read_key);
	if($cache_ip)
		thingspeak_enable_cache_ip();
	else
		thingspeak_disable_cache_ip();
}

/*
Arguments: 
	-$paras: a string contains update infomation: field id and value. for example: field1=25&field2=60
	-$method: POST (default) or GET. 
Return:
	- -1: error when making http request
	- 0: update false. Since ThingSpeak.com has a rate limit of an update every 15 seconds per channel, if update rate is lower than limit, value "0" will be return.
	- interger > 0 : successful. return value is the latest entry id.
*/
function thingspeak_write($paras, $method = "POST")
{
	global $is_cache_ip;
	global $ip_cache;
	global $ts_write_api_key;
	global $ts_tcp_id;
	
	if($ts_write_api_key == "")
		exit "please set write api key\r\n";
	
	$url = "";
	
	if($is_cache_ip)
		$url = $ip_cache;
	else 
		$url = THINGSPEAK_HOSTNAME;
	
	$paras = "api_key=$ts_write_api_key&" . $paras;
	
	$url .= "/update";
	
			
	$reval = http_send_request($ts_tcp_id, $url, $paras, $method);
	
	if($reval)
	{
		$resp = http_response_body();
		$reval = (int)$resp;
	}
	else
		$reval = -1;
	
	
	return $reval;
}

function thingspeak_read_raw_feed($result_num)
{
	global $is_cache_ip;
	global $ip_cache;
	global $ts_tcp_id;
	global $ts_channel_id;
	global $ts_read_api_key;
	
	if($ts_channel_id == "")
		exit "please set channel ID\r\n";
	
	$url = "";
	
	if($is_cache_ip)
		$url = $ip_cache;
	else 
		$url = THINGSPEAK_HOSTNAME;
	
	$url .= "/channels/$ts_channel_id/feeds.json";
	
	$paras = "results=$result_num&api_key=$ts_read_api_key";		
	
	$reval = http_send_request($ts_tcp_id, $url, $paras);
	
	if($reval)
		$reval = http_response_body();
	else
		$reval = "";
	
	return $reval;
}

function thingspeak_read_raw_field($field_id, $result_num)
{
	global $is_cache_ip;
	global $ip_cache;
	global $ts_tcp_id;
	global $ts_channel_id;
	global $ts_read_api_key;
	
	if($ts_channel_id == "")
		exit "please set channel ID\r\n";
	
	$url = "";
	
	if($is_cache_ip)
		$url = $ip_cache;
	else 
		$url = THINGSPEAK_HOSTNAME;
	
	$url .= "/channels/$ts_channel_id/fields/$field_id.json";
	
	$paras = "results=$result_num&api_key=$ts_read_api_key";		
	
	$reval = http_send_request($ts_tcp_id, $url, $paras);
	
	if($reval)
		$reval = http_response_body();
	else
		$reval = "";
	
	return $reval;
}

function thingspeak_read_raw_status()
{
	global $is_cache_ip;
	global $ip_cache;
	global $ts_tcp_id;
	global $ts_channel_id;
	global $ts_read_api_key;
	
	if($ts_channel_id == "")
		exit "please set channel ID\r\n";
	
	$url = "";
	
	if($is_cache_ip)
		$url = $ip_cache;
	else 
		$url = THINGSPEAK_HOSTNAME;
	
	$url .= "/channels/$ts_channel_id/status.json";
	
	$paras = "api_key=$ts_read_api_key";		
	
	$reval = http_send_request($ts_tcp_id, $url, $paras);
	
	if($reval)
		$reval = http_response_body();
	else
		$reval = "";
	
	return $reval;
}

function thingspeak_read_raw_last_feed()
{
	global $is_cache_ip;
	global $ip_cache;
	global $ts_tcp_id;
	global $ts_channel_id;
	global $ts_read_api_key;
	
	if($ts_channel_id == "")
		exit "please set channel ID\r\n";
	
	$url = "";
	
	if($is_cache_ip)
		$url = $ip_cache;
	else 
		$url = THINGSPEAK_HOSTNAME;
	
	$url .= "/channels/$ts_channel_id/feeds/last.json";
	
	$paras = "api_key=$ts_read_api_key";		
	
	$reval = http_send_request($ts_tcp_id, $url, $paras);
	
	if($reval)
		$reval = http_response_body();
	else
		$reval = "";
	
	return $reval;
}

function thingspeak_read_raw_last_field($field_id)
{
	global $is_cache_ip;
	global $ip_cache;
	global $ts_tcp_id;
	global $ts_channel_id;
	global $ts_read_api_key;
	
	if($ts_channel_id == "")
		exit "please set channel ID\r\n";
	
	$url = "";
	
	if($is_cache_ip)
		$url = $ip_cache;
	else 
		$url = THINGSPEAK_HOSTNAME;
	
	$url .= "/channels/$ts_channel_id/fields/$field_id/last.json";
	
	$paras = "api_key=$ts_read_api_key";		
	
	$reval = http_send_request($ts_tcp_id, $url, $paras);
	
	if($reval)
		$reval = http_response_body();
	else
		$reval = "";
	
	return $reval;
}

function thingspeak_read_last_field($field_id)
{
	global $is_cache_ip;
	global $ip_cache;
	global $ts_tcp_id;
	global $ts_channel_id;
	global $ts_read_api_key;
	
	if($ts_channel_id == "")
		exit "please set channel ID\r\n";
	
	$url = "";
	
	if($is_cache_ip)
		$url = $ip_cache;
	else 
		$url = THINGSPEAK_HOSTNAME;
	
	$url .= "/channels/$ts_channel_id/fields/$field_id/last.txt";
	
	$paras = "api_key=$ts_read_api_key";		
	
	$reval = http_send_request($ts_tcp_id, $url, $paras);
	
	if($reval)
	{
		$restr = http_response_body();
		$rearr = explode("\r\n", $restr);	
		$reval = $rearr[1];
	}
	else
		$reval = "";
	
	return $reval;
}

?>