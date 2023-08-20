<?php	//SetKoeff.php - установка коэффициентов
		if( isset($_POST['sid']) ){
			//ОБРАБОТКА ajax-запроса
			session_id($_POST['sid']);
			session_start();
			header('Content-Type: text/plain; charset=utf-8');
			include("..\\inc\\rielter.ini");
			$wrk = isset($_POST['wrk']) ? $_POST['wrk'] : "";
			$idGuide = isset($_POST['bt']) ? $_POST['bt'] : "";	//ид.
			$ret = '';
			$conn = @mysqli_connect($AppAddr, $AppUsr, $AppPsw, $AppDB);
			if(! $conn)
				$ret = 'При попытке подключения к MySQL-серверу произошла ошибка: ' . mysqli_connect_error();
			else{
				switch($wrk){
					case "csh":	//сдвиг значения категории
						$x = $_POST["vshft"];
						$Result = mysqli_query($conn, $cmd = "SELECT A.srt, B.idGuide, B.srt bsrt FROM GuideCategory A ".
							"INNER JOIN GuideCategory B ON B.idCategory=A.idCategory AND B.srt=$x+A.srt WHERE A.idGuide=$idGuide");
						if(!$Result)
							$ret = 'При получении порядка значений категории произошла ошибка: '.$cmd.' '.mysqli_error($conn);
						else{
							$r = mysqli_fetch_array($Result);
							$aSrt = $r['srt'];	$ig2 = $r['idGuide']; $bSrt = $r['bsrt'];	
							$Result = mysqli_query($conn, $cmd = "UPDATE GuideCategory SET srt=IF(idGuide=$idGuide,$bSrt,$aSrt) WHERE idGuide IN($idGuide,$ig2)");
							if(!$Result)
								$ret = "При попытке изменения порядка значений категории произошла ошибка: ".$cmd.' '.mysqli_error($conn);
							else
								$ret = "ok;csh";
						}
						break;
					case "kfc":		//установка коэффициентов
						//что было ранее?
						$k = $_POST['k'];	//коэфф.
						$nm = isset($_POST['nm']) ? $_POST['nm'] : '';	//наимен.знач.характеристики
						$Result = mysqli_query($conn, $cmd = "SELECT CONCAT(L.Category,' - ',G.name) Posit, G.k " .
									"FROM ListCategory L INNER JOIN GuideCategory G ON G.idCategory=L.idCategory ".
									"WHERE idGuide=".$idGuide);					
						if(!$Result)
							$ret = 'При выполнении запроса произошла ошибка: '.$cmd.' '.mysqli_error($conn);
						else{
							$r = mysqli_fetch_array($Result);
							$rk = $r['k'];
							$upd = $log = "";
							if($rk<>$k){
								$log = ", [k: $rk ⇒ ".$k."]";
								$upd = ",k=$k";
							}
							if($nm){
								$log .= ", [характеристика категории переименована в «".$_POST['nm']."»]"; 
								$upd .= ",name='".$nm."'";
							}
							if($upd){
								$log = "Для позиции $idGuide (".$r['Posit']."):". substr($log,1);
								$upd = "UPDATE GuideCategory SET ".substr($upd,1)." WHERE idGuide=".$idGuide;
								$Result = mysqli_query($conn, $upd);
								if(!$Result)
									$ret = 'При выполнении запроса обновления произошла ошибка: '.$upd.' '.mysqli_error($conn);
								else{
									$Result = mysqli_query($conn, $cmd = "CALL WriteLog($_SESSION[UserID],10,0,'". $log ."')");
									if(!$Result)
										$ret = 'При регистрации в логе произошла ошибка: '.$cmd.' '.mysqli_error($conn);
								}
							}
						}
						if(!$ret)					
							$ret = "ok;kfc;".$idGuide.";".number_format($k,4,'.','').";".$nm;
						break;
					case "nct":		//новая категория характеристики
						$k   = $_POST['k'];		//коэфф.
						$nm  = $_POST['nm'];	//наимен. значения характеристики
						$cat = $_POST['cat'];	//категория
						$Result = mysqli_query($conn, $cmd = "SELECT L.idCategory, COUNT(1)+1 srt ".
							"FROM ListCategory L INNER JOIN GuideCategory G ON G.idCategory=L.idCategory WHERE L.Category='".$cat."'");
						if(!$Result)
							$ret = "При попытке получения идентификатора характеристики произошла ошибка: ".$cmd.' '.mysqli_error($conn);
						else{
							$r = mysqli_fetch_array($Result);
							$idc = $r['idCategory'];	$srt = $r['srt'];
							$cmd = "INSERT INTO GuideCategory(idCategory,name,k,srt,code)VALUES($idc,'".$nm."',$k,$srt,'".chr(64+$srt)."')";
							$Result = mysqli_query($conn, $cmd);
							if(!$Result)
								$ret = 'При выполнении запроса произошла ошибка: '.$cmd.' '.mysqli_error($conn);
							else{
								$idGuide = mysqli_insert_id($conn);
								$log = "В категорию «".$cat."» добавлена характеристика «".$nm."» (idGuide=$idGuide) с кодом «".chr(64+$srt)."» и k=".$k;
								$Result = mysqli_query($conn, $cmd = "CALL WriteLog($_SESSION[UserID],11,0,'". $log ."')");
								if(!$Result)
									$ret = 'При регистрации в логе произошла ошибка: '.$cmd.' '.mysqli_error($conn);
								else
									$ret = "ok;nct;".$idGuide.";".number_format($k,4,'.','').";".$nm;
							}
						}
						break;
					default:
						$ret = "Ошибка в параметрах ajax-запроса.";
				} //switch
			} //if
			echo $ret;
			exit;
		}	//ОБРАБОТКА ajax-запроса
		include("..\\inc\\BeginPage.php");
		AssertLogon($_SESSION['UserRights'] == 0xFFFFFFFF);
		echo $BeginPage;
