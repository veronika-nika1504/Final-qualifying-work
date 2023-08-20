/*	Общие функции - для каждой (ну почти) страницы
	Должен подключаться типа <script src="/MyApp/Diplom2021/js/public.js"></script>
*/
function    getObj(id){return document.getElementById(id);}
//-----------------------
function    isDate(str_d){
	//проверка, является ли параметр датой ДД.ММ.ГГГГ
	var ok = (/\d\d\.\d\d\.\d\d\d\d/).test(str_d);
	if(ok){
            var d, m, y;
            var dt = new Date(y=parseInt(str_d.substr(6,4),10), (m=parseInt(str_d.substr(3,2),10))-1, d=parseInt(str_d.substr(0,2),10));
            ok = !isNaN(dt);
            if(ok) ok = ((d==dt.getDate()) && (m-1==dt.getMonth()) && (y==dt.getFullYear()));
        }
	return ok;
}
//-----------------------
function    Set_EnterEqTab(){
//	ДЛЯ ПЕРЕХОДА ПО ENTER КАК ПО TAB
	window.inputs = document.getElementsByTagName('input');	//все input
	window.inputsLength = inputs.length;		// их кол-во
	window.buttonFirst = document.getElementsByTagName('button')[0];	// первая кнопка
    function    getNextInput(inp){
	// возвращает следующий input, а на последнем - первую кнопку
	var j;
	for(j = 0; j < inputsLength; j++) if(inputs[j] == inp) break;
	j++;
	return (j==inputsLength) ? buttonFirst : inputs[j];
    }
    function    EnterEqTab(e) {
	// на input'е при нажатии на enter переводит фокус на следующий input (на последнем - на кнопку)
	if((e.keyCode || e.which) == 13){
	    e.preventDefault();
	    getNextInput(e.target).focus();
        }
    }
	// установка обработчика на input'ы
	for(var j = 0; j < inputsLength; j++)	 inputs[j].onkeydown = EnterEqTab;
}
//-----------------------
function    homeUrl(){
    //Ищем расположение данного файла, чтобы верно указать расположение нужной страницы
    var i, j, src = "",
	S = document.getElementsByTagName("script"),   L = S.length;
    for(j=0; j<L; j++){
	src = S[j].src;
	i = src.indexOf("/js/public.js?x=");
	if(i>0){ src = src.substr(0,i+1); break; }
    }
    // src содержит адрес этого файла - типа	http://localhost/myapp/Diplom2021/js/public.js?x=324567...
    return src;	// возвращает			http://localhost/myapp/Diplom2021/
}
//-----------------------
function    correctA(){
	// Скрывает адреса в ссылках, заменяя их на "/Diplom2021"
	// Вызов должен включаться в обработку onload окна или body, например <body onload="correctA()"> или window.onload=function(){correctA();}
	var tagA = document.getElementsByTagName("a"), L = tagA.length, j;
	for(j=0; j<L; j++)
		with(tagA[j]){
			if(! getAttribute("onclick"))	setAttribute("onclick", 'this.href="'+href+'";');
			href = homeUrl();
		}
}
//-----------------------
function    toLogin(){
	//открыть окно входа в систему
	//формируем параметры окна для расположения по центру
	var W = 700, H = 400, T = screen.height - H, L = screen.width - W, 
	    Params = " menubar=no toolbar=no location=no status=no resizable=no scrollbars=no";
	T = (T - (T % 2)) / 2;		L = (L - (L % 2)) / 2;
	Params = "top="+T+" left="+L+" width="+W+" height="+H+ Params;
	window.open (homeUrl()+"login/do.php", "Login", Params);
	return false;
}
//-----------------------
function    NumPositiveStrSpace(N,d){
	//возвращает в виде строки положительное число, разделённое по разрядам
	//если не число - возвращает пустую строку
	//при d>0 - число с 2-мя знаками после запятой, иначе - целое
	N = N.toString().replace(/ /g,'');
	N = N.replace('.',',');
	var drb = '', j = N.indexOf(',');
	if(j>0){
		if(!d) return '';
		else{
			drb = N.substr(j+1);	//дробная часть
			N = N.substr(0,j);		//целая часть
		}
	}
	while(N.length && N.substr(0,1)=='0')	N = N.substr(1);
	var nmb = parseInt(N);
	if(isNaN(nmb) || !nmb || /[^\d]/.test(N)) return '';
	N = N.replace(/(\d)(?=(\d{3})+$)/g, '$1 ');
	if(d){
		if(!drb)
			drb='00';
		else{
			if(drb.length==1) drb = drb+'0';
			nmb = parseInt(drb);
			if(isNaN(nmb) || /[^\d]/.test(drb)) return '';
		}
		N = N+','+drb;
	}
	return N;
}

function    getAbsoluteParams(obj){
    //возвращает объект с абсолютными координатами, высотой и шириной 
    var t, l, w, h;
    with(obj){
	w = offsetWidth;    h = offsetHeight;
	t = offsetTop;      l = offsetLeft;
    }
    while(obj=obj.offsetParent)	with(obj){  t += offsetTop - scrollTop;	l += offsetLeft - scrollLeft;	}
    return { top:t, left:l, height:h, width:w }
}
function    getAbsoluteTop(obj){
    //возвращает абсолютную координату верха
    var t = obj.offsetTop;
    while(obj=obj.offsetParent)	with(obj)	t += offsetTop - scrollTop;
    return t;
}
function    getAbsoluteLeft(obj){
    //возвращает абсолютную левую координату
    var l = obj.offsetLeft;
    while(obj=obj.offsetParent)	with(obj)	l += offsetLeft - scrollLeft;
    return l;
}
function getClassByName(className) {
    //возвращает объект класса, заданный именем
    //применяем так: getClassByName('tr:hover').style.backgroundColor = "blue";
    //позаимствовано с ресурса https://ru.stackoverflow.com/questions/633429/Как-изменить-свойство-класса-с-помощью-js
    for(var ssNum in document.styleSheets)
        for(var ruleNum in document.styleSheets[ssNum].cssRules)
            if(document.styleSheets[ssNum].cssRules[ruleNum].selectorText)
                if(document.styleSheets[ssNum].cssRules[ruleNum].selectorText.indexOf(className) == 0)
                    return document.styleSheets[ssNum].cssRules[ruleNum];
}