<?php $Home=''; $say = 'Система пакетного сканирования v5.0'; //2024.02.05
include('lib.inc'); //сервис
include('set.inc'); //настройки

if (isset($_POST['com'])) { // Обработчик команд
	try { 
		switch ($_POST['com']) {
		case "Сохранить" : // отправить данные формы на сервер 
			if( isset($_POST['bats']) ) { // принять список пакетов данных и сохранить
				foreach ( json_decode($_POST['bats'],TRUE) as $_POST )  {
					if (isset($_POST['fact'])) db_insert($_POST['fact']);
					if ($units) echo ' Сохранено:'.$units;
				}
				exit;
			}	
			if( isset($_POST['bat']) ) { // принять пакет данных и сохранить
				$_POST = json_decode($_POST['bat'],TRUE);
				if (isset($_POST['fact'])) {
					db_insert($_POST['fact']);
					if ($units) echo ' Сохранено:'.$units;
				} 
				exit;
			}
			// по запросу из формы и подготовка ответной формы
			$say = '';
			if ($_POST['list1']==='' AND $_POST['replace'] != 'true' ) {
				$say = 'не отравлено, список пуст!';
				break;
			}
			if ($_POST['replace']=='true') {
				$say = 'c заменой по отбору'; 
				db_delete($_POST['fact']);
			}
			db_insert($_POST['fact']);
			$list1=" ";
			$say.= ' cохранено в '.$_POST['cell'].'='.$units;
			break;
		case "Получить" : // Получить данные формы из базы
			if( isset($_POST['JSON']) ) { // отправить пакет данных
				$rez = db_select($_POST['fact']);
				header("Content-Type: application/json; charset=utf-8");
				echo json_encode( array('list' => $rez , 'units' => $units, 'cell' => $_POST['cell']) );
				exit;
			}
			if($_POST['fact']==1) {
				if (! $_POST['list1']==='') {
					$say = 'Сначала отправте или очистите список!'; 
					$list1 = $_POST['list1'];
					$list0 = $_POST['list0']; 
					break;
				}
				$list1 = db_select(1);
			}else	$list0 = db_select($_POST['fact']);
			$say = 'Получено:'.$units;
			break;
		case "Сверка" : // отправить факт, получить учет и сравнить, разницу вывести по всей ячейке
			$_POST['id']=''; 
			$_POST['user']='';
			$rez = list_inventar_fast(db_select(1),db_select(0));
			if( isset($_POST['JSON']) ) { // отправить пакет данных
				header("Content-Type: application/json; charset=utf-8");
				echo json_encode( array('list' => $rez , 'say' => $say, 'cell' => $_POST['cell']) );
				exit;
			}
			break;
		case "Где" : // ячейка по ШК 
			$last_kod = mb_substr(",".list_clear($_POST['list1']),-12,11);
			$p1 = strpos($last_kod,","); //первый разделитель
			if ( $p1===false ) $say = "не указан ШК!".$last_kod;
			else { 
				$last_kod = str_replace(',','',mb_substr($last_kod,$p1+1));
				$sells = db_select('0','`cell`,`units`',"`list` like '%$last_kod%'");
				if ($sells == '') $sells= 'не найдено!';
				$say = "ШК $last_kod в: $sells";
				$list1 = $_POST['list1'];
			}
			if( isset($_POST['JSON']) ) { // отправить только ответ
				echo $say;
				exit;
			}
			break;
		case "Поиск" : // ячейки по списку ШК 
			$alist1 = array_unique(explode(",",list_clear($_POST['list1']) ) );//чистим, список в массив и удаляем дубли
			$say='F3 Поиск<br>';
			for ($i= 0; $i < count($alist1); $i ++) {
				if ($alist1[$i] != '') {
					$say .= $alist1[$i];
					$sells = db_select('1','`cell`,`units`',"`list` like '%$alist1[$i]%'");
					$say .= ' по факту';
					if ($sells == '') $say .= ' нет!';
					else $say .= ' в: '.$sells;
					$sells = db_select('0','`cell`,`units`',"`list` like '%$alist1[$i]%'");
					$say .= ' по учёту';
					if ($sells == '') $say .= ' нет!';
					else $say .= ' в: '.$sells.'<br>';
				}	
			}
			break;
		case "Карта" : // шт.ШК по ячейкам
			$cell_rest = $_POST['cell'];
			$sells = '';
			$units_total=0;
			for ($i= 0; $i < count($acell); $i ++) {
				$_POST['cell']=$acell[$i];
				db_select($_POST['fact']);
				if ($units > 0) {
					$sells.= " <button type='submit' name='com' value='Получить' onClick=\"return loadF('".$_POST['cell']."'); \" >".$_POST['cell']."=$units</button>";
					$units_total+=$units;
				}	
			}
			if ($units_total == 0) $sells= 'не найдено!';
			$say='Карта по учёту';
			if ($_POST['fact']) $say='Карта по факту';
			$say.= "=$units_total: $sells";
			$_POST['cell'] = $cell_rest;
			break;
		case "Скачать" : // Получить данные формы из базы
			$list=$_POST['list1'];
			$list.="\r\n".$_POST['list0'];
			header('Cache-control: private');
			header('Content-type: text/csv');//win-1251; charset=utf-8
			header('Content-disposition:  attachment; filename="'.$_POST['date'].'_'.$_POST['cell'].'_'.$_POST['id'].'_inv.csv"');
			print "N;".$_POST['cell'].";".$_POST['date'].";".$_POST['id'].";".$_POST['user'].PHP_EOL .str_replace('	',';',mb_convert_encoding($list, 'cp1251', 'utf-8')).PHP_EOL;
			exit;

		case "Выгрузить" : // Получить данные формы из базы		
			$l0 = list_clear(db_select(0,'`list`', 'true'));
			$list='';
			$tab=',';
			for ($i= 0; $i < count($acell); $i ++) {
				$_POST['cell']=$acell[$i];
				$l1 = list_clear(db_select($_POST['fact'],'`list`')); //,' and true '
				$p2=0;
				if ( strpos($l1,"	") > 0 ) $l1 = preg_filter("/[	]+[^\,]\,/",$tab,$l1);//удалим лишнее кроме 1 колнки с ШК	
				$loop = true;
				$p1 = strpos($l1,$tab); //первый разделитель
				if ( $p1 === false ) $loop = false;
				while ($loop) { 
					$r1 = mb_substr($l1,0,$p1); //вырезаем номер,
					$list.= $acell[$i].";";
					$p0 = strpos($l0,$r1);
					if ( $p0 === false ) $list.= $r1; // нет данных для группы
					else { // есть данные, формируем строку группы
						$p00 = strpos(mb_substr($l0,$p0),$tab); // конец строки					
						$list.= mb_substr($l0,$p0,$p00); // шк+доп
					}
					$list.=PHP_EOL;
					$l1 = str_replace($r1.$tab,'',$l1); // удалим в факте
					$p1 = strpos($l1,$tab); //следующий разделитель
					$p2+=1;
					if ( $p1 === false or $p2>5000 ) $loop = false; // сраховка зацикливания
				}
			}
			header('Cache-control: private');
			header('Content-type: text/csv');//win-1251; charset=utf-8
			header('Content-disposition:  attachment; filename="'.$_POST['date'].'_all_inv.csv"');
			print str_replace('	',';',mb_convert_encoding(	"Складское место;Единица обработки;Количество;Краткое описание продукта;Продукт	Формат;Заказ клиента/проект;Поз. заказа клиента;Базисная ЕИ;Вид запаса".PHP_EOL.$list.PHP_EOL, 'cp1251', 'utf-8'));
			exit;

		case "Печать" :   // сформировать печатную форму для результата сканирования
			include('print.php'); 	
			exit;
		
		case "Загрузить" : // Загрузить данные в базу из Эксель
			if($_FILES["filename"]["size"] > 5*1024*1024) {// Максимальный размер 
			  $say='Уменьшите размер файла загрузки - max 5Mb!';
			  break;
			}
			$filename = $_SERVER['DOCUMENT_ROOT']."/tmp/".$_FILES["filename"]["name"];
			if( copy($_FILES["filename"]["tmp_name"],$filename) )   {
				$say='Загрузка: ';
				//удалим старое за дату
				if ($_POST['replace']) {
					$say.= 'замена по дате '; 
					db_delete_all_date(0);
				}
				setlocale(LC_ALL, 'ru_RU.utf8');
				$file = fopen($filename, 'r');
				rewind($file);
				$r = 0; $u=0; $total=0; $cell='нет'; $list='';
				while ( ($row = fgetcsv($file, 0,';','~') ) != FALSE ) // $file - имя файла; 1000 - длина; ,(запятая) - это разделитель полей
				{
					$r++;
					if ($r>2 and count($row)>9 and $row[1]!='' and array_search($row[0], $acell, true) != FALSE ) {
						if ( ($row[0]==$cell or $cell=='нет') and $u < 500 ) {
							if ($cell=='нет') $cell=$row[0]; //новое МХ текщее	
						} else {
							$_POST['cell']=$cell; // что было в МХ
							$_POST['list1']=$list; //перенос накопленого списка в 1-й
							db_insert(0); // запись строки учета в базу
							$say.= ' '.$cell.'+'.$u;//.mysql_errno().mysql_error(); 
							$cell = $row[0]; // новое МХ текщее	
							$u = 0; //счетчик ШК в МХ
							$list='';//обнуление списка ШК
							//break;
						}
						$u++;
						$total++;
						// формируем список учета
						$list.= iconv('windows-1251', 'UTF-8',($row[1]."	".$row[2]."	".$row[3]."	".$row[4]."	".$row[5]."	".$row[6]."	".$row[7]."	".$row[8]."	".$row[9]))."\r\n";
					}
				}
				fclose($file);
				// обработка последнего
				if ($cell!='нет') {
					$_POST['cell']=$cell; // что было в МХ
					$_POST['list1']=$list; $list0=$list; $list1=''; //перенос накопленого списка в 1-й
					db_insert(0); // запись строки учета в базу
					$say.= ' '.$cell.'+'.$u;
				}	
				$say.='='.$total;  
			}   else   {
			  $say='Ошибка загрузки файла:'.$filename;
			}
			break;
		case "Справка" :			// получение описания системы
			$autofocus='';
			include('help.inc');	// подготовка описания системы
			$say = $f[0];
			$f[1]  = "Получить справку<br><br> <a href=$Home >Начать︎!</a><br><br>";																					   
			$f[2]  = 'Левый список Ф - Фактический для ввода сканером ШК';
			$f[3]  = 'Правый список У - Учётный для сравнения и поиска соответствия, защищен от изменения, заполняется кнопкой [Получить] или [Ф➞У]';
			$f[4]  = 'сохранить в хранилище пакета ШК из списка Факта<br>';
			$f[5]  = 'Найти для последнего ШК, где его МХ по учету<br>';
			$f[6]  = 'Очистить левый список Факт<br>';
			$f[7]  = 'Перенести список Факт в Учет<br>';
			$f[8]  = 'Заменить список Факт из Учета<br>';
			$f[9]  = 'Сравнить Факт со списком Учет в памяти<br>';
			$f[10]  = 'Получить из хранилища данные по параметрам отбора<br>';
			$f[11]  = 'Получить из хранилища шт. ЕО по МХ на дату<br>';
			$f[12]  = 'Сверить данные хранилища факт с учетом по параметрам<br>';
			$f[13]  = 'Скачать списки в файл Эксель<br>';
			$f[14]  = 'Получить последний сохранный список<br>';
			$f[15]  = 'Печатная форма ведомости факта с группировкой по учетным данным<br>';
			$f[16]  = 'Загрузить данные в базу из Эксель<br>порядок колонок важен: 1-МХ 2-ШК 3-вес, прочие... <br>сохраните как CSV(разделители-запятые)(.csv)<br>';
			$f[17]  = 'Очистить правый список Учёт<br>';
			$list1='';
			$list0='';
			break;
		} 
	}	
	catch (PDOException $e_stata) {
		$say = "Ошибка: " . $e_stata->POSTMessage(); 
	}
} 
include('head.htm');	//заголовок
include('body.htm');	//основа
include('js.htm'); 		//клиент
echo '</body></html>';	//конец
?>