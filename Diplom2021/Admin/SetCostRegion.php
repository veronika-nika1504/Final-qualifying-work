<?php	//SetCostRegion.php - Установка средних цен по регионам на кв.м жилья и сотку земли
		if( isset($_POST['sid']) && isset($_POST['m2']) && isset($_POST['ar']) && isset($_POST['dt']) && isset($_POST['bt']) ){
			//ОБРАБОТКА ajax-запроса
			session_id($_POST['sid']);
			session_start();
			header('Content-Type: text/plain; charset=utf-8');
			include("..\\inc\\rielter.ini");
			$ret = '';
			$conn = @mysqli_connect($AppAddr, $AppUsr, $AppPsw, $AppDB);
			if(! $conn)
				$ret = 'При попытке подключения к MySQL-серверу произошла ошибка: ' . mysqli_connect_error();
			else{
				//что было ранее?
				$Result = mysqli_query($conn, $cmd = "SELECT Cost_m2, Cost_Sotka, DATE_FORMAT(DateA,'%d.%m.%Y')D FROM CostRegion WHERE rid=".$_POST['bt']);					
				if(!$Result)
					$ret = 'При выполнении запроса произошла ошибка: '.$cmd.' '.mysqli_error($conn);
				else{
					$r = mysqli_fetch_array($Result);
					$m2 = $r[0];	$ar = $r[1];	$dt = $r[2];
					$upd = $log = "";
					if($m2<>$_POST['m2']){
						$upd = ",Cost_m2=".$_POST['m2'];
						$log = ", [Cost_m2: $m2 ⇒ ". $_POST['m2']. "]";
					}
					if($ar<>$_POST['ar']){
						$upd .= ",Cost_Sotka=".$_POST['ar'];
						$log .= ", [Cost_Sotka: $ar ⇒ ". $_POST['ar']. "]";
					}
					if($dt<>$_POST['dt']){
						$upd .= ",DateA='".substr($_POST['dt'],6,4).'-'.substr($_POST['dt'],3,2).'-'.substr($_POST['dt'],0,2)."'";
						$log .= ", [DateA: $dt ⇒ ". $_POST['dt'] ."]";
					}
					if($upd){
						$upd = "UPDATE CostRegion SET ". substr($upd,1) ." WHERE rid=".$_POST['bt'];
						$log = "Для региона ".$_POST['bt']. ":". substr($log,1);
						$Result = mysqli_query($conn, $upd);
						if(!$Result)
							$ret = 'При выполнении запроса обновления произошла ошибка: '.$upd.' '.mysqli_error($conn);
						else{
							$Result = mysqli_query($conn, $cmd = "CALL WriteLog($_SESSION[UserID],9,0,'". $log ."')");
							if(!$Result)
								$ret = 'При регистрации в логе произошла ошибка: '.$cmd.' '.mysqli_error($conn);
						}
					}
				}
			}
			if(!$ret)
				$ret = "ok;".$_POST['m2'].";".$_POST['ar'].";".$_POST['dt'].";".$_POST['bt'];
			echo $ret;
			exit;
		}	//ОБРАБОТКА ajax-запроса завершена
		//Обычный запрос страницы
		include("..\\inc\\BeginPage.php");
		AssertLogon($_SESSION['UserRights'] == 0xFFFFFFFF);
		echo $BeginPage;
