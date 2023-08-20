<?php	// LogView.php - ПРОСМОТР ЛОГА (ПРОТОКОЛА)
	include("..\\inc\\BeginPage.php");
	AssertLogon(($_SESSION['UserRights'] & 4) == 4);

	$dtb = isset($_POST['DTB']) ? $_POST['DTB'] : date("d.m.Y");
	$dte = isset($_POST['DTE']) ? $_POST['DTE'] : "";
	$where = $dte ? "BETWEEN STR_TO_DATE('$dtb','%d.%m.%Y') AND STR_TO_DATE('$dte','%d.%m.%Y')" : ">=STR_TO_DATE('$dtb','%d.%m.%Y')";
	$Result = QuerySelect($conn, "SELECT DATE_FORMAT(L.oDate,'%d.%m.%Y %T') oDate, U.uName, O.oName, R.rName, L.info ".
		"FROM log L ".
		"LEFT JOIN logins U ON u.Uid=L.Uid ".
		"INNER JOIN Oper O ON O.Oid=L.Oid ".
		"INNER JOIN Result R ON R.Rid=L.Rid ".
		"WHERE L.oDate $where ORDER BY L.Lid");
	echo $BeginPage;
?>
<title><?=$AppFirm?>: просмотр лога</title>
<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/calendar.css">
<style>
	.date{ text-align:center }
	.zag { font: bold italic 20pt "Times New Roman"; color:magenta}
</style>
<script src="../js/public.js"></script>
</head><body onload="correctA();Set_EnterEqTab();">
<?php	SayMenu("просмотр лога");	?>
	
<div class="mnu">
	<div class="floatLeft" style="padding-top:2px">
		<form id="frm" method="post" onsubmit="return false">
			Период от <input class="date" id="dtb" name="DTB" size="10" maxlength="10" value="<?=$dtb?>" autocomplete="off" onclick="initCalend(this)" />
			до <input class="date" id="dte" name="DTE" size="10" maxlength="10" value="<?=$dte?>" autocomplete="off" onclick="initCalend(this)" />
		</form>
	</div>
	<img class="knop float" src="../img/Применить.png" title="Применить !" onclick="Sbmt()" />
	<div class="clear"></div>
</div>
<table border="1" width="100%">
	<tr><th>Дата-время</th><th>User</th><th>Операция</th><th>Результат</th><th>Примечания</th></tr>
<?php	while($row = mysqli_fetch_array($Result)){	?>
	<tr>
		<td style="white-space:nowrap"><?=$row['oDate']?></td>
		<td><?=$row['uName']?></td>
		<td><?=$row['oName']?></td>
		<td><?=$row['rName']?></td>
		<td><?=$row['info']?></td>
	</tr>
<?php	}	?>
</table>
</body>
<script src="../js/calendar.js"></script>
<script>
function	 Sbmt(){
	var ok = isDate(getObj("dtb").value);
	if(ok)	with(getObj("dte"))	if(value)	ok = isDate(value);
	if(ok)	getObj("frm").submit();
	else		alert("Даты вводите в формате ДД.ММ.ГГГГ.\nДата начала периода обязательна.");
}
</script>
</html>
