<?php	// search.php - ПОИСК ЖИЛЬЯ
	include("inc\\BeginPage.php");
	echo $BeginPage;
	$etp1 = isset($_POST['etp1']) ? $_POST['etp1'] : "";
	$etp2 = isset($_POST['etp2']) ? $_POST['etp2'] : "";
	$etp3 = isset($_POST['etp3']) ? $_POST['etp3'] : "";
	$etp4 = isset($_POST['etp4']) ? $_POST['etp4'] : "";
	$wp = isset($_POST['wp']) ? $_POST['wp'] : '';
	$wa = isset($_POST['wa']) ? $_POST['wa'] : '';
	$Region = isset($_POST['Region']) ? $_POST['Region'] : '';
	$minS = isset($_POST['minS']) ? $_POST['minS'] : 0;
		$minS = str_replace(' ','',str_replace(',','.',$minS));	if($minS) $minS = 1.0 * $minS;	if(!$minS) $minS = '';
	$maxS = isset($_POST['maxS']) ? $_POST['maxS'] : 0;
		$maxS = str_replace(' ','',str_replace(',','.',$maxS));	if($maxS) $maxS = 1.0 * $maxS;	if(!$maxS) $maxS = '';
	$minCost = isset($_POST['minCost']) ? $_POST['minCost'] : 0;
		$minCost = str_replace(' ','',$minCost);				if($minCost) $minCost = 1 * $minCost;	if(!$minCost) $minCost = '';
	$maxCost = isset($_POST['maxCost']) ? $_POST['maxCost'] : 0;
		$maxCost = str_replace(' ','',$maxCost);				if($maxCost) $maxCost = 1 * $maxCost;	if(!$maxCost) $maxCost = '';
?>
<title><?=$AppFirm?>: Поиск жилья</title>
<link rel="stylesheet" href="css/main.css">
<style>
	.contanerObject{ width:300px; height:340px; display:inline-block; border:solid 1px white; margin-bottom:30px; }
	.contanerObject:hover {background-color: #C5CBE5}
	.contImg { height:200px; width:100%; text-align:center; padding-top:5px}
	img { max-width:90%; height:100%; margin:auto }
	.cost{float:left; font:normal normal 18pt "Times New Roman"; padding-left:10px}
	.dtr {float:right; font: normal normal 11px "Times New Roman"; color:black; padding-right:10px}
	.clr { clear:both }

	.xxx {width:100%; font: normal normal 12pt "Times New Roman"; color:blue; padding-left:10px; padding-right:10px;}
	.xxx td:nth-child(1){text-align:left}
	.xxx td:nth-child(2){text-align:center; color:gray; }
	.xxx td:nth-child(3){text-align:right}
	
	.region {width:100%; text-align:center}
	#btnSuper { width:800px;height:24px;display:table-cell; position:relative;
				font:normal italic 14pt 'Times New Roman'; text-align:center;
				background-color: #d8dced; border:solid 2px black; border-radius:15px;
				cursor:pointer;}
	.nums { width:100px; text-align:center; }
</style>
</head><body onload="Init()">
<?php	SayMenu("Поиск недвижимости");
	$row = $fc = false;
	$etp = "";
	if($etp1) $etp .= $etp1;
	if($etp2) $etp .= $etp2;
	if($etp3) $etp .= $etp3;
	if($etp4) $etp .= $etp4;
	if($etp) $etp = " AND '$etp' LIKE CONCAT('%',E.etype,'%')";
	$numFltr = $etp ? 1 : 0;

	if($wp || $wa) $numFltr++;
	if($wp && !$wa)		$wpa = " AND sell='П'";
	elseif(!$wp && $wa)	$wpa = " AND rent='А'";
	else				$wpa = "";

	$Result = QuerySelect($conn, "SELECT DISTINCT region FROM Estate WHERE state=1 ORDER BY region");
	$regSel = "<select id='Region' name='Region' style='width:99%' form='fltr'>"
					."<option value=''".($Region?'':' selected').">--не выбрано--</option>";
	while($row=mysqli_fetch_array($Result))
		$regSel .= "<option value='$row[0]'".(($Region==$row[0])?' selected':'').">".$row[0]."</option>";
	$regSel .= "</select>";
	if($Region){
		$numFltr++;
		$Region = " AND region='$Region'";
	}
	$diapS = '';
	if($minS || $maxS){
		$numFltr++;
		if($minS > $maxS && $maxS){	$tmp = $minS;	$minS = $maxS;	$maxS = $tmp;	}
		if($minS && $maxS)	$diapS = " AND E.S BETWEEN $minS AND $maxS";
		elseif($minS)		$diapS = " AND E.S>=$minS";
		else /*$maxS*/		$diapS = " AND E.S<=$maxS";
	}
	$diapCost = '';
	if($minCost || $maxCost){
		$numFltr++;
		if($minCost > $maxCost && $maxCost){	$tmp = $minCost;	$minCost = $maxCost;	$maxCost = $tmp;	}
		if($minCost && $maxCost)	$diapCost = " AND E.RealPrice BETWEEN $minCost AND $maxCost";
		elseif($minCost)			$diapCost = " AND E.RealPrice>=$minCost";
		else /*$maxCost*/			$diapCost = " AND E.RealPrice<=$maxCost";
	}

	// получаем коды и названия типов недвижимости в массив $ArrEtps
	$Result = QuerySelect($conn, $cmd = "SELECT gc.code, gc.name FROM ListCategory lc INNER JOIN GuideCategory gc ON gc.idCategory=lc.idCategory AND lc.fname='etype' WHERE gc.code>' ' ORDER BY gc.srt");
	for($j=1; $r = mysqli_fetch_array($Result); $j++){	$ArrEtps[$j][0] = $r['code'];	 $ArrEtps[$j][1] = $r['name'];	}

	$Result = QuerySelect($conn, $cmd = "SELECT E.Eid"
					.",CONCAT(REPLACE(FORMAT(E.RealPrice,0),',','&nbsp;'))cost"
					.",DATE_FORMAT(E.regDate,'%d.%m.%Y')dtr"
					.",gc.name vid"
					.",CONCAT(E.S,'&nbsp;м&sup2;')S"
					.",SUBSTR(CONCAT(CASE E.sell WHEN 'П' THEN ' / Продажа' ELSE '' END, CASE E.rent WHEN 'А' THEN ' / Аренда' ELSE '' END),4) what"
					.",E.region"
							." FROM Estate E"
							." INNER JOIN LinkEstateSotr L ON L.Eid=E.Eid"
							." INNER JOIN Logins U ON U.Uid=L.Uid"
							." INNER JOIN ListCategory lc ON lc.fname='etype'"
							." INNER JOIN GuideCategory gc ON gc.idCategory=lc.idCategory AND gc.code=E.etype"
							." WHERE E.state=1".$etp.$Region.$diapS.$diapCost.$wpa);
