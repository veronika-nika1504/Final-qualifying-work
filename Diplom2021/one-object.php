<?php	// one-object.php - Подробный показ одного объекта
	include("inc\\BeginPage.php");
	$UserID = isset($_SESSION['UserID'])?$_SESSION['UserID']:0;
	$Eid = isset($_POST['Eid']) ? $_POST['Eid'] : 0;
	echo $BeginPage;
	include("inc\\inetConnect.php");
?>
<title><?=$AppFirm?>: Просмотр объекта</title>
<link rel="stylesheet" href="css/main.css">
<style>
	.contanerObject{ width:1054px; border:solid 1px white; margin-left:auto; margin-right:auto }
	.dtr {font: normal normal 11px "Times New Roman"; color:black; padding-right:10px}

	table {padding-left:10px; padding-right:10px;}
	.contImg { height:300px; right:10%; text-align:center; padding-top:5px}
	img { max-width:90%; height:100%; margin:auto }
	.FontT18{font:normal normal 18pt "Times New Roman";}
	.Gray {color:gray}
	.cont {color:blue; font:normal normal 14pt "Times New Roman";}

	.arrowImg{ height:40px; position:absolute; top:250px; cursor:pointer; }
	#leftArrow { left:15% }
	#rightArrow { right:15% }

	#map a, #map a:visited, #map a:hover, #map a:active, #map a:link {display:none}
	.cols {  float : left; width : 50%; }
	.cols div { display : table-cell }
	.cols div:nth-child(1) { width : 235px }
	.cols div:nth-child(2) { font-weight : bold }
</style>
</head><body onload="correctA();">
<?php	SayMenu("");
	echo"<br><br>";
	$tlc = QuerySelect($conn, "SELECT idCategory, Category, fname, type FROM ListCategory ORDER BY srt"); //получаем сведения справочника категорий
	//формируем команду для получения данных
	$cmd = "SELECT CONCAT(REPLACE(FORMAT(E.RealPrice,0),',','&nbsp;'))cost" //цена объекта
				.",DATE_FORMAT(E.regDate,'%d.%m.%Y')dtr"	//дата объявления
				.",SUBSTR(CONCAT(CASE E.sell WHEN 'П' THEN ' / Продажа' ELSE '' END, CASE E.rent WHEN 'А' THEN ' / Аренда' ELSE '' END),4) what"
				.",E.region"								//район объекта
				.",E.address"								//адрес объекта
				.",E.note";								//описание объекта
	for($acl=0; $rlc=mysqli_fetch_array($tlc); $acl++){
		$cmd .= ",E.".$rlc['fname'];
		$arrCateg[$acl][0] = $rlc['type'];
		$arrCateg[$acl][1] = $rlc['idCategory'];
		$arrCateg[$acl][2] = $rlc['fname'];
		$arrCateg[$acl][3] = $rlc['Category'];		
	}
	$cmd .=  	",CONCAT(U.fam,' ',U.nam,' ',U.otc)rfio"	//фио риэлтера
				.",CONCAT(U.phone,' ',U.wPhone)rPhone"		//тф риэлтера
				.",U.note rNote"							//характеристика риэлтера
				.",CONCAT(O.fam,' ',O.nam,' ',O.otc)ofio"	//фио владельца
				.",CONCAT(O.phone,' ',O.wPhone,' ',O.eMail)oContacts"	//контакты владельца
				.",O.note oNote"							//характеристика риэлтера
				.",U.Uid rUid"								//Uid риэлтера
				.",E.Uid oUid"								//Uid владельца
			." FROM Estate E"
			." INNER JOIN LinkEstateSotr L ON L.Eid=E.Eid"
			." INNER JOIN Logins U ON U.Uid=L.Uid"
			." INNER JOIN Logins O ON O.Uid=E.Uid"
			." WHERE E.Eid=$Eid";
	$row = EasyQuery($conn, $cmd);	//выполнить запрос и получить массив данных
 	$divs  = '';

	for($j=0; $j<$acl; $j++){
		$v = trim($row[$cnm=$arrCateg[$j][2]]);
//echo "<br>j=$j; cnm=$cnm; v=$v; arrCateg[$j][1]=".$arrCateg[$j][1]."; arrCateg[$j][3]=".$arrCateg[$j][3];
		if($v>"" && $v>"0")	//значение не пустое / не нулевое
			if($cnm=='etype'){
				$gv = EasyQuery($conn,"SELECT name FROM GuideCategory WHERE idCategory='".$arrCateg[$j][1]."' AND code='".$v."'");
				$row['vid'] = $gv[0];
			}
			elseif($arrCateg[$j][0]=='S'){	//select
				$gv = EasyQuery($conn,"SELECT name FROM GuideCategory WHERE idCategory='".$arrCateg[$j][1]."' AND code='".$v."'");
				$divs .= '<div class="cols"><div>'.$arrCateg[$j][3].' : </div><div>'.$gv[0].'</div></div>';
			}elseif($cnm=="S") // ($arrCateg[$j][0]=='I')
				$row['S'] .= '&nbsp;м²';
			else
				$divs .= '<div class="cols"><div>'.$arrCateg[$j][3].' : </div><div>'.$row[$cnm].'</div></div>';
	}
	$pres = mysqli_query($conn, "SELECT num, img FROM photoestate WHERE Eid=$Eid ORDER BY num");
	$arrImg[0] = 'img/NoImg.jpg';
	for($LarrImg = 0; $prow = @mysqli_fetch_array($pres); $LarrImg++)
		$arrImg[$LarrImg] = BegImg . base64_encode($prow['img']);	//	define('BegImg', "data:image/png;base64,");	//объявлено в BeginPage.php
