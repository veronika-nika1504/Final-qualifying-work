<?php	// Как связаться - coord.php
	include("inc\\BeginPage.php");
	include("inc\\inetConnect.php");
	echo $BeginPage;
?>
<title><?=$AppFirm?>: Как связаться</title>
<link rel="stylesheet" href="css/main.css">
<style>
	.news { font: italic 16pt "Times New Roman" }
	th	{ font-size: 14pt; font-style: italic; text-align: center; }
	td	{ width: 50% }
	p	{	text-indent:30px; margin: 0px 30px 0px 30px; text-align: justify }	 /* сверху справа снизу слева */
	#map a, #map a:visited, #map a:hover, #map a:active, #map a:link {display:none}
	td:nth-child(1) { width: 300px}
</style>
<script src="js/min.jquery.js?v3"></script>
<script src="https://api-maps.yandex.ru/2.1/?apikey=<?=YmapApiKey()?>&lang=ru_RU"></script>
<script src="js/view-map.js"></script>
</head><body>
<?php	SayMenu("Как с нами связаться");	?>
<br><br>
<table id="news" align="center" width="94%"><tr><th colspan="2" style="color:blue">В этом разделе Вы видите наши номер телефона, адреса почтовый и электронный и положение на карте<br>&nbsp;</th></tr>
	<tr><td align="center">Мы располагаемся по адресу:</td>
		<td id="address" align="center">обл Воронежская, г Воронеж, ул Ленина, дом 86</td>
	</tr>
	<tr><td colspan="2"><hr></td></tr>
	<tr>
		<td align="left"><img src="img/VSPU.jpg" style="height:400px;" /></td>
		<td align="right"><div id="map" style="width:100%;height:400px;border:solid 1px gray"><?=noInternet('Чтобы увидеть карту подкючитесь к сети Интернет')?></div></td>
	</tr>
	<tr><td colspan="2"><hr></td></tr>
	<tr><td>Телефон: (473)255-24-11</td><td align="right">Почта: <a href="mailto:fmdek@mail.ru">fmdek@mail.ru</a></td></tr>
</table>
</body>
<script>
window.onload = function(){
	correctA();
	var str = getObj('address').innerHTML;
	buildMap("map", str, false, "ООО «Недвижимость» находится здесь");
}
</script>
</html>
