<?php	session_start();
		header('Content-Type: text/html; charset=utf-8');
		include("..\\inc\\inetConnect.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru-RU">
<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="content-language" content="ru" />
<meta name="description" content="Недвижимость: карта расположения объекта недвижимости" />
<meta name="robots" content="index,follow" />
<link rel="shortcut icon" href="../img/favicon.png">
<script src="../js/min.jquery.js?v3"></script>
<script src="https://api-maps.yandex.ru/2.1/?apikey=<?=YmapApiKey()?>&lang=ru_RU"></script>
<script src="../js/view-map.js"></script>
<script>
var ss = 0, W = null;
function Z(z){YmapApiVars.mapMap.setZoom(z,{duration:1000});}
function T(){
	switch(ss++){
		case 0:	break;
		case 1:	Z(18);	break;
		case 2:	Z(16);	W.document.getElementById("region").focus();	 return;
	}
	setTimeout(T,1000);
}
window.onload = function(){
	W = window.opener;
	var str = W.document.getElementById("address").value;
	buildMap("map",str, T);
}
</script>
<style>
	a,	a:visited, a:hover, a:active, a:link {display:none}
</style>
</head><body><div id="map" style="position:absolute;top:0px;left:0px;width:100%;height:100%"></div></body>
</html>
