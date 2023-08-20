<?php	/* do.php ВХОД В СИСТЕМУ */
	include("..\\inc\\BeginPage.php");	//если неинициализировано - сюда не приходим
	$rejim = isset($_POST['rejim'])		? $_POST['rejim'] : "";
	$uLogin = isset($_POST['youName'])	? $_POST['youName'] : "";
	$uPsw = isset($_POST['youPassw'])	? $_POST['youPassw'] : "";
	$msg = count($_POST) ? "Пользователь с указанным логином и паролем не зарегистрирован !" : "";
	if($uLogin && $uPsw){	//был ввод пользователем
		// проверить на допустимость
		$row = EasyQuery($conn, "SELECT uName, Uid, uRights, uState FROM logins WHERE uName='$uLogin' AND uPsw=md5('$uPsw')");
		if($row && $row["uState"]<>1){
			$msg = "Вам вход запрещен !";
			EasyQueryNoResult($conn, "CALL WriteLog($row[Uid],2,2,'$uLogin / $uPsw / $_SERVER[REMOTE_ADDR] / $_SERVER[HTTP_USER_AGENT], uState=$row[uState]')");
		}elseif($row && $row["Uid"]){	// всё совпало, вход !
			session_destroy();
			session_start();
			$_SESSION['AppName'] = $AppName;
			$_SESSION['UserLogin'] = $row['uName'];
			$_SESSION['UserID'] = $row['Uid'];
			$_SESSION['UserRights'] = $row['uRights'];

			EasyQueryNoResult($conn, "CALL WriteLog($row[Uid],2,0,'$row[Uid] / [пароль] / $_SERVER[REMOTE_ADDR] / $_SERVER[HTTP_USER_AGENT]')");
			if($rejim=='n')
				header('Location: '.$AppHttp."index.php");	//  http://localhost/MyApp/Diplom2021/index.php
			else
				echo "<html><head><script>".
							"function init(){".
								"var lk='".$AppHttp."l-k/lk.php', ol=window.opener;".
								"if(ol)with(ol.document.location)".
									"if(href==lk)href=lk;else reload(true);".
								"window.close();".
 							"}".
						"</script></head>".
						"<body onload='init()'></body></html>";						
			exit;
		}else
			EasyQueryNoResult($conn, "CALL WriteLog(0,2,1,'$uLogin / $uPsw / $_SERVER[REMOTE_ADDR] / $_SERVER[HTTP_USER_AGENT]')");

		
	}else{	// ещё не было ввода или был пустым
		session_destroy();
		session_start();
	}
	// ещё не было ввода или неидентифицирован
	echo $BeginPage;
?>
<title><?=$AppFirm?>: Авторизация</title>
<link rel="stylesheet" href="../css/main.css">
<style>
	body	{	background-color:lightblue }
	.hlp	{	font-size:9pt; font-style:italic }
</style>
<script src="../js/public.js"></script>
</head><body onload="Init()">
<form hidden id="frm" name="frm" method="post">	<input id="rejim" name="rejim" value="<?=$rejim?>" /></form>
<table width="600" align="center">
	<tr><td colspan="4">&nbsp;</td></tr>
	<tr><th colspan="4" style="font: bold italic 16pt 'Times New Roman'; color:red">А в т о р и з а ц и я</th></tr>
	<tr><td rowspan="4" valign="top"><img src="../img/Vhod.jpg" height="110"/></td><td colspan="3">&nbsp;</td></tr>
	<tr>
		<td align="right">Ваш &nbsp; логин:</td>
		<td><input id="youName" name="youName" value="<?=$uLogin?>" form="frm" autofocus /></td>
		<td class="hlp">Выбран Вами при регистрации</td>
	</tr>
	<tr>
		<td align="right">Ваш пароль:</td>
		<td><input type="password" id="youPassw" name="youPassw" value="<?=$uPsw?>" form="frm" /></td>
		<td class="hlp">Был выбран Вами ранее</td>
	</tr>
	<tr><td colspan="3">&nbsp;</td></tr>
	<tr><td colspan="4" align="center" id="msg"><?=$msg?>&nbsp;</td></tr>
	<tr><td colspan="4">&nbsp;</td></tr>
	<tr><th colspan="4"><button class="BtnBig" onclick="TestSbmt()">Войти !</button></th></tr>
	<tr><td colspan="4">&nbsp;</td></tr>
	<tr><td colspan="4" align="center"><button class="BtnSmall" onclick="ToReg();">регистрация</button></td></tr>
</table>
</body>
<script>
	getObj("rejim").value = window.opener ? "y" : "n";
function	 ToReg(){
	window.opener.name=window.opener.document.title;
	getObj("youPassw").name="";
	with(getObj("youName")){	 name = "editID";	value = "newUsr";	 }
	with(getObj("frm")){ action = "../l-k/lk.php";	target = window.opener.name;	submit();	}
}
function	 TestSbmt(){
	if(!getObj("youName").value || !getObj("youPassw").value)	getObj("msg").innerHTML = "Нужно ввести логин и пароль !";
	else getObj("frm").submit();
}
function	 Init(){
	correctA();
	Set_EnterEqTab();
}
window.onblur = function(){	if(window.opener) window.close(); };
</script>
</html>