?>
	<div class='contanerObject'>
		<table width='100%'>
			<tr><td class='dtr' width='200'>опубликовано: <?=$row['dtr']?></td>
				<td align='center'><?=$row['what']?></td>
				<td width='200'>&nbsp;</td>
			</tr>
		</table>
		<div class='contImg' align='center' onclick='DivClicked(this,event)'>
			<img id='theImg' align='center' src='<?=$arrImg[0]?>' />
<?php	if($LarrImg>1){ ?>
			<img class='arrowImg' id='leftArrow'  src='img/LeftArrow.png'  title='К предыдущему' />
			<img class='arrowImg' id='rightArrow' src='img/RightArrow.png' title='К следующему' />
<?php	}	?>
		</div>
		<br>
		<table width='100%'><tr>
				<td class='FontT18' width='33%'><?=$row['cost']?></td>
				<td align='center'><?=$row['vid']?></td>
				<td width='33%' align='right'><?=$row['S']?></td>
		</tr></table>

		<table width='100%'>
			<tr><td class='Gray' width='40'>район:</td>
				<td><?=$row['region']?></td>
				<td width="60%" align="right" onclick="viewMap(this)" style="cursor:pointer;text-decoration:underline" title="Показать/скрыть карту"><?=$row['address']?></td>
			</tr>
			<tr><td colspan="3" align="center" class="Gray"><div id="map" style="display:none;width:60%;height:400px;"><?=noInternet('Чтобы увидеть карту подкючитесь к сети Интернет')?></div>описание:</td></tr>
			<tr><td colspan="3" align="wide"><?=$divs?></td></tr>
			<tr><td colspan="3" align="wide"><?=($row['note']?'<center class="Gray">комментарий:</center>':'') . $row['note']?></td></tr>
