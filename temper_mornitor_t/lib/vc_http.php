<?php

include_once "/lib/sd_340.php";
include_once "/lib/sn_dns.php";

$resp_header = "";
$resp_body = "";

function http_response_header()
{
	global $resp_header;
	
	return $resp_header;
}

function http_response_body()
{
	global $resp_body;
	
	return $resp_body;
}

/* 
To save memory
*/
function http_clear_header_buf()
{
	global $resp_header;
	
	$resp_header = "";
}

/* 
To save memory
*/
function http_clear_body_buf()
{
	global $resp_body;
	
	$resp_body = "";
}

/* 
Usage Example:
	- http_send_request(1, "[192.168.0.3]", "a=hello&b=PHPoC");
	- http_send_request(1, "domainname.com/path/filename.php", "a=hello&b=PHPoC");
 */
function http_send_request($tcp_id, $url, $paras, $method = "GET")
{
	global $resp_header;
	global $resp_body;
	
	$resp_header = "";
	$resp_body = "";
	
	$pos = strpos($url, "/");
	
	if(is_bool($pos) && $pos == false)
	{
		$host_name = $url;
		$path = "";
	}
	else
	{
		$host_name = substr($url, 0, $pos);
		$path = substr($url, $pos +1);
	}	
	
	$hn_len = strlen($host_name);
	
	if($host_name[0] == "[" && $host_name[$hn_len-1] == "]")
		$host_addr = substr($host_name, 1, $hn_len -2);
	else
	{
		$host_addr = dns_lookup($host_name, RR_A);

		if($host_addr == $host_name)
		{
			echo "http: $host_name : Not Found\r\n";
			return false;
		}
	}
	
	// Open TCP
	$http_pid = pid_open("/mmap/tcp$tcp_id"); 
	
	pid_bind($http_pid, "", 0);
	
	pid_connect($http_pid, $host_addr, 80);

	for(;;)
	{
		$state = pid_ioctl($http_pid, "get state");

		if($state == TCP_CLOSED)
		{
			pid_close($http_pid);
			echo "http: connection failed\r\n";
			return false;
		}

		if($state == TCP_CONNECTED)
			break;
	}

	if($method = "GET")
	{
		$http_req  = "GET /" . $path . "?" . $paras . " HTTP/1.1\r\n";
		$http_req .= "Host: $host_addr\r\n";
		$http_req .= "Connection: closed\r\n";
		$http_req .= "\r\n\r\n";
	}
	else
	{
		$len =(string) strlen($paras);
	
		// Header
		$http_req  = "POST /" . $path . " HTTP/1.1\r\n";
		$http_req .= "Host: $host_addr\r\n";
		$http_req .= "Content-Length: " . $len ."\r\n";
		$http_req .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$http_req .= "Connection: closed\r\n";
		$http_req .= "\r\n";
		
		// Body
		$http_req .= $paras;
		
		$http_req .= "\r\n\r\n";
	}

	pid_send($http_pid, $http_req);
	
	
	$rbuf = "";
	$reponse = "";
	
	$is_body = false;
	
	$body_len = 0;
	
	for(;;)
	{
		if(($len = pid_recv($http_pid, $rbuf)) > 0)
		{
			if(!$is_body)
			{
				$reponse .= $rbuf ;
				
				$pos = strpos($reponse,  "\r\n\r\n");
				
				if(!is_bool($pos) )
				{
					$body_len = $len - $pos - 4;
					
					if($body_len > MAX_STRING_LEN)
					{
						echo "http: \r\nbody size exceed the buffer size";
						break;
					}
					
					$resp_header = substr($reponse, 0, $pos);
					// Excluse "\r\n\r\n"
					$resp_body = substr($reponse, $pos + 4); 
				}
			}
			else
			{				
				$body_len +=$len;
				
				if($body_len > MAX_STRING_LEN)
				{
					echo "http: \r\nbody size exceed the buffer size";
					break;
				}
				
				$resp_body .= $rbuf;
			}
			continue;
		}

		if(pid_ioctl($http_pid, "get state") == TCP_CLOSED)
			break;
	}

	// Close TCP
	pid_close($http_pid); 	

	return true;
}

?>