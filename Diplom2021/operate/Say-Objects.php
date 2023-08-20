<?php	//Say-Objects.php: Показать таблицу объекта недвижимости
	include("..\\inc\\BeginPage.php");
	AssertLogon();
	$sessID = $_SESSION['UserID'];
	if(($r=$_SESSION['UserRights']) == 0xFFFFFFFF)	//Admin
		$where = "";
	elseif($r & 2)		//Sotr
		$where = " WHERE U.Uid=$sessID";
	elseif($r & 1)		//Klient
		$where = " WHERE E.Uid=$sessID";
	else				//нет владения и прав сотрудника
		$where = " WHERE 1=0";
	
	$Result = QuerySelect($conn, "SELECT E.Eid,E.Uid"
			. ",CASE E.state WHEN 1 THEN 'активна' WHEN 2 THEN 'блок' WHEN 3 THEN 'архив' ELSE 'проверка' END Состояние"
			. ((($r & 2) && ($r <> 0xFFFFFFFF)) ? "" :
						",IFNULL(CONCAT(U.fam,' ',U.nam,' ',U.otc),'--не распределен--') Сотрудник"
						. ",IFNULL(U.phone,'') Телефон"
						. ",IFNULL(U.wPhone,'') 'Раб.телефон'")
			.(($r & 2) ? ",CONCAT(V.fam,' ',V.nam,' ',V.otc) Владелец"
						.",CASE WHEN V.Phone='' THEN V.eMail WHEN V.eMail='' THEN V.Phone ELSE CONCAT(V.phone,'; ',v.eMail) END Координаты"
						: ""
			)
			. ",E.regDate Зарег"
			. ",GC.name Вид"
			. ",CONCAT(E.sell,E.rent) Что"
			. ",E.S 'S(м&sup2;)'"
			. ",E.region Район"
			. ",E.address Адрес"
			. ",REPLACE(FORMAT(E.price,0),',','&nbsp;') 'Сумма(р.)'"
							." FROM Estate E"
							." LEFT JOIN LinkEstateSotr L ON L.Eid=E.Eid"
							." LEFT JOIN logins U ON U.Uid=L.Uid"
							." INNER JOIN logins V ON V.Uid=E.Uid"
							." INNER JOIN GuideCategory GC ON GC.code=E.etype"
							." INNER JOIN ListCategory LC ON LC.fname='etype' AND LC.idCategory=GC.idCategory"
							.$where
							." ORDER BY ". ((($r & 2) && ($r <> 0xFFFFFFFF))?"Зарег":"Сотрудник") );
	$fc = mysqli_field_count($conn);
	echo $BeginPage;
?>
<title><?=$AppFirm.": Мои объекты"?></title>
<style>	
	td:nth-child(1),
<?php if($Klient){ ?>
	td:nth-child(8),td:nth-child(9),td:nth-child(12)
<?php }else{ ?>
	td:nth-child(10),td:nth-child(11),td:nth-child(14)
<?php } ?>			{ text-align: center}
	tr {cursor: pointer}
	tr:hover{background-color: #C5CBE5}
	tr:nth-child(1):hover {background-color: inherit}

</style>
</head>
<body onload="correctA();">
<?php	SayMenu("Мои объекты");	?>
	<table border="1" align="center">
<?php
		if($fc){
			echo "<tr><th>№ пп</th>";
			for($j=2; $j < $fc; $j++){
				$f = mysqli_fetch_field_direct($Result, $j);
				if($f->name=="Что"){
					$nTchto = $j;
					echo "<th title='П-продажа; А-аренда'>Что</th>";
				}else
					echo "<th>". $f->name ."</th>";
			}
			echo "</tr>";
			for($cnt=1; $row = mysqli_fetch_array($Result); $cnt++){
				echo"<tr id='$row[0]' onclick='ClickRow(this)' Uid='$row[1]'><td>$cnt</td>";
				for($j=2; $j < $fc; $j++)
					echo "<td".(($j==$nTchto)?" title='П-продажа; А-аренда'":"").">".$row[$j]."</td>";
				echo "</tr>";
			}
		}	?>
	</table>
</body>
<form hidden id="frm" method="post" action="Edit-Estate.php">
	<input type="hidden" name="Eid" id="Eid"/>
	<input type="hidden" name="editID" id="editID"/>	
</form>
<script>
function	ClickRow(tr) {
    getObj("Eid").value = tr.id;
	getObj("editID").value = tr.getAttribute("Uid");
	getObj("frm").submit();
}
</script>
</html>
