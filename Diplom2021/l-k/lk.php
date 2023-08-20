<?php	//ЛИЧНЫЙ КАБИНЕТ - ЛИЧНЫЕ ДАННЫЕ ПОЛЬЗОВАТЕЛЯ
	include("..\\inc\\BeginPage.php");
	$sessID = isset($_SESSION['UserID'])? $_SESSION['UserID'] : 0;	// вошедший в систему пользователь
	$editID = isset($_POST['editID'])	? $_POST['editID'] : "";		// Uid пользователя, данные которого надо редактировать
	if($sessID){	// т.е. похоже, был вход в систему и это переход на редактирование себя
		AssertLogon();
		if($editID=="" || $editID==$sessID)	$WhatEdit = 1;	// редактирование своих собственных данных
		elseif($editID=='newUsr')			$WhatEdit = 0;	// регистрация администратором/сотрудником нового пользователя
		else								$WhatEdit = 2;	// зарегистрированный пользователь системы хочет редактировать другого пользователя
	}elseif($editID=='newUsr')				$WhatEdit = 0;	// это пришли по кнопке регистрации	
	else{
		session_destroy();
		header('Location: '.$AppHttp."404.html");	//  http://localhost/MyApp/curs2020/404.html
		exit;
	}
	
	if($sessID==1 && isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], "setup"))
		EasyQueryNoResult($conn, "CALL WriteLog($sessID,2,0,'автовход')");

	$TEST = "---";
	$Admin = $Kurat = false;	
	if($WhatEdit==2){	 	// выясняем собственные полномочия
		$row = EasyQuery($conn, "SELECT uRights FROM Logins WHERE Uid=$sessID");
		$Admin = ($row[0] == 0xFFFFFFFF);
	}
	if(!$Admin && $WhatEdit==2 && ($sessID & 2))	// проверить разрешённость редактирования данных пользователя $editID
		$Kurat = true;
	if($WhatEdit){	// не регистрация
		$row = EasyQuery($conn, "SELECT L.Uid, L.uName, L.regDate, L.regIP, R.uName regName, L.uState, L.uRights,".
										"L.fam, L.nam, L.otc, L.phone, L.eMail, L.address, L.wPhone, L.cabNo, L.wPlace, L.note ".
								"FROM Logins L LEFT JOIN Logins R ON R.Uid=L.regUid WHERE L.Uid=".(($WhatEdit==1)?$sessID:$editID));
  		if($WhatEdit==1)$Admin = ($row['uRights'] == 0xFFFFFFFF);
	}else{	// инициализация массива
		$row['Uid'] = $row['uName'] = $row['regDate'] = $row['regIP'] = $row['regName'] = $row['uState'] = "";
		$row['uRights'] = 0;
		$row['fam'] = $row['nam'] = $row['otc'] = "";
		$row['phone'] = $row['eMail'] = $row['address'] = $row['wPhone'] = $row['cabNo'] = $row['wPlace'] = $row['note'] = "";
	}

	$pst['uName']	= isset($_POST['uName'])	? $_POST['uName']	: $row['uName'];
	$pst['fam']		= isset($_POST['fam'])		? $_POST['fam']		: $row['fam'];
	$pst['nam']		= isset($_POST['nam'])		? $_POST['nam']		: $row['nam'];
	$pst['otc']		= isset($_POST['otc'])		? $_POST['otc']		: $row['otc'];
	$pst['phone']	= isset($_POST['phone'])	? $_POST['phone']	: $row['phone'];
	$pst['eMail']	= isset($_POST['eMail'])	? $_POST['eMail']	: $row['eMail'];
	$pst['address']	= isset($_POST['address'])	? $_POST['address']	: $row['address'];
	$pst['wPhone']	= isset($_POST['wPhone'])	? $_POST['wPhone']	: $row['wPhone'];
	$pst['cabNo']	= isset($_POST['cabNo'])	? $_POST['cabNo']	: $row['cabNo'];
	$pst['wPlace']	= isset($_POST['wPlace'])	? $_POST['wPlace']	: $row['wPlace'];
	$pst['note']	= isset($_POST['note'])		? $_POST['note']	: $row['note'];

	$ResultMsg = "";
	if(isset($_POST['uState']) && $_POST['uState']<>1 && ($row['uRights']==0xFFFFFFFF)){
		//задано "заблокировать/заархивировать" пользователя, который админ.
		//проверяем, а есть ли ещё админ, кроме этого, если нет - то запретить
		$rw2 = EasyQuery($conn, "SELECT COUNT(1) cnt FROM logins WHERE uRights=0xFFFFFFFF AND uState=1 AND Uid<>".(($WhatEdit==1)?$sessID:$editID));
		if($rw2[0])
			$pst['uState'] = $_POST['uState'];
		else{
			$pst['uState'] = $row['uState'];
			$ResultMsg = "Отказ блокировки последнего админа!<br><br>";
		}
	}else
		$pst['uState']	= isset($_POST['uState']) ? $_POST['uState'] : $row['uState'];

	if(isset($_POST['uRights']) && $_POST['uRights']<>0xFFFFFFFF && $row['uRights']==0xFFFFFFFF){
		//задано понижение прав администратора
		//проверяем, а есть ли ещё админ, кроме этого, если нет - то запретить
		$rw2 = EasyQuery($conn, "SELECT COUNT(1) cnt FROM logins WHERE uRights=0xFFFFFFFF AND Uid<>".(($WhatEdit==1)?$sessID:$editID));
		if($rw2[0])
			$pst['uRights'] = $_POST['uRights'];
		else{
			$pst['uRights']	= $row['uRights'];
			$ResultMsg = "Отказ понижения прав последнего админа!<br><br>";
		}		
	}else
		$pst['uRights']	= isset($_POST['uRights']) ? $_POST['uRights'] : $row['uRights'];
	
	// по возможности формируем команду UPDATE или обе части INSERT
	$InsFld = $InsVal = "";
	$rw2[0]=0;
	if($pst['uName']<>$row['uName']){	// проверка, нет ли такого в БД
		$rw2 = EasyQuery($conn, "SELECT COUNT(1) cnt FROM Logins WHERE uName='$pst[uName]'");
		if($rw2[0]<>0)		$ResultMsg = "Такой логин уже зарегистрирован !<br><br>Выберите другой !";
		else{
			$InsFld = ",uName";
			if($WhatEdit)	$InsFld .= "='$pst[uName]'";
			else			$InsVal = ",'$pst[uName]'";
		}
	}
	if(! $ResultMsg)		//ошибки не было
		if(isset($_POST['oldPsw'])){	//задан старый пароль, правильный ли?
			$rw2 = EasyQuery($conn, "SELECT COUNT(1) cnt FROM Logins WHERE Uid=". (($WhatEdit==1)?$sessID:$editID) . " AND md5('$_POST[oldPsw]')=uPsw");
			if($rw2[0]<>1){
				EasyQueryNoResult($conn, "CALL WriteLog($sessID,4,1,'$_POST[oldPsw]')");
				$ResultMsg = "Вы ввели неверный прежний пароль !<br><br>Сделайте новую попытку !";
			}
		}
	if(! $ResultMsg){	//ошибки не было
		if(isset($_POST['newPsw'])){
			$InsFld .= ",uPsw";
			if($WhatEdit)	$InsFld .= "='$_POST[newPsw]'";
			else			$InsVal .= ",'$_POST[newPsw]'";
		}
		if($pst['uState'] <> $row['uState']){
			$InsFld .= ",uState";
			if($WhatEdit)	$InsFld .= "=$pst[uState]";
			else			$InsVal .= ",$pst[uState]";
		}
		if($pst['uRights'] <> $row['uRights']){
			$InsFld .= ",uRights";
			if($WhatEdit)	$InsFld .= "=$pst[uRights]";
			else			$InsVal .= ",$pst[uRights]";
			if($WhatEdit==1){
				$UserRights = 0 + $pst['uRights'];
				$_SESSION['UserRights'] = $UserRights;
				$Admin = ($UserRights==0xFFFFFFFF);
			}
		}
		if($pst['fam'] <> $row['fam']){
			$InsFld .= ",fam";
			if($WhatEdit)	$InsFld .= "='$pst[fam]'";
			else			$InsVal .= ",'$pst[fam]'";
		}
		if($pst['nam'] <> $row['nam']){
			$InsFld .= ",nam";
			if($WhatEdit)	$InsFld .= "='$pst[nam]'";
			else			$InsVal .= ",'$pst[nam]'";
		}
		if($pst['otc'] <> $row['otc']){
			$InsFld .= ",otc";
			if($WhatEdit)	$InsFld .= "='$pst[otc]'";
			else			$InsVal .= ",'$pst[otc]'";
		}
		if($pst['phone'] <> $row['phone']){
			$InsFld .= ",phone";
			if($WhatEdit)	$InsFld .= "='$pst[phone]'";
			else			$InsVal .= ",'$pst[phone]'";
		}
		if($pst['eMail'] <> $row['eMail']){
			$InsFld .= ",eMail";
			if($WhatEdit)	$InsFld .= "='$pst[eMail]'";
			else			$InsVal .= ",'$pst[eMail]'";
		}
		if($pst['address'] <> $row['address']){
			$InsFld .= ",address";
			if($WhatEdit)	$InsFld .= "='$pst[address]'";
			else			$InsVal .= ",'$pst[address]'";
		}
		if($pst['wPhone'] <> $row['wPhone']){
			$InsFld .= ",wPhone";
			if($WhatEdit)	$InsFld .= "='$pst[wPhone]'";
			else			$InsVal .= ",'$pst[wPhone]'";
		}
		if($pst['cabNo'] <> $row['cabNo']){
			$InsFld .= ",cabNo";
			if($WhatEdit)	$InsFld .= "='$pst[cabNo]'";
			else			$InsVal .= ",'$pst[cabNo]'";
		}
		if($pst['wPlace'] <> $row['wPlace']){
			$InsFld .= ",wPlace";
			if($WhatEdit)	$InsFld .= "='$pst[wPlace]'";
			else			$InsVal .= ",'$pst[wPlace]'";
		}
		if($pst['note'] <> $row['note']){
			$InsFld .= ",note";
			if($WhatEdit)	$InsFld .= "='$pst[note]'";
			else			$InsVal .= ",'$pst[note]'";
		}
		if($InsFld)	{ //что-то заполнено, добавляем - кто
			$InsFld .=	",lastUid";
			if($WhatEdit)	$InsFld .= "=$sessID";
			else			$InsVal .= ",$sessID";
		}
		if($InsFld && !$WhatEdit){	// новая регистрация и что-то заполнено
			$InsFld .=	",regIP";	$InsVal .= ",'$_SERVER[REMOTE_ADDR]'";	// IP
			$InsFld .=	",uRights";	$InsVal .= ",1";	// права=клиент
			$InsFld .=	",uState";	$InsVal .= ",1";	// состояние=активен
		}

		if($InsFld){	// ЗАПОЛНЕНО ЧТО-ТО ДЛЯ КОМАНДЫ SQL
			// завершаем формирование команды, обрезая 1-ый символ (,)
			if($WhatEdit)	// 1,2 - замена - UPDATE
				$InsFld = "UPDATE logins SET ". substr($InsFld,1) ." WHERE Uid=". (($WhatEdit==1)?$sessID:$editID)."; ";
			else					// 0 - новая регистрация - INSERT
				$InsFld = "INSERT INTO logins(". substr($InsFld,1) .")VALUES(". substr($InsVal,1) .");";
			// выполнить!
			EasyQueryNoResult($conn, $InsFld);
			$ResultMsg .= "Изменения внесены !";
			
			// если был insert, надо получить ID, сгенерированный автоматически /mysqli_insert_id($conn)/ и по нему обновить $row
			switch($WhatEdit){
				case 0:		// новая регистрация
					$i = mysqli_insert_id($conn);
					if($editID=="newUsr")	$editID = $i;
					if(!$sessID){
						$_SESSION['AppName'] = $AppName;
						$UserLogin = $_SESSION['UserLogin'] = $pst['uName'];
						$_SESSION['UserID'] = $sessID = $i;
						$_SESSION['UserRights'] = 1;
						$WhatEdit = 1;
						$editID = $i;
					}
					break;
				case 1:		//редактирую себя
					$i = $sessID;	
					break;
				case 2:		//редактирую другого
					$i = $editID;
					break;
			}//switch
			// обновить $row
			$row = EasyQuery($conn, "SELECT L.Uid, L.uName, L.regDate, L.regIP, R.uName regName, L.uState, L.uRights,".
											"L.fam, L.nam, L.otc, L.phone, L.eMail, L.address, L.wPhone, L.cabNo, L.wPlace, L.note ".
									"FROM Logins L LEFT JOIN Logins R ON R.Uid=L.regUid WHERE L.Uid=".$i, "Повторный. ");
		}//if($InsFld)
	}//if(! $ResultMsg)
	echo $BeginPage;