<?php
/* не вошёл:				только кнопка контактов риэлтера
 * клиент, объект не его:	только кнопка контактов риэлтера
 * клиент, объект его:		видим риэлтера, кнопка изменить объект
 * сотрудник, объект не его:видим риэлтера
 * сотрудник, объект его:	видим риэлтера, видим владельца, кнопка изменить объект, кнопка изменить клиента
 * админ:					видим риэлтера, видим владельца, кнопка изменить объект, кнопка изменить клиента, кнопка изменить риэлтера
 */
		$ViewRielter = $ViewOwner = $ChgObj = $ChgOwner = $ChgRielter = false;
		if($UserRights==0xFFFFFFFF)
			$ViewRielter = $ViewOwner = $ChgObj = $ChgOwner = $ChgRielter = true;
		elseif($UserRights & 2 && $UserID==$row['rUid'])
			$ViewRielter = $ViewOwner = $ChgObj = $ChgOwner = true;
		else{
			if($UserRights & 2)			$ViewRielter = true;
			if($UserID==$row['oUid'])	$ViewRielter = $ChgObj = true;
		}
		if(!$ViewRielter){ ?>
			<tr><td colspan='3' align='center'><br>
					<button class='BtnBig' onclick='this.style.display="none";getObj("cont").style.display="";'>Контакты</button>
					<div id='cont' style='display:none'>Контактные данные нашего специалиста: <span class='cont'><?=$row['rfio']?></span>, телефон: <span class='cont'><?=$row['rPhone']?></span></div>
				</td>
			</tr>
<?php	}else{
			if($ChgObj){ ?>
			<tr><td colspan='3' align='center'><button class='BtnBig' onclick='DoChange(0,<?=$Eid?>)'>Изменить</button></td></tr>			
<?php		}	?>
			<tr><td class="Gray">Риэлтер:</td>
				<td><?=$row['rfio']?></td>
				<td><?=$row['rPhone']?></td>
			</tr>
<?php		if($ChgRielter){ ?>
			<tr><td colspan="3" class="Gray" align="center">характеристика сотрудника:</td></tr>
			<tr><td colspan="3"><?=$row['rNote']?></td></tr>
			<tr><td colspan="3" align='center'><button class='BtnBig' onclick='DoChange(2,<?=$row['rUid']?>)'>Изменить</button></td></tr>
<?php		}
			if($ViewOwner){	?>
			<tr><td class="Gray">Владелец:</td>
				<td><?=$row['ofio']?></td>
				<td><?=$row['oContacts']?></td>
			</tr>			
<?php		}
			if($ChgOwner){	?>
			<tr><td colspan="3" class="Gray" align="center">характеристика владельца:</td></tr>
			<tr><td colspan="3"><?=$row['oNote']?></td></tr>
			<tr><td colspan="3" align='center'><button class='BtnBig' onclick='DoChange(1,<?=$row['oUid']?>)'>Изменить</button></td></tr>
<?php		}
		}	?>
		</table>
	</div>
	<form hidden id="frm" method="post">
		<input type="hidden" id="x1"/>
		<input type="hidden" id="x2"/>
	</form>
</body>
<script>
var arrImg = [], nImg = 0;
<?php	for($i=0; $i < $LarrImg; $i++)	echo "arrImg[$i]='$arrImg[$i]';";
		if($LarrImg > 1){	?>
function DivClicked(obj, evt){
	var X = evt.clientX - obj.offsetLeft;
	nImg += (X <= obj.offsetWidth/2) ? -1 : 1;
	if(nImg==-1) nImg = <?=$LarrImg?> -1;
	else if(nImg==<?=$LarrImg?>) nImg = 0;
	getObj('theImg').src = arrImg[nImg];
}
<?php	} ?>
function	DoChange(o, id){
	var f = getObj('frm'), x1 = getObj('x1'), x2 = getObj('x2');
	switch(o){
		case 0:		//редактировать объект
			with(x1){ value = "<?=$row['oUid']?>";	name = "editID"; }
			with(x2){ value = "<?=$Eid?>";			name = "Eid"; }
			f.action = "operate/Edit-Estate.php";
			break;
		case 1:		//редактировать владельца
			with(x1){ value = "<?=$row['oUid']?>";	name = "editID"; }
			f.action = "l-k/lk.php";
			break;
		case 2:		//редактировать сотрудника
			with(x1){ value = "<?=$row['rUid']?>";	name = "editID"; }
			f.action = "l-k/lk.php";
			break;
	}
	f.submit();
}
</script>
<script src="js/min.jquery.js?v3"></script>
<script src="https://api-maps.yandex.ru/2.1/?apikey=<?=YmapApiKey()?>&lang=ru_RU"></script>
<script src="js/view-map.js"></script>
<script>
function	 viewMap(objAddress){
	var str = "";
	if(typeof objAddress == "string")	str = objAddress;
	else if(objAddress.innerHTML)		str = objAddress.innerHTML;
	else if(objAddress.value)	str = objAddress.value;
	str = str.trim();	if(!str) return;
	var m = getObj("map");
	with(m.style)
		if(display == "block"){	display = "none";	window.scrollTo(0,0); }
		else if(YmapApiVars.mapMap){		display = "block";	scrollDown(); }
		else	buildMap("map", str, scrollDown);
}
function	 scrollDown(){
	var obj = getObj("map"),	toScroll = obj.offsetTop;
	while(obj = obj.offsetParent) toScroll += obj.offsetTop;
	window.scrollTo(0, toScroll - 100);
}
</script>
</html>