?>
	<div style='max-width:1056px;margin-left:auto;margin-right:auto;'>
		<div style='margin-left:150px;margin-top:5px;margin-bottom:10px'>
			<div id='btnSuper' onclick='FilterSay()'>
				<div id='ug' style='position:absolute;top:3px;width:100%'><div style='float:left;margin-left:20px'>&#9660;</div><div style='float:right;margin-right:20px'>&#9660;</div><div class='clr'></div></div>
				<div style='position:absolute;top:5px;right:50px;color:red;font-size:12px'>
					<span id='УстSpan'>установлено: <?=$numFltr?></span>
					<button id='УстButton' style='display:none; border-width:1px; border-radius:15px; font: italic 12px "Times New Roman"; width:140px;' 
							title='Нажмите для очистки фильтра' onclick='event.stopPropagation();ClearFilters()'>установлено: <?=$numFltr?></button>
				</div>
				<div style='position:absolute;top:1px;width:50%;right:25%'>Ф &nbsp; и &nbsp; л &nbsp; ь &nbsp; т &nbsp; р</div>
				<br>
				<div id='Filters' style='display:none'>
					<table align='center' width='50%' onclick="event.stopPropagation();CountFilters();">
						<tr><td colspan='2'><hr></td></tr>
						<tr><td colspan='2' align='center'><label title='Все виды отметить или снять отметки'>Виды недвижимости:<input id="allEtp" type="checkbox" onclick="AllEtp(this)" <?=($etp?"checked":"")?>></label></td></tr>
						<tr><td align='left' width="50%">
								<label><input type="checkbox" id="etp1" name="etp1" value="<?=$ArrEtps[1][0]?>" form="fltr" <?=(($etp1==$ArrEtps[1][0])?'checked':'')?>/><?=$ArrEtps[1][1]?></label><br>
								<label><input type="checkbox" id="etp2" name="etp2" value="<?=$ArrEtps[2][0]?>" form="fltr" <?=(($etp2==$ArrEtps[2][0])?'checked':'')?>/><?=$ArrEtps[2][1]?></label>
							</td>
							<td align='right' width="50%">
								<label><?=$ArrEtps[3][1]?><input type="checkbox" id="etp3" name="etp3" value="<?=$ArrEtps[3][0]?>" form="fltr" <?=(($etp3==$ArrEtps[3][0])?'checked':'')?>/></label><br>
								<label><?=$ArrEtps[4][1]?><input type="checkbox" id="etp4" name="etp4" value="<?=$ArrEtps[4][0]?>" form="fltr" <?=(($etp4==$ArrEtps[4][0])?'checked':'')?>/></label>
							</td>
						</tr>
						<tr><td colspan='2'><hr> Тип сделки:</td></tr>
						<tr><td align='left'><label><input type='checkbox' id='wp' name='wp' value='П' form='fltr' <?=(($wp=='П')?'checked':'')?>/>Продажа</label></td>
							<td align='right'><label>Аренда<input type='checkbox' id='wa' name='wa' value='А' form='fltr' <?=(($wa=='А')?'checked':'')?>/></label></td>
						</tr>
						<tr><td colspan='2'><hr> Выберите район:</td></tr>
						<tr><td colspan='2'><?=$regSel?></td></tr>
						<tr><td colspan='2' align='center'><hr> Площадь:</td></tr>
						<tr><td align='center'><input id='minS' name='minS' class='nums' form="fltr" value='<?=$minS?>' placeholder="от" onchange="with(this)value=NumPositiveStrSpace(value,2);CountFilters();"/></td>
							<td align='center'><input id='maxS' name='maxS' class='nums' form="fltr" value='<?=$maxS?>' placeholder='до' onchange="with(this)value=NumPositiveStrSpace(value,2);CountFilters();"/></td>
						</tr>
						<tr><td colspan='2'><hr> Цена:</td></tr>
						<tr><td align='center'><input id='minCost' name='minCost' class='nums' form="fltr" value='<?=$minCost?>' placeholder="от" onchange="with(this)value=NumPositiveStrSpace(value);CountFilters();"/></td>
							<td align='center'><input id='maxCost' name='maxCost' class='nums' form="fltr" value='<?=$maxCost?>' placeholder='до' onchange="with(this)value=NumPositiveStrSpace(value);CountFilters();"/></td>
						</tr>
						<tr><td colspan='2' align='center'><hr><button class='BtnBig' onclick='getObj("fltr").submit()'>Применить</button></td></tr>
					</table>
					<div style="font-size:6px; margin-bottom:1px">&nbsp;</div>
				</div>
			</div>
		</div>
	<div style="columns: 310px auto">
