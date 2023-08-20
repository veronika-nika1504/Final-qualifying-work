<?php	//BeginPage.php - НАЧАЛЬНОЕ ОФОРМЛЕНИЕ ПОЧТИ ВСЕХ СТРАНИЦ
	header('Content-Type: text/html; charset=utf-8');
	$IncDir = str_replace('/', '\\', dirname(__FILE__));	// полный маршрут папки inc в файловой системе: D:\xampp\htdocs\MyApp\Diplom2021\inc
	$AppDir = substr($IncDir, 0, strrpos($IncDir, '\\')+1);	// полный маршрут корневой папки приложения: D:\xampp\htdocs\MyApp\Diplom2021\
	$AppHttp = str_replace(str_replace("/", "\\", $_SERVER["DOCUMENT_ROOT"]), 'http://'.$_SERVER["HTTP_HOST"], $AppDir);
	$AppHttp = str_replace("\\", "/", $AppHttp);			// корневой http-адрес приложения: http://localhost/MyApp/Diplom2021/
	$IncDir .= '\\';										// добавить конечный слэш, получим: D:\xampp\htdocs\MyApp\Diplom2021\inc\
	$AppName = $IncDir . "rielter.ini";		// полный маршрут ini-файла: D:\xampp\htdocs\MyApp\Diplom2021\inc\rielter.ini
	if(PHP_OS <> 'WINNT'){	//если сервер на линуксе исправляем в маршрутах слэш "\" на "/"
		$IncDir = str_replace('\\','/',$IncDir);
		$AppDir = str_replace('\\','/',$AppDir);
	}
	$AppFirm = "Недвижимость";
	$BeginPage = 
'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
'<html xmlns="http://www.w3.org/1999/xhtml" lang="ru-RU">'.
'<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'.
'<meta http-equiv="content-language" content="ru" />'.
'<meta name="description" content="Недвижимость: оценка, купля, продажа, риэлтор" />'.
'<meta name="keywords" content="Недвижимость: оценка, купля, продажа, риэлтор" />'.
'<meta name="robots" content="index,follow" />'.
'<link rel="shortcut icon" href="'.$AppHttp.'img/favicon.png">'.
'<link rel="stylesheet" href="'.$AppHttp.'css/top-menu.css">'.
'<script src="'.$AppHttp.'js/public.js?x='.microtime(true).'"></script>';

function	 SayErrorAndExit($ErrMsg, $style="font-size:36pt;color:red;text-align:center"){
	//Вывод сообщение и завершение
	GLOBAL $BeginPage, $AppFirm;
	die($BeginPage . '<style>.Error{'.$style.'}</style><title>'.$AppFirm.'</title></head><body><div class="Error">'.$ErrMsg.'</div></body></html>');
} //SayErrorAndExit
function	 EasyQuery($link, $command, $dop=""){
	//	выполняет команду MySQL, возвращает 1 строку результата
	$Result = mysqli_query($link, $command);
	if(! $Result || ! mysqli_field_count($link))
		SayErrorAndExit($dop.'При выполнении запроса произошла ошибка:<br>'.$command.'<br>'.mysqli_error($link));
	return mysqli_fetch_array($Result);
} //EasyQuery
function	 EasyQueryNoResult($link, $command){
	//	выполняет команду MySQL, которая не возвращает результата
	$Result = mysqli_query($link, $command);
	if(! $Result )
		SayErrorAndExit('При выполнении запроса произошла ошибка:<br>'.$command.'<br>'.mysqli_error($link));
} //EasyQueryNoResult
function	QuerySelect($link, $command, $dop=""){
	//	выполняет команду MySQL, проводя проверку на отсутствие ошибки
	$Result = mysqli_query($link, $command);
	if(! $Result || ! mysqli_field_count($link))
		SayErrorAndExit($dop.'При выполнении запроса произошла ошибка:<br>'.$command.'<br>'.mysqli_error($link));
	return $Result;
} //QuerySelect
	if(! file_exists($AppName))
		SayErrorAndExit('<br><br>Приложение не настроено,<br>нужно выполнить установку.<br><br>Загляните в руководство.');

	include_once($AppName);
	session_start();
	$conn = @mysqli_connect($AppAddr, $AppUsr, $AppPsw, $AppDB);
	if(! $conn)
		SayErrorAndExit('<br><br>При попытке подключения к MySQL-серверу произошла ошибка:<br>' . mysqli_connect_error());
	mysqli_set_charset($conn, 'utf8');
	$UserLogin = isset($_SESSION['UserLogin']) ? $_SESSION['UserLogin'] : "";
	$UserRights = isset($_SESSION['UserRights']) ? $_SESSION['UserRights'] : 0;