?>
<title><?=$AppFirm?>: Коэффициенты</title>
<link rel="stylesheet" href="../css/main.css">
<style>
	tr {cursor: pointer}
	td	{text-align: center}
	tr:hover{background-color: #C5CBE5}
	tr:nth-child(1):hover {background-color: inherit; cursor:default;}
	input { position:absolute; text-align:center;}
	button {position:absolute;}
</style>
</head><body onload="correctA();" onkeydown="if(event.keyCode==27)breakEdit();">
<?php	SayMenu("коэффициенты");	?>
<table id="tkf" border="1" align="center">
	<tr><th>Категория</th>
		<th>Вариант</th>
		<th>Коэффициент</th>
	</tr>
<?php	$t = QuerySelect($conn, "SELECT L.srt CatSrt, L.type, G.srt ValSrt, G.idGuide, L.Category, G.name, G.k " .
								"FROM ListCategory L " .
								"INNER JOIN GuideCategory G ON G.idCategory=L.idCategory " .
								"WHERE L.fname<>'S' AND L.fname<>'Szemlia' " .
								"ORDER BY L.srt, G.srt");
		$td1 = $part = $ctype = "";
		while($r=mysqli_fetch_array($t)){
			$cat = $r['Category'];
			if($cat<>$td1){
				if($td1)
					echo "<tr onclick='beginEdit(this)' onmouseover='mover(this)' onmouseout='mout(this)'><td".(($rowspan==1) ? '' : " rowspan='$rowspan'")." ctype='$ctype'>".$td1.$part;
				$rowspan = 1;
				$td1 = $cat;
				$ctype = $r['type'];
				$part = "</td><td>".$r['name']."</td><td id='$r[idGuide]'>$r[k]</td></tr>";
			}else{
				$rowspan++;
				$part .= "<tr onclick='beginEdit(this)' onmouseover='mover(this)' onmouseout='mout(this)'><td>".$r['name']."</td><td id='$r[idGuide]'>$r[k]</td></tr>";
			}
		}
		echo "<tr onclick='beginEdit(this)' onmouseover='mover(this)' onmouseout='mout(this)'><td".(($rowspan==1) ? '' : " rowspan='$rowspan'")." ctype='$ctype'>".$td1.$part;
?>
</table>
<input id="nm" style="display:none" onchange="test(this)" />
<input id="k" style="display:none" onchange="test(this)" />
<button id="bt" style="display:none;" title="Сохранить" onclick="saveValue(this)">ok</button>
<button id="vUp" style="display:none;" title="Значение вверх" onclick="valSrt(-1)">&Uparrow;</button>
<button id="vDn" style="display:none;" title="Значение вниз" onclick="valSrt(1)">&Downarrow;</button>
<button id="vAd" style="display:none;font-size:18px;" title="Добавить значение" onclick="valAdd()"><div style="transform: translateY(-2px)">+</div></button>
</body>
<script src="../js/min.jquery.js"></script>
<script>
	var trHoverCSS = getClassByName("tr:hover").style,
		nm = getObj("nm"),
		k  = getObj("k"),
		bt = getObj("bt"),
		vUp= getObj("vUp"),
		vDn= getObj("vDn"),
		vAd= getObj("vAd"),
		ed = isnow = false;
function	mover(tr){
	if(ed)return; 
	if(tr.cells.length==2){
		do tr = tr.previousElementSibling;
		while(tr.cells.length==2);
		tr.cells[0].style.backgroundColor="#C5CBE5";
	}
}
function	mout(tr){
	if(ed)return; 
	if(tr.cells.length==2){
		do tr = tr.previousElementSibling;
		while(tr.cells.length==2);
		tr.cells[0].style.backgroundColor="inherit";
	}	
}
function test(obj){
	var v;
	with(obj){
		if(id=='k'){
			v = parseFloat(value);
			if(isNaN(v)) value = "";
		}else{	//if(id=='nm')
			v = getAttribute('old');
			if(readonly || value=='-не указано-')
				value = v;
		}
	}
}
function initInpFromCell(Inp, Cell){
	var pos = getAbsoluteParams(Cell);
	Inp.value = Cell.innerHTML;
	with(Inp.style){
		top = pos.top +"px";
		left = pos.left +"px";
		width = (pos.width-8) +"px";
		display = "";
	}
	if(Inp==nm)
		with(Inp){
			setAttribute("old",value);
			readonly = (value=='-не указано-');
		}
}
function	getCtype(tr){
	while(tr.cells.length==2) tr = tr.previousElementSibling;
	return tr.cells[0].getAttribute("ctype");
}
function	beginEdit(tr){	//показать элементы редактирования
	var dispNone = false;
	if(isnow){
		if(tr.rowIndex==isnow.Last) dispNone = true;
		else{
			with(tr.parentNode.parentNode){
				deleteRow(isnow.Last);
				with(rows[isnow.First].cells[0])
					setAttribute("rowspan",parseInt(getAttribute("rowspan"))-1);
			} //with
			isnow = false;
		}
	}
	mout(tr);
	var Atd = tr.cells;
	ed = true;
    trHoverCSS.backgroundColor = "inherit";
	initInpFromCell(nm,Atd[Atd.length-2]);	
	initInpFromCell(k, Atd[Atd.length-1]);
	var Hpx = k.offsetHeight +"px", Tpx = k.style.top, L;
	function	sayBtn(btn, L){with(btn.style){height=Hpx; top=Tpx; left=L+"px"; display="";}}
	sayBtn(bt, L = getAbsoluteLeft(k) + k.offsetWidth + 4);
	bt.setAttribute("code",	Atd[Atd.length-1].id);
	if(dispNone){
		vUp.style.display = vDn.style.display = vAd.style.display = "none";
		nm.select();
	}else{
		sayBtn(vUp, L += bt.offsetWidth);
		sayBtn(vDn, L += vUp.offsetWidth);
		if((getCtype(tr)=="I")) vAd.style.display = "none";
		else	sayBtn(vAd, L += vDn.offsetWidth);
		k.select();
	}
}
function	saveValue(btn){	//нажата кнопка "сохранить"
	//проверим верность
	switch(nm.value = nm.value.trim()){
		case "":
			alert("Наименование пустым быть не должно!"); return;
		case "новое значение":
			alert("Необходимо прежде отредактировать наименование «новое значение».");	return;
	}
	if(!k.value) k.value = "0.0000";
	//ajax-отправка
	if(nm.getAttribute("old")!=nm.value)
		if(!confirm("Вы уверены в правильном вводе наименования «"+nm.value+"» ?")) return;
	var vars;
	if(nm.getAttribute("old")==nm.value)
		vars = { sid:"<?=session_id()?>", wrk:"kfc", bt:bt.getAttribute("code"), k:k.value	};
	else if(isnow){
		vars = { sid:"<?=session_id()?>", wrk:"nct", bt:bt.getAttribute("code"), k:k.value, nm:nm.value, cat:getObj('tkf').rows[isnow.First].cells[0].innerHTML };
		isnow = false;
	}else
		vars = { sid:"<?=session_id()?>", wrk:"kfc", bt:bt.getAttribute("code"), k:k.value, nm:nm.value };
	$.post(window.location.href, vars, callBack, "text");
	nm.style.display = k.style.display = bt.style.display = vUp.style.display = vDn.style.display = vAd.style.display = "none";
	trHoverCSS.backgroundColor = "#C5CBE5";
	ed = false;
}
function callBack(data, textStatus){	//ответ ajax
/* в data получаем:
	 0  1  2  3  4
	ok;kfc;id;k;nm
	ok;nct;id;k;nm
	ok;csh
*/
	if(textStatus != "success")	
		data = "Статус: "+textStatus+"\n"+data;
	else{
		var arr = data.split(";");
		if(arr[0] != "ok"){	alert(data); return; }
		var td;
		switch(arr[1]){
			case "csh":					return;
			case "kfc":
				td = getObj(arr[2]);	break;
			case "nct":
				td = getObj("now");
				td.id = arr[2];			break;
			default:
				alert(data);
				return;
		} //switch
		td.innerHTML = arr[3];
		if(arr.length==5 && arr[4])
			td.previousElementSibling.innerHTML = arr[4];
	}
}
function	breakEdit(){	// отмена режима редактирования
	ed = false;
	nm.style.display = k.style.display = bt.style.display = vUp.style.display = vDn.style.display = vAd.style.display = "none";
	trHoverCSS.backgroundColor = '#C5CBE5';
	var e, qs = document.querySelectorAll(":hover");
	e = qs[qs.length-2];
	if(e.tagName=="TR") mover(e);
}
function	valSrt(x){		//изменить порядок сортировки сдвинув строку вниз(x==1) или вверх(x==-1)
	var tr = getObj(bt.getAttribute("code")).parentNode, cs = tr.cells, csl = cs.length;
	var j=0, tr2, cs2, csl2;
	if(x<0){ //вверх
		if(csl==3) return;
		tr2 = tr.previousElementSibling, cs2 = tr2.cells, csl2 = cs2.length; //это верхняя
		if(csl2==3) j++; 
		tr2.appendChild(cs[0]); tr2.appendChild(cs[0]);
		tr.appendChild(cs2[j]); tr.appendChild(cs2[j]);
		nm.style.top = k.style.top = bt.style.top = vUp.style.top = vDn.style.top = vAd.style.top = getAbsoluteTop(tr2) + "px";
	}else{ //вниз
		tr2 = tr.nextElementSibling;
		if(!tr2) return;	//последний
		cs2 = tr2.cells, csl2 = cs2.length;
		if(csl2==3) return; //это уже другая категория
		if(csl==3) j++;
		tr.append(cs2[0]); tr.append(cs2[0]);
		tr2.appendChild(cs[j]); tr2.appendChild(cs[j]);
		nm.style.top = k.style.top = bt.style.top = vUp.style.top = vDn.style.top = vAd.style.top = getAbsoluteTop(tr2) + "px";
	}
	//ajax-отправка
	$.post(window.location.href, { sid:"<?=session_id()?>", wrk:"csh", bt:bt.getAttribute("code"), vshft:x },  callBack, "text");
}
function	valAdd(){	//нажата кнопка <+>
	var tr = getObj(bt.getAttribute("code")).parentNode,
		tr2 = tr.nextElementSibling;
	while(tr.cells.length==2) tr = tr.previousElementSibling;
	while(tr2 && tr2.cells.length==2) tr2 = tr2.nextElementSibling;
	if(tr2=='undefined') tr2 = null;
	with(tr.cells[0]) setAttribute("rowspan",parseInt(getAttribute("rowspan"))+1);
	var ntr = document.createElement("tr");
	ntr.setAttribute('onclick','beginEdit(this)');
	ntr.setAttribute('onmouseover','mover(this)');
	ntr.setAttribute('onmouseout','mout(this)');
	ntr.innerHTML="<td>новое значение</td><td id='now'>0.0000</td>";
	ntr = tr.parentNode.insertBefore(ntr,tr2);	ntr.style.display="";
	isnow = { First:tr.rowIndex, Last:ntr.rowIndex };
	beginEdit(ntr);
	// сохранение не произошло
}
</script>
</html>
