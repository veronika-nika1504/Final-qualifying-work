<?php   //  inetConnect.php
function    inetConnect(){
    $ok = false;
    if($f = @fsockopen("93.158.134.11",80)){    // попытка подключения к Яндекс
		$ok = true;
		fclose($f);
    }
    return $ok;       //истина, если до Яндекса достучались
}
function    noInternet($msg){	//возвращает указанное сообщение, если нет подкдючения к Интернет
    return (inetConnect()?'':$msg);
}
function	 YmapApiKey(){
/*	Возвращает YmapApiKey из такой строки:
var YmapApiKey="8572d1fa-8580-4a61-96d0-50ca4bb7dfce";
*/
        global $AppDir;
	$key = "key-not-found";
	$f = @fopen($AppDir."\\js\\view-map.js", "r");
	$s = $f ? @fgets($f) : false;
	@fclose($f);
	if($s){
		$b = strpos($s, '"')+1;
		$e = strpos($s, '"', $b);
		$key = substr($s, $b, $e - $b);
	}
	return $key;
}
