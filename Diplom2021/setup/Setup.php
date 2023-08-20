<?php	//SETUP
	header('Content-Type: text/html; charset=utf-8');
	$MyAddr = isset($_POST['MyAddr']) ? $_POST['MyAddr'] : "";
	$root = isset($_POST['root']) ? $_POST['root'] : "";
	$rPsw = isset($_POST['rPsw']) ? $_POST['rPsw'] : "";
	$firm = isset($_POST['firm']) ? $_POST['firm'] : "ООО «Недвижимость»";
	$dsbl = $ErrorMsg = "";
	$BeginPage = 
'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
'<html xmlns="http://www.w3.org/1999/xhtml" lang="ru-RU">'.
'<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'.
'<meta http-equiv="content-language" content="ru" />'.
'<meta name="description" content="Недвижимость: оценка, купля, продажа" />'.
'<meta name="keywords" content="Недвижимость: оценка, купля, продажа, риэлтер" />'.
'<meta name="robots" content="index,follow" />'.
'<link rel="shortcut icon" href="../img/favicon.png">'.
'<style>#Err{color:red}</style>'.
'<title>Недвижимость</title>'.
'<link rel="stylesheet" href="../css/main.css">'.
'</head><body>	<form hidden id="SetupForm" name="SetupForm" method="post" onsubmit="return false"></form>';

	$Connect = false;
