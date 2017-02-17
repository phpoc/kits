<?php
include_once "/lib/sd_340.php";
include_once "/lib/sc_envu.php";
include_once "config.php";

set_time_limit(30);

$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);
if(!($baud = envu_find($envu, "wsm_baud")))
	$baud = "9600";
if(!($ms_unc_path = envu_find($envu, "ms_unc_path")))
	$ms_unc_path = "0";
if(!($title_console = envu_find($envu, "title_console")))
	$title_console = "Temperature Console";
$ws_host = _SERVER("HTTP_HOST");
?>
<!DOCTYPE html>
<html>
<head>
<title>PHPoC</title>
	<meta content="initial-scale=0.5, maximum-scale=1.0, minimum-scale=0.5, width=device-width" name="viewport">
	<link type="text/css" rel="stylesheet" href="common.css">
	<script type="text/javascript">
	var ws;
	 
	var wc_max_len = 32768;

	function ws_connect()
	{
		document.getElementById("ws_state").innerHTML = "OPEN";
		document.getElementById("wc_conn").innerHTML = "Disconnect";    
	}

	function ws_disconnect()
	{
		document.getElementById("ws_state").innerHTML = "CLOSED";
		document.getElementById("wc_conn").innerHTML = "Connect";
	 
		ws.onopen = null;
		ws.onclose = null;
		ws.onmessage = null;
		ws = null;
	}

	function wc_onclick()
	{
		if(ws == null)
		{
			ws = new WebSocket("ws://<?echo _SERVER("HTTP_HOST")?>/temperature", "text.phpoc");
			document.getElementById("ws_state").innerHTML = "CONNECTING";
	 
			ws.onopen = ws_connect;
			ws.onclose = ws_disconnect;
			ws.onmessage = ws_onmessage;
			
		}
		else
			ws.close();
	}
	 
	function ws_init()
	{
		ws = new WebSocket("ws://<?echo _SERVER("HTTP_HOST")?>/temperature", "text.phpoc");
	 
		ws.onopen  = function(){ 
		
		document.getElementById("ws_state").innerHTML = "OPEN";
		document.getElementById("wc_conn").innerHTML = "Disconnect";    
			<?php
			
			$temp60 = "";
			nm_read(0,0,$temp60, 300);
	 
			$temp = explode(",", $temp60, 61); //60ÃÊ
	 
			for($i = 0; $i < count($temp)-1; $i++) 
			{?>
				wc_text.innerHTML += "Temperature <? echo $temp[$i]?> 'C\r\n";
			<?}?>    
		};
		ws.onclose = function(){         
			document.getElementById("ws_state").innerHTML = "CLOSED";
			document.getElementById("wc_conn").innerHTML = "Connect";
	 
			ws.onopen = null;
			ws.onclose = null;
			ws.onmessage = null;
			ws = null;
		};
	   // ws.onerror = function(){ alert("websocket error " + this.url) };
	 
		ws.onmessage = ws_onmessage;
		
	}
	function ws_onmessage(e_msg)
	{
		e_msg = e_msg || window.event; 
	 
		var wc_text = document.getElementById("wc_text");
		var len = wc_text.value.length;
	 
		if(len > (wc_max_len + wc_max_len / 10))
			wc_text.innerHTML = wc_text.value.substring(wc_max_len / 10);
	 
		wc_text.scrollTop = wc_text.scrollHeight;
		wc_text.innerHTML += "Temperature " + e_msg.data + " 'C\r\n";
	}
	function wc_clear()
	{
		document.getElementById("wc_text").innerHTML = "";
	}
	window.onload = ws_init;
	</script>
</head>
<div class="temperature_wrap">
    <div class="header">
	
		<div class="midHeader">
			<center>
				<h1 class="headerTitle"><?echo $title_console;?></h1>
				<div class="headerMenu">
					<div class="left">
						<a href="index.php">HOME</a>| 
						<a href="setup_net.php">SETUP</a>| 
						<a href="index_graph.php">Realtime Web Graph</a>
					</div>
					<div class="right">
					</div>
				</div>
			</center>
		</div>
		
		<div class="subHeader">
		</div>	
	</div>
    <div class="index_contents">

		Web Socket : <span id="ws_state"></span><br /><br />
		<button id="wc_conn" type="button" class="con_button" onclick="wc_onclick();">Connect</button>
		<button id="wc_clear" type="button" class="con_button" onclick="wc_clear();">Clear</button><br /><br />
		<textarea id="wc_text" readonly="readonly"></textarea>
		<br /><br /><br /><br />
    </div>
	<div class="footer">
		<div class="superFooter">
			<a href="http://www.sollae.co.kr/kr/home/" target="_blank">SOLLAE SYSTEMS</a>	
		</div>
	</div>	
</h2>
</body>
</html>