?>
<title><?=$AppFirm?>: Личный кабинет</title>
<link rel="stylesheet" href="../css/main.css">
<style>
<?php	if(!$WhatEdit){	// регистрация ?>
	body { background: rgba(245,245,220) url(../img/Пишу-ручкой.gif) no-repeat fixed center; background-size:contain; }
<?php	}	?>
	textarea{	width:99%; resize:none; border-width:1px	}
	select	{	width:99%; border:none }
	.cer	{ color:gray }
</style>
</head><body onload="Init()">
<?php	//
	switch($WhatEdit){
		case 0:	$zag = "р е г и с т р а ц и я";	break;
		case 1:	$zag = "ваш личный кабинет (личные данные)";	break;
		case 2:	$zag = "редактирование данных пользователя";	break;
		default:	$zag = "отладка: $WhatEdit";
	}
	SayMenu($zag);	?>

	<form hidden id="frm" method="post"><input id="editID" name="editID" form="frm" value="<?=$editID?>" /></form>
	<table align="center" cellpadding="0">
		<tr>
			<td><?=(($sessID && !$editID)?"Ваш ":"")?>логин:</td>
			<td><?=($WhatEdit ? "&nbsp;" : "&#9830;")?></td>
			<td><input id="uName" <?=(($WhatEdit && $editID<>"newUsr") ? "disabled" : "")?> form="frm" oldValue="<?=$row['uName']?>" value="<?=$pst['uName']?>" autofocus /></td>
