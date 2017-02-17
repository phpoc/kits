<!DOCTYPE html>
<html>
<head>
	<title>PHPoC</title>
	<meta content="initial-scale=0.7, maximum-scale=1.0, minimum-scale=0.5, width=device-width, user-scalable=yes" name="viewport">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
	<style type="text/css">
	body {
		font-family: calibri, verdana, Helvetica, Arial, sans-serif, gulim;
		margin: 0; padding: 0;
	}
	h1 { font-weight: bold; font-size: 13pt; padding-bottom: 3px; }
	hr { width: 450px; margin: 10px auto; }
	.wrapper { margin:0 auto; }	 
	.midHeader { 
		color: white; background-color: rgb(6, 38, 111); 
		position:fixed; left:0; right:0; z-index:3;
	}
	.headerTitle {
		font-size: 2em;
		font-weight: bold;
		text-align: center;
	}
	.headerMenu {
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
	.right a, .left a {
		text-align:center;
		color: white;
		background-color: transparent;
		text-decoration: none;
		margin: 0;
		padding:0 1ex 0 1ex;
	}			
	.right a:hover, .left a:hover {
		color: white;
		text-decoration: underline;
	}	
	.contents {
		text-align:center;
		padding-top : 100px; 
		color: #3E3A39; 
	}
	.footer { 
		margin:0 auto; height:auto !important; height:100%; margin-bottom:-100px; 
	}
	.superFooter {
		text-align: center; height: 2em; color: white; background-color: rgb(6, 38, 111); font-size:9pt; position:fixed; left:0; right:0; bottom:0; z-index:4;  font-size:10pt;
	}
	.menu:hover { cursor: pointer; text-decoration: underline; }
	#websocket_area, #setup_area {
		width: 450px;
		display: table;
	}
	#row1, #row2  {
		width: 450px;
		height: 100%;
		display: table-row;
	}
	#left1, #right1 {	
		display: table-cell;
		height: 10px;
	}
	#control_button {		
		background:lightgrey;
		display: table-cell;
		border:solid 1px #ccc;
		padding:10px 20px;
	}
	.td_fix { width: 100px; height:10px; }
	#row2_1, #row2_2, #row2_3, #row2_4, #row2_5 { height: 70px; margin-bottom : 15px }
	.button1 {
		display: inline-block; width: 95px; height: 60px; 
		border-radius: 50%; font-size: 20px; color: #A8A8A8; line-height: 60px;
		text-align: center; font-weight: bold; background: #eee; margin: 7px;
	}
	#A {float: left; width: 110px;}
	#B {float: right; width: 110px;}
	.button2 {
		display: inline-block; width: 95px; height: 50px; font-size: 20px; color: #D8D8D8; line-height: 50px; text-align: center; font-weight: bold; background: #eee; margin:20px 5px; 
	}
	#camera_area {
		margin-right: auto;
		margin-left: auto; 
		position: relative;
		margin-bottom: 5px;
	}
	#canvas {
		margin-right: auto;
		margin-left: auto;
		width: 370px; 
		height: 200px; 
		background-size: contain;
		position: relative;
		margin-bottom: 5px;
		border: 1px solid #ccc;
	}
	.footer { 
		margin:0 auto; height:auto !important; height:100%; margin-bottom:-100px;
	}
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

	//command sent from client to PHPoC
	var CMD_GET_CONFIG  = 0x01;
	var CMD_CONTROL     = 0x02;
	var CMD_UPDATE      = 0x03;
	var CMD_SCAN        = 0x04;
	var CMD_TEST        = 0x05;
	var CMD_SCAN_CANCEL = 0x06;
	var CMD_MODE        = 0x07;
	var CMD_REMOVE      = 0x08;

	//command sent from PHPoC to client
	var _CMD_CONFIG_DATA       = 0x11;
	var _CMD_SCANNED           = 0x12;
	var _CMD_UPDATED           = 0x13;
	var _CMD_CAMERA_DATA_START = 0x14;
	var _CMD_CAMERA_DATA       = 0x15;
	var _CMD_CAMERA_DATA_STOP  = 0x16;	
	var _CMD_REQUEST_DENY      = 0x17;
	
	var MODE_SETTING 	  = 0x02;
	var MODE_CAMERA_CTRL  = 0x03;
	
	var STATE_UNSCAN   = 0x01;
	var STATE_SCANNING = 0x02;
	var STATE_SCANNED  = 0x03;
	
	var setup_state = STATE_UNSCAN;// state of setup mode
	
	var btn_states = null; // an array to store button state (clickable or unclickable)
	var mode = MODE_CAMERA_CTRL;
	
	var update_time = 0;
	
	var camera_data = "";
	var color_clickable_but1 = "#038699";
	var color_clickable_but2 = "#1d64a0"; 
	var color_clicked_but2 = "#06266F"; 
	var color_unclickable = "#eee";
	var color_setup = "#ca0000";
	var color_active_font = "#fff";
	var color_inactive_font = "#A8A8A8";
	
	var setup_button_id = null;
	var connected = false;
	
	var ws = null;

	function init() 
	{
		
		if(ws == null)
		{
			ws = new WebSocket("ws://<?echo _SERVER("HTTP_HOST")?>/phpoc_infrared", "csv.phpoc");
			document.getElementById("ws_state").innerHTML = "CONNECTING";
			
			ws.onopen = ws_onopen;
			ws.onclose = ws_onclose;
			ws.onmessage = ws_onmessage; 
		}
		else
			ws.close();
		
		var view = document.querySelector(".headerMenu");
		view.onclick = option_onclick;
		
		var controls = document.querySelector("#control_button");
		var setups = document.querySelector("#setup_button");
		
		controls.ontouchstart = setups.ontouchstart = mouse_down;
		controls.ontouchend = setups.ontouchend = mouse_up;
		controls.ontouchcancel = setups.ontouchcancel = mouse_up;
		controls.onmousedown = setups.onmousedown = mouse_down;
		controls.onmouseup = setups.onmouseup = mouse_up;
		controls.onmouseout = setups.onmouseout = mouse_up;   
				
		var date = new Date();
		update_time = date.getTime();
	}
	function ws_onopen()
	{
		document.getElementById("ws_state").innerHTML = "OPEN";
		document.getElementById("wc_conn").innerHTML = "Disconnect";
		send_command(CMD_MODE + " " + mode);
		send_command(CMD_GET_CONFIG);
		connected = true;
		update_view();
	}
	
	function ws_onclose()
	{
		document.getElementById("ws_state").innerHTML = "CLOSED";
		document.getElementById("wc_conn").innerHTML = "Connect";
		console.log("socket was closed");
			
		ws.onopen = null;
		ws.onclose = null;
		ws.onmessage = null;
		ws = null;
		
		connected = false;
		setup_state = STATE_UNSCAN;
		
		update_view();
	}
	
	function wc_onclick()
	{
		if(ws == null)
		{
			ws = new WebSocket("ws://<?echo _SERVER("HTTP_HOST")?>/phpoc_infrared", "csv.phpoc");
			document.getElementById("ws_state").innerHTML = "CONNECTING";
			
			ws.onopen = ws_onopen;
			ws.onclose = ws_onclose;
			ws.onmessage = ws_onmessage; 
		}
		else
			ws.close();
	}	
	
	function mouse_down(event) 
	{
		event.stopPropagation();    
		event.preventDefault(); 
		
		if (!connected)
			return;
		
		if(event.target === event.currentTarget) 
			return;
				
		var id = event.target.id;	
		var pre = id.substring(0, 3);
		
		if(pre === "row")
			return;
		
		if(event.currentTarget.id == "control_button")
		{
			if(mode == MODE_SETTING)
			{
				setup_button_id = id;
				update_view();
			}
			else
			{
				if (btn_states != null && btn_states[id])
				{
					event.target.style.backgroundColor = color_clicked_but2;
					event.target.style.color = color_active_font;
					send_command(CMD_CONTROL + " " + id);
				}
			}			
		}
		else if (event.currentTarget.id == "setup_button")
		{
			switch (id)
			{
				case "scan":
					setup_state = STATE_SCANNING;
					send_command(CMD_SCAN);
					event.target.style.backgroundColor = color_clicked_but2;
					break;
				case "save":
					if(setup_button_id != null && setup_state == STATE_SCANNED)
					{		
						var date = new Date();
						var cur_time = date.getTime();
						if((cur_time - update_time) > 2000)
						{
							update_time = cur_time;
							send_command(CMD_UPDATE + " " + setup_button_id);
							document.getElementById("scan").style.backgroundColor = color_clicked_but2;
							document.getElementById("save").style.backgroundColor = color_clicked_but2;
							document.getElementById("test").style.backgroundColor = color_unclickable;
						}
						else
						{
							alert("Can't do two consecutive actions(save or delete) within 2 seconds\nTry again");
						}											
					}
					break;
				case "test":
					if(setup_state == STATE_SCANNED)
					{
						send_command(CMD_TEST);
						event.target.style.backgroundColor = color_clicked_but2;
					}
					break;
				case "delete":
					if(setup_button_id != null)
					{
						if (btn_states != null && btn_states[setup_button_id])
						{														
							var date = new Date();
							var cur_time = date.getTime();
							if((cur_time - update_time) > 2000)
							{
								update_time = cur_time;
								send_command(CMD_REMOVE + " " + setup_button_id);
								event.target.style.backgroundColor = color_clicked_but2;	
							}
							else
							{
								alert("Can't do two consecutive actions(save or delete) within 2 seconds\nTry again");
							}
						}
					}
					break;
			}
		}   
	}
	
	function mouse_up(event) 
	{
		if (connected && event.target !== event.currentTarget) 
		{
			update_view();
		}
		event.stopPropagation();   
		event.preventDefault();    
	}	
	
	function option_onclick(event)
	{
		if (event.target !== event.currentTarget) 
		{
			var id = event.target.id;
			
			setup_state = STATE_UNSCAN;
			setup_button_id = null;
			
			switch(id)
			{
				case "setup":
					mode = MODE_SETTING;									
					break;				
					
				case "control":
					mode = MODE_CAMERA_CTRL;
					send_command(CMD_SCAN_CANCEL);
					break;
							
			}
			
			send_command(CMD_MODE + " " + mode);
			
			update_view();
		} 
	}
	
	function send_command(cmd) 
	{	
		if(ws != null)
			if(ws.readyState == 1)
				ws.send(cmd + " \r\n"); 
		
		console.log("cmd:"+cmd);
	}
	
	function update_view()
	{
		if(mode == MODE_CAMERA_CTRL)
		{
			var setup_button = document.getElementById('setup_button');
			setup_button.style.display = 'none';
			
			var camera_area = document.getElementById('camera_area');
			camera_area.style.display = 'block';
			
		}
		else if(mode == MODE_SETTING)
		{		
			var setup_button = document.getElementById('setup_button');
			setup_button.style.display = 'block';
			
			var camera_area = document.getElementById('camera_area');
			camera_area.style.display = 'none';
		}
		
		if(connected)
		{
			if(mode == MODE_SETTING)
			{
				for (btn in btn_states) 
				{
					var button = document.getElementById(btn);
					
					if(button != null)
					{
						if (btn_states[btn])
						{	
							button.style.backgroundColor = color_clickable_but1;
							button.style.color = color_active_font;
						}
						else
						{
							button.style.backgroundColor = color_unclickable;
							button.style.color = color_inactive_font;
						}							
					}
					
					if (setup_button_id != null && btn == setup_button_id)
					{	document.getElementById(btn).style.backgroundColor = color_setup;
							document.getElementById(btn).style.color = color_active_font;}
						
				}
				
				switch(setup_state)
				{
					case STATE_UNSCAN:
						document.getElementById("scan").style.backgroundColor = color_clickable_but2;   
						document.getElementById("save").style.backgroundColor = color_unclickable;
						document.getElementById("test").style.backgroundColor = color_unclickable;
						break;
					case STATE_SCANNING:
						// SCAN button is blinking. see setInterval function
						document.getElementById("save").style.backgroundColor = color_unclickable;
						document.getElementById("test").style.backgroundColor = color_unclickable;
						break;
					case STATE_SCANNED:
						document.getElementById("scan").style.backgroundColor = color_clicked_but2;
						document.getElementById("test").style.backgroundColor = color_clickable_but2;
						if(setup_button_id != null)
							document.getElementById("save").style.backgroundColor = color_clickable_but2;
						break;
				}

				if(setup_button_id != null && btn_states != null && btn_states[setup_button_id])
					document.getElementById("delete").style.backgroundColor = color_clickable_but2;
				else
					document.getElementById("delete").style.backgroundColor = color_unclickable;
			}
			else
			{
				for (btn in btn_states) 
				{
					var button = document.getElementById(btn);
					
					if(button != null)
					{
						if (btn_states[btn])
						{	
							button.style.backgroundColor = color_clickable_but1;
							button.style.color = color_active_font;
						}
						else
						{	
							button.style.backgroundColor = color_unclickable;
							button.style.color = color_inactive_font;
						}
					}
				}
				
				document.getElementById("scan").style.backgroundColor = color_unclickable;
				document.getElementById("test").style.backgroundColor = color_unclickable;
				document.getElementById("delete").style.backgroundColor = color_unclickable;
				document.getElementById("save").style.backgroundColor = color_unclickable;
			}				
		}
		else 
		{
			for (btn in btn_states) 
			{
				var button = document.getElementById(btn);
				if(button != null)
				{
					button.style.backgroundColor = color_unclickable;
					button.style.color = color_inactive_font;
				}
			}
				
			document.getElementById("scan").style.backgroundColor = color_unclickable;
			document.getElementById("test").style.backgroundColor = color_unclickable;
			document.getElementById("delete").style.backgroundColor = color_unclickable;
			document.getElementById("save").style.backgroundColor = color_unclickable;
		}
	}
	
	function hexToBase64(str) 
	{
		return btoa(String.fromCharCode.apply(null, str.replace(/\r|\n/g, "").replace(/([\da-fA-F]{2}) ?/g, "0x$1 ").replace(/ +$/, "").split(" ")));
	}
	
	function ws_onmessage(e_msg)
	{
		var arr = JSON.parse(e_msg.data);
		
		switch(arr.cmd)
		{
			case _CMD_CONFIG_DATA:
				btn_states = arr.data;
				break;
				
			case _CMD_SCANNED:
				setup_state = STATE_SCANNED;
				break;
				
			case _CMD_UPDATED:
				setup_state = STATE_UNSCAN;
				setup_button_id = null;
				btn_states = arr.data;				
				break;
				
			case _CMD_CAMERA_DATA_START:			
				camera_data = "";
				break;
				
			case _CMD_CAMERA_DATA:
				camera_data += arr.data;
				break;
				
			case _CMD_CAMERA_DATA_STOP:
				document.getElementById("canvas").src = "data:image/jpeg;base64,"+ hexToBase64(camera_data);
				break;
				
			case _CMD_REQUEST_DENY:	
				alert(arr.msg);
				// Change to Control mode
				mode = MODE_CAMERA_CTRL;
				update_view();
				break;
			
		}
		update_view();
	}
	
	var blink_state = true;
	setInterval(function () {

		if(setup_state == STATE_SCANNING)
		{
			var scan_btn = document.getElementById("scan");
			scan_btn.style.backgroundColor = blink_state ? color_clickable_but2 : color_clicked_but2;
			blink_state = !blink_state;
		}
	}, 500);
	
	window.onload = init;
	
	</script>