<?php
	for(; $row = mysqli_fetch_array($Result); ){
		$pres = QuerySelect($conn, "SELECT img FROM PhotoEstate WHERE Eid=$row[Eid] ORDER BY num LIMIT 1");
		$img = 'img/NoImg.jpg';
		if($pres && ($prow = mysqli_fetch_array($pres)))
			$img = BegImg . base64_encode($prow['img']);	//	define('BegImg', "data:image/png;base64,");	//объявлено в BeginPage.php
		echo"<div class='contanerObject' onclick='BtnClick($row[Eid])'>"
				."<div class='contImg' align='center'>"
					."<img align='center' src='$img' />"
				."</div>"
				."<div class='cost'>$row[cost]</div><div class='dtr' align='right'>$row[dtr]</div><div class='clr'></div>"
				."<table class='xxx'><tr><td>$row[vid]</td><td>$row[what]</td><td>$row[S]</td></tr></table>"
				."<div class='region'><font color='gray'>район: </font>$row[region]</div>"
				."<div align='center'><br><button class='BtnBig'>Подробнее</button></div>"
			."</div>";
	}
	echo"</div></div>";
?>
	<form hidden id="frm" method="post" action="one-object.php" target="_blank">
		<input type="hidden" id="Eid" name="Eid">
	</form>
	<form hidden id='fltr' method='post'></form>
</body>
<script>
function	BtnClick(eid){
	getObj("Eid").value = eid;
	getObj("frm").submit();
}
function	FilterSay(){
	var ugs = getObj('ug').style, fs = getObj('Filters').style,
		uss = getObj('УстSpan').style, usb = getObj('УстButton').style;
	if(ugs.display=='none'){	//было раскрыто
		ugs.display = "";
		fs.display = "none";
		uss.display = "";
		usb.display = "none";
	}else{						//было спрятано
		ugs.display = "none";
		fs.display = "";		
		uss.display = "none";
		usb.display = "";
	}
}
function	CountFilters() {
	var s=0;
	if(getObj("etp1").checked) s++;
	if(getObj("etp2").checked) s++;
	if(getObj("etp3").checked) s++;
	if(getObj("etp4").checked) s++;
	if(s) s=1;
	if(getObj("wp").checked || getObj("wa").checked) s++;
	if(getObj('Region').selectedIndex > 0) s++;
	if(getObj('minS').value || getObj('maxS').value) s++;
	if(getObj('minCost').value || getObj('maxCost').value) s++;
	getObj('УстSpan').innerHTML = getObj('УстButton').innerHTML = "установлено: " + s;
}
function	AllEtp(cb){
	var s=0, t1 = getObj("etp1"), t2 = getObj("etp2"), t3 = getObj("etp3"), t4 = getObj("etp4");
	if(t1.checked) s++;
	if(t2.checked) s++;
	if(t3.checked) s++;
	if(t4.checked) s++;
	s = (s!=4);
	t1.checked = t2.checked = t3.checked = t4.checked = cb.checked = s;
	CountFilters();
}
function	ClearFilters(){
	for(var j=1; j<5; j++)
		getObj("etp"+j).checked = false;
	getObj("allEtp").checked = getObj("wp").checked = getObj("wa").checked = false;
	getObj('minS').value = getObj('maxS').value = 
	getObj('minCost').value = getObj('maxCost').value =	"";
	getObj('Region').selectedIndex = 0;
	getObj('УстSpan').innerHTML = getObj('УстButton').innerHTML = "установлено: 0";
}
function	Init(){
	with(getObj("minS")) value = NumPositiveStrSpace(value,2);
	with(getObj("maxS")) value = NumPositiveStrSpace(value,2);
	with(getObj("minCost")) value = NumPositiveStrSpace(value);
	with(getObj("maxCost")) value = NumPositiveStrSpace(value);
	correctA();
}
</script>
</html>
