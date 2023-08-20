<?php	//LoginView.php		ПРОСМОТР ПОЛЬЗОВАТЕЛЕЙ
		include("..\\inc\\BeginPage.php");
		AssertLogon((($r=$_SESSION['UserRights']) & 2) == 2);
		$sessID = $_SESSION['UserID'];
		echo $BeginPage;		
?>
<title><?=$AppFirm?>: users</title>
<style>
	tr {cursor: pointer}
	tr:hover{background-color: #C5CBE5}
	tr:nth-child(1):hover {background-color: inherit}
</style>
</head><body onload="correctA();">
<?php	SayMenu("пользователи");	?>
<br>
<table border="1" align="center" width="80%">
	<tr><th>Id</th>		
		<th>Логин</th>
		<th>Дата регистрации</th>
		<th>ФИО</th>
		<th>Телефон</th>
		<th>Почта</th>
		<th>Состояние</th>		
		<th>Права</th>
<?php	if($r == 0xFFFFFFFF){	?>
		<th>Зарег.</th>
<?php	}	?>
	</tr>
<?php	//если не администратор:
		$cmd = "SELECT DISTINCT ".	//без повторов
							"L.Uid,L.uName,DATE_FORMAT(L.regDate,'%d.%m.%Y %T') RD,".
							"L.fam,L.nam,L.otc,L.phone,L.eMail,L.uState,L.uRights,IFNULL(K.uName,'сам') rName ".
				"FROM LinkEstateSotr les ".				//связь сотрудник-объект
				"INNER JOIN Estate e ON e.Eid=les.Eid ".//объект из этой связи
				"INNER JOIN Logins L ON L.Uid=e.Uid ".	//влвделец объекта
				"LEFT JOIN Logins K ON K.Uid=L.regUid ". //чтоб найти, кем создана учётка
				"WHERE les.Uid=$sessID";				//сотрудник, обрабатывающий объект - тот, что вошёл
		if($r == 0xFFFFFFFF) //для админа:
			$cmd = "SELECT L.Uid,L.uName,DATE_FORMAT(L.regDate,'%d.%m.%Y&nbsp;%T') RD,".
							"L.fam,L.nam,L.otc,L.phone,L.eMail,L.uState,L.uRights,IFNULL(K.uName,'сам') rName ".
					"FROM logins L LEFT JOIN logins K ON K.Uid=L.regUid";
		$tbl= QuerySelect($conn, $cmd);
		while($row= mysqli_fetch_array($tbl)){	?>
	<tr onclick="ClickRow(this)">
		<td align="center" width="50"><?=$row["Uid"]?></td>
		<td><?=$row["uName"]?></td>
		<td><?=$row["RD"]?></td>
		<td><?=$row["fam"]." ".$row["nam"]." ".$row["otc"]?></td>
		<td><?=$row["phone"]?></td>
		<td><?=$row["eMail"]?></td>
		<td align="center"><?php switch($row["uState"]){
									case 1: echo "активна"; break;
									case 2: echo "блокирована"; break;
									case 3: echo "заархивирована"; break;
									default: echo "какая-то ошибка";
								}?></td>
		<td align="center"><?php switch($row["uRights"]){
									case 1:				echo'клиент';			break;
									case 2:				echo'сотрудник';		break;
									case 10:			echo'распределитель';	break;
									case 0xFFFFFFFF:	echo'администратор';	break;
									default:			echo'х.е.з.';
								} ?></td>
<?php	if($r == 0xFFFFFFFF){	?>
		<td><?=$row["rName"]?></td>
<?php	}	?>
	</tr>
<?php	}	?>
	<tr onclick="ClickRow(this)"><td colspan="<?=(($r && 2)?9:8)?>" align="center">Добавить нового пользователя!</td></tr>
</table>
<form id='toLK' hidden method="post" action="../l-k/lk.php">		
	<input hidden id="editID" name="editID">
</form>
</body>
<script>
function ClickRow(obj) {
	var i = obj.cells[0].innerHTML;
	if(i=="Добавить нового пользователя!")	i = "newUsr";
	getObj("editID").value = i;
	getObj("toLK").submit();
}
</script>
</html>