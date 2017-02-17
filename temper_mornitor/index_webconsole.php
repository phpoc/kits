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
	<style type="text/css">
	body {
		font-family: calibri, verdana, Helvetica, Arial, sans-serif, gulim; 
		margin: 0; padding: 0;
	}
	h1 { font-weight: bold; font-size: 18pt; padding-bottom: 5px;}
	h3 { color: #3E3A39;}
	hr {width: 450px; margin: 30px auto; }
	.con_button { background-color: #A9A9A9; padding:5px 3px; color: #fff; width: 100px; font-size: 10pt; border:0px}
	.temperature_wrap{
		margin:0 auto;
	}	 
	.midHeader {color: white; background-color: rgb(6, 38, 111);  position:fixed; left:0; right:0; z-index:3;}
	.headerTitle {
	  font-size: 2em;
	  font-weight: bold;
		text-align:center;
	}
	.headerMenu{
		position:relative;
		width: 450px;
		margin:0 auto;
		padding: 5px;
	}
	.right {
	  color: white;
	  position: absolute;
	  right: 1px;
	  bottom: 4px;
	  font-size:10pt;		  
	}	
	.left {
	  color: white;
	  position: absolute;
	  left: 1px;
	  bottom: 4px;
	  font-size:10pt;		  
	}
	.right a, .left a
	{
		text-align:center;
	  color: white;
	  background-color: transparent;
	  text-decoration: none;
	  margin: 0;
	  padding:0 1ex 0 1ex;
	}			
	.right a:hover, .left a:hover 
	{
	  color: white;
	  text-decoration: underline;
	 }	
	.contents{
		text-align:center;
		padding-top : 100px; 
		color: #3E3A39; 
	}
	.index_contents{
		margin:0 auto;
		padding-top : 150px;
		text-align:center;
	}
	.index_contents a{
		text-decoration:none;
		color:gray;
		font-size:1.1em;
		line-height:4.5em;
	}
	.index_contents a:hover{
		text-decoration:underline;
	}
	table  {border-collapse:collapse; width:450px; font-size:12pt; text-align:center; margin:0 auto; color: #3E3A39;}
	.theader { font-weight: bold; border-right:solid 1px #ccc;}
	tr { height :35px;}
	td { padding-left: 10px; text-align: left; border-top:solid 1px #ccc; border-bottom:solid 1px #ccc;}
	input.formtext  { border:0; background-color: #eee; padding:5px }
	input.formtext:disabled  { border:0; background-color: #DBDBDB; padding:5px; color: #c6c6c6; }
	textarea { width:400px; height:400px; padding:10px; font-family:courier; font-size:14px; }
	.footer{margin:0 auto; height:auto !important; height:100%; margin-bottom:-100px;  }
	.superFooter {
		text-align: center;
		height: 2em; color: white; background-color: rgb(6, 38, 111); font-size:9pt; position:fixed; left:0; right:0; bottom:0; z-index:4;  font-size:10pt;
	}
	.superFooter a {
		text-decoration: none; color: white; margin:0 auto; position:relative; top:5px;
	}	
	.superFooter a:hover {
		text-decoration: underline;
	}
	</style>
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