?>
<title><?=$AppFirm?>: Региональные цены</title>
<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/calendar.css">
<style>
	tr {cursor: pointer}
	td	{text-align: center}
	td:nth-child(1) { min-width:40px }
	td:nth-child(2)	{text-align:left}
	tr:hover{background-color: #C5CBE5}
	tr:nth-child(1):hover {background-color: inherit; cursor:default}
	input { position:absolute; text-align:center;}
</style>
</head><body onload="correctA();" onkeydown="if(event.keyCode==27)breakEdit();">
<?php	SayMenu("Региональные цены");	?>
<br>
<table id="rlist" border="1" align="center">
	<tr><th>Код</th>
		<th>Наименование региона</th>
		<th>За 1 м&sup2; жилья<br>(руб.)</th>
		<th>За 1 сотку земли<br>(руб.)</th>
		<th>Дата<br>актуализации</th>
	</tr>
<?php	$t = QuerySelect($conn, "SELECT * FROM CostRegion ORDER BY rid");
		while($r=mysqli_fetch_array($t))
			echo '<tr onclick="beginEdit(this)">'
					.'<td>'.$r[0].'</td>'
					.'<td>'.$r[1].'</td>'
					.'<td>'.number_format($r[2],0,',',' ').'</td>'
					.'<td>'.number_format($r[3],0,',',' ').'</td>'
					.'<td>'.($r[4] ? substr($r[4],8,2).'.'.substr($r[4],5,2).'.'.substr($r[4],0,4) : '').'</td>'
				.'</tr>';
?>
</table>
	<input id="m2" style="display:none" onchange="with(this)value=NumPositiveStrSpace(value)" />
	<input id="ar" style="display:none" onchange="with(this)value=NumPositiveStrSpace(value)" />
	<input id="dt" style="display:none" size="10" maxlength="10" autocomplete="off" onclick="initCalend(this)" />
	<button id="bt" style="position:absolute;display:none;" title="Сохранить" onclick="save(this)">ok</button>
</body>
<script src="../js/min.jquery.js"></script>
<script src="../js/calendar.js"></script>
<script>
	var trHoverCSS = getClassByName("tr:hover").style,
		m2 = getObj("m2"),
		ar = getObj("ar"),
		dt = getObj("dt"),
		bt = getObj("bt");
function	initInpFromCell(Inp, Cell){
	var pos = getAbsoluteParams(Cell); 
	Inp.value = Cell.innerHTML;
	with(Inp.style){
		top = pos.top +"px";
		left = pos.left +"px";
		width = (pos.width-8) +"px";
		display = "";
	}
}
function	beginEdit(obj) {
    trHoverCSS.backgroundColor = "inherit";
	initInpFromCell(m2,obj.cells[2]);
	initInpFromCell(ar,obj.cells[3]);
	initInpFromCell(dt,obj.cells[4]);
	with(bt.style){
		height = dt.offsetHeight +"px";
		top = dt.style.top;
		left = (getAbsoluteLeft(dt) + dt.offsetWidth + 4) +"px";
		display = "";
	}
	bt.setAttribute("code",obj.cells[0].innerHTML);
	m2.select();
}
function	callBackSave(data, textStatus) {
	if(textStatus != "success")	
		data = "Статус: "+textStatus+"\n"+data;
	else{ 
		var ok_m2_ar_dt_bt = data.split(";");
		if(ok_m2_ar_dt_bt[0] == "ok"){
			data = "";
			var j, r = getObj("rlist").rows, rl = r.length;
			for(j=1; j<rl; j++)
				with(r[j])
					if(cells[0].innerHTML == ok_m2_ar_dt_bt[4]){
						cells[2].innerHTML = NumPositiveStrSpace(ok_m2_ar_dt_bt[1]);
						cells[3].innerHTML = NumPositiveStrSpace(ok_m2_ar_dt_bt[2]);
						cells[4].innerHTML = ok_m2_ar_dt_bt[3];
						break;
					} //if,with,for
		}
	}
	if(data) alert(data);
}
function	save(btn){
	var m2v = m2.value.toString().replace(/ /g,''),
		arv = ar.value.toString().replace(/ /g,''),
		dtv = dt.value,
		msg = "";
	if(!m2v || !parseInt(m2v)) msg  = "\nНеобходимо ввести цену 1 кв.м жилья.";
	if(!arv || !parseInt(arv)) msg += "\nНеобходимо ввести цену земли за 1 сотку.";
	if(!isDate(dtv)) msg += "\nНеобходимо ввести дату актуализации в формате ДД.ММ.ГГГГ.";
	if(msg) alert("При вводе данных допущены ошибки:\n"+msg);
	else{
		$.post(	window.location.href, { m2:m2v, ar:arv, dt:dtv, bt:bt.getAttribute("code"), sid:"<?=session_id()?>"	}, callBackSave, "text");
		m2.style.display = "none";
		ar.style.display = "none";
		dt.style.display = "none";
		bt.style.display = "none";
		trHoverCSS.backgroundColor = "#C5CBE5";
	}
}
function	breakEdit(){
	m2.style.display = ar.style.display = dt.style.display = bt.style.display = "none"; 
	trHoverCSS.backgroundColor = '#C5CBE5';
	CalendarObj.me.style.display = "none";
}
</script>
</html>