function	 RunQuery($command, $ErrFrm, &$ErrStr="", &$RowArray=NULL){
	//	выполняет команду MySQL, возвращает результат, помещает в $ErrStr сообщение об ошибке или пустую строку,а в $RowArray первую строку выборки
	GLOBAL $Connect;
	$ErrStr = "";	$RowArray = NULL;
	$Result = mysqli_query($Connect, $command);
	if(! $Result)
		$ErrStr = '<tr><th id="Err" colspan="3">При попытке ' . $ErrFrm . ' произошла ошибка: ' . mysqli_error($Connect) . '<br>Дальнейшая работа невозможна.</th></tr>';
	elseif(mysqli_field_count($Connect))
		$RowArray = mysqli_fetch_array($Result);
	return $Result;
}
function	 CreateTable($TableName, $Definition, &$ErrStr){
	// выполняет команду создания таблицы $TableName с описанием в $Definition. Если ошибка - заполняет $ErrStr
	GLOBAL $Connect;
	$ErrStr = "";
	$Result = mysqli_query($Connect, "CREATE TABLE ".$TableName."(".$Definition.")");
	if(! $Result)
		$ErrStr = '<tr><th id="Err" colspan="3">При попытке создания таблицы ' . $TableName . '('. $Definition . ') произошла ошибка: ' . mysqli_error($Connect) .
			'<br>Дальнейшая работа невозможна.</th></tr>';
	return $Result;
}
function	 CreateTableSprav($TableName, &$ErrStr, $LenName=12){
	//создание таблицы - справочника, $LenName - длина строки названия
	GLOBAL $Connect;
	if($LenName<12) $LenName = 12;
	return CreateTable($TableName, "srt tinyint not null AUTO_INCREMENT PRIMARY KEY, code char(1) NOT NULL, name varchar($LenName) NOT NULL, k DECIMAL(6,4) DEFAULT 0.0",  $ErrStr);
	$Result = mysqli_query($Connect, "CREATE INDEX IX_".$TableName." ON ".$TableName."(code)");
	if(! $Result )
		$ErrStr = '<tr><th id="Err" colspan="3">При выполнении создания индекса для таблицы '.$TableName.' произошла ошибка:<br>'.mysqli_error($Connect) .
			'<br>Дальнейшая работа невозможна.</th></tr>';
}
function	 InsertTable($TableName, $FieldName, $ListValues, &$ErrStr){
	// вставка в таблицу
	/*	INSERT INTO roles (rName) VALUES ('Администратор'),('Сотрудник'),('Клиент') */
	GLOBAL $Connect;
	$ErrStr = "";
	$Result = mysqli_query($Connect, "INSERT INTO ".$TableName."(".$FieldName.") VALUES ".$ListValues);
	if(! $Result)
		$ErrStr = '<tr><th id="Err" colspan="3">При попытке добавления данных в таблицу ' . $TableName . ' произошла ошибка: ' . mysqli_error($Connect) .
			'<br>Дальнейшая работа невозможна.</th></tr>';
	return $Result;
}
function	 UpdateTable($TableName, $SetList, $Where, &$ErrStr){
	// замена значений полей в таблице
	/* UPDATE Logins SET fam='Запара',nam='Вероника' WHERE Uid=1 */
	/* $reg = UpdateTable('Logins', "fam='Запара',nam='Вероника'", 'Uid=1', $errMsg); */
	GLOBAL $Connect;
	$ErrStr = "";
	$Result = mysqli_query($Connect, "UPDATE ".$TableName." SET ".$SetList. ($Where ? (" WHERE ".$Where) : ""));
	if(! $Result)
		$ErrStr = '<tr><th id="Err" colspan="3">Неудача UPDATE таблицы ' . $TableName . ' произошла ошибка: ' . mysqli_error($Connect) .
			'<br>Дальнейшая работа невозможна.</th></tr>';
	return $Result;
}
function	DropAutoIncrement($TableName, &$ErrStr){
	// сбросить в 1 автоинкрементное поле таблицы (для справочников, где создаем поле и ид=0 для "не задано")
	GLOBAL $Connect;
	$ErrStr = "";
	$Result = mysqli_query($Connect, "ALTER TABLE " . $TableName . " AUTO_INCREMENT=1;");
	if(! $Result)
		$ErrorMsg = '<tr><th id="Err" colspan="3">Неудача сброса автоинкремента в 1 для таблицы ' . $TableName . ', ошибка: '. mysqli_error($Connect).
			'<br>Дальнейшая работа невозможна.</th></tr>';
}
///////////////////////////////////

	if($MyAddr){
		$Connect = @mysqli_connect($MyAddr, $root, $rPsw);
		if(! $Connect)
			$ErrorMsg = '<tr><th id="Err" colspan="3">При попытке подключения к MySQL-серверу произошла ошибка:<br>'.mysqli_connect_error().'</th></tr>';
		else{
			mysqli_set_charset($Connect, 'utf8');
			$dsbl = "disabled";
			// пытаемся выяснить, существует ли база данных с именем DB_RIELTER и если да - её кодовую страницу : выполнить запрос :
			$CharSet = false;
			RunQuery("SELECT default_character_set_name AS cs FROM information_schema.schemata WHERE schema_name = 'DB_RIELTER'", "чтения схемы", $ErrorMsg, $CharSet);
			if($CharSet){	//БД существует, сообщить, спросить надо ли удалить, если ещё нет этого ответа
				$CharSet  = $CharSet['cs'];
				if(isset($_POST['drop'])){	//передана переменная drop
					if($_POST['drop']=='drop'){	// надо удалить и перезагрузить страницу, чтобы начать сначала - будто БД не существовало
						if(RunQuery("DROP DATABASE DB_RIELTER;", 'удаления базы данных DB_RIELTER', $ErrorMsg)){
/*		Проводим попытку удаления БД, если произойдёт ошибка - $ErrorMsg будет содержать текст:
<tr><th id="Err" colspan="3">При попытке удаления базы данных DB_RIELTER произошла ошибка:<br>ЧТО ВЕРНУЛ MySQL
	<br>Дальнейшая работа невозможна.
</th></tr>
		Но если успех -  БД успешно удалена, перезагружаем страницу, посылая данные о подключении, то есть завершаем вывод и работу скрипта	*/
							echo $BeginPage.
'<input type="hidden" name="MyAddr" form="SetupForm" value="'.$MyAddr.'" />'.
'<input type="hidden" name="root" form="SetupForm" value="'.$root.'" />'.
'<input type="hidden" name="rPsw" form="SetupForm" value="'.$rPsw.'" />'.
'<input type="hidden" name="firm" form="SetupForm" value="'.$firm.'" />'.
'</body><script>window.onload=function(){SetupForm.submit();};</script></html>';
							exit;
						}//if(RunQuery)
					}else{	 //передано, что удалять не надо - завершаем работу.
						$ErrorMsg = '<tr><th id="Err" colspan="3">База данных DB_RIELTER существует и имеет кодовую страницу "'.$CharSet.'".<br>От её удаления Вы отказались.<br>Дальнейшая работа невозможна.</th></tr>';
					}//if($_POST['drop']=='drop')else
				}else{		//БД существует, имеет кодовую страницу $CharSet, нужно спросить: удалить? и продолжить работу или завершить.
						$ErrorMsg = '<tr><th id="Err" colspan="3"><input type="hidden" id="drop" name="drop" form="SetupForm" />'.
														'База данных DB_RIELTER существует и имеет кодовую страницу "'.$CharSet.'".</th></tr>'.
							'<tr><th colspan="3"><button class="BtnBig" onclick="Btn(this)" title="Удалить БД и установить заново">Переустановить</button>&nbsp;&nbsp;&nbsp;<button class="BtnSmall" onclick="Btn(this)">Останов</button></th></tr>';
				}//if(isset($_POST['drop']))else
			}elseif(RunQuery("CREATE DATABASE DB_RIELTER", "создания базы данных DB_RIELTER", $ErrorMsg)){
/*	В $CharSet пусто, т.е. нет такой БД, пытаемся создать базу, если ошибка - $ErrorMsg заполнится
<tr><th id="Err" colspan="3">При попытке создания базы данных DB_RIELTER произошла ошибка:<br>ЧТО ВЕРНУЛ MySQL
	<br>Дальнейшая работа невозможна.
</th></tr>
		Но в случае успеха (БД успешно создана)	*/
				$UserHost = false;
/*	Выясняем, есть ли на MySQL-сервере пользователь RielterUsr, при ошибке $ErrorMsg будет заполнена,
	при удаче $UserHost будет содержать имя сервера, к которому разрешено подключение:	*/
				RunQuery("SELECT Host FROM mysql.user WHERE User='RielterUsr'", "доступа к таблице mysql.user", $ErrorMsg, $UserHost);
				if(! $ErrorMsg && $UserHost) RunQuery("DROP USER 'RielterUsr'@'%';", "удаления пользователя", $ErrorMsg);
				if(! $ErrorMsg)
					RunQuery("CREATE USER 'RielterUsr'@'%' IDENTIFIED BY 'AppPassWord';", "создания пользователя для БД DB_RIELTER", $ErrorMsg);
				if(! $ErrorMsg)	// нет ошибки, пытаемся дать пользователю RielterUsr права на БД 
					RunQuery("GRANT ALL PRIVILEGES ON DB_RIELTER.* TO 'RielterUsr'@'%' WITH GRANT OPTION;", "наделения пользователя правами в БД DB_RIELTER", $ErrorMsg);
				if(! $ErrorMsg) RunQuery("FLUSH PRIVILEGES;", "сохранения привилегий пользователей", $ErrorMsg);
				if(! $ErrorMsg)	if(! mysqli_select_db($Connect, "DB_RIELTER"))
					$ErrorMsg = '<tr><th id="Err" colspan="3">Не удалось подключиться к БД DB_RIELTER, ошибка: '. mysqli_error($Connect).
								'<br>Дальнейшая работа невозможна.</th></tr>';
//--------------------------
				// пытаемся создать таблицу Oper - коды операций в системе
				if(! $ErrorMsg)	CreateTable("Oper",	"Oid tinyint unsigned AUTO_INCREMENT PRIMARY KEY" .
																		",oName varchar(50)"
																												,$ErrorMsg);
				// пытаемся заполнить таблицу Oper
				if(! $ErrorMsg)	InsertTable("Oper", "oName",
																	"('регистрация пользователя'),('вход в систему'),('выход из системы'),('изменение личных данных')"	.	// 1, 2, 3, 4
																	",('новый объект продажи'),('изменение объекта продажи')" .	// 5, 6
																	",('распределение объекта сотруднику')"	.					// 7
																	",('операции с фото объекта')" .							// 8
																	",('изменение цен в регионах')" .							// 9
																	",('установка коэффициента для характеристики')" .			//10
																	",('добавление варианта характеристики')"					//11
																												,$ErrorMsg);
//--------------------------
				// пытаемся создать таблицу Result - коды результатов операций в системе
				if(! $ErrorMsg)	CreateTable("Result",	"Rid tinyint unsigned AUTO_INCREMENT PRIMARY KEY" .
																			",rName varchar(50)"
																												,$ErrorMsg);
				// пытаемся заполнить таблицу Result
				if(! $ErrorMsg)	InsertTable("Result", "rName","('успех')", $ErrorMsg);
				if(! $ErrorMsg)	UpdateTable("Result", "Rid=0", "Rid=1", $ErrorMsg);
				if(! $ErrorMsg) DropAutoIncrement("Result", $ErrorMsg);
				if(! $ErrorMsg)	InsertTable("Result", "rName",	
																		"('пароль неверен')"	.	// 1
																		",('запрещено')"			// 2
																									,$ErrorMsg);
//--------------------------
				// пытаемся создать таблицу Roles
				if(! $ErrorMsg)	CreateTable("Roles", 'Rid tinyint unsigned AUTO_INCREMENT PRIMARY KEY, rName varchar(50)', $ErrorMsg);
				// пытаемся заполнить таблицу Roles
				if(! $ErrorMsg)	InsertTable("Roles", "rName", 
																	"('Клиент')".						//	1		0000 0001		1.
																	",('Сотрудник фирмы')".		//	2		0000 0010		2.
																	",('Просмотр лога')".			//	3		0000 0100		4.
																	",('Распределение')".			//	4		0000 1000		8.
																	""
																									, $ErrorMsg);
				/* Полномочие      бит	двоичн. десятич. шестнад.
				 * SuperAdmin							xFFFFFFFF
				 * Клиент			1	000001	 1.		x00000001
				 * Сотрудник фирмы	2	000010	 2.		x00000002
				 * Просмотр лога	3	000100	 4.		x00000004
				 * Распределение	4	001000	 8.		x00000008
				 * 
				 */
//--------------------------
				// пытаемся создать таблицу Log
				if(! $ErrorMsg)	CreateTable("Log",	"Lid bigint unsigned AUTO_INCREMENT PRIMARY KEY" .
															",oDate datetime" .			// дата операци
															",Uid int unsigned" .		// ид. пользователя
															",Oid tinyint unsigned" .	// ид. операции
															",Rid tinyint unsigned" .	// ид. результата
															",info varchar(3000) DEFAULT ''"		// расширенная информация
																				,$ErrorMsg);
//--------------------------
				// пытаемся создать таблицу пользователей Logins
				if(! $ErrorMsg)	CreateTable("Logins",	"Uid int unsigned AUTO_INCREMENT PRIMARY KEY" .	// ид. пользователя
															",regIP varchar(15)" .					// IP-адрес, с которого прошла регистрация - неизменяемое
															",regUid int unsigned" .				// ид. пользователя, кто зарегистрировал - неизменяемое
															",regDate datetime" .					// дата-время регистрации - неизменяемое
															",uName varchar(20)" .					// login - неизменяемое
															",uPsw varchar(32)" .	 				// пароль, автоматом преобразуется в md5-хэш пароля
															",uRights int unsigned DEFAULT 0" .		// битовый набор прав
															",uState tinyint unsigned DEFAULT 1".	// состояние учётной записи
															",fam varchar(32) DEFAULT ''" .			// фамилия
															",nam varchar(32) DEFAULT ''" .			// имя
															",otc varchar(32) DEFAULT ''" .			// отчество
															",phone varchar(30) DEFAULT ''" .		// телефон
															",eMail varchar(128) DEFAULT ''" .		// почта
															",address varchar(200) DEFAULT ''" .	//адрес
															",wPlace varchar(200) DEFAULT ''" .		// место работы
															",cabNo varchar(10) DEFAULT ''" .		// № кабинета
															",wPhone varchar(10) DEFAULT ''" .		// рабочий телефон
															",note varchar(300) DEFAULT ''" .		// примечания
															",lastUid int unsigned"	 		// ид. пользователя, внесшего последние изменения - для регистрации в лог триггером
																				,$ErrorMsg);
				// при добавлении пользователя (INSERT) обязательные поля: uName, uPsw, regIP, lastUid
				// создаём триггеры...
				if(! $ErrorMsg)		// 1) на BEFORE INSERT -  для автоматизации установки даты-времени регистрации, замены пароля на его md5-хеш
					RunQuery("CREATE TRIGGER Logins_Bef_Ins BEFORE INSERT ON Logins FOR EACH ROW BEGIN ".
										"SET NEW.regUid=NEW.lastUid; ".
										"SET NEW.regDate=now(); " .
										"SET NEW.uPsw=md5(NEW.uPsw); ".
									"END;"															, "создания Before-Insert-триггера для таблицы Logins", $ErrorMsg);
				if(! $ErrorMsg)		// 2) на AFTER INSERT -  для лога
					RunQuery("CREATE TRIGGER Logins_Aft_Ins AFTER INSERT ON Logins FOR EACH ROW BEGIN ".
										"IF NEW.lastUid=NEW.Uid THEN SET @xName='УСТАНОВЩИК'; ".
										"ELSEIF NEW.lastUid=0 THEN SET @xName='САМОРЕГИСТРАЦИЯ'; ".
										"ELSE SELECT CONCAT(uName,'(',Uid,')') INTO @xName FROM logins WHERE Uid=NEW.lastUid; ".
										"END IF; ".
										"SET @msg=CONCAT('[Uid=',NEW.Uid,'], [Логин: ',NEW.uName,'], [Права: ',hex(NEW.uRights),'], [Статус: ',hex(NEW.uState),'], ".
													"[Фам: ',NEW.fam,'], [Имя: ',NEW.nam,'], [Отч: ',NEW.otc,'], [Тел: ',NEW.phone,'], [Почта: ',NEW.eMail,'], ".
													"[Адрес: ',NEW.address,'], [Работа: ',NEW.wPlace,'], [Каб: ',NEW.cabNo,'], [Р.тел: ',NEW.wPhone,'], [Прим: ',NEW.note,']'); ".
										"SET @msg=CONCAT(@xName,': ',@msg); ".
										"INSERT INTO Log(oDate,Uid,Oid,Rid,info)VALUES(NEW.regDate,NEW.Uid,1,0,@msg); " .
									"END;"															, "создания After-Insert-триггера для таблицы Logins", $ErrorMsg);
				if(! $ErrorMsg)		// 3) на BEFORE UPDATE - для автоматизации замены пароля на его md5-хеш
					RunQuery("CREATE TRIGGER Logins_Bef_Upd BEFORE UPDATE ON Logins FOR EACH ROW BEGIN ".
										"IF LENGTH(NEW.uPsw)<32 THEN SET NEW.uPsw=md5(NEW.uPsw); END IF; ".
									"END;"															, "создания Before-Update-триггера для таблицы Logins", $ErrorMsg);
				if(! $ErrorMsg)		// 4) на AFTER UPDATE - для автоматизации лога
					RunQuery("CREATE TRIGGER Logins_Aft_Upd AFTER UPDATE ON Logins FOR EACH ROW BEGIN ".
										"SET @msg=''; SET @xNme=''; SET @xid=0; ".
										"IF NEW.uName<>OLD.uName THEN SET @msg=CONCAT(@msg,', [Логин: ',OLD.uName,' ⇒ ',NEW.uName,']'); END IF; ".
										"IF NEW.uPsw<>OLD.uPsw THEN SET @msg=CONCAT(@msg,', [Пароль новый]'); END IF; ".
										"IF NEW.uRights<>OLD.uRights THEN SET @msg=CONCAT(@msg,', [Права: ',hex(OLD.uRights),' ⇒ ',hex(NEW.uRights),']'); END IF; ".
										"IF NEW.uState<>OLD.uState THEN SET @msg=CONCAT(@msg,', [Статус: ',hex(OLD.uState),' ⇒ ',hex(NEW.uState),']'); END IF; ".
										"IF NEW.fam<>OLD.fam THEN SET @msg=CONCAT(@msg,', [Фам: ',OLD.fam,' ⇒ ',NEW.fam,']'); END IF; ".
										"IF NEW.nam<>OLD.nam THEN SET @msg=CONCAT(@msg,', [Имя: ',OLD.nam,' ⇒ ',NEW.nam,']'); END IF; ".
										"IF NEW.otc<>OLD.otc THEN SET @msg=CONCAT(@msg,', [Отч: ',OLD.otc,' ⇒ ',NEW.otc,']'); END IF; ".
										"IF NEW.phone<>OLD.phone THEN SET @msg=CONCAT(@msg,', [Тел: ',OLD.phone,' ⇒ ',NEW.phone,']'); END IF; ".
										"IF NEW.eMail<>OLD.eMail THEN SET @msg=CONCAT(@msg,', [Почта: ',OLD.eMail,' ⇒ ',NEW.eMail,']'); END IF; ".
										"IF NEW.address<>OLD.address THEN SET @msg=CONCAT(@msg,', [Адрес: ',OLD.address,' ⇒ ',NEW.address,']'); END IF; ".
										"IF NEW.wPlace<>OLD.wPlace THEN SET @msg=CONCAT(@msg,', [Работа: ',OLD.wPlace,' ⇒ ',NEW.wPlace,']'); END IF; ".
										"IF NEW.cabNo<>OLD.cabNo THEN SET @msg=CONCAT(@msg,', [Каб: ',OLD.cabNo,' ⇒ ',NEW.cabNo,']'); END IF; ".
										"IF NEW.wPhone<>OLD.wPhone THEN SET @msg=CONCAT(@msg,', [Р.тел: ',OLD.wPhone,' ⇒ ',NEW.wPhone,']'); END IF; ".
										"IF NEW.note<>OLD.note THEN SET @msg=CONCAT(@msg,', [Прим: ',OLD.note,' ⇒ ',NEW.note,']'); END IF; ".
										"IF NEW.lastUid=NEW.Uid THEN SET @xNme='САМОСТОЯТЕЛЬНО'; ".
										"ELSE SELECT CONCAT(uName,'(',Uid,')') INTO @xNme FROM logins WHERE Uid=NEW.lastUid; ".
										"END IF; ".
										"IF @msg<>'' THEN INSERT INTO Log(oDate,Uid,Oid,Rid,info)VALUES(now(),NEW.Uid,4,0,CONCAT(@xNme,': ',SUBSTRING(@msg,3))); END IF; ".
									"END;"																, "создания After-Update-триггера для таблицы Logins", $ErrorMsg);
				// регистрация пользователя - администратора:
				if(! $ErrorMsg)	InsertTable("Logins", "uName,uPsw,uRights,regIP,lastUid", "('Admin','Admin',0xFFFFFFFF,'$_SERVER[REMOTE_ADDR]',1)", $ErrorMsg);
//////////////////////////////////////////////////////////////////////
				if(! $ErrorMsg)	// создаём процедуру WriteLog
					RunQuery("CREATE PROCEDURE WriteLog(IN UserId int unsigned, IN OperId tinyint unsigned, IN ResultId tinyint unsigned, IN Note varchar(2000)) BEGIN ".
											"INSERT INTO Log(oDate,Uid,Oid,Rid,info)VALUES(now(),UserId,OperId,ResultId,Note); ".
									"END;"																, "создания процедуры WriteLog", $ErrorMsg);
//////////////////////////////////////////////////////////////////////
				// пытаемся создать таблицы справочника: категории/поля/типы
				if(! $ErrorMsg)	CreateTable("ListCategory", "idCategory tinyint not null AUTO_INCREMENT PRIMARY KEY" .
															",Category varchar(100)" .	//категория по-русски
															",fname varchar(20)".		//имя поля в Estate
															",type char(1) NOT NULL".	//тип DOM-элемента: S-select, I-input
															",srt tinyint NULL"			//порядок следования в экранныъ формах
																		,$ErrorMsg);
				if(! $ErrorMsg)	// индексы
					RunQuery("CREATE INDEX IX_ListCategory_S ON ListCategory(srt);CREATE INDEX IX_ListCategory_F ON ListCategory(fname);", $ErrorMsg);
				if(! $ErrorMsg)	InsertTable("ListCategory", "Category,fname,type",
							"('Тип недвижимости','etype','S'),('Общая площадь (м²)','S','I'),('Число жилых комнат','komnat','I')" .
							",('Площадь кухни (м²)','Skuh','I'),('Электроснабжение','elektro','S'),('Газоснабжение','gaz','S')" .
							",('Водоснабжение','voda','S'),('Канализация','kanal','S'),('Горячее водоснабжение','gvoda','S')" .
							",('Отопление','otoplen','S'),('Санузел','sanuzel','S'),('Площадь ванной комнаты (м²)','Svanna','I')" .
							",('Высота до потолка','potolok','S'),('Материал строения','mater','S'),('Площадь балкона/лоджии (м²)','Sbalkon','I')" .
							",('Всего этажей в строении','etajej','I'),('Площадь подвала/цоколя (м²)','Spodval','I')" .
							",('Наличие парковки','parking','S'),('Наличие ограждения','ograd','S'),('Безопасность','bezopas','S')" .
							",('Внутренняя отделка','otdelka','S'),('Окна','okna','S'),('Необходимость ремонта','remont','S')" .
							",('Неоформленная перепланировка','nezakon','S'),('Вид из окна','vidokna','S'),('Меблированность','mebel','S')" .
							",('Готовность документов','gotov','S'),('Бар/ресторан на нижних этажах','magaz','S'),('Наличие тамбура','tambur','S')" .
							",('Наличие лифта','lift','S'),('Этаж','etaj','I'),('Площадь всего строения (м²)','Sstroen','I')" .
							",('Число совладельцев','sovlad','I'),('Надворные постройки','sarai','S'),('Площадь земельн.участка(сот.)','Szemlia','I')"
																		,$ErrorMsg);
				// установить сотрировку именно в этом порядке:
				if(! $ErrorMsg) RunQuery("UPDATE ListCategory SET srt=idCategory", $ErrorMsg);
				// создаём таблицу справочника
				if(! $ErrorMsg)	CreateTable("GuideCategory", "idGuide smallint NOT NULL AUTO_INCREMENT PRIMARY KEY" .
															",idCategory tinyint NOT NULL" .	//указатель категории
															",code char(1) NOT NULL" .			//однобуквенный код (для select)
															",name varchar(100) NOT NULL" .		//расшифровка пункта (для select)
															",k decimal(6,4) DEFAULT 0.0" .		//коэффициент влияния
															",srt tinyint NOT NULL"				//порядок следования (в select)
																		,$ErrorMsg);
				if(! $ErrorMsg) //индексы
					RunQuery("CREATE INDEX IX_GuideCategory_CC ON GuideCategory(idCategory,code);"
							."CREATE INDEX IX_GuideCategory_CS ON GuideCategory(idCategory,srt);"
																		,$ErrorMsg);
				if(! $ErrorMsg) //заполняем
					InsertTable("GuideCategory", "idCategory,code,name,k,srt",
				"(1,' ','-не указано-',0.0,1),(1,'Д','дом',0.01,2), (1,'Ч','часть дома',-0.01,3),(1,'К','квартира',0.0,4),(1,'М','комната',-0.02,5)" .
				",(2,' ','',0,1)" .
				",(3,' ','',-0.0050,1)" .
				",(4,' ','',0.0010,1)" .
				",(5,' ','-не указано-',0.0,1), (5,'Н','нет',-0.02,2),(5,'Д','да',0.0,3)" .
				",(6,' ','-не указано-',0.0,1), (6,'Н','нет',-0.02,2),(6,'Д','да',0.0,3)" .
				",(7,' ','-не указано-',0.0,1), (7,'Н','нет',-0.02,2),(7,'С','скважина',-0.01,3),(7,'Ц','центральное',0.0,4)" .
				",(8,' ','-не указано-',0.0,1), (8,'Н','нет',-0.02,2),(8,'М','местная',-0.01,3),(8,'Ц','центральная',0.0,4)" .
				",(9,' ','-не указано-',0.0,1), (9,'Н','нет',-0.02,2),(9,'Г','газовая колонка/котёл',-0.01,3),(9,'Э','элетроводонагреватель',-0.015,4),(9,'Ц','центральное',0.0,5)" .
				",(10,' ','-не указано-',0.0,1),(10,'Н','нет/печь',-0.03,2),(10,'Г','газовый котёл/АОГВ',-0.01,3),(10,'Ц','центральное',0.0,4),(10,'Т','центральное с терморегуляцией',0.01,5)" .
				",(11,' ','-не указано-',0.0,1),(11,'Н','нет/во дворе',-0.03,2),(11,'Т','только туалет',-0.02,3),(11,'С','совмещённый',-0.01,4),(11,'Р','раздельный',0.0,5),(11,'У','улучшенный',0.01,6)" .
				",(12,' ','',0.0010,1)" .
				",(13,' ','-не указано-',0.0,1),(13,'Н','до 270 см',-0.01,2),(13,'В','свыше 270см',0.01,3)" .
				",(14,' ','-не указано-',0.0,1),(14,'С','сендвич-панель',-0.03,2),(14,'Б','бетон-плиты',-0.02,3),(14,'М','монолит',-0.0,4),(14,'К','кирпич',0.01,4),(14,'Д','бревно',0.02,5)" .
				",(15,' ','',0.0010,1)" .
				",(16,' ','',0.0001,1)" .
				",(17,' ','',0.0010,1)" .
				",(18,' ','-не указано-',0.0,1),(18,'Н','нет',-0.01,2),(18,'Е','есть',0.0,3),(18,'З','наземная',0.005,4),(18,'П','подземная',0.01,5),(18,'Г','свой гараж',0.02,6)" .
				",(19,' ','-не указано-',0.0,1),(19,'Н','нет',0.0,2),(19,'Д','да',0.01,3)" .
				",(20,' ','-не указано-',0.0,1),(20,'Н','нет',-0.01,2),(20,'Д','домофон',0.005,3),(20,'В','видеонаблюдение',0.01,4),(20,'О','вневедомственная охрана',0.015,5)" .
				",(21,' ','-не указано-',0.0,1),(21,'Н','нет',-0.02,2),(21,'С','стандартная',0.0,3),(21,'В','высококачественная',0.01,4),(21,'Э','эксклюзивная',0.02,5)" .
				",(22,' ','-не указано-',0.0,1),(22,'Н','деревянные рамы',-0.01,2),(22,'П','пластиковые стеклопакеты',0.0,3),(22,'Д','деревянные стеклопакеты',0.01,4)" .
				",(23,' ','-не указано-',0.0,1),(23,'С','нужен серьёзный',-0.02,2),(23,'К','нужен косметический',-0.01,3),(23,'Н','не нужен',0.01,4)" .
				",(24,' ','-не указано-',0.0,1),(24,'Д','да',-0.02,2),(24,'Н','нет',0.0,3)" .
				",(25,' ','-не указано-',0.0,1),(25,'З','завод',-0.02,2),(25,'Д','дорога',-0.01,3),(25,'В','двор',0.0,4),(25,'П','природа',0.02,5)" .
				",(26,' ','-не указано-',0.0,1),(26,'Н','нет',0.0,2),(26,'Ч','частично',0.005,3),(26,'Д','да',0.01,4)" .
				",(27,' ','-не указано-',0.0,1),(27,'Н','нет',0.0,2),(27,'Ч','частично',0.005,3),(27,'Д','да',0.01,4)" .
				",(28,' ','-не указано-',0.0,1),(28,'Д','да',-0.02,2),(28,'Н','нет',0.0,3)" .
				",(29,' ','-не указано-',0.0,1),(29,'Н','нет',0.0,2),(29,'Д','да',0.005,3)" .
				",(30,' ','-не указано-',0.0,1),(30,'Н','нет',0.0,2),(30,'Д','да',0.005,3)" .
				",(31,' ','1-ый',-0.10,1),(31,' ','последний',-0.05,2)" .
				",(32,' ','',-0.0010,1)" .
				",(33,' ','',-0.02,1)" .
				",(34,' ','-не указано-',0.0,1),(34,'Н','нет',0.0,2),(34,'Д','да',0.005,3)" .
				",(35,' ','',0,1)"
																		,$ErrorMsg);
//////////////////////////////////////////////////////////////////////
				// пытаемся создать таблицу объектов недвижимости
				if(! $ErrorMsg)	CreateTable("Estate",	"Eid int unsigned AUTO_INCREMENT PRIMARY KEY" .
															",regDate datetime" .		// дата ввода
															",Uid int unsigned" .		// ид. пользователя - владельца
															",etype char(1)"	.		// ид.типа недвижимости: дом / часть дома / квартира / комната
															",S DECIMAL(6,2)" .			// площадь
															",address varchar(200)" .	// адрес
															",region varchar(50)" .		//район
															",komnat tinyint" .			// количество комнат	 !
															",Skuh DECIMAL(6,2) DEFAULT 0.0" .	// площадь кухни
															",gaz char(1) DEFAULT ''" .		// газ: есть / нет	:	" " / "Д" / "Н"
															",elektro char(1) DEFAULT ''" .	// электричество: есть / нет	:	" " / "Д" / "Н"
															",voda char(1) DEFAULT ''" .	// вода: нет / скважина / центральное	:	" " / "Н" / "С" / "Ц"
															",kanal char(1) DEFAULT ''" .	// канализация: нет / местная / центральная	:	" " / "Н" / "М" / "Ц"
															",gvoda char(1) DEFAULT ''" .	// горячее вода: нет / газовая колонка или котёл / центральное	: " " / "Н" / "Г" / "Ц"
															",otoplen char(1) DEFAULT ''" .	// отопление: печь / котел или АОГВ / центральное / центр.с терморегуляцией: " " / "Н" / "К" / "Ц" / "Т"
															",mater char(1) DEFAULT ''" .	// материал: дерево / кирпич / бетон / монолит / пеноблок / сэндвич-панель : " " / "Д" / "К" / "Б" / "М" / "П" / "С"
															",sanuzel char(1) DEFAULT ''" .	// санузел: нет (во дворе) / только туалет / совмещён / раздельный	:	" " / "Н" / "Т" / "С" / "Р"
															",potolok char(1) DEFAULT ''" .	// высота потолка: до 270см / более 270см	:	" " / "Н" / "В"
															",Svanna DECIMAL(6,2) DEFAULT 0.0" .	// площадь ванной комнаты
															",Sbalkon DECIMAL(6,2) DEFAULT 0.0" .	// площадь балкона (лоджии)
															",tambur char(1) DEFAULT ''" .	// наличие тамбура перед квартирой	:	" " / "Д" / "Н"
															",lift char(1) DEFAULT ''" .	// лифт: есть / нет	:	" " / "Д" / "Н"
															",magaz char(1) DEFAULT ''" .	// наличие магазинов, офисов, баров на нижних этажах: есть / нет	 :	" " / "Д" / "Н"
															",etajej tinyint DEFAULT 1" .	// всего этажей в строении
															",etaj tinyint DEFAULT 1" .		// этаж - для квартиры
															",Spodval DECIMAL(6,2) DEFAULT 0.0" .		// площадь подвала (цокольного этажа)
															",Szemlia DECIMAL(10,2) DEFAULT 0.0" .			// площадь участка
															",sovlad tinyint DEFAULT 0" .	// количество совладельцев для части дома
															",Sstroen DECIMAL(6,2) DEFAULT 0.0" .			// общая площадь строения для части дома
															",sarai char(1) DEFAULT ''" .	// надворные постройки для дома или части дома: есть / нет	 :	" " / "Д" / "Н"
															",parking char(1) DEFAULT ''" .	// парковка: нет / есть / наземная / подземная / свой гараж	:	" " / "Н" / "Е" / "З" / "П" / "Г"
															",ograd char(1) DEFAULT ''" .	// огороженность: есть / нет	 :	" " / "Д" / "Н"
															",bezopas char(1) DEFAULT ''" .	// безопасность: нет / домофон / видеонаблюдение / ОВО	:	" " / "Н" / "Д" / "В" / "О"
															",otdelka char(1) DEFAULT ''" .	// отделка: нет / стандарт / высококачественная / эксклюзив	: " " / "С" / "В" / "Э"
															",okna char(1) DEFAULT ''" .	// окна: старые деревянные рамы / пластиковый стеклопакет / деревянный стеклопакет	:	" " / "С" / "П" / "Д"
															",remont char(1) DEFAULT ''" .	// нужен ремонт: нет / косметический / серьёзный	 :	" " / "Н" / "К" / "С"
															",nezakon char(1) DEFAULT ''" .	// неоформленная перепланировка: есть / нет	 : " " / "Д" / "Н"
															",mebel char(1) DEFAULT ''" .	// меблированное помещение: нет / частично / полно	:	" " / "Ч" / "П"
															",vidokna char(1) DEFAULT ''" .	// вид из окна: двор / завод / дорога / природа	:	" " / "В" / "З" / "Д" / "П"
															",gotov char(1) DEFAULT ''" .	// готовность документов: хоть сегодня / нужно время	:	" " / "Д" / "Н"
															",note varchar(2000) DEFAULT ''".	// расширенная информация
															",price int unsigned" .			//цена
															",sell char(1) DEFAULT ''" .	//продажа
															",rent char(1) DEFAULT ''" .	//сдача в аренду
															",state tinyint unsigned DEFAULT 0". //состояние 0-не опубликовано., 1-активно, 2-заблокировано, 3-заархивировано
															",AutoPrice int unsigned NULL".	//авто-оценка системой
															",RealPrice int unsigned NULL".	//оценка риэлтором
															",lastUid int unsigned"			// ид. пользователя, внесшего последние изменения - для регистрации в лог триггером
																							,$ErrorMsg);
				if(! $ErrorMsg)
					RunQuery("CREATE TRIGGER Estate_Bef_Ins BEFORE INSERT ON Estate FOR EACH ROW BEGIN ".
										"SET NEW.regDate=now(); " .
										"IF NEW.Uid=0 THEN SET NEW.Uid=NEW.lastUid; END IF; ".
									"END;"				, "создания Before-Insert-триггера для таблицы Estate", $ErrorMsg);
				if(! $ErrorMsg)
					RunQuery("CREATE TRIGGER Estate_Aft_Ins AFTER INSERT ON Estate FOR EACH ROW BEGIN ".
					"IF NEW.lastUid=NEW.Uid THEN SET @kto='САМОСТОЯТЕЛЬНО'; ".
					"ELSE SELECT CONCAT(uName,'(',Uid,')') INTO @kto FROM logins WHERE Uid=NEW.lastUid; ".
					"END IF; ".
					"SET @msg=CONCAT(@kto,': [тип: ',IFNULL(NEW.etype,'?'),'], [S: ',IFNULL(NEW.S,'?'),'], [адрес: ',IFNULL(NEW.address,'?'),'], ".
						"[район: ',IFNULL(NEW.region,'?'),'], [цена: ',IFNULL(NEW.price,'?'),'], [комнат: ',IFNULL(NEW.komnat,'?'),'], ".
						"[Sкухни: ',NEW.Skuh, '], [газ: ',NEW.gaz,'], [электро: ',NEW.elektro,'], [вода: ',NEW.voda,'], [канализ: ',NEW.kanal,'], ".
						"[гор.вода: ',NEW.gvoda,'], [отопление: ',NEW.otoplen,'], [материал: ',NEW.mater,'], [санузел: ',NEW.sanuzel,'], ".
						"[потолок: ',NEW.potolok,'], [Sванны: ',NEW.Svanna,'], [Sбалкона: ',NEW.Sbalkon,'], [тамбур: ',NEW.tambur,'], ".
						"[лифт: ',NEW.lift,'], [бары: ',NEW.magaz,'], [этажей: ',NEW.etajej,'], [этаж: ',NEW.etaj,'], [Sподвала: ',NEW.Spodval,'], ".
						"[Sземли: ',NEW.Szemlia,'], [совлад.: ',NEW.sovlad,'], [Sстроения: ',NEW.Sstroen,'], [сарай: ',NEW.sarai,'], ".
						"[парковка: ',NEW.parking,'] [огражден: ',NEW.ograd,'], [безопасн: ',NEW.bezopas,'], [отделка: ',NEW.otdelka,'], ".
						"[окна: ',NEW.okna,'], [ремонт: ',NEW.remont,'], [неоформл: ',NEW.nezakon,'], [мебель: ',NEW.mebel,'], ".
						"[изОкна: ',NEW.vidokna,'], [готовность: ',NEW.gotov,'], [что: ',NEW.sell,NEW.rent,'], [примечание: ',NEW.note,'], ".
						"[AutoPrice: ',IFNULL(NEW.AutoPrice,'?'),'], [RealPrice: ',IFNULL(NEW.RealPrice,'?'),'], [State: ',NEW.state,']'); ".
					"INSERT INTO Log(oDate,Uid,Oid,Rid,info)VALUES(NEW.regDate,NEW.Uid,5,0,@msg); " .
				"END;"				, "создания After-Insert-триггера для таблицы Estate", $ErrorMsg);
				if(! $ErrorMsg)
					RunQuery("CREATE TRIGGER Estate_Aft_Upd AFTER UPDATE ON Estate FOR EACH ROW BEGIN ".
					"IF NEW.lastUid=NEW.Uid THEN SET @kto='САМОСТОЯТЕЛЬНО'; ".
					"ELSE SELECT CONCAT(uName,'(',Uid,')') INTO @kto FROM logins WHERE Uid=NEW.lastUid; ".
					"END IF; ".
					"SET @msg=''; ".
					"IF IFNULL(NEW.etype,'?')<>IFNULL(OLD.etype,'?') THEN SET @msg=CONCAT(@msg, ', [S: ',IFNULL(OLD.etype,'?'),' ⇒ ',IFNULL(NEW.etype,'?'),']'); END IF; ".
					"IF IFNULL(NEW.S,'?')<>IFNULL(OLD.S,'?') THEN SET @msg=CONCAT(@msg, ', [S: ',IFNULL(OLD.S,'?'),' ⇒ ',IFNULL(NEW.S,'?'),']'); END IF; ".
					"IF IFNULL(NEW.address,'?')<>IFNULL(OLD.address,'?') THEN SET @msg=CONCAT(@msg, ', [адрес: ',IFNULL(OLD.address,'?'),' ⇒ ',IFNULL(NEW.address,'?'),']'); END IF; ".
					"IF IFNULL(NEW.region,'?')<>IFNULL(OLD.region,'?') THEN SET @msg=CONCAT(@msg, ', [район: ',IFNULL(OLD.region,'?'),' ⇒ ',IFNULL(NEW.region,'?'),']'); END IF; ".
					"IF IFNULL(NEW.price,'?')<>IFNULL(OLD.price,'?') THEN SET @msg=CONCAT(@msg, ', [цена: ',IFNULL(OLD.price,'?'),' ⇒ ',IFNULL(NEW.price,'?'),']'); END IF; ".
					"IF IFNULL(NEW.komnat,'?')<>IFNULL(OLD.komnat,'?') THEN SET @msg=CONCAT(@msg, ', [комнат: ',IFNULL(OLD.komnat,'?'),' ⇒ ',IFNULL(NEW.komnat,'?'),']'); END IF; ".
					"IF NEW.Skuh<>OLD.Skuh THEN SET @msg=CONCAT(@msg, ', [Sкухни: ',OLD.Skuh,' ⇒ ',NEW.Skuh,']'); END IF; ".
					"IF NEW.gaz<>OLD.gaz THEN SET @msg=CONCAT(@msg, ', [газ: ',OLD.gaz,' ⇒ ',NEW.gaz,']'); END IF; ".
					"IF NEW.elektro<>OLD.elektro THEN SET @msg=CONCAT(@msg, ', [электро: ',OLD.elektro,' ⇒ ',NEW.elektro,']'); END IF; ".
					"IF NEW.voda<>OLD.voda THEN SET @msg=CONCAT(@msg, ', [вода: ',OLD.voda,' ⇒ ',NEW.voda,']'); END IF; ".
					"IF NEW.kanal<>OLD.kanal THEN SET @msg=CONCAT(@msg, ', [канализ: ',OLD.kanal,' ⇒ ',NEW.kanal,']'); END IF; ".
					"IF NEW.gvoda<>OLD.gvoda THEN SET @msg=CONCAT(@msg, ', [гор.вода: ',OLD.gvoda,' ⇒ ',NEW.gvoda,']'); END IF; ".
					"IF NEW.otoplen<>OLD.otoplen THEN SET @msg=CONCAT(@msg, ', [отопление: ',OLD.otoplen,' ⇒ ',NEW.otoplen,']'); END IF; ".
					"IF NEW.mater<>OLD.mater THEN SET @msg=CONCAT(@msg, ', [материал: ',OLD.mater,' ⇒ ',NEW.mater,']'); END IF; ".
					"IF NEW.sanuzel<>OLD.sanuzel THEN SET @msg=CONCAT(@msg, ', [санузел: ',OLD.sanuzel,' ⇒ ',NEW.sanuzel,']'); END IF; ".
					"IF NEW.potolok<>OLD.potolok THEN SET @msg=CONCAT(@msg, ', [потолок: ',OLD.potolok,' ⇒ ',NEW.potolok,']'); END IF; ".
					"IF NEW.Svanna<>OLD.Svanna THEN SET @msg=CONCAT(@msg, ', [Sванны: ',OLD.Svanna,' ⇒ ',NEW.Svanna,']'); END IF; ".
					"IF NEW.Sbalkon<>OLD.Sbalkon THEN SET @msg=CONCAT(@msg, ', [Sбалкона: ',OLD.Sbalkon,' ⇒ ',NEW.Sbalkon,']'); END IF; ".
					"IF NEW.tambur<>OLD.tambur THEN SET @msg=CONCAT(@msg, ', [тамбур: ',OLD.tambur,' ⇒ ',NEW.tambur,']'); END IF; ".
					"IF NEW.lift<>OLD.lift THEN SET @msg=CONCAT(@msg, ', [лифт: ',OLD.lift,' ⇒ ',NEW.lift,']'); END IF; ".
					"IF NEW.magaz<>OLD.magaz THEN SET @msg=CONCAT(@msg, ', [бары: ',OLD.magaz,' ⇒ ',NEW.magaz,']'); END IF; ".
					"IF NEW.etajej<>OLD.etajej THEN SET @msg=CONCAT(@msg, ', [этажей: ',OLD.etajej,' ⇒ ',NEW.etajej,']'); END IF; ".
					"IF NEW.etaj<>OLD.etaj THEN SET @msg=CONCAT(@msg, ', [этаж: ',OLD.etaj,' ⇒ ',NEW.etaj,']'); END IF; ".
					"IF NEW.Spodval<>OLD.Spodval THEN SET @msg=CONCAT(@msg, ', [Sподвала: ',OLD.Spodval,' ⇒ ',NEW.Spodval,']'); END IF; ".
					"IF NEW.Szemlia<>OLD.Szemlia THEN SET @msg=CONCAT(@msg, ', [Sземли: ',OLD.Szemlia,' ⇒ ',NEW.Szemlia,']'); END IF; ".
					"IF NEW.sovlad<>OLD.sovlad THEN SET @msg=CONCAT(@msg, ', [совлад: ',OLD.sovlad,' ⇒ ',NEW.sovlad,']'); END IF; ".
					"IF NEW.Sstroen<>OLD.Sstroen THEN SET @msg=CONCAT(@msg, ', [Sстроения: ',OLD.Sstroen,' ⇒ ',NEW.Sstroen,']'); END IF; ".
					"IF NEW.sarai<>OLD.sarai THEN SET @msg=CONCAT(@msg, ', [сарай: ',OLD.sarai,' ⇒ ',NEW.sarai,']'); END IF; ".
					"IF NEW.parking<>OLD.parking THEN SET @msg=CONCAT(@msg, ', [парковка: ',OLD.parking,' ⇒ ',NEW.parking,']'); END IF; ".
					"IF NEW.ograd<>OLD.ograd THEN SET @msg=CONCAT(@msg, ', [огражден: ',OLD.ograd,' ⇒ ',NEW.ograd,']'); END IF; ".
					"IF NEW.bezopas<>OLD.bezopas THEN SET @msg=CONCAT(@msg, ', [безопасн: ',OLD.bezopas,' ⇒ ',NEW.bezopas,']'); END IF; ".
					"IF NEW.otdelka<>OLD.otdelka THEN SET @msg=CONCAT(@msg, ', [отделка: ',OLD.otdelka,' ⇒ ',NEW.otdelka,']'); END IF; ".
					"IF NEW.okna<>OLD.okna THEN SET @msg=CONCAT(@msg, ', [окна: ',OLD.okna,' ⇒ ',NEW.okna,']'); END IF; ".
					"IF NEW.remont<>OLD.remont THEN SET @msg=CONCAT(@msg, ', [ремонт: ',OLD.remont,' ⇒ ',NEW.remont,']'); END IF; ".
					"IF NEW.nezakon<>OLD.nezakon THEN SET @msg=CONCAT(@msg, ', [неоформл: ',OLD.nezakon,' ⇒ ',NEW.nezakon,']'); END IF; ".
					"IF NEW.mebel<>OLD.mebel THEN SET @msg=CONCAT(@msg, ', [мебель: ',OLD.mebel,' ⇒ ',NEW.mebel,']'); END IF; ".
					"IF NEW.vidokna<>OLD.vidokna THEN SET @msg=CONCAT(@msg, ', [изОкна: ',OLD.vidokna,' ⇒ ',NEW.vidokna,']'); END IF; ".
					"IF NEW.gotov<>OLD.gotov THEN SET @msg=CONCAT(@msg, ', [готовность: ',OLD.gotov,' ⇒ ',NEW.gotov,']'); END IF; ".
					"IF CONCAT(NEW.sell,NEW.rent)<>CONCAT(OLD.sell,OLD.rent) THEN SET @msg=CONCAT(@msg, ', [что: ',OLD.sell,OLD.rent,' ⇒ ',NEW.sell,NEW.rent,']'); END IF; ".
					"IF NEW.note<>OLD.note THEN SET @msg=CONCAT(@msg, ', [примечание: ',OLD.note,' ⇒ ',NEW.note,']'); END IF; ".
					"IF IFNULL(NEW.AutoPrice,'?')<>IFNULL(OLD.AutoPrice,'?') THEN SET @msg=CONCAT(@msg, ', [AutoPrice: ',IFNULL(OLD.AutoPrice,'?'),' ⇒ ',IFNULL(NEW.AutoPrice,'?'),']'); END IF; ".
					"IF IFNULL(NEW.RealPrice,'?')<>IFNULL(OLD.RealPrice,'?') THEN SET @msg=CONCAT(@msg, ', [RealPrice: ',IFNULL(OLD.RealPrice,'?'),' ⇒ ',IFNULL(NEW.RealPrice,'?'),']'); END IF; ".
					"IF NEW.state<>OLD.state THEN SET @msg=CONCAT(@msg, ', [State: ',OLD.state,' ⇒ ',NEW.state,']'); END IF; ".
					"IF @msg<>'' THEN INSERT INTO Log(oDate,Uid,Oid,Rid,info)VALUES(now(),NEW.Uid,6,0,CONCAT(@kto,': ',SUBSTRING(@msg,3))); END IF; ".
				"END;"			, "создания After-Update-триггера для таблицы Estate", $ErrorMsg);
////////////////////////////////////////////////////////////////////////
				// пытаемся создать таблицу назначения объектов недвижимости для обработки риэлтеру
				if(! $ErrorMsg)	CreateTable('LinkEstateSotr', "Lid int unsigned AUTO_INCREMENT PRIMARY KEY" .
																			",regDate datetime" .	// дата привязки
																			",Eid int unsigned" .	// ид. объекта недвижимости
																			",Uid int unsigned"	.	// ид. сотрудника
																			",SetUid int unsigned"	// ид. сотрудника, кто привязал
																									,$ErrorMsg);
				if(! $ErrorMsg)
					RunQuery("CREATE TRIGGER LinkEstateSotr_Bef_Ins BEFORE INSERT ON LinkEstateSotr FOR EACH ROW BEGIN ".
										"SET NEW.regDate=now(); " .
									"END;"			, "создания Before-Insert-триггера для таблицы LinkEstateSotr", $ErrorMsg);
				if(! $ErrorMsg)
					RunQuery("CREATE TRIGGER LinkEstateSotr_Aft_Ins AFTER INSERT ON LinkEstateSotr FOR EACH ROW BEGIN ".
										"SELECT CONCAT('[Eid=',NEW.Eid,'] - [Сотрудник=',uName,'(',Uid,')]') INTO @msg FROM logins WHERE Uid=NEW.Uid; " .
										"INSERT INTO Log(oDate,Uid,Oid,Rid,info)VALUES(NEW.regDate,NEW.SetUid,7,0,@msg); " .
									"END;"			, "создания After-Insert-триггера для таблицы LinkEstateSotr", $ErrorMsg);
				if(! $ErrorMsg)
					RunQuery("CREATE TRIGGER LinkEstateSotr_Aft_Upd AFTER UPDATE ON LinkEstateSotr FOR EACH ROW BEGIN ".
										"SET @msg=''; ".
										"IF NEW.Uid<>OLD.Uid THEN ".
											"SELECT CONCAT('[Eid=',NEW.Eid,'] - [Сотрудник: ',uName,'(',Uid,') ⇒ ') INTO @msg FROM logins WHERE Uid=OLD.Uid; " .
											"SELECT CONCAT(@msg, uName,'(',Uid,')]') INTO @msg FROM logins WHERE Uid=NEW.Uid; ".
										"END IF; ".
										"IF @msg<>'' THEN INSERT INTO Log(oDate,Uid,Oid,Rid,info)VALUES(now(),NEW.SetUid,7,0,CONCAT(@kto,': ',SUBSTRING(@msg,3))); END IF; ".
									"END;"			, "создания After-Update-триггера для таблицы LinkEstateSotr", $ErrorMsg);
////////////////////////////////////////////////////////////////////////
				// создание таблицы средней по регионам стоимости кв.м жилья и сотки земли
				if(! $ErrorMsg)	CreateTable('CostRegion', "rid smallint NOT NULL, region varchar(100) NOT NULL PRIMARY KEY, Cost_m2 int, Cost_Sotka int, DateA date NULL", $ErrorMsg);
				if(! $ErrorMsg)	InsertTable("CostRegion", "rid,region,Cost_m2,Cost_Sotka", 
						"(01,'Респ Адыгея',0,0),				(02,'Респ Башкортостан',0,0),		(03,'Респ Бурятия',0,0),".
						"(04,'Респ Алтай',0,0),					(05,'Респ Дагестан',0,0),			(06,'Респ Ингушетия',0,0),".
						"(07,'Респ Кабардино-Балкарская',0,0),	(08,'Респ Калмыкия',0,0),			(09,'Респ Карачаево-Черкесская',0,0),".
						"(10,'Респ Карелия',0,0),				(11,'Респ Коми',0,0),				(12,'Респ Марий Эл',0,0),".
						"(13,'Респ Мордовия',0,0),				(14,'Респ Саха /Якутия/',0,0),		(15,'Респ Северная Осетия - Алания',0,0),".
						"(16,'Респ Татарстан (Татарстан)',0,0),	(17,'Респ Тыва',0,0),				(18,'Респ Удмуртская',0,0),".
						"(19,'Респ Хакасия',0,0),				(20,'Респ Чеченская',0,0),			(21,'Респ Чувашская (Чувашия)',0,0),".
						"(22,'край Алтайский',0,0),				(23,'край Краснодарский',0,0),		(24,'край Красноярский',0,0),".
						"(25,'край Приморский',0,0),			(26,'край Ставропольский',0,0),		(27,'край Хабаровский',0,0),".
						"(28,'обл Амурская',0,0),				(29,'обл Архангельская',0,0),		(30,'обл Астраханская',0,0),".
						"(31,'обл Белгородская',0,0),			(32,'обл Брянская',0,0),			(33,'обл Владимирская',0,0),".
						"(34,'обл Волгоградская',0,0),			(35,'обл Вологодская',0,0),			(36,'обл Воронежская',40251,33333),".
						"(37,'обл Ивановская',0,0),				(38,'обл Иркутская',0,0),			(39,'обл Калининградская',0,0),".
						"(40,'обл Калужская',0,0),				(41,'край Камчатский',0,0),			(42,'обл Кемеровская область - Кузбасс',0,0),".
						"(43,'обл Кировская',0,0),				(44,'обл Костромская',0,0),			(45,'обл Курганская',0,0),".
						"(46,'обл Курская',0,0),				(47,'обл Ленинградская',0,0),		(48,'обл Липецкая',0,0),".
						"(49,'обл Магаданская',0,0),			(50,'обл Московская',0,0),			(51,'обл Мурманская',0,0),".
						"(52,'обл Нижегородская',0,0),			(53,'обл Новгородская',0,0),		(54,'обл Новосибирская',0,0),".
						"(55,'обл Омская',0,0),					(56,'обл Оренбургская',0,0),		(57,'обл Орловская',0,0),".
						"(58,'обл Пензенская',0,0),				(59,'край Пермский',0,0),			(60,'обл Псковская',0,0),".
						"(61,'обл Ростовская',0,0),				(62,'обл Рязанская',0,0),			(63,'обл Самарская',0,0),".
						"(64,'обл Саратовская',0,0),			(65,'обл Сахалинская',0,0),			(66,'обл Свердловская',0,0),".
						"(67,'обл Смоленская',0,0),				(68,'обл Тамбовская',35490,22821),	(69,'обл Тверская',0,0),".
						"(70,'обл Томская',0,0),				(71,'обл Тульская',0,0),			(72,'обл Тюменская',0,0),".
						"(73,'обл Ульяновская',0,0),			(74,'обл Челябинская',0,0),			(75,'край Забайкальский',0,0),".
						"(76,'обл Ярославская',0,0),			(77,'г Москва',0,0),				(78,'г Санкт-Петербург',0,0),".
						"(79,'Аобл Еврейская',0,0),				(83,'АО Ненецкий',0,0),				(86,'АО Ханты-Мансийский автономный округ - Югра',0,0),".
						"(87,'АО Чукотский',0,0),				(89,'АО Ямало-Ненецкий',0,0),		(91,'Респ Крым',0,0),".
						"(92,'г Севастополь',0,0),				(99,'г Байконур',0,0)" ,$ErrorMsg);
//////////////////////////////////////////////////////////////////////
				// создание таблицы фотографий объектов недвижимости
				if(! $ErrorMsg)	CreateTable('PhotoEstate',	"Eid int unsigned" .		// ид.объекта недвижимости
															",num tinyint unsigned".	// порядковый номер фотки
															",img MEDIUMBLOB" .				// тело рисунка
															",PRIMARY KEY(Eid,num)"		// первичный ключ
																					,$ErrorMsg);
				if(! $ErrorMsg)	// создаём процедуру ImageSaveGet - для сохранения и/или чтения картинки объекта недвижимости
					RunQuery("CREATE PROCEDURE ImageSaveGet(IN _UserId int unsigned, IN _Eid int unsigned, IN _num tinyint, IN _img MEDIUMBLOB) BEGIN"
							." DECLARE _bimg MEDIUMBLOB; DECLARE _txt varchar(100); DECLARE _chgd varchar(1);"
							." SET _txt = ''; SET _chgd = '';"
							." SELECT img INTO _bimg FROM PhotoEstate WHERE Eid = _Eid AND num = _num;"
							." IF _img = 'READ' THEN"
							."	SET _img = IFNULL(_bimg,'');"
							." ELSEIF _bimg IS NULL AND _img <> '' THEN"
							."	INSERT INTO PhotoEstate(Eid,num,img)VALUES(_Eid,_num,_img);"
							."	SET _txt = CONCAT('Добавление для объекта Eid=',_Eid,' фото № ',_num); SET _chgd = 'I';"
							." ELSEIF _bimg IS NOT NULL AND _img = '' THEN"
							."	DELETE FROM PhotoEstate WHERE Eid = _Eid AND num = _num;"
							."	SET _txt = CONCAT('Удаление для объекта Eid=',_Eid,' фото № ',_num); SET _chgd = 'D';"
							." ELSEIF _bimg IS NOT NULL AND _img <> '' AND _bimg <> _img THEN"
							."	UPDATE PhotoEstate SET img = _img WHERE Eid = _Eid AND num = _num;"
							."	SET _txt = CONCAT('Замена для объекта Eid=',_Eid,' фото № ',_num); SET _chgd = 'U';"
							." END IF;"
							." IF _txt <> '' THEN CALL WriteLog(_UserId,8,0,_txt); END IF;"
							." SELECT _img AS img, _chgd AS chgd;"
							." END;"			, "создания процедуры ImageSaveGet", $ErrorMsg);
//////////////////////////////////////////////////////////////////////
				if(! $ErrorMsg){	//всё ок, записать настроечные данные в файл, чтобы наше приложение могло их использовать
					// __FILE__ - маршрут нашего скрипта в файловой системе, типа 	d:\xampp/htdocs/MyApp/Curs2020/setup/index.php
					$fn = str_replace('/', '\\', dirname(__FILE__));	//заменить символы / на символ \ . Получим:	d:\xampp\htdocs\MyApp\Curs2020\setup
					$fn = substr($fn, 0, strrpos($fn, '\\')) . '\\inc';		// обрежем, получим: d:\xampp\htdocs\MyApp\Curs2020 и допишем \inc . Будет: d:\xampp\htdocs\MyApp\Curs2020\inc
					$fn .= '\\rielter.ini';	// получаем: d:\xampp\htdocs\MyApp\Curs2020\inc\rielter.ini
					if(PHP_OS <> 'WINNT')	//если сервер на линуксе исправляем в маршрутах слэш "\" на "/"
						$fn = str_replace('\\','/',$fn);
					$f = fopen($fn, 'wb');	 //открываем файл для записи, бинарно
					if(! $f)	 //ошибка создания файла
						$ErrorMsg = '<tr><th id="Err" colspan="3">При попытке создания файла настроек ("'.$fn.'") произошла ошибка.<br>'.
								'<br>Дальнейшая работа невозможна.</th></tr>';
					else{
						fwrite($f,	"<?php\n\r".
										"$"."AppName='Rielter-v.1';\n\r".
										"$"."AppAddr='$MyAddr';\n\r".
										"$"."AppUsr='RielterUsr';\n\r".
										'$'."AppPsw='AppPassWord';\n\r".
										'$'."AppDB='DB_RIELTER';\n\r".
										'$'."AppFirm='$firm';\n\r".
										"?>");
						fclose($f);
						session_start();
						$_SESSION['AppName'] = 'Rielter-v.1';
						$_SESSION['UserID'] = '1';
						$_SESSION['UserLogin'] = 'Admin';
						$_SESSION['UserRights'] = 0xFFFFFFFF;
						$ErrorMsg = '<tr><th id="Err" colspan="3">Ура! БД создана! Настройки сохранены!<br>' .
							'<br>Внимание: Администратору системы назначен логин "Admin" и пароль "Admin"' .
							'<br>Пройдите по ссылке в <a href="../l-k/lk.php">личный кабинет</a> для изменения параметров Вашего аккаунта.</th></tr>';
					}//if(! $f)else
				}//if(! $ErrorMsg)
			}//if($CharSet)else
		}//if(! $conn)else
	}//if($MyAddr)

	echo	$BeginPage;
