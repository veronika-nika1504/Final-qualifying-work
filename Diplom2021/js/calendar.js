/* calendar.js
-----------------------------------
Используются имена объектов DOM:	tCalendar, forChangeYear, v1 - v42
Используютя функции из public.js:       getObj(), isDate(), getAbsoluteParams()
Применяем примерно так:
<input maxlength="10" autocomplete="off" onclick="initCalend(this)" />
*/
//если ранее подключения модуля не объявлена функция ReSetBtn - используется эта:
if(! window.ReSetBtn)   window.ReSetBtn = function(obj){
    //проверка нормальности с выводом сообщения об ошибке
    var v = obj.value;
    if(v && !isDate(v)){
        alert("Неверный формат даты.\nОжидался ДД.ММ.ГГГГ");
        obj.value = "";
    }
    return false;
};

function DateNormalize(d,m,y){
    // нормализация даты - добавление нулей в "число" и "месяц"
    return '' + ((d<10)?'0':'') +d+ '.' +((m<10)?'0':'')+m+ '.' +y;
}

function isChild(s,d){
    //является ли s сыновним для d
    var y;
    for(y = false; s && !y; s = s.parentNode) y = (s==d);
    return y;
}

var CalendarObj = { me : null,          //объект календаря (табличка)
                    forChangeYear:null, //span-объект для смены года
                    inpObj : null,      //изменяемый объект input
                    now : new Date,     //текущая дата
                    now_d : 0,          //постоянное:   число месяца
                    now_m : 0,          //              месяц
                    now_y : 0,          //              год
                    ccm  : 0,           //изменяемое:   месяц
                    ccy  : 0,           //              год
                    selectedd : 0,      //выбранное:    число месяца
                    selectedm : 0,      //              месяц
                    selectedy : 0,      //              год
                    monthNames : ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
                    daysMonths : ['31','28','31','30','31','30','31','31','30','31','30','31'], //дней в каждом месяце
                    arrCells : new Array(42)    //дней-ячеек таблицы
                }
with(CalendarObj){
    now_d = now.getDate();           //текущий день месяца
    now_m = ccm = now.getMonth();    //текущий месяц-1 сегодня
    now_y = ccy = now.getFullYear(); //текущий год сегодня
}

// Формированик календаря в виде таблицы
document.write('<table id="tCalendar" cellpadding="2" style="display:none;">');
document.write('<tr onselectstart="return false">'+
			'<td onclick="chgMonth(-1)">&laquo;</td>'+
                        '<td colspan="5" id="forChangeYear" align="center"></td>'+
			'<td align="right" onclick="chgMonth(1)">&raquo;</td>'+
		'</tr>');
document.write ('<tr><td>П</td>'+
                    '<td>В</td>'+
                    '<td>С</td>'+
                    '<td>Ч</td>'+
                    '<td>П</td>'+
                    '<td>С</td>'+
                    '<td>В</td>'+
		'</tr>');
for(var kk=1; kk<=6; kk++) {    //6 строк
    document.write('<tr>');
    for(var tt=1; tt<=7; tt++) {  //по 7 ячеек
	var num = 7 * (kk-1) - (-tt);
	document.write('<td id="v' + num + '">&nbsp;</td>');
    }
    document.write('</tr>');
}
with(CalendarObj)
    document.write('<tr><td colspan="7" onclick="setToday()">Сегодня: '+DateNormalize(now_d,now_m+1,now_y)+'</td></tr>');
document.write('</table>');
// «                  »
//  П  В  С  Ч  П  С  В
// __ __ __ __ __ __ 01
// 02 03 04 05 06 07 08
// 09 10 11 12 13 14 14
// 16 17 18 19 20 21 22
// 23 24 25 26 27 28 29
// 30 31 __ __ __ __ __
//       Сегодня
with(CalendarObj){
    me = getObj("tCalendar");
    forChangeYear = getObj('forChangeYear');
}
//настраиваем прослушивание события клика мыши на странице, чтобы скрыть, если клик не на нашем объекте
function checkClick(e){ //где был клик
    var evt = e ? e : event,
        elem = evt.target ? evt.target : evt.srcElement;
    with(CalendarObj)
        if(!isChild(elem,me))     //если не на нашем объекте - скрыть
            me.style.display = 'none';
}
document.all ? document.attachEvent('onclick',checkClick) : document.addEventListener('click',checkClick,false);

