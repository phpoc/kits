<?php 
include_once "/lib/sd_340.php";
include_once "/lib/sn_tcp_ws.php";
include_once "config.php";
include_once "/lib/sc_envu.php";

set_time_limit(30);

$ws_host = _SERVER("HTTP_HOST");

$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

if(!($title_home = envu_find($envu, "title_home")))
	$title_home = "Temperature";
?>

<!DOCTYPE html>
<html>
<head>
	<title>PHPoC / <?echo system("uname -i")?></title>
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
</head>
<body>
<div class="temperature_wrap">
	
		<div class="midHeader">
			<center>
				<h1 class="headerTitle"><?php echo $title_home;?></h1>
				<div class="headerMenu">
					<div class="left">
					</div>
					<div class="right">
					</div>
				</div>
			</center>
		</div>
		
		<div class="subHeader">
		</div>		
	
    <div class="index_contents">
        <a href="setup_net.php">Setup</a><br/>
        <a href="index_graph.php">Realtime Graph</a><br/>
        <a href="index_webconsole.php">Realtime Web Console</a>
    </div>
   
	<div class="footer">
		<div class="superFooter">
			<a href="http://www.sollae.co.kr/kr/home/" target="_blank">SOLLAE SYSTEMS</a>	
		</div>
	</div>
</div>	
</body>
</html>