?>
	<img src="favicon.png" style="position:absolute;height:80px;" />
	<div align="center" style="font:bold italic 15pt 'Times New Roman'"><br>УСТАНОВКА И НАСТРОЙКА БАЗЫ ДАННЫХ<br>ДЛЯ РИЭЛТЕРСКОЙ ФИРМЫ<br>&nbsp;</div>
	<table id="tbl" align="center">
		<tr><th colspan="3">Для начала процедуры установки и настройки базы данных Вы должны ввести необходимые для подключения к серверу MySQL данные:</th></tr>
		<tr><td width="300">Адрес или имя MySQL-сервера:</td>
				<td width="200"><input id="MyAddr" name="MyAddr" form="SetupForm" value="<?=$MyAddr?>" <?=$dsbl?> tabindex="1" autofocus /></td>
				<td rowspan="4"><button class="BtnBig" <?=$dsbl?> tabindex="5" onclick="Btn(this);">Начать</button></td></tr>
		<tr><td>Логин пользователя с правами на создание базы данных и аккаунтов пользователей:</td>
				<td><input id="root" name="root" form="SetupForm" value="<?=$root?>" <?=$dsbl?> tabindex="2" /></td></tr>
		<tr><td>Пароль этого пользователя:</td><td><input id="rPsw" name="rPsw" type="password" form="SetupForm" value="<?=$rPsw?>" <?=$dsbl?> tabindex="3" /></td></tr>
		<tr><td>Наименование фирмы:</td><td><input id="firm" name="firm" form="SetupForm" value="<?=$firm?>" <?=$dsbl?> tabindex="4"  /></td></tr>
		<tr><td></td><td></td><td></td></tr><?=$ErrorMsg?>
	</table>
