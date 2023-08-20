<?php	// Сотрудники - employes.php
	include("inc\\BeginPage.php");
	echo $BeginPage;
?>
<title><?=$AppFirm?>: Сотрудники агентства</title>
<link rel="stylesheet" href="css/main.css">
<style>
	#obsh img {width:150px}
	tr { vertical-align: top }
	td { text-align: center }
	td:nth-child(2) { font-weight:bold; width:200px}
	#zag { font: bold italic 20pt "Times New Roman"; color: blue; }
</style>
</head><body onload="correctA();">
<?php	SayMenu("Сотрудники нашего агентства");	?>
<br><br>
<div id="obsh" style="columns: 450px auto" align="center">
	<div class="emp">
		<table>
			<tr><td rowspan="3"><img src="img/employX.jpg" /></td>
				<td>Генеральный директор</td>
			</tr>
			<tr><td>Фамилия Имя Отчество</td></tr>
			<tr><td>email@domain.ru</td></tr>
			<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
		</table>
	</div>

	<div class="emp">
		<table>
			<tr><td rowspan="3"><img src="img/employX.jpg" /></td>
				<td>Менеджер по работе с клиентами</td>
			</tr>
			<tr><td>Фамилия Имя Отчество</td></tr>
			<tr><td>email@domain.ru</td></tr>
			<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
		</table>
	</div>

	<div class="emp">
		<table>
			<tr><td rowspan="3"><img src="img/employX.jpg" /></td>
				<td>Менеджер по работе с персоналом</td>
			</tr>
			<tr><td>Фамилия Имя Отчество</td></tr>
			<tr><td>email@domain.ru</td></tr>
			<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
		</table>
	</div>

	<div class="emp">
		<table>
			<tr><td rowspan="3"><img src="img/employX.jpg" /></td>
				<td>Ведущий специалист</td>
			</tr>
			<tr><td>Фамилия Имя Отчество</td></tr>
			<tr><td>email@domain.ru</td></tr>
			<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
		</table>
	</div>

	<div class="emp">
		<table>
			<tr><td rowspan="3"><img src="img/employX.jpg" /></td>
				<td>Старший специалист</td>
			</tr>
			<tr><td>Фамилия Имя Отчество</td></tr>
			<tr><td>email@domain.ru</td></tr>
			<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
		</table>
	</div>

	<div class="emp">
		<table>
			<tr><td rowspan="3"><img src="img/employX.jpg" /></td>
				<td>Специалист</td>
			</tr>
			<tr><td>Фамилия Имя Отчество</td></tr>
			<tr><td>email@domain.ru</td></tr>
			<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
		</table>
	</div>

	<div class="emp">
		<table>
			<tr><td rowspan="3"><img src="img/employX.jpg" /></td>
				<td>Специалист</td>
			</tr>
			<tr><td>Фамилия Имя Отчество</td></tr>
			<tr><td>email@domain.ru</td></tr>
			<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
		</table>
	</div>

	<div class="emp">
		<table>
			<tr><td rowspan="3"><img src="img/employX.jpg" /></td>
				<td>Специалист</td>
			</tr>
			<tr><td>Фамилия Имя Отчество</td></tr>
			<tr><td>email@domain.ru</td></tr>
			<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
		</table>
	</div>

	<div class="emp">
		<table>
			<tr><td rowspan="3"><img src="img/employX.jpg" /></td>
				<td>Специалист</td>
			</tr>
			<tr><td>Фамилия Имя Отчество</td></tr>
			<tr><td>email@domain.ru</td></tr>
			<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
		</table>
	</div>
</div>
</body>
</html>
