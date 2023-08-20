<?php	// ГЛАВНАЯ
	include("inc\\BeginPage.php");
	echo $BeginPage;
?>
<title><?=$AppFirm?>: главная</title>
<link rel="stylesheet" href="css/main.css">
<style>
	.zagt {font:bold italic 18pt "Times New Roman"; color:blue; text-align: center}
	p, p1, ps { text-indent:30px; margin: 0px 0px 0px 0px; font-style: italic}
	p { font-size: 16pt; }
	p1, ps { font-size: 15pt; }
	ps:before {content:"•\A0"}
	p1:before {content:"\A0\A0\A0\A0"}
	p1:after, ps:after {content:''; display:block;}
	.kart { width:300px }
	td { vertical-align: top; text-align: justify }
	td:first-child, td:last-child {width:8%}
	.imgl { float:left; height: 280px; margin-right: 30px}
	.imgr { float:right; height: 280px; margin-left: 30px}
	.kart img {width:95%}
	tr:last-child {height:25px; font-size: 12px; font-style: italic}
	tr:last-child td {vertical-align: inherit}
	tr:last-child td:nth-child(4) {text-align: right}
	.ptr {cursor:pointer}
</style>
</head><body onload="correctA();">
<?php	SayMenu("Главная");	?>
<br><br>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td></td>
		<td colspan="3" style="margin-right:10%"><center class="zagt">Агентство недвижимости <?=$AppFirm?></center><br>
	<img src="img/Главная 1.jpg" class="imgl">
<p>Агентство недвижимости <?=$AppFirm?> предлагает полный комплекс риелторских услуг. 
Опытные специалисты успешно и быстро решают поставленные клиентом задачи. 
Оказываем профессиональную помощь при покупке, продаже, сдаче, аренде недвижимости. 
Наши специалисты проводят консультации на сайте.</p>
<p>Если вы хотите купить, продать, сдать или арендовать квартиру, комнату, дом, часть дома, 
лучше обращаться в надежные и проверенные риелторские агентства. Это одна из основных гарантий безопасности сделки. 
Специалисты нашего Агентства недвижимости профессионально сопровождают продажу, покупку, аренду объектов.<br>&nbsp;</p>
		</td>
		<td></td>
	</tr>
	<tr>
		<td></td>
		<td colspan="3"><img src="img/TheNews.jpeg" class="imgr ptr" title="Кликните, чтобы прочесть новости" onclick="window.location.href='news.php'" /><center class="zagt">Риелторские услуги</center><br>
<p1>Специалисты <?=$AppFirm?> предлагают:</p1>
<ps>профессиональную помощь в продаже, покупке, найме жилья и коммерческих объектов;</ps>
<ps>сопровождение юристами нашего риелторского агентства сделок на всех этапах их совершения;</ps>
<ps>экспертизу документов с проверкой юридической чистоты объекта;</ps>
<ps>подготовку договоров купли-продажи, мены, аренды, долевых соглашений;</ps>
<ps>оформление наследства, приватизации, сбор пакета документов для регистрации прав;</ps>
<ps>содействие в ипотечном кредитовании;</ps>
<ps>помощь в приобретении жилья по сертификатам (для военнослужащих, по материнскому капиталу и т. д.).</ps>
<p1>Стоимость услуг, предлагаемых агентством, чрезвычайно сильно зависит от конкретного объектов недвижимости, поэтому определяется в каждом случае индивидуально. Для уточнения - свяжитесь с Вашим специалистом.</p1><br>&nbsp;
</td>
		<td></td>
	</tr>
	<tr>
		<td></td>
		<td class="kart"><img src="img/prisoed.jpg" class="ptr" alt="Присоединяйся" title="Кликните, чтобы перейти на страницу ''Работа у нас''" onclick="window.location.href='prisoed.php'" /></td>
		<td class="kart" align="ceneter"><img src="img/Главная 2.png" alt="Картинка" /></td>
		<td class="kart" align="right"><img src="img/Главная 2.png" alt="Картинка" /></td>
		<td></td>
	</tr>
	<tr><td colspan="5">&nbsp;</td></tr>
	<tr bgcolor="#C5CBE5">
		<td></td>
		<td>г.Воронеж, ул.Ленина, 86</td>
		<td></td>
		<td>Версия 2.0. Автор: Запара В.</td>
		<td></td>
	</tr>
</table>
</body>
</html>
