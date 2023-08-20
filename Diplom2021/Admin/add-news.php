<?php	// Добавление новости - add-news.php
	include("..\\inc\\BeginPage.php");
	AssertLogon($_SESSION['UserRights'] == 0xFFFFFFFF);
	echo $BeginPage;
	$nmsg = "";
	$msg = isset($_POST["msg"]) ? $_POST["msg"] : "";	$msg = urldecode($msg);
	$imgb64 = isset($_POST["imgb64"]) ? $_POST["imgb64"] : "";
	$wrk = isset($_POST["wrk"]) ? $_POST["wrk"] : "";
	$d = '<span class="theDate">' . date("d.m.Y") . '<br></span>';
	
function	imageCompress($ib64){
	if(!$ib64) return '';	// вернуть пусто, раз было пусто
	$img = imagecreatefromstring(base64_decode(substr($ib64,1+strpos($ib64,','))));	//обрежем заголовочную часть (data:image/png;base64,) и декодируем из base64
	$w = imagesx($img);	$h = imagesy($img);		//получим ширину и высоту
	// устанавливаем новые размеры: 300 - ширина, пропорционально высота
	$new_width = 300;	$new_height = round($new_width * $h / $w);
	$imgNew = imagecreatetruecolor($new_width, $new_height);	//создаём ноаый пустой полноцветный рисунок
	// ресэмплирование:
	imagecopyresampled($imgNew, $img, 0, 0, 0, 0, $new_width, $new_height, $w, $h);
	imagedestroy($img);		// освободить ресурс
	ob_start();	imagepng($imgNew);	$img = ob_get_clean();	// захватить вывод в браузер сгенерированного PNG
	imagedestroy($imgNew);	// освободить ресурс
	return "data:image/png;base64,".base64_encode($img);	// вернуть готовый рисунок (строкой)
}	//imageCompress

	$imgNew = imageCompress($imgb64);

	if($msg && !$imgb64)		//нет картинки
		$nmsg = '<tr><td colspan="3">' . $d . $msg . '</td></tr>';
	else if(!$msg && $imgb64)	//нет текста
		$nmsg = '<tr><td colspan="3" align="center">' . $d . '<img src="' . $imgNew . '" style="max-width:300px" /></td></tr>';
	else if($msg && $imgb64)	//есть и то, и другое. пока картинка слева, а текст справа
		$nmsg = '<tr><td>' . $d . '<img src="' . $imgNew . '" style="max-width:300px" /></td><td colspan="2" class="PL">' . $msg . '</td></tr>';
	$res = $tested = false;
	switch($wrk){
		case 'tst':
			$tested = true;
			break;
		case 'pbl':		// публиковать
			$fn = "..\\news.php";
			$f = @fopen($fn, "r");
			if(!$f){	//нет файла, создавать нужно
				$golova = '<'.'?php include("inc\\BeginPage.php");echo $BeginPage; ?'.'>'.
	'<title><?=$AppFirm?>: Новости</title><link rel="stylesheet" href="css/main.css">'.
	'<style>'.
		'.news,caption{font:italic 16pt "Times New Roman";text-align:justify}'.
		'caption{font-weight:bold;text-align:center}'.
		'tr{vertical-align:top}'.
		'th{font-size:18pt;text-align:center}'.
		'.news td{font:italic 14pt "Times New Roman";text-align:justify}'.
		'.PL{padding-left:10px}'.
		'.PR{padding-right:10px}'.
		'p{text-indent:30px;margin:0px 0px 0px 0px;font:italic 14pt "Times New Roman";text-align:justify}'.
		'textarea{font:inherit;resize:none;width:99%}'.
		'.res{text-align:center;position:fixed;top:30px;height:50px;font-size:30px;color:magenta;width:50%;left:25%}'.
		'.theDate{font:normal normal 10pt Courier;color: blue}'.
	'</style></head><body onload="correctA();">'.
	'<'.'?php SayMenu(""); ?'.'>'."\r\n".'<table id="news" align="center" width="90%"><tr><th colspan="3">Линия новостей</th></tr>'."\r\n";
				$hvost = '<tr><td width="300"></td><td></td><td width="300"></td></tr></table></body></html>';
				$nmsg = '<tr><td colspan="2" class="PR">' . $d. $msg . '</td><td><img src="' . $imgNew . '" style="max-width:300px" /></td></tr>';
			}else{		//удачное открытие файла
				$golova = "";
				while(!feof($f)){
					$s = fgets($f);
					$golova .= $s;				
					if($s=='<table id="news" align="center" width="90%"><tr><th colspan="3">Линия новостей</th></tr>'."\r\n") break;
				}
				$hvost = fgets($f);	
/* Варианты первой строки:
<tr><td colspan="3">... нет картинки или она по центру без текста...</td></tr>
<tr><td colspan="2">... картинка справа ...</td></tr>
<tr><td><span class="theDate">01.01.2021</span><img /></td><td colspan="2">картинка слева...</td></tr>
*/
				if($msg && $imgb64 && substr($hvost,0,8)=="<tr><td>") // переставить картинку вправо
					$nmsg = '<tr><td colspan="2" class="PR">' . $d. $msg . '</td><td><img src="' . $imgNew . '" style="max-width:300px" /></td></tr>';
				while(!feof($f))	$hvost .= fgets($f);	//считали хвост определённой нами длины
				fclose($f);
			} //if
			$f = fopen($fn, "w");	//пересоздание файла заново
			fputs($f, $golova . $nmsg ."\r\n". $hvost);	// записали нашу строку и хвост
			fclose($f);				//закрыли
			$res = true;
		default:
			$nmsg = $msg = $wrk = $imgb64 = "";
	} //switch
