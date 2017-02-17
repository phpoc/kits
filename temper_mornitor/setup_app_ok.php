<?php
include_once "config.php";
include_once "/lib/sc_envu.php";

$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

if(!_SERVER("HTTP_REFERER"))
{
	header('HTTP/1.1 403 Forbidden');

	$php_name = _SERVER("SCRIPT_NAME");

	echo "<html>\r\n",
		"<head><title>403 Forbidden</title></head>\r\n",
		"<body>\r\n",
		"<h1>Forbidden</h1>\r\n",
		"<p>You don't have permission to access /$php_name on this server.</p>\r\n",
		"</body></html>\r\n";

	return;
}

$title_home    		= _POST("title_home");
$title_graph		= _POST("title_graph");
$title_console  	= _POST("title_console");
$email_opt      	= _POST("email_opt");   // Enable 1 - Disbale 0 (email)
$email_min_temp  	= _POST("email_min_temp");
$email_max_temp  	= _POST("email_max_temp");
$email_address 		= _POST("email_address");
$email_server  		= _POST("email_server");
$email_server_port  = _POST("email_server_port");
$email_server_id  	= _POST("email_server_id");
$email_server_pw  	= _POST("email_server_pw");
$interval  			= _POST("interval");

envu_update($envu, "title_home", $title_home);
envu_update($envu, "title_graph", $title_graph);
envu_update($envu, "title_console", $title_console);
envu_update($envu, "email_opt", $email_opt);
envu_update($envu, "email_min_temp", $email_min_temp);
envu_update($envu, "email_max_temp", $email_max_temp);
envu_update($envu, "email_address", $email_address);
envu_update($envu, "email_server", $email_server);
envu_update($envu, "email_server_port", $email_server_port);
envu_update($envu, "email_server_id", $email_server_id);
envu_update($envu, "email_server_pw", $email_server_pw);
envu_update($envu, "interval", $interval);

envu_write("nm0", $envu, NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

system("php task0.php");
?>
<script type="text/javascript">
	window.self.location.replace("setup_app.php");	
</script>
