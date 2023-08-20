<?php	//Edit-Estate.php: Добавление / изменение объекта недвижимости
	include("..\\inc\\BeginPage.php");
	AssertLogon();
	$sessID = $_SESSION['UserID'];	//id вошедшего в систему
	$eid = isset($_POST['Eid']) ? $_POST['Eid'] : "newEid";	// eid объекта
	$editID = isset($_POST['editID']) ? $_POST['editID'] : $sessID;	//id того, кто редактирует
	$page = isset($_POST['page']) ? $_POST['page'] : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "");
	$TheIns = false;
	$ResultMsg = "";
	include("..\\inc\\inetConnect.php");
	$isYandex = inetConnect();
	//получение справочников
	$tlc = QuerySelect($conn, "SELECT L.Category, L.fname, L.type, G.code, G.name, G.k "
								."FROM ListCategory L INNER JOIN GuideCategory G ON G.idCategory=L.idCategory ORDER BY L.srt, G.srt");
/*
Category			fname	type	code	name			k
Тип недвижимости	eType	S				-не указано-	0.0000
Тип недвижимости	eType	S		Д		дом				0.0100
Тип недвижимости	eType	S		Ч		часть дома		-0.0100
Тип недвижимости	eType	S		К		квартира		0.0000
Тип недвижимости	eType	S		М		комната			-0.0200
Общая площадь (м²)	S		I								0.0000

*/
	$bytes = 0; //число байт, передаваемых черех POST
	$fname = '';
	$aol = 0;	//длина массива подготавливаемых объектов DOM
	for($acl=0; $rlc=mysqli_fetch_array($tlc); $acl++){
		$arrCateg[$acl]['fname']	= $rlc['fname'];
		$arrCateg[$acl]['type']		= $rlc['type'];
		if($rlc['fname']<>$fname){	//новое
			if($acl){	//это не самое первое
				if($arrCateg[$acl-1]['type']=='S')	//надо закончить формирование select-а
					$arrObject[$aol]['object'] .= "</select>";
				$aol++;
			}
			$fname = $rlc['fname'];
			$pst[$fname] = null;
			$arrObject[$aol]['Category'] = $rlc['Category'];
			$arrObject[$aol]['fname']	= $fname;
			if($rlc['type']=='S'){				//тип = select - будет код
				if($eid=="newEid")			$row[$fname] = '';
				if(isset($_POST[$fname]))	$pst[$fname] = $_POST[$fname];
				$bytes += 2;	// 1 русская буква в UTF-8
				//начинаем формирование select
				$arrObject[$aol]['object'] = "<select id='$fname' name='$fname' form='frm' "
												.(($_SESSION['UserRights'] & 2 || $fname == 'etype') ? " onchange='SprChanged(this)'" : "") 
												."><option ". (($_SESSION['UserRights'] & 2) ? "kf='$rlc[k]' " : "")
														."value='$rlc[code]'>$rlc[name]</option>";
			}elseif(substr($fname,0,1)=='S'){	//($rlc['type']=='I'), input - будет число, при начале с S - площадь
				if($eid=="newEid")			$row[$fname] = 0.0;
				if(isset($_POST[$fname]))	$pst[$fname] = str_replace(",",".",str_replace(" ","",$_POST[$fname]));
				$bytes += 7;	//на decimal(6,4)
				//формируем input
				$arrObject[$aol]['object'] = "<input id='$fname' name='$fname' maxlength='50' form='frm' value='#input' "
												.(($_SESSION['UserRights'] & 2) ? "kf='$rlc[k]' " : "")
												."onchange='with(this)value=NumPositiveStrSpace(value,2);Rasschet();' />";
			}else{								//число целое
				if($eid=="newEid")			$row[$fname] = 0;
				if(isset($_POST[$fname]))	$pst[$fname] = $_POST[$fname];
				$bytes += 2;	//на пару цифр целого числа
				//формируем input
				$arrObject[$aol]['object'] = "<input id='$fname' name='$fname' maxlength='50' form='frm' value='#input' " 
												.(($_SESSION['UserRights'] & 2) ? "kf='$rlc[k]' " : "")
												."onchange='with(this)value=NumPositiveStrSpace(value);Rasschet();' />";
			}
		}elseif($rlc['type']=='S')	//($rlc['fname']==$fname) - продолжаем формирование select
			$arrObject[$aol]['object'] .= "<option ". (($_SESSION['UserRights'] & 2) ? "kf='$rlc[k]' " : "")
														."value='$rlc[code]'>$rlc[name]</option>";
		elseif($fname=='etaj')		//повторное ($rlc['type']=='I') - скорректировать input
			if($_SESSION['UserRights'] & 2)
				$arrObject[$aol]['object'] = str_replace("kf=", "kfLast='$rlc[k]' kf=", $arrObject[$aol]['object']);
	}
	if($arrCateg[$acl-1]['type']=='S')	//надо закончить формирование select-а
		$arrObject[$aol]['object'] .= "</select>";
	$aol++;
	
	// поля Estate не имеющие информации в справочнике:
	//		Szem	//sell rent address region note	 price AutoPrice RealPrice state
	$bytes += 4 + 2 *(1  + 1  + 200   + 50   + 2000) + 10 + 10      + 10      + 1;
	$pst['sell'] = $pst['rent'] = $pst['address'] = $pst['region'] = $pst['note'] = 
	$pst['price'] = $pst['AutoPrice'] = $pst['RealPrice'] = $pst['state'] = null;

	if(isset($_POST['sell']))		$pst['sell'] = $_POST['sell'];
	if(isset($_POST['rent']))		$pst['rent'] = $_POST['rent'];
	if(isset($_POST['address']))	$pst['address'] = $_POST['address'];
	if(isset($_POST['region']))		$pst['region'] = $_POST['region'];
	if(isset($_POST['note']))		$pst['note'] = $_POST['note'];
	if(isset($_POST['price']))		$pst['price'] = $_POST['price'];
	if(isset($_POST['state']))		$pst['state'] = $_POST['state'];
	if(isset($_POST['AutoPrice']))	$pst['AutoPrice'] = $_POST['AutoPrice'];
	if(isset($_POST['RealPrice']))	$pst['RealPrice'] = $_POST['RealPrice'];
		
	if($eid=="newEid"){
		$title = "Добавляем объект!";
		$row['sell'] = $row['rent'] = $row['address'] = $row['region'] = $row['note'] = '';
		$row['state'] = $row['price'] = 0;
		$row['AutoPrice'] = $row['RealPrice'] = "NULL";
		$TheIns = true;
	}else{
		$title = "Изменение объекта";
		$row = EasyQuery($conn, "SELECT * FROM Estate WHERE Eid=$eid");	//получаем содержание объекта недвижимости
	}
	
	$InsFld = $InsVal = "";	//для возможной записи в таблицу готовим части команды INSERT или UPDATE
	//заполняем массив $pst (для null) или части команды записи в БД для отличающихся полей
	foreach($pst as $key => $value)
		if($value===null)	$pst[$key] = $row[$key];
		elseif($pst[$key]<>$row[$key]) {	//есть POST-переменная, т.е. надо в базу что-то записать, если изменения есть
			//выясняем тип поля
			switch($key){	//перебираем те, что без справочника
				case 'sell': case 'rent': case 'address': case 'region': case 'note':
					$chr = true;	break;
				case 'price': case 'AutoPrice': case 'RealPrice':
					$pst[$key] = str_replace(" ", "", $pst[$key]);
				case 'state':
					$chr = false;	break;
				default:	// ищем в спрвочнике
					for($j=0; $j<$acl; $j++) if($arrCateg[$j]['fname']==$key) break;
					$chr = ($arrCateg[$j]['type']=='S');
			}
			if(!$chr && !$pst[$key])	$pst[$key] = 0;	//для числа - если пусто - будет ноль
			$InsFld .= ",$key";													// ,sell
			if($TheIns)	$InsVal .= ",". ($chr ? "'$pst[$key]'" : $pst[$key]);	//				,'П'
			else		$InsFld .= "=".	($chr ? "'$pst[$key]'" : $pst[$key]);	//		='П'			
		} //if,foreach
	//есть есть изменение или вставка, проводим операцию
	if($InsFld){
		//добавить инфу: кто внёс:
		$InsFld .= ",lastUid";
		if($TheIns)	$InsVal .= ",$sessID";
		else		$InsFld .= "=$sessID";
		if($TheIns){	// INSERT, надо добавить ссылку на владельца
			$InsFld .= ",Uid";	$InsVal .= ",$editID";
			$InsFld = "INSERT INTO Estate(". substr($InsFld,1) .")VALUES(". substr($InsVal,1) .");";
		}else
			$InsFld = "UPDATE Estate SET ". substr($InsFld,1) ." WHERE Eid=$eid; ";
		EasyQueryNoResult($conn, $InsFld);
		$ResultMsg = "Изменения внесены !";
		if($TheIns) // если был insert, надо получить ID, сгенерированный автоматически /mysqli_insert_id($conn)/ и по нему обновить $row
			$eid = mysqli_insert_id($conn);
		$row = EasyQuery($conn, "SELECT * FROM Estate WHERE Eid=$eid");
	}
	
	//	выясняем, сколько байт разрешено в PHP переслать POST-ом
 	$MaxLenImages = ini_get("post_max_size");
	switch(strtolower(substr($MaxLenImages,-1))){
		case 'k':	$MaxLenImages = substr($MaxLenImages,0,strlen($MaxLenImages)-1) * 1024;			break;
		case 'm':	$MaxLenImages = substr($MaxLenImages,0,strlen($MaxLenImages)-1) * 1024 * 1024;		break;
		case 'g':	$MaxLenImages = substr($MaxLenImages,0,strlen($MaxLenImages)-1) * 1024 * 1024 * 1024;	break;
	}
	$MaxLenImages -= $bytes;	//столько остаётся на рисунки
	
	// Получение региональных цен
	$tbl = QuerySelect($conn, "SELECT region, Cost_m2, Cost_sotka FROM CostRegion");
	$ArrCostRegion = "var ArrCostRegion=[];";
	for($j=0; $r = mysqli_fetch_array($tbl); $j++)
	    $ArrCostRegion .= "ArrCostRegion[$j]=[];"
			    . "ArrCostRegion[$j][0]='$r[region]';" 
			    . "ArrCostRegion[$j][1]='$r[Cost_m2]';"
			    . "ArrCostRegion[$j][2]='$r[Cost_sotka]';";
	$ArrCostRegion .= "var LenArrCostRegion=$j;";
	
	$KVclass = "Квартира NoDisp";
	$DMclass = "Дом NoDisp";
	$CSclass = "Часть NoDisp";
	$DCclass = "ДомЧасть NoDisp";
	switch($pst['etype']){
		case "К":	case "М":	
			$KVclass = "Квартира";	break;
		case "Д":	
			$DMclass = "Дом";
			$DCclass = "ДомЧасть";	break;
		case "Ч":	
			$CSclass = "Часть";
			$DCclass = "ДомЧасть";	break;
	}
	
	$disabled = "";
	if( ($eid == $editID) && ($_SESSION['UserRights'] & 2 <> 2) && $row['state'] )
		$disabled 	= ' disabled';
	elseif( $row['state'] == 3)
		$disabled 	= ' disabled';
	$OnClickClearCnv = $disabled ? "" :	' onclick="clearCnv(this)"';

	// вносим изменения в $arrObject, чтобы указать выделенное и добавляем <td>, </td>, название категории
	for($j=0; $j<$aol; $j++){
		if(substr($arrObject[$j]['object'],0,7)=='<select'){
			$i = strpos($arrObject[$j]['object'], " value='".$row[$arrObject[$j]['fname']]."'");
			while(substr($arrObject[$j]['object'],$i,1)!='>') $i++;
			$arrObject[$j]['object'] = substr($arrObject[$j]['object'],0,$i)." selected $disabled".substr($arrObject[$j]['object'],$i);
		}else
			$arrObject[$j]['object'] = str_replace("value='#input'","value='".$row[$arrObject[$j]['fname']]."'". $disabled, $arrObject[$j]['object']);
		$arrObject[$j]['object'] = '<td class="zagt">'. $arrObject[$j]['Category'] .':</td><td>'. $arrObject[$j]['object'] .'</td>';
	}