</body>
<script src="../js/public.js"></script>
<script>
function	 Btn(obj){
	switch(obj.innerHTML){
		case "Начать":
			var ErrMsg = "";
			with(getObj("MyAddr")){
				value = value.trim();	if(!value) ErrMsg += "\nПоле адреса/имени MySQL-сервера не заполнено.";
			}
			with(getObj("root")){
				value = value.trim();	if(!value) ErrMsg += "\nПоле логина суперпользователя не заполнено.";
			}
			with(getObj("rPsw"))	value = value.trim();
			with(getObj("firm")){
				value = value.trim();	if(!value) ErrMsg += "\nПоле наименования предприятия не заполнено.";
			}
			if(ErrMsg)	{
				alert("Необходимо заполнить все поля!\n"+ErrMsg);	return;
			}else{
			}
			break;
		case "Останов":
			getObj("drop").value = "no";
			break;
		case "Переустановить":
			getObj("drop").value = "drop";
			break;
	}
	getObj("tbl").style.display = "none";
	getObj("MyAddr").disabled = false;
	getObj("root").disabled = false;
	getObj("rPsw").disabled = false;
	getObj("firm").disabled = false;
	SetupForm.submit();
}
window.onload=function(){
	Set_EnterEqTab();
}
</script>
</html>
