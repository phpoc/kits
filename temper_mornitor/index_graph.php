<?php
$ws_host = _SERVER("HTTP_HOST");

set_time_limit(30);
 
include_once "/lib/sd_340.php";
include_once "/lib/sn_tcp_ws.php";
include_once "config.php";
include_once "/lib/sc_envu.php";

$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

if(!($title_graph = envu_find($envu, "title_graph")))
	$title_graph = "Temperature Graph";
?>
<!DOCTYPE html>
<html>
<head>
<title>PHPoC</title>
	<meta content="initial-scale=0.5, maximum-scale=1.0, minimum-scale=0.5, width=device-width" name="viewport">
	<link type="text/css" rel="stylesheet" href="common.css">
	<script type="text/javascript">
	var ws;
	var canvas;
	var ctx;
	var WIDTH = 650;
	var HEIGHT = 460;
	var graphWIDTH = 590;
	var graphHEIGHT = 400;
	var padding = 30;
	var temps = Array(60); 
	<?php

	$temp60 = "";
	nm_read(0,0,$temp60, 300);
	$temp = explode(",", $temp60, 61); //60ÃÊ

	for($i = 0; $i < count($temp)-1; $i++) 
	{?>
	temps[<?echo $i?>] = <? echo (float) $temp[$i]?>; 
	<?}?>	

	function addlabels(){
	ctx.font = "15px calibri";
	ctx.fillStyle = "#383838";
	/* y axis labels */
	ctx.fillText("Temperature ('C)", 2, 15);
	ctx.fillText(" 50", 5, 35);
	ctx.fillText(" 40", 5, 115);
	ctx.fillText(" 30", 5, 195);
	ctx.fillText(" 20", 5, 275);
	ctx.fillText(" 10", 5, 355);
	ctx.fillText(" 0",  10, 435);
	/* x axis labels */
	ctx.fillText("Time (Sec.)", 570, 450);
	}

	function drawguide()
	{
		ctx.lineWidth = 0.5;  	
		ctx.strokeStyle = "#d3d3d3";
		ctx.beginPath();
		
		//10'c
		ctx.moveTo(padding, 350);
		ctx.lineTo(graphWIDTH + padding, 350);
		ctx.stroke();  
		//20'c
		ctx.moveTo(padding, 270);
		ctx.lineTo(graphWIDTH + padding, 270);
		ctx.stroke();  
		//30'c
		ctx.moveTo(padding, 190);
		ctx.lineTo(graphWIDTH + padding, 190);
		ctx.stroke();  
		//40'c
		ctx.moveTo(padding, 110);
		ctx.lineTo(graphWIDTH + padding, 110);
		ctx.stroke();  
		
	}

	function drawXY()
	{
		ctx.lineWidth =  4;  	
		ctx.strokeStyle = "#828282";
		ctx.beginPath();
		/* y axis along the left edge of the canvas*/  
		ctx.moveTo(padding,padding);
		ctx.lineTo(padding,graphHEIGHT + padding+2);
		ctx.stroke();  
		/* x axis along the bottom edge of the canvas*/  
		ctx.moveTo(padding, graphHEIGHT + padding);
		ctx.lineTo(graphWIDTH + padding,graphHEIGHT + padding);
		ctx.stroke();  
		
	}

	function plotdata()
	{
		ctx.lineWidth = 3;  
		ctx.strokeStyle = "#20B2AA";
		ctx.shadowBlur = 2;
		ctx.shadowColor = 'rgb(255, 255, 255)';	
		ctx.beginPath();  
		
		for (var j in temps)
		{
			val = HEIGHT-(temps[j] * 8); 
			ctx.lineTo((j*10) + padding, (graphHEIGHT + padding) - (temps[j]) * 8); //1ÃÊ¿¡ 10ÇÈ¼¿¾¿ ¿·À¸·Î
			ctx.stroke();
		} 
	}

	function clear() {
		ctx.clearRect(0, 0, WIDTH, HEIGHT);
	}

	function draw() {
		clear();
		drawguide();
		plotdata();  
		drawXY();
		addlabels();
	}

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
		document.getElementById("ws_state").innerHTML = "CONNECTING";
	 
		ws.onopen  = function()
		{ 
			document.getElementById("ws_state").innerHTML = "OPEN";
			document.getElementById("wc_conn").innerHTML = "Disconnect";   
		};
		ws.onclose = function()
		{
			document.getElementById("ws_state").innerHTML = "CLOSED"
			document.getElementById("wc_conn").innerHTML = "Connect";
			
			ws.onopen = null;
			ws.onclose = null;
			ws.onmessage = null;
			ws = null;
		};
		//ws.onerror = function(){ alert("websocket error " + this.url) };
	 
		ws.onmessage = ws_onmessage;
	}

	function temp_array(temp)
	{
		var last_pos;
		last_pos = 59;
		
		for (var i =  0; i < last_pos; i ++ )
			temps[i] = temps[i+1];
			
		temps[last_pos] = temp;  
	}

	function ws_onmessage(e_msg)
	{
		e_msg = e_msg || window.event; 

		temp_array(e_msg.data);

		canvas  = document.getElementById("canvas_graph");
		ctx = canvas.getContext("2d");
		draw();
		
		document.getElementById("ws_reply").innerHTML = "Current Temperature : " + e_msg.data + " 'C";    
	}
	window.onload = ws_init;
	</script>
</head>
<body>
<div class="temperature_wrap">
    <div class="header">
	
		<div class="midHeader">
			<center>
				<h1 class="headerTitle"><?echo $title_graph;?></h1>
				<div class="headerMenu">
					<div class="left">
						<a href="index.php">HOME</a>| 
						<a href="setup_net.php">SETUP</a>| 
						<a href="index_webconsole.php">Realtime Web Console</a>
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
		<button id="wc_conn" type="button" class="con_button" onclick="wc_onclick();">Connect</button><br /><br /><br />
		<h3><span id="ws_reply"></span></h3><br />
		
		<canvas id="canvas_graph" width="650" height="460"></canvas><br />
		<br /><br /><br /><br />
    </div>
	<div class="footer">
		<div class="superFooter">
			<a href="http://www.sollae.co.kr/kr/home/" target="_blank">SOLLAE SYSTEMS</a>	
		</div>
	</div>	
</div>	
</body>
</html>