//define('BegImg', "data:image/png;base64,"); //объявлено в BeginPage.php
//define('LenBegImg', 22);	//22=strlen(BegImg);
function savegetImg($num){
	global $conn, $sessID, $eid, $ResultMsg;
	$img = "";
	$name = "img$num";
	if(!isset($_POST[$name]))
		$img = "READ";
	else{
		$img = $_POST[$name];	//$img="data:image/png;base64,кодированное_значение_рисунка"
								//      01234567890123456789012
		if(substr($img,0,LenBegImg)==BegImg)
			$img = base64_decode(substr($img,LenBegImg));
		else
			$img = "";
	}
	$Result = mysqli_query($conn, "CALL ImageSaveGet($sessID,$eid,$num,'". addslashes($img) ."')");
	$img = "";
	if($Result && mysqli_field_count($conn) && ($row = mysqli_fetch_array($Result))){
		$img = BegImg.base64_encode($row['img']);
		if($row['chgd']>'') $ResultMsg = "Изменения внесены !";
		while(mysqli_next_result($conn))	mysqli_store_result($conn);	//пропустить дополнительный ответ MySQL о результатах работы
	}
	return $img;
}	//function savegetImg
	for($j = 1; $j < 9; $j++)	$imgArr[$j] = savegetImg($j);
	
	// для сотрудника:
	$xoz = "";
	if( ($_SESSION['UserRights'] & 2) && ($eid<>"newEid")){	// вошёл сотрудник - надо получить и показать доп.инфо.
		// кто владелец
		$xoz = EasyQuery($conn, "SELECT CONCAT( L.fam, ' ', L.nam, ' ', L.otc ) FIO, L.phone, L.eMail ".
													"FROM estate E INNER JOIN logins L ON L.Uid = E.Uid WHERE E.Eid=$eid");
		$xoz = '<tr><td class="zagt">Авторассчитанная цена (в руб):</td><td id="vAutoPrice" style="color: red; text-align:center; font-size:14pt; font-weight:bold"></td></tr>'.
					'<tr><td class="zagt">РЕАЛЬНАЯ ОЦЕНКА (в руб.):</td><td>'.
		'<input id="RealPrice" name="RealPrice" onchange="with(this)value=NumPositiveStrSpace(value)" placeholder="0" form="frm" value="'.$row['RealPrice'].'"' .$disabled .'/></td></tr>' .
					'<tr><td colspan="2" align="center">Владелец: '. $xoz[0] . ($xoz[1] ? ", тел: $xoz[1]" : "") . ($xoz[2] ? ", eMail: $xoz[2]" : "") . '</td></tr>' .
					'<tr><td colspan="2" align="center">';
		switch($row['state']){
			case '0':	// было "не опубликовано"
				$xoz .= '<font color="gray">не опубликовано</font><br><button class="BtnBig" style="width:300px" onclick="getObj(\'state\').value=\'1\';doSubmit()" title="Опубликовать, редактировать только риэлтору!">Опубликовать</button>'.
					'&nbsp;&nbsp;<button class="BtnBig" style="width:150px" onclick="getObj(\'state\').value=\'2\';doSubmit()" title="Не публиковать, разрешить редактировать только риэлтору!">Блокировать</button>';
				break;	
			case '1':	// было "опубликовано"
				$xoz .= '<font color="gray">опубликовано</font><br><button class="BtnBig" style="width:150px" onclick="getObj(\'state\').value=\'0\';doSubmit()" title="Снять с публикации, разрешить редактировать хозяину!">Разблокировать</button>'.
					'&nbsp;&nbsp;<button class="BtnBig" style="width:150px" onclick="getObj(\'state\').value=\'2\';doSubmit()" title="Снять с публикации, разрешить редактировать только риэлтору!">Блокировать</button>'.
					'&nbsp;&nbsp;<button class="BtnBig" style="width:150px" onclick="getObj(\'state\').value=\'3\';doSubmit()" title="Сделка завершена">Архивировать</button>';
				break;	
			case '2':	// было "блокировано"
				$xoz .= '<font color="gray">блокировано</font><br><button class="BtnBig" style="width:150px" onclick="getObj(\'state\').value=\'0\';doSubmit()" title="Не публиковать, разрешить редактировать хозяину!">Разблокировать</button>'.
					'&nbsp;&nbsp;<button class="BtnBig" style="width:150px" onclick="getObj(\'state\').value=\'1\';doSubmit()" title="Опубликовать, редактировать только риэлтору!">Опубликовать</button>'.
					'&nbsp;&nbsp;<button class="BtnBig" style="width:150px" onclick="getObj(\'state\').value=\'3\';doSubmit()" title="Сделка завершена">Архивировать</button>';
				break;
			case '3':	// было "архивировано"
				$xoz .= '<font color="gray">архивировано</font><br><button class="BtnBig" style="width:150px" onclick="getObj(\'state\').value=\'2\';doSubmit()" title="Снять с публикации, редактировать только риэлтору!">Блокировать</button>'.
					'&nbsp;&nbsp;<button class="BtnBig" style="width:300px" onclick="getObj(\'state\').value=\'1\';doSubmit()" title="Опубликовать, редактировать только риэлтору!">Опубликовать</button>';
				break;	
		}
		$xoz .= '</td></tr>';
	}
	echo $BeginPage;