function	AssertLogon($ok=true){
	//ПРОВЕРКА ЗАКОННОСТИ ПОДКЛЮЧЕНИЯ
	GLOBAL $AppName, $AppHttp;
	if((!isset($_SESSION['AppName'])) || ($_SESSION['AppName']<>$AppName) || (!isset($_SESSION['UserID'])) || !$ok){	// проверяем наша ли система? нет, какое-то безобразие!
		session_destroy();
		header('Location: '.$AppHttp."404.html");	//  http://localhost/myapp/Diplom2021/404.html
		exit;
	}
} //AssertLogon

function	 SayMenu($zag=""){
	//Показать меню, добавив заголовок и дополнительные пункты меню
	//Временная страница-заглушка для ещё нереализованного:  tst/t.php 
	GLOBAL $AppFirm, $UserLogin, $AppHttp, $UserRights;
	if($zag)	 $zag = ": ".$zag;	?>
<div id="ParentZag">	<a href="<?=$AppHttp?>"><div id="firmName"><?=$AppFirm . $zag?></div></a></div>
<?php	if($UserLogin){	 ?>
<a id="ToQuit" href="#" onclick="getObj('ToQuit2').style.display='';return false;" title="Выход из аккаунта"><?=$UserLogin?></a>
<a id="ToQuit2" href="<?=$AppHttp?>logout.php" style="display:none" onblur="this.style.display='none';" onmouseout="this.style.display='none';" title="Кликните для выхода">Точно выходим?</a>
<?php	}else{	?>
<a id="goto-login" href="#" title="Войти!" onclick="return toLogin();"></a>
<?php	}	?>
<div id="top-menu">
	<ul>
		<li>
			<ul>
				<li><a id="ToGlavnaja" href="<?=$AppHttp?>"></a></li>
				<li><a id="ToSearch" href="<?=$AppHttp?>search.php"></a></li>
				<li><a id="help" href="<?=$AppHttp?>help.php"></a></li>
				<li>
					<ul>
						<li><a href="<?=$AppHttp?>coord.php">Как связаться</a></li>
						<li><a href="<?=$AppHttp?>employes.php">Сотрудники</a></li>
						<li><a href="<?=$AppHttp?>prisoed.php">Работа у нас</a></li>
					</ul>
					<a href="#" id="onas"><div></div></a>
				</li>
<?php	if($UserLogin){
			// страница сейчас:
			$scn = $_SERVER['SCRIPT_NAME'];
			$p = strrpos($scn, '\\');	if(!$p) $p = strrpos($scn, '/');
			$scn = strtolower(substr($scn,$p+1));
			switch($scn){
				case "prisoed.php":
				case "index.php":
				case "help.php":
				case "coord.php":
				case "employes.php":
				case "search.php":
				case "one-object.php":
					echo '<li><a id="goto-lk"  href="'.$AppHttp.'l-k/lk.php"></a></li>';
					break;
				default:
					if($scn<>"lk.php" || $scn=="lk.php" && $_SESSION['UserID']<>(isset($_POST['editID'])?$_POST['editID']:$_SESSION['UserID']))
						echo'<li><a id="goto-lk" href="'.$AppHttp.'l-k/lk.php"></a></li>';
					if((0+$UserRights) & 4 && $scn<>"logview.php")
						echo'<li><a id="goto-logview" href="'.$AppHttp.'admin/LogView.php"></a></li>';
					if(($UserRights & 2) && $scn<>"loginview.php")
						echo'<li><a id="goto-users" href="'.$AppHttp.'admin/LoginView.php"></a></li>';
					if($UserRights == 0xFFFFFFFF){	//админу подменю настроек
						echo'<li><ul>';
						if($UserRights == 0xFFFFFFFF && $scn<>"setcostregion.php")
							echo'<li><a id="cost-reg" href="'.$AppHttp.'admin/SetCostRegion.php"></a></li>';
						if($UserRights == 0xFFFFFFFF && $scn<>"setkoeff.php")
							echo'<li><a id="koeff" href="'.$AppHttp.'admin/SetKoeff.php"></a></li>';	
						echo'</ul><a id="settings" href="#"><div></div></a>';
					} ?>
				<li><ul>
<?php			if((0+$UserRights) & 8 && $scn<>"distributor.php"){	?>
						<li><a href="<?=$AppHttp?>operate/Distributor.php">Распределить</a></li>
<?php			}	?>
						<li><a href="<?=$AppHttp?>operate/Say-Objects.php">Мои объекты</a></li>
						<li><a href="<?=$AppHttp?>operate/Edit-Estate.php">Добавить объект</a></li>
					</ul>
					<a id="ToObjects" href="#"><div></div></a>
				</li>
<?php			if($UserRights == 0xFFFFFFFF && $scn<>"add-news.php")
						echo	'<li><a id="add-news" href="'.$AppHttp.'admin/add-news.php"></a></li>';	?>
<?php		}	//switch
		}	//if	?>
			</ul>
			<a href="" id="menu-png" onclick="return false"></a>
		</li>
	</ul>
</div>
<br>
<br>
<?php
} //SayMenu
define('BegImg', "data:image/png;base64,");	//начало рисунка, кодированного в base64
define('LenBegImg', 22);	//22=strlen(BegImg);
?>