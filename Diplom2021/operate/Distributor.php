<?php	//Disributor.php: Распределение объектов недвижимости
	include("..\\inc\\BeginPage.php");
	AssertLogon(($_SESSION['UserRights'] & 10) == 10);
	$sessID = $_SESSION['UserID'];
	$Eid = isset($_POST['Eid']) ? $_POST['Eid'] : '';
	$Uid = isset($_POST['Uid']) ? $_POST['Uid'] : '';
	if($Eid && $Uid){
		$row = EasyQuery($conn, "SELECT COUNT(1)cnt FROM LinkEstateSotr WHERE Eid=$Eid AND Uid=$Uid");
		if(! $row[0])
			EasyQueryNoResult($conn, "INSERT INTO LinkEstateSotr(Eid,Uid,SetUid)VALUES($Eid,$Uid,$sessID)"); 
	}
	$Restate = QuerySelect($conn, "SELECT E.Eid"
			.",E.address Адрес"
			.",E.region Район"
			.",E.regDate Зарег"
			.",G.name Вид"
			.",E.S 'S(м&sup2;)'"
			.",REPLACE(FORMAT(E.price,0),',','&nbsp;') 'Сумма(р.)'"
							." FROM Estate E"
							." INNER JOIN logins V ON V.Uid=E.Uid"
							." INNER JOIN ListCategory C ON C.fname='etype'"
							." INNER JOIN GuideCategory G ON G.idCategory=C.idCategory AND G.Code=E.etype"
							." LEFT JOIN LinkEstateSotr L ON L.Eid=E.Eid"
							." WHERE L.Eid IS NULL"
							." ORDER BY E.regDate");
	$fce = 0;	if($Restate) $fce = mysqli_field_count($conn);
	
	$Rsotr = QuerySelect($conn, "SELECT U.Uid,CONCAT(U.fam,' ',U.nam,' ',U.otc) ФИО,IFNULL(V.Cnt,0) Объектов".
								" FROM logins U".
								" LEFT JOIN(SELECT L.Uid, COUNT(1)Cnt ".
											"FROM LinkEstateSotr L ".
											"INNER JOIN Estate e ON e.Eid=L.Eid ".
											"GROUP BY L.Uid)V ON V.Uid=U.Uid ".
											"WHERE U.uState=1 AND U.uRights & 2 = 2 AND U.uRights < 4294967295");
	$fcs = 0;	if($Rsotr) $fcs = mysqli_field_count($conn);
	
	echo $BeginPage;
?>
<title><?=$AppFirm.": Распределение объектов"?></title>
<link rel="stylesheet" href="../css/main.css">
<style>
	TABLE table td	{ text-align: center}
	TABLE table td:nth-child(2)	{ text-align: left}
	table table tr {cursor: pointer}
	table table tr:hover{background-color: #C5CBE5}
	table table tr:nth-child(1):hover {background-color: inherit}
	.highlight { background-color: LightBlue}
	.highlight:hover { background-color: #C5CBE5}
	.highlight2 { background-color: LightBlue}
	.highlight2:hover { background-color: LightBlue}
</style>
</head>
<body onload="correctA();">
<?php	SayMenu("Распределение объектов");	?>
<br>
<TABLE width="100%">
	<TR><TH>Нераспределённые объекты</TH><TH>Сотрудники</TH></TR>
	<TR><TD width="70%" valign="top">
	<table id="objs" border="1" width="100%">
<?php
		if($fce){
			echo "<tr><th>№ пп</th>";
			for($j=1; $j < $fce; $j++){
				$f = mysqli_fetch_field_direct($Restate, $j);
				echo "<th>". $f->name ."</th>";
			}
			echo "</tr>";
			for($cnt=1; $row = mysqli_fetch_array($Restate); $cnt++){
				echo"<tr id='$row[0]' onclick='ClickRow(this)' onmouseout='clearHL2(this)'><td>$cnt</td>";
				for($j=1; $j < $fce; $j++)
					echo "<td>".$row[$j]."</td>";
				echo "</tr>";
			}
		}	?>
	</table>
		</TD><TD valign="top" style="padding-left:7px;">
	<table id="sotr" border="1" width="100%">
<?php
		if($fcs){
			echo "<tr><th>№ пп</th>";
			for($j=1; $j < $fcs; $j++){
				$f = mysqli_fetch_field_direct($Rsotr, $j);
				echo "<th>". $f->name ."</th>";
			}
			echo "</tr>";
			for($cnt=1; $row = mysqli_fetch_array($Rsotr); $cnt++){
				echo"<tr id='$row[0]' onclick='ClickRow(this)' onmouseout='clearHL2(this)'><td>$cnt</td>";
				for($j=1; $j < $fcs; $j++)
					echo "<td>".$row[$j]."</td>";
				echo "</tr>";
			}
		}	?>
	</table>		
	</TD></TR>
	<TR><TD colspan="2" style="font-size:6px">&nbsp;</TH></TR>
	<TR><TH colspan="2"><button class="BtnBig" onclick="Sbmt()">Назначить</button></TH></TR>
</TABLE>
</body>
<form hidden id="frm" method="post">
	<input type="hidden" id="Eid" name="Eid" />
	<input type="hidden" id="Uid" name="Uid" />	
</form>
<script>
var HLineObject = HLineSotr = 0;
function	ClickRow(tr) {
	var tbl = tr.parentNode.parentNode, tid = tbl.id, r = tr.rowIndex;
	if(tid=="objs")
		if(HLineObject == r){	HLineObject = 0;	tr.className = ""; }
		else{
			tbl.rows[HLineObject].className = "";
			tr.className = "highlight2";
			HLineObject = r;
		}
	else
		if(HLineSotr == r){	HLineSotr = 0;	tr.className = ""; }
		else{
			tbl.rows[HLineSotr].className = "";
			tr.className = "highlight2";
			HLineSotr = r;
		}
}
function	clearHL2(tr){
	var cn, L = 0;
	if(cn = tr.className)
		if(L = cn.length - 1){
			if(cn.substr(L,1)=="2")
				tr.className = cn.substr(0,L);
		}
}
function	Sbmt(){
	if(HLineObject && HLineSotr){
		getObj("Eid").value = getObj("objs").rows[HLineObject].id;
		getObj("Uid").value = getObj("sotr").rows[HLineSotr].id;
		getObj("frm").submit();
	}else
		alert("Сперва выберите объект недвижимости и сотрудника, кликнув по соответствующим строкам.");
}
</script>
</html>