?>
<title><?=$AppFirm.": ".$title?></title>
<link rel="stylesheet" href="../css/main.css">
<style>
	select, select option { background-color: #d8dced; }
	select {width:168px; border-radius:5px; border-width:2px;}
	input[type="number"]::-webkit-outer-spin-button,
	input[type="number"]::-webkit-inner-spin-button {
		-webkit-appearance: none;
		margin: 0;
	}
	input[type="number"] { -moz-appearance: textfield;}
	input[type="number"]:hover,
	input[type="number"]:focus { -moz-appearance: number-input; }
	
	.zagt { font: bold italic 13pt "Times New Roman"; color:blue; }
	textarea,
	input { background-color: #d8dced; border-radius:5px; }
	input	 { width:160px; text-align:center}
	input[type="checkbox"] {width:20px; }
	textarea { resize:none; width:473px; border-width:2px }
	label { font-size:11pt }
	.lft	 { float: left }
	.rgt { float: right }
	#TCL td, #TCR td { height:304px; vertical-align:middle; }
	#TCL td { text-align: right }
	#TCR td { text-align: left }
	.NoDisp { display : none }
	.Gray { color: gray}
</style>
</head><body onload="Init();">
<?php	SayMenu($title);	?>
	<form id="frm" method="post" onsubmit="return false">
		<input type="hidden" name="Eid" value="<?=$eid?>"/>
		<input type="hidden" name="editID" value="<?=$editID?>"/>
		<input type="hidden" name="page" value="<?=$page?>"/>
		<input type="hidden" name="sell" id="sell" value="<?=$row['sell']?>"/>
		<input type="hidden" name="rent" id="rent" value="<?=$row['rent']?>"/>
		<input type="hidden" name="state" id="state" value="<?=$row['state']?>"/>
		<input type="hidden" name="AutoPrice" id="AutoPrice" value="<?=$row['AutoPrice']?>"/>
		<input type="hidden" id="img1" value=""/>
		<input type="hidden" id="img2" value=""/>
		<input type="hidden" id="img3" value=""/>
		<input type="hidden" id="img4" value=""/>
		<input type="hidden" id="img5" value=""/>
		<input type="hidden" id="img6" value=""/>
		<input type="hidden" id="img7" value=""/>
		<input type="hidden" id="img8" value=""/>
<?php	if(!$xoz){	?>
		<input type="hidden" name="RealPrice" value="<?=$row['RealPrice']?>"<?=$disabled?>/>
<?php	}
function	 Form_TR($name){
	GLOBAL $arrObject, $aol;
	for($j=0; $j<$aol; $j++) if($arrObject[$j]['fname']==$name) return $arrObject[$j]['object'];
}
?>
	</form>
	<TABLE align="centr" width="100%">
		<TR>
			<TD align="center" height="50%" width="30%" title="Для удаления фото щёлкните по ней" valign="top">
				<table id="TCL" width="100%">
					<tr><td><canvas id="c1" state="empty"<?=$OnClickClearCnv?>></canvas></td></tr>
					<tr><td><canvas id="c3" state="empty"<?=$OnClickClearCnv?>></canvas></td></tr>
					<tr><td><canvas id="c5" state="empty"<?=$OnClickClearCnv?>></canvas></td></tr>
					<tr><td><canvas id="c7" state="empty"<?=$OnClickClearCnv?>></canvas></td></tr>
				</table>
			</TD>
			<TD width="40%" valign="top">
				<table align="center">
					<tr><td colspan="2" align="center" class="Gray">Обязательно к заполнению:</td></tr>
					<tr><td class="zagt">Тип сделки:</td>
							<td align="right" style="width:160px">
								<label class="lft"><input type="checkbox" onchange="getObj('sell').value=this.checked?'П':'';Rasschet()" <?=(($row['sell']=='П')?'checked':'').$disabled?>/>Продажа</label>
								<label class="rgt">Аренда<input type="checkbox" onchange="getObj('rent').value=this.checked?'А':'';Rasschet()" <?=(($row['rent']=='А')?'checked':'').$disabled?>/></label>
							</td>
					</tr>
					<tr><?=Form_TR('etype')?></tr>
					<tr><?=Form_TR('S')?></tr>
					<tr><td colspan="2"><div class="zagt lft">Адрес:</div>
								<div class="rgt" style="color:gray; font-size:9pt; transform:translateY(4px)">(сохранение объекта возможно лишь после ввода адреса с выбором из подсказок)</div>
							</td>
					</tr>
					<tr><td colspan="2"><input id="address" maxlength="200" name="address" placeholder="Начните вводить адрес и обязательно выберите из подсказок" form="frm" style="width:473px" value="<?=$row['address']?>" onkeydown="if(event.keyCode==13)setAddress(this);else setAddress=false;return false;" <?=$disabled?>/></td></tr>
					<tr><td class="zagt">Район:</td><td><input id="region" maxlength="50" name="region" form="frm" value="<?=$row['region']?>"<?=$disabled?>/></td></tr>
					<tr><td class="zagt">Ваша оценка (в руб.):</td><td><input id="price" name="price" onchange="with(this)value=NumPositiveStrSpace(value)" placeholder="0" form="frm" value="<?=$row['price']?>"<?=$disabled?>/></td></tr>
					<tr><td colspan="2" align="center" class="Gray">Желательно к заполнению:</td></tr>
<?php	
	for($j=0; $j<$aol; $j++)
		switch($arrObject[$j]['fname']){
			case 'etype': case 'S':		//уже обработано
			case 'magaz': case 'tambur': case 'lift': case 'etaj':	//будет обработано позже - для квартиры/комнаты
			case 'Sstroen': case 'sovlad':							//будет обработано позже - для части дома
			case 'Szemlia': case 'sarai':							//будет обработано позже - для дома
				break;
			default:
				echo "<tr>".Form_TR($arrObject[$j]['fname'])."</tr>";
				break;
		}
?>					<tr class="<?=$KVclass?>"><td class="zagt" colspan="2" style="text-align:center">Для квартиры / комнаты:</td></tr>
					<tr class="<?=$KVclass?>"><?=Form_TR('magaz')?></tr>
					<tr class="<?=$KVclass?>"><?=Form_TR('tambur')?></tr>
					<tr class="<?=$KVclass?>"><?=Form_TR('lift')?></tr>
					<tr class="<?=$KVclass?>"><?=Form_TR('etaj')?></tr>

					<tr class="<?=$CSclass?>"><td class="zagt" colspan="2" style="text-align:center">Для части дома:</td></tr>
					<tr class="<?=$CSclass?>"><?=Form_TR('Sstroen')?></tr>
					<tr class="<?=$CSclass?>"><?=Form_TR('sovlad')?></tr>

					<tr class="<?=$DMclass?>"><td class="zagt" colspan="2" style="text-align:center">Для дома:</td></tr>
					<tr class="<?=$DCclass?>"><?=Form_TR('Szemlia')?></tr>
					<tr class="<?=$DCclass?>"><?=Form_TR('sarai')?></tr>

					<tr><td colspan="2" align="center">
							<button class="BtnBig" style="width:165px" onclick="getObj('fn').click();"<?=$disabled?>>Добавить фото</button>
							<input id="fn" type="file" accept="image/*" multiple style="display:none" onchange="GetImages(this)" />
						</td>
					</tr>
					<tr><td colspan="2" class="zagt">Описание:</td></tr>
					<tr><td colspan="2"><textarea id="note" rows="6" maxlength="2000" form="frm" name="note"<?=$disabled?>><?=$row['note']?></textarea></td></tr>
					<tr><td colspan="2" align="center"><br><button class="BtnBig" style="width:300px" onclick="doSubmit(true)"<?=$disabled?>><?=($xoz ? "Сохранить характеристики" : "Отправить на проверку")?></button></td></tr>
<?=$xoz?>
				</table>
			</TD>
			<TD align="center" height="50%" width="30%"<?=($OnClickClearCnv ? ' title="Для удаления фото щёлкните по ней"' : "")?> valign="top">
				<table id="TCR" width="100%">
					<tr><td><canvas id="c2" state="empty"<?=$OnClickClearCnv?>></canvas></td></tr>
					<tr><td><canvas id="c4" state="empty"<?=$OnClickClearCnv?>></canvas></td></tr>
					<tr><td><canvas id="c6" state="empty"<?=$OnClickClearCnv?>></canvas></td></tr>
					<tr><td><canvas id="c8" state="empty"<?=$OnClickClearCnv?>></canvas></td></tr>
				</table>
			</TD>
		</TR>
	</TABLE>
<style>
	.u-AhunterSuggestions{
		border: 1px solid #AAAAAA;
		background: white;
		overflow: auto;
		border-radius: 2px;
		transform: translateY(10px) translateX(7px);;
		cursor: pointer;
	}
	.u-AhunterSuggestions strong { 
		font-weight: bold; 
		color: #1B7BB1; 
	}
</style>
<div id="ResMsg" class="notice" style="display:none" onclick="this.style.display='none'"><?=$ResultMsg?></div>
</body>
<script>
function	GetImages(fobj){
// получить выбранные фотографии
	var i, num = fobj.files.length;
	if(num>8) num = 9;
	for(i = 0; i < num; i++)
		getOneImage(fobj.files[i]);
	fobj.value = "";
	window.scrollTo(0, 0);
} //function	GetImages
function	getOneImage(oneFile){	
// отработка получения одной фотки
	var fr = new FileReader();
	fr.onload = function(){
		var img = document.createElement("img");
		img.onload = function(){	/*	при W=380	 	-	maxW x maxH = 360 x 300 */
			var x, w, h, cnv = getObj("c1"),	 maxH = 300,	maxW = cnv.parentNode.offsetWidth-20;
			for(x = 1; cnv = getObj("c"+x); x++)
				if(cnv.getAttribute("state")=="empty") break;
			if(x>8){
				alert("Достигнуто максимальное число фотографий - 8.");
				return;
			}else cnv.setAttribute("state","new");
			with(img){ w = width; h = height; }
			if(w > maxW){
				h = Math.round(h * maxW / w);
				w = maxW;
			}
			if(h > maxH){
				w = Math.round(w * maxH / h);
				h = maxH;
			}
			with(cnv){ width = w; height = h; }
			var ctx = cnv.getContext("2d");
			ctx.drawImage(img, 0,0,w,h);
			img = null;
		} ;
		img.src = fr.result;
	};
	fr.readAsDataURL(oneFile);
}	//function	getOneImage
function	drawImg(cnv, base64){
// нарисовать на канве cnv кодированный в base64 рисунок
	var img = document.createElement("img");
	img.onload = function(){
		with(cnv){
			width = img.width;
			height = img.height;
			setAttribute("state","db");
			getContext("2d").drawImage(img, 0,0,width,height);
		}
		img = null;
	} ;
	img.src = base64;
}	//function	drawImg
function	clearCnv(cnv){
//	очистить рисунок на канве
	cnv.setAttribute("state","empty");
	cnv.width = cnv.height = 0;
}	//function	clearCnv
function	CloseResMsg(){
//скрыть сообщение о результате и перейти на страницу со списком объектов
	getObj("ResMsg").style.display="none";
	window.location.href = "Say-Objects.php";
}	//function	CloseResMsg
function	toNum(val, drob){
	val = val.toString().trim().replace(/ /g,'').replace(/\,/,'.');
	if(!val) return drob ? 0.0 : 0;
	if(drob)
		val = parseFloat(val);
	else{
		var j = val.indexOf('.');
		if(j>=0) val = val.substr(0,j);
		val = parseInt(val);
	}
	return val;
}	//function toNum
<?php	if($_SESSION['UserRights'] & 2){		// функции для вычисления авто-оценки 	?>
	// изменение всякого справочника
var ListSprSel = ListSprInp = null, LenListSprSel = LenListSprInp = OSNOVA = ZEMLIA = 0;
<?php //объяв. var ArrCostRegion[];ArrCostRegion[0][0]='обл Тамбовская';ArrCostRegion[0][1]=100;ArrCostRegion[0][2]=100;...
	echo $ArrCostRegion;  ?>

function	 FormListSpr(){
// сформировать массивы объектов Select - справочников и Input - полей ввода
	if(LenListSprSel) return;
	ListSprSel = document.getElementsByTagName("SELECT");
	LenListSprSel = ListSprSel.length;
	var inps = document.getElementsByTagName("INPUT"),	//все input-ы
		linp = inps.length, j;
	ListSprInp = [];
	for(j=LenListSprInp=0; j<linp; j++)
		if(inps[j].getAttribute("kf") && inps[j].id!='S')
			ListSprInp[LenListSprInp++] = inps[j];
}	//function	 FormListSprSel
function	 SetOsnova(){
// установить основу исчисления цены
	var j, re, a = getObj("address").value;
	for(j=0; j < LenArrCostRegion; j++){
	    re = new RegExp("^"+ArrCostRegion[j][0]+".*");
	    if(re.test(a)) break;
	}
	OSNOVA = getObj('S').value.toString().replace(/ /g,'');
	OSNOVA = parseFloat(OSNOVA.replace(/\,/,'.'));
	if(isNaN(OSNOVA) || OSNOVA < 0.1) OSNOVA = 0;
	if(j==LenArrCostRegion) j = 35;	//не нашлось - считаем, что Воронежская
    OSNOVA *= ArrCostRegion[j][1];
    ZEMLIA = ArrCostRegion[j][2];
}	//function	 SetOsnova
function	 Rasschet(){
// провести полный перерасчёт стоимости объекта
	// коррекция по 1 показателю справочника:
	function	rOneSel(sel){	with(sel) return OSNOVA * options[selectedIndex].getAttribute("kf");	}
	// коррекция по показателю из поля ввода:
	function	rOneInp(inp){
		var v = 0;
		with(inp)
			switch(id){
				case 'Szemlia':
					v = ZEMLIA * toNum(value, true);
					break;
				case 'etaj':
					var e = getObj('etajej').value;
					if(e>1)
						if(value==1)
							v = OSNOVA * getAttribute("kf");
						else if(value==e)
							v = OSNOVA * getAttribute("kfLast");
					break;
				default:
					v = toNum(value, id.substr(0,1)=='S') * getAttribute("kf");
			} //switch,with
		return v;
	}
	SetOsnova();
	var j, Sum = OSNOVA;
	for(j = 0; j < LenListSprSel; j++)	Sum += rOneSel(ListSprSel[j]);
	for(j = 0; j < LenListSprInp; j++)	Sum += rOneInp(ListSprInp[j]);
	Sum = Math.floor(Sum);
	if(getObj("rent").value=="А" && getObj("sell").value!="П") Sum = Math.round(Sum/96);
	getObj("AutoPrice").value = Sum;
	getObj("vAutoPrice").innerHTML = NumPositiveStrSpace(Sum);
}	//function	 Rasschet
<?php	}else{ ?>
function	Rasschet(){}
<?php	}	?>

function	SprChanged(sel){
//Вызывается при изменении справочника
	function	NoDisp(cName){
	//не показывать элементы с указанным классом, очистить значения в них
		var j, s, i,
			arr = document.getElementsByClassName(cName),
			la = arr.length;
		cName += " NoDisp";
		for(j = 0; j < la; j++){
			arr[j].className = cName;
			s = arr[j].getElementsByTagName("SELECT");
			for(i = 0; i < s.length; i++)	s[i].selectedIndex = 0;
			s = arr[j].getElementsByTagName("INPUT");
			for(i = 0; i < s.length; i++)	s[i].value = "";
		}
	}
	function	Disp(cName){
	//показать элементы с указанным классом
		var j,
			arr = document.getElementsByClassName(cName),
			la = arr.length;
		for(j = 0; j < la; j++)	arr[j].className = cName;
	}
	// основное тело функции SprChanged
	if(sel.id=='etype')	//справочник типа объекта
		switch(sel.options[sel.selectedIndex].value){
			case 'К':	case 'М':
				NoDisp("Дом");	NoDisp("ДомЧасть"); NoDisp("Часть");	
				Disp("Квартира");	
				break;
			case 'Д':
				NoDisp("Часть");NoDisp("Квартира");
				Disp("Дом");	Disp("ДомЧасть");
				break;
			case 'Ч':
				NoDisp("Дом");	NoDisp("Квартира");
				Disp("Часть");	Disp("ДомЧасть");
				break;
			default:
				NoDisp("Дом");	NoDisp("ДомЧасть"); NoDisp("Часть");
				NoDisp("Квартира");
		}	//switch, if(sel=='etype')
<?php	if($_SESSION['UserRights'] & 2){	?>
	Rasschet();
<?php	}	?>
}	//function SprChanged

function	Init(){
// инициализация страницы
	correctA();
	Set_EnterEqTab();
	with(getObj("S")) value=NumPositiveStrSpace(value,2);
	with(getObj("Skuh")) value=NumPositiveStrSpace(value,2);
	with(getObj("Svanna")) value=NumPositiveStrSpace(value,2);
	with(getObj("Sbalkon")) value=NumPositiveStrSpace(value,2);
	with(getObj("Spodval")) value=NumPositiveStrSpace(value,2);
	with(getObj("Sstroen")) value=NumPositiveStrSpace(value,2);
	with(getObj("price")) value=NumPositiveStrSpace(value);
	with(getObj("Szemlia")) value=NumPositiveStrSpace(value,2);
	if(getObj("RealPrice"))with(getObj("RealPrice"))value=NumPositiveStrSpace(value);
	getObj("address").onkeydown = setAddress;
<?php
			for($j = 1; $j < 9; $j++)
				if($imgArr[$j])	echo "drawImg(getObj('c$j'),'$imgArr[$j]');";
			if($_SESSION['UserRights'] & 2){	?>
	FormListSpr(); Rasschet();
<?php	}
			if($ResultMsg){ ?>
	getObj("ResMsg").style.display="";
	setTimeout(CloseResMsg,1000);
<?php	}	?>
	window.IsAddress = (getObj("address").value.length > 0);
}	//function	Init
function prepSbmtImg(cnv_num){
//подготовка отправки формой рисунка по номеру (канвы)
	var img = getObj("img"+cnv_num), cnv = getObj("c"+cnv_num);
	switch(cnv.getAttribute("state")){
		case 'empty':
			with(img){ value = ""; name = id; }
			break;
		case 'new':
			with(img){ value = cnv.toDataURL(); name = id; }
			break;
		case 'db':
	}
}	//function prepSbmtImg
function	 	doSubmit(No){
//провести отправку формы
	var inps = document.getElementsByTagName("INPUT"), L = inps.length, j, v, cb = 0, msg="";
	for(j=0; j<L; j++)
		switch(inps[j].getAttribute('type')){
			case "hidden":
				break;
			case "checkbox":
				if(inps[j].checked) cb++;
				break;
			default:
				v = inps[j].value.trim();
				switch(inps[j].id){
					case "etype":
						if(!v.trim()) msg += "\nНе выбран тип недвижимости.";
						break;
					case "price":
						v = toNum(v);
						if(isNaN(v) || v < 1) msg += "\nВведена неверная стоимость.";
						break;
					case "address":
						if(!v) msg += "\nНе введен адрес объекта.";
						else if(!IsAddress) msg += "\nНе введен правильно, либо не выбран из подсказок адрес объекта.";
						break;
					case "region":
						if(!v) msg += "\nНе введен район расположения объекта.";
						break;
					case "S":
						v = toNum(v, 2);
						if(isNaN(v) || v < 0.1) msg += "\nВведена неверная общая площадь.";
						break;
					default:
						if(inps[j].id.substr(0,1)=="S"){	// "Skuh", "Svanna", "Sbalkon", "Spodval", "Szemlia", "Sstroen"
							if(!v.trim()) break;	
							v = toNum(v, 2);
							if(isNaN(v)) msg += "\nВведена неверная площадь.";
						}else{								// "komnat", "etajej", case "etaj", "sovlad"
							if(!v.trim()) break;	
							v = toNum(v);
							if(isNaN(v)) msg += "\nВведено неверное числовое значение.";
						}
				} //switch
		} //switch,for

	if(msg)	alert("Устраните недостатки ввода информации:\n"+msg);
	else{
		if(!cb){
			getObj('sell').value = 'П';
			getObj('rent').value = 'А';
		}
		prepSbmtImg(1);		prepSbmtImg(2);		prepSbmtImg(3);		prepSbmtImg(4);
		prepSbmtImg(5);		prepSbmtImg(6);		prepSbmtImg(7);		prepSbmtImg(8);
		for(L = cb = 0, j = 1; j < 9; j++){
			with(getObj("img"+j))	if(name){	L += value.length;	 cb++ ; }
			if(L > <?=$MaxLenImages?>){
				alert("Суммарный размер загружаемых фотографий велик.\nБудут загружены только первые "+(cb-1)+"\nВы можете добавить ещё, отредактировав снова.");
				for(; j < 9; j++)	 getObj("img"+j).name = "";
				break;
			}
		}
<?php	if($xoz){	?>
		inps = getObj("RealPrice");
		v = inps.value.toString().trim().replace(/ /g,'');
		v = toNum(v);
		if(isNaN(v)) v = 0;
		if((v < 1) && !No){
			if(!confirm("Вы не ввели реальную оценку стоимости.\nОпубликовать автоматическую (ОК)?\nИли Вы всё же введёте (ОТМЕНА)?")) return;
			inps.value = getObj("AutoPrice").value.toString().replace(/ /g,'');
		}
<?php	}	?>
		getObj('frm').submit();
	}
}	//function	 doSubmit
</script>

<script src="../js/min.jquery.js?v3"></script>
<script src="../js/ahunter.js?v10"></script>
<script src="../js/ahunter_suggest.js?v3.2"></script>

<script>
var AhunterVersion = "4.7.3";
//готовим опции модуля подсказок адреса
var options = {
		id : 'address',		// на каком поле работает
		ahunter_url : 'https://ahunter.ru/',	// куда запрос
		user : "demotoken",	// имя пользователя в ahunter
		on_choose : function( Suggestion ){	runGeoCoder(); }	 // вызвать при выборе подсказки
};
//запускаем модуль подсказок адреса
AhunterSuggest.Address.Solid( options );

var WinMap = null;	// отдельное окно в браузере с картой
function	 runGeoCoder(){
//вызов карты
	if(WinMap && !WinMap.closed) WinMap.close();
	IsAddress = true;
	var wdt = Math.floor(window.screen.width / 3), hgt = window.screen.height - 100;
	WinMap = window.open("view-map.php", "MyMap", "top=50,left=0,width="+wdt+",height="+hgt);
}	//function	runGeoCoder
function	 setAddress(e){
//получить строку из поля ввода адреса (из подсказки) и вызвать карту
	if((e.keyCode || e.which) == 13){
		var adr = document.getElementsByClassName("u-AhunterSuggestionMainValue")[0];
		if(adr){
			e.target.value = adr.innerHTML.replace(/\<strong\>/g,'').replace(/\<\/strong\>/g,'');
<?php	if($isYandex){ ?>
			runGeoCoder();
<?php	}else{ ?>
			IsAddress = true;
<?php	} ?>
		}
		document.getElementsByClassName("u-AhunterSuggestions").style.display = "none";
		getObj("region").focus();
	}else
		IsAddress = false;
}	//function	setAddress
</script>
</html>