<?php	if($WhatEdit){	?>
			<td colspan="2">Зарег.:</td>
			<td align="center"><?=$row['regDate']?></td>
			<td colspan="2" align="center"><?=$row['regIP']?></td>
			<td align="center"><?=($Admin?$row['regName']:"&nbsp;")?>
<?php	}else echo '<td colspan="6"></td>';	?>
		</tr>
<?php	if($Admin){	?>
		<tr>
			<td>Uid:</td>
			<td>&nbsp;</td>
			<td align="center"><?=$row['Uid']?>
			<td colspan="2">uState:</td>
			<td style="border:solid black 1px">
				<select id="uState" form="frm" oldValue="<?=$row['uState']?>">
					<option value="1" <?=(($pst['uState']==1)?"selected":"")?>>активна</option>
					<option value="2" <?=(($pst['uState']==2)?"selected":"")?>>блокирована</option>
					<option value="3" <?=(($pst['uState']==3)?"selected":"")?>>заархивирована</option>
				</select>
			</td>
			<td>&nbsp;</td>
			<td>Права:</td>
			<td align="center" style="border:solid black 1px">
				<select id="uRights" form="frm" oldValue="<?=$row['uRights']?>">
					<option value="1" <?=(($row['uRights']==1)?"selected":"")?>>клиент</option>
					<option value="2" <?=(($row['uRights']==2)?"selected":"")?>>сотрудник</option>
					<option value="10" <?=(($row['uRights']==10)?"selected":"")?>>распределитель</option>
					<option value="4294967295" <?=(($row['uRights']==4294967295)?"selected":"")?>>администратор</option>
				</select>
			</td>
		</tr>
<?php	}	?>
		<tr>
			<td>Фамилия:</td>
			<td>&#9830;</td>
			<td><input id="fam" form="frm" oldValue="<?=$row['fam']?>" value="<?=$pst['fam']?>" /></td>
			<td>Имя:</td>
			<td>&#9830;</td>
			<td><input id="nam" form="frm" oldValue="<?=$row['nam']?>" value="<?=$pst['nam']?>" /></td>
			<td colspan="2">Отчество:</td>
			<td><input id="otc" form="frm" oldValue="<?=$row['otc']?>" value="<?=$pst['otc']?>" /></td>
		</tr>
		<tr>
			<td>Телефон:</td>
			<td>&#9674;</td>
			<td><input id="phone" form="frm" oldValue="<?=$row['phone']?>" value="<?=$pst['phone']?>" /></td>
			<td>eMail:</td>
			<td>&#9674;</td>
			<td><input id="eMail" form="frm" oldValue="<?=$row['eMail']?>" value="<?=$pst['eMail']?>" /></td>
			<td colspan="2">Адрес:</td>
			<td><input id="address" form="frm" oldValue="<?=$row['address']?>" value="<?=$pst['address']?>" /></td>
		</tr>