</head>
<body>
<div class="wrapper">
    <div class="header">
		<div class="midHeader">
			<center>
				<h1 class="headerTitle">Smart Control</h1>
				<div class="headerMenu">
					<div class="left">
						<span id="setup" class="menu">SETUP</span>
					</div>
					<div class="right">
						<span id="control" class="menu">CONTROL</span>	
					</div>
				</div>
			</center>
		</div>
	</div>
    <div class="contents">		
	<center>
		
		<div id="camera_area">
			<hr size="2" noshade>
			<h1>Camera</h1>		
			<img id="canvas">			
		</div>		
		
		<div id="setup_area">
		<hr size="2" noshade>
		<h1>Button</h1>
			<div id="row2">
				<div id="left2" width="100%">	
					<div id="control_button">
					<div id="row2_1">
						<div id ="A" class="button1">ON/OFF</div>
						<div id ="B" class="button1">OFF</div>
					</div>
					<div id="row2_2">
						<div id ="C" class="button1">UP</div>
					</div>
					<div id="row2_3">
						<span id ="D" class="button1">LEFT</span>
						<span id ="E" class="button1">◎</span>
						<span id ="F" class="button1">RIGHT</span>
					</div>
					<div id="row2_4">
						<div id ="G" class="button1">DOWN</div>
					</div>			
					</div>			
					<div id="setup_button">		
					<div id="row2_5">
						<span id="scan" class="button2">SCAN</span>
						<span id="test" class="button2">TEST</span>
						<span id="delete" class="button2">DELETE</span>
						<span id="save" class="button2">SAVE</span> 
					</div>
					</div>
				</div>
			</div>		
		</div>
		<br/>
		<hr size="2" noshade>
		<div id="websocket_area">
			<div id="row1">
				<h1 id="left1" class="td_fix">
					WebSocket
				</h1>
				<div id="middle1">
					<span id="ws_state">-</span>
				</div>
				<div id="right1" class="td_fix">
					<button id="wc_conn" onclick="wc_onclick();">Connect</button>
				</div>
			</div>
		</div>			
	</center>		
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