?>
<title><?=$AppFirm?>: Добавление новости</title>
<link rel="stylesheet" href="../css/main.css">
<style>
	.news, caption { font: italic 16pt "Times New Roman"; text-align: justify }
	caption { font-weight: bold; text-align: center; }	
	tr { vertical-align: top }
	th { font-size: 18pt; text-align: center}
	.news td { font: italic 14pt "Times New Roman"; text-align: justify  }
	.PL { padding-left: 10px; }
	.PR { padding-right: 10px; }
	p {	text-indent:30px; margin: 0px 0px 0px 0px; font: italic 14pt "Times New Roman"; text-align: justify; }	 /* сверху справа снизу слева */
	textarea { font:inherit; resize: none; width:99%}
	.theDate { font: normal normal 10pt Courier; color: blue; }
	#res { position: fixed; font: italic bold 30pt "Times New Roman"; color: lightgreen; background-color: blue;
			top:30%; left:50%; margin-left: -160px;	border: solid 2px blue; border-radius: 80px; }
	#ris { max-width:100%; max-height:210px; }
	#load { height:140px; width:140px; border-radius:65px; font:normal italic 14pt 'Times New Roman'; cursor: pointer}
</style>
</head><body onload="correctA();">
<?php	SayMenu("");	?>
<input id="fn" type="file" accept="image/*" style="display:none" onchange="getImages(this)" />
<form method="post" id="frm" hidden><input hidden id="wrk"><input hidden id="imgb64"></form>
<table align="center">
	<caption>Добавление новости<br>&nbsp;</caption>
	<tr>
		<td width="50" align="center">Введите содержание новости c тегами. Абзац: &lt;p&gt;так&lt;/p&gt;: </td>
		<td width="400"><textarea id="msg" rows="12" form="frm" onchange="tested=false"><?=$msg?></textarea></td>
		<td width="50"><button id="load" title="Кликните для загрузки рисунка" onclick="getObj('fn').click()"><br>При необходимости загрузите картинку<br>&nbsp;</button></td>
		<td width="400" style="border: solid 1px black; height: 220px; text-align: center;"><img id="ris" <?=($imgb64 ? "src='$imgb64'" : "")?> /></td>
	</tr>
	<tr><td colspan="4" align="center">
		<hr><button class="BtnBig" onclick="doWork('tst')">Тестировать !</button>&nbsp;&nbsp;&nbsp;
		<button class="BtnBig" onclick="doClear()">Очистить !</button>&nbsp;&nbsp;&nbsp;
		<button class="BtnBig" onclick="doWork('pbl')">Публиковать !</button>&nbsp;&nbsp;&nbsp;
		<hr>
	</td></tr>
</table>

<table id="news" align="center" width="90%"><tr><th colspan="3">Линия новостей</th></tr>
<?=$nmsg?>
<tr><td width="300"></td><td></td><td width="300"></td></tr>
</table>
<div id="res" style="<?=($res?"":"display: none;")?>"><br>&nbsp;&nbsp;&nbsp;Опубликовано&nbsp;&nbsp;&nbsp;<br>&nbsp;</div>
</body>
<script>
var tested = <?=($tested ? "true": "false")?>;
function		doClear(){
	tested = false;
	var r = getObj('ris'), im = getObj('imgb64'), m = getObj('msg');
	m.value = im.value = "";
	r.removeAttribute("src");
	getObj('frm').submit();
}
function		getImages(fobj){	
// отработка получения одной фотки
	tested = false;
	var fr = new FileReader(); 
	fr.onload = function(){	getObj("ris").src = fr.result;	};
	fr.readAsDataURL(fobj.files[0]);
}
function		doWork(wrk){
	var r = getObj('ris'), im = getObj('imgb64'), m = getObj('msg');
	if(r.src) with(im){ value = r.src;	name = id; }
	with(m) if(value) name = id;
	if(r.src || m.value){
		with(getObj('wrk')){ value=wrk; name=id; }
		if(wrk=='pbl' && !tested)
			alert('Опубликовать можно только после тестирования!');
		else
			getObj('frm').submit();
	}else
		alert('Ввода не было.');
}
<?php	if($res){	?>
	setTimeout(function(){getObj("res").style.display="none";}, 3000);
<?php	}	?>
</script>
</html>