<?php	if($WhatEdit){	?>
		<tr>
			<td>Раб. / тел:</td>
			<td>&nbsp;</td>
			<td><input id="wPhone" form="frm" oldValue="<?=$row['wPhone']?>" value="<?=$pst['wPhone']?>" /></td>
			<td colspan="2">Каб.№:</td>
			<td><input id="cabNo" form="frm" oldValue="<?=$row['cabNo']?>" value="<?=$pst['cabNo']?>" /></td>
			<td colspan="2">Работает:</td>
			<td><input id="wPlace" form="frm" oldValue="<?=$row['wPlace']?>" value="<?=$pst['wPlace']?>" /></td>
		</tr>
<?php		if($Admin || $Kurat){ ?>
		<tr>
			<td colspan="9"><textarea id="note" form="frm" placeholder="Характеристика пользователя" rows="5" oldValue="<?=$row['note']?>"><?=$pst['note']?></textarea></td>
		</tr>
<?php		}
		}	?>
		<tr><th colspan="9" valign="bottom" height="30">&nbsp;</th></tr>
		<tr>
<?php	if($WhatEdit==1){		?>	
			<td rowspan="3" colspan="5">Для смены пароля необходимо заполнить поля:</td>
			<td  align="right">Прежний пароль:</td>
			<td>&#10104;</td>
			<td colspan="2"><input id="oldPsw" form="frm" type="password" /></td>
		</tr>
		<tr>
<?php	}elseif(!$WhatEdit){	?>
			<td rowspan="2" colspan="5">Введите свой пароль дважды:</td>
<?php	}elseif($Admin || $Kurat){			?>
			<td rowspan="2" colspan="5">Введите новый пароль для пользователя дважды:</td>
<?php	}	?>
			<td align="right">Новый пароль:</td>
			<td><?=($WhatEdit ? "&#10102;" : "&#9830;")?></td>
			<td colspan="2"><input id="newPsw" form="frm" type="password" onkeyup="TestPsw()" title="От 5 до 20 символов" /></td>
		</tr>
		<tr>
			<td id="np2" align="right">Повтор нового:</td>
			<td><?=($WhatEdit ? "&#10103;" : "&#9830;")?></td>
			<td colspan="2"><input id="newPsw2" type="password" onkeyup="TestPsw()" title="От 5 до 20 символов" /></td>
		</tr>
		<tr><td colspan="9">&nbsp;</td></tr>
		<tr><th colspan="9"><button class="BtnBig" onclick="ClickBtn()">Сохранить</button></th></tr>
		<tr><td colspan="9">&nbsp;</td></tr>
		<tr>
			<td width="80">&nbsp;</td>
			<td width="9">&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td width="9">&nbsp;</td>
			<td>&nbsp;</td>
			<td width="9">&nbsp;</td>
			<td width="80">&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td></td>
			<td colspan="8" class="cer">Примечание 1: &#9830; помечены обязательные для заполнения поля.
				<br>Примечание 2: &#9674; помечены поля, одно из которых обязательно должно быть заполнено