function initCalend(ielem){
    //инициализация объекта
    event.stopPropagation();	//остановить всплытие события
    var coord = getAbsoluteParams(ielem);
    CalendarObj.inpObj = ielem;
    with(CalendarObj.me.style){
	left = coord.left + 'px';
	top = coord.top + coord.height + 'px';
	display = '';
    }
    // проверка даты на правильность
    var curdt = ielem.value,
        curdtarr = curdt.split('.');    //разбиваем дату на массив: день, месяц, год
    var isdt = (curdtarr.length==3);    //есть верная дата - если 3 элемента в дате
    for(var k=0; isdt && (k<3); k++)
    	isdt = !isNaN(curdtarr[k]); //есть дата =  нет ошибки
    if(!isdt){	//нет правильной даты - определяем по сегодняшнему дню
	with(new Date()) curdt = DateNormalize(getDate(),getMonth()+1,getFullYear());
        curdtarr = curdt.split('.');
    }
    with(CalendarObj){
	ccm = curdtarr[1]-1;    //месяц-1
	ccy = curdtarr[2];      //год
	selectedd = parseInt( curdtarr[0], 10 );    //строку дня в int
	selectedm = parseInt( curdtarr[1]-1, 10 );  //строку мес.в int
	selectedy = parseInt( curdtarr[2], 10 );    //строку года в int
    }
    prepCalendar(curdtarr[1]-1, curdtarr[2]);
}

function evtTgt(e){
    //получить элемент, на котором произошло событие <e>
    var elem;
    if(e.target)elem = e.target;
    else if(e.srcElement)elem = e.srcElement;
    if(elem.nodeType==3)elem = elem.parentNode; // defeat Safari bug
    return elem;
}
function calendCellClick(e){
    //обработка клика по ячейке таблицы с числом месяца
    if(!e)e=window.event;
    with(CalendarObj){
        var old = inpObj.value;
        inpObj.value = arrCells[evtTgt(e).id.substr(1)];
        me.style.display = 'none';
        if(old != inpObj.value) ReSetBtn(inpObj);
    }
}

// выбран день
function prepCalendar(cm,cy) {
    CalendarObj.now = new Date;   //сегодня
    var td = new Date;
    td.setDate(1);      //установить дате 1 число месяца
    td.setFullYear(cy); //установить год дате
    td.setMonth(cm);    //установить месяц дате
    var cd = td.getDay();   //получить день недели для 1-го числа
    if(cd==0)cd=6; else cd--;

    with(CalendarObj){
	//формируем название месяца, строку года и span-теги с обработчиком на изменение года:
        forChangeYear.innerHTML = monthNames[cm]+
			'&nbsp;<span onclick="event.cancelBubble=true;chgMonth(-12)">&nbsp;&lt;&nbsp;</span>' +cy+
			'<span onclick="event.cancelBubble=true;chgMonth(12)">&nbsp;&gt;&nbsp;</span>';
        //сколько дней в феврале этого года
	daysMonths[1] = (cy%4) ? '28' : '29';
        // заполнить таблицу, снабжая стилями, обработчиком клика
        for(var d=1; d<=42; d++) {
            var v = getObj('v'+d);
            if((d >= (cd -(-1)))&&(d<=cd-(-daysMonths[cm]))) {      //есть дата в ячейке
                if(now_m == cm && now_d == (d-cd) && now_y == cy)  // если сегодня
                    v.className = "dCalendarT";	//T-текущая
                else if(cm == selectedm && cy == selectedy && selectedd == (d-cd) )  // если выделенная дата
                    v.className = "dCalendarA";	//A-активная
                else
                    v.className = "dCalendarD";	//D-другая
                v.innerHTML = d-cd;			
                v.onclick = calendCellClick;             
                arrCells[d] = DateNormalize(d-cd,cm-(-1),cy);
            }else                                                   //даты в ячейке нет
                with(v){
                    innerHTML = '&nbsp;';
                    className = "dCalendarN";
                    onclick = null;
                }
        }
    }
}

function chgMonth(s){
    //  +/- s к месяцу
    with(CalendarObj){
        daysMonths[1] = (ccy%4) ? '28' : '29';
        ccm += s;
        if(ccm >= 12)	{ ccm -= 12;	ccy++; }
        else if(ccm < 0)	{ ccm += 12;	ccy--; }
        prepCalendar(ccm,ccy);
    }
}

function setToday() {
    // установить "сегодня"
    with(CalendarObj){
        var old = inpObj.value;
        inpObj.value = DateNormalize(now_d,now_m+1,now_y);
        me.style.display = 'none';
        prepCalendar(now_m,now_y);
        if(old != inpObj.value) ReSetBtn(inpObj);
    }
}
