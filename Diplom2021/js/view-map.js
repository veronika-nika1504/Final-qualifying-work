var YmapApiKey="8a453f9f-56b9-4cf9-80f8-e328e2f692e2";
/*	view-map.js	-	Показ карты.	Мой апи-ключ: 8a453f9f-56b9-4cf9-80f8-e328e2f692e2
==========================================================================================================
ПЕРВУЮ СТРОКА ДАННОГО ФАЙЛА НЕ МЕНЯТЬ, КРОМЕ API-KEY, КОТОРЫЙ ДОЛЖЕН БЫТЬ УКАЗАН ДЛЯ КОНКРЕТНОГО ВЛАДЕЛЬЦА
Этот api-key считывается функцией YmapApiKey() PHP-модуля "/inc/inetConnect.php" для указания верной(!!!) 
строки подключения карт.
==========================================================================================================
	В вызывающем PHP-модуле необходимо подключение jQuery, API Яндекс-карт и этого скрипта
<script src="js/min.jquery.js?v3"></script>
<script src="https://api-maps.yandex.ru/2.1/?apikey=<?=YmapApiKey()?>&lang=ru_RU"></script>
<script src="js/view-map.js"></script>
==========================================================================================================
	Используем глобальную объект-переменную для передачи в процедуру iniMaps (сработает по готовности API карт),
не имеющую параметров:
*/
var YmapApiVars = { mapLat : null,	    /* геокоординаты - широта				 */
		    mapLon : null,	    /*		    и долгота				 */
		    mapMsg : null,	    /* строковая метка					 */
		    mapId  : null,	    /* id того div, в котором будет отображена карта	 */
		    mapGeoObject : null,    /* функция для запуска по окончании отображения карт */
		    mapMap : null,	    /* объект карт, который будет создан API-карт	 */
		    mapAfterFunction : null /* объект метки на карте			    	 */
		    };
/*  	Параметры функции buildMap, запускающей построение карт:
		divMapId	- id того div, в котором будет отображена карта
		strAddress	- адресная строка, для которой строится карта
		afterFunction - функция, запускаемая по окончании отображения карт (не обязательный)
		specMsg		- сообщение метки (не обязательный, если не указан - будет отображаться 'Это где-то тут' или 'Это здесь')
*/
function	 buildMap(divMapId, strAddress, afterFunction, specMsg){
	YmapApiVars.mapId = divMapId;
	var strUrl = 'https://geocode-maps.yandex.ru/1.x/?format=json&apikey=' + YmapApiKey + '&geocode=' + strAddress;
	if(afterFunction)	YmapApiVars.mapAfterFunction = afterFunction;	else YmapApiVars.mapAfterFunction = null;
	YmapApiVars.mapMsg = specMsg || "";
	$.ajax({
		url: strUrl,
		success: function(Response){
			var pos = Response.response.GeoObjectCollection.featureMember[0].GeoObject.Point.pos;
			var i = pos.indexOf(" ");
			YmapApiVars.mapLat = pos.substr(i+1);
			YmapApiVars.mapLon = pos.substr(0,i);
			if(!YmapApiVars.mapMsg)
				YmapApiVars.mapMsg = (strAddress.indexOf('дом')==-1) ? 'Это где-то тут' : 'Это здесь';
			ymaps.ready(iniMaps);
		}
	});
}
function iniMaps(){
	YmapApiVars.mapMap = new ymaps.Map(YmapApiVars.mapId,
					    {	center : [YmapApiVars.mapLat, YmapApiVars.mapLon], 
						zoom : 16,
						controls : [ "rulerControl", "typeSelector", "zoomControl", "fullscreenControl", "geolocationControl" ]
					    }
					  );
	YmapApiVars.mapGeoObject = new ymaps.GeoObject( {geometry:   {type : "Point", coordinates : [YmapApiVars.mapLat, YmapApiVars.mapLon]},
							 properties: {iconContent : YmapApiVars.mapMsg, hintContent : ""}
							},
							{preset : "islands#blackStretchyIcon", draggable : true	}
							);
	YmapApiVars.mapMap.geoObjects.add(YmapApiVars.mapGeoObject);
	document.getElementById(YmapApiVars.mapId).style.display = "block";
	if(YmapApiVars.mapAfterFunction)	setTimeout(YmapApiVars.mapAfterFunction,200);
}
