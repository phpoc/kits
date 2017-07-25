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
$title_console  	= _POST("title_console");
$channel_id 		= _POST("channel_id");
$write_api_key  	= _POST("write_api_key");
$read_api_key  		= _POST("read_api_key");
	
$days  				= _POST("days");
if($days == "0")
	$days  			= "";
$results  			= _POST("results");
if($results == "0")
	$results  		= "";
$ymin  				= _POST("ymin");
if($ymin == "0")
	$ymin  			= "";
$ymax  				= _POST("ymax");
if($ymax == "0")
	$ymax  			= "";

$interval  			= _POST("interval");

envu_update($envu, "title_home", $title_home);
envu_update($envu, "title_console", $title_console);
envu_update($envu, "channel_id", $channel_id);
envu_update($envu, "write_api_key", $write_api_key);
envu_update($envu, "read_api_key", $read_api_key);
envu_update($envu, "days", $days);
envu_update($envu, "results", $results);
envu_update($envu, "ymin", $ymin);
envu_update($envu, "ymax", $ymax);
envu_update($envu, "interval", $interval);

envu_write("nm0", $envu, NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

system("php task0.php");
?>
<script type="text/javascript">
	window.self.location.replace("setup_app.php");	
</script>