<?php	if($WhatEdit){					// не регистрация ?>	
				<br>Примечание 3: если введено поле &#10102;, &#8211; поле &#10103; должно быть заполнено тем же значением
<?php	}
			if($WhatEdit==1){		// редактирование своего ?>	
				<br>Примечание 4: если введено поле &#10102;, &#8211; поле &#10104; должно быть также обязательно заполнено
<?php	}	?>
			</td>
		</tr>
		<tr><td colspan="9">&nbsp;</td></tr>
	</table>
</div>
<div id="ResMsg" class="notice" style="display:none" onclick="this.style.display='none'"><?=$ResultMsg?></div>
</body>
<script>
function	 Init(){
	correctA();
	Set_EnterEqTab();
<?php	if($ResultMsg){ ?>
	getObj("ResMsg").style.display="";
	setTimeout(CloseResMsg,1000);
<?php	}	?>
}
function	CloseResMsg(){getObj("ResMsg").style.display="none";}

function	 SetName(arr){
	var L = arr.length, cnt = 0, j;
	for(j = 0; j < L; j++)
		with(arr[j])
			if(!disabled)	if(value && (getAttribute("oldValue") != value)){ name = id;	cnt++; }
							else name = "";
	return cnt;
}
function	 ClickBtn(){
	var p = getObj("newPsw"), p2 = getObj("newPsw2"), msg = "";
<?php	if(!$WhatEdit){	// нужны обязательные поля	?>
	if(!getObj("uName").value)	msg += "\n- необходим Ваш логин";
	if(!getObj("fam").value)			msg += "\n- необходима Ваша фамилия";
	if(!getObj("nam").value)			msg += "\n- необходимо Ваше имя";
	if(!getObj("phone").value && !getObj("eMail").value)	msg += "\n- необходимо хотя бы одно из двух полей: телефон, адрес э/почты";
	if(getObj("eMail").value && ! /\S+@\S+\.\S+/.test(getObj("eMail").value)) msg += "\n - адрес э/почты введён неверно";
	if(!p.value)	msg += "\n- необходим ввод пароля";
	else if(p.value.length < 5)		msg += "\n- пароль должен быть не менее 5 символов";
	if(!p2.value)	msg += "\n- необходим повторный ввод пароля";
	if(msg){	alert("Не введены обязательные поля либо не верно введены:\n"+msg);	return;	}
<?php	}	?>
	if(p && p.value.length){
		if(p.value != p2.value){	alert("Неверный ввод:\n\nпароли не совпадают");	 return;	}
		p = getObj("oldPsw");
		if(p && ! p.value){	alert('Необходим ввод прежнего пароля'); return; }
	}
	p = SetName(document.getElementsByTagName("input"));
	p += SetName(document.getElementsByTagName("select"));
	p += SetName(document.getElementsByTagName("textarea"));
	if(p)	getObj("frm").submit();
	else	alert("Вы не сделали изменений!");
}
function	 TestPsw(){
	var p = getObj("newPsw"), p2=getObj("newPsw2"), x=getObj("np2");
	x.style.textDecoration=(p2.value==p.value)?"none":"line-through";
}
</script>
</html>
