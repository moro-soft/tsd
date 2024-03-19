<?php

function db_select($fact, $fld='`list`,`units`', $filtr='', $group= '') {	// выборка из базы с параметрами: типом, полями, отбором, группировкой
	global $db_seans,$db_table,$units;
	$q = "SELECT $fld FROM $db_table where true ";
	if ( $filtr !='') $q.="and $filtr " ;
	else {
		if ( $_POST['cell']!='') $q.="and `cell`='".$_POST['cell']."' ";
		if ( $fact != '0') {
			if ( $_POST['id']!='') $q.="and `id`='".$_POST['id']."' ";
			if ( $_POST['user']!='') $q.="and `user` like '%".str_replace(" ","",$_POST['user'])."%' ";
		}
	}
	$q.="and `fact`=$fact ";
	if ($_POST['date']!='') $q.="and `date`='".$_POST['date']."' ";
	if ($group!='') $q.="group by $group";
	$q = str_replace('true end','',$q);
	$st = $db_seans->prepare($q);
	$st -> execute();
	$z='';
	$units=0;
	if ( $group === '') {						// отбор ШК
		while ($r = $st->fetch()) 
			$z.= $r[0]."\r\n";					// клеим списки в один
		$a = array_unique(explode("\r\n", $z));	//удаляем дубли
		if ($fact < '3' )	sort($a);			//сортируем
		$a		= array_filter($a);				// убираем пустые
		$units	= count($a);					//считаем елементы
		$z		= implode("\r\n",$a);			// в список
	} elseif ( $group === '`cell`') {
		while ($r = $st->fetch()) {
			if ( $r[0] != '' and strpos($z,$r[0])===false ) {
				$z.= " <button type='submit' name='com' value='Получить' onClick=\"return loadF('$r[0]'); \" >$r[0]=$r[1]</button>";
				$units+= $r[1];
			}
		}
	}
	return $z;
}

function numToRead($num, $word_one= '', $word_mult= '', $word_med = '') {
    $num = intval($num);
    if		(!$word_med)	$word_med = $word_mult;
    if 		($num===1)		$ret = $word_one;
    elseif	($num===0)		$ret = $word_mult;
    elseif	(in_array(substr($num,-2),array(11,12,13,14)))	$ret = $word_mult;
    elseif 	($GLOBALS['lang']=='ru' && substr($num,-1)==1)	$ret = $word_one;
    elseif	(substr($num,-1)>=2 && substr($num,-1)<=4)		$ret = $word_med;
    else	$ret = $word_mult;
    return	$ret;
}

function select_option($L) {
	return "<option value='$L' >$L</option>";
}

function list_clear($l) { // список очистить от префиксов 0 и пробелов, привести к разделителю запятая и цесятичной точке
	return	str_replace(",0",",",
			str_replace(",0",",",
			str_replace("0000,",",",
			str_replace(",,",",", 
			str_replace(",,",",", 
			str_replace("\n",",", 
			str_replace("\r",",", 
			str_replace(" ","", 
			// начальная запятая ",".
			str_replace(",",".",$l).","))))))));
}

function db_insert($fact) {
	global $db_seans,$db_table,$units,$say;
	$q = "INSERT INTO $db_table (`user`,`id` ,`cell`,`fact`,`date`,`list`,`units`,`time`) VALUES (?,?,?,?,?,?,?,NOW())";
	$st = $db_seans->prepare($q);			// запрос на вставку строки данных
	if ($fact==2)	$l=$_POST['list0'];		// запоминаем результат сверки из прового списка
	else			$l=$_POST['list1'];		// из левого списка
	$l = list_clear($l);					// чистим список
	$a = array_unique(explode(',', $l));	// удаляем дубли
	if ($fact < 3)	sort($a);				// сортируем если не отгрузка
	$a = array_filter($a); 					// убираем пустые
	$units = count($a);						// считаем итог
	$l = implode(',',$a);					// в список
	$l = str_replace(",","\r\n",$l);		// в строки
	if ($fact==0)	$_POST['list0']=$l;		// в правый список учета
	$st -> execute(array($_POST['user'],$_POST['id'],$_POST['cell'],$fact,$_POST['date'],$l,$units)); // выполнить запрос
}			

function db_delete($fact) { // удаление по типу за дату по ячейке
	global $db_seans,$db_table,$units;
	$q = "DELETE FROM $db_table WHERE `fact`=$fact AND `date`='".$_POST['date']."' ";
	if ( $_POST['cell']!='') $q.="and `cell`='".$_POST['cell']."' ";
	if ( $fact==1) {
		if ( $_POST['id']!='') $q.="AND `id`='".$_POST['id']."' ";
		if ( $_POST['user']!='') $q.="AND `user` like '%".str_replace(" ","",$_POST['user'])."%' ";
	}
	$st = $db_seans->prepare($q);
	$st -> execute();
}			

function db_delete_all_date($fact) { // удаление по типу за дату 
	global $db_seans,$db_table,$units;
	$q = "DELETE FROM $db_table WHERE `date`='".$_POST['date']."' AND `fact`=$fact ";
	$st = $db_seans->prepare($q);
	$st -> execute();
//	print_r($q);
}			

function list_inventar_fast($par1,$par0) { //сравниваем два списка удаляем совпадения, определяем излишки/недостачу
	global $say,$list0,$list1;
	//Очистим списки
	$l1 = list_clear($par1);
	$l0 = list_clear($par0);
	$list1	= "список совпадения:\r\n";
	$res	= "список разниц:\r\n";
	$p=0;
	$q=0;
	$q1=0;
	$q0=0;
	if ( strpos($l0,"	") > 0 ) $l0 = preg_filter("/[	]+[^\,]+\,/",',',$l0);//удалим лишнее кроме 1 колнки с ШК	
	if ( strpos($l1,"	") > 0 ) $l1 = preg_filter("/[	]+[^\,]\,/",',',$l1);//удалим лишнее кроме 1 колнки с ШК	
	$p1 = strpos($l1,','); //первый разделитель
	while (! $p1===false ) { 
		$r1 = mb_substr($l1,0,$p1); //вырезаем номер,
		if ( strpos($l0,$r1) === false ) { // нет такого -
			$res.=$r1.'+'.db_select('0','`cell`,`units`',"`list` like '%$r1%'").PHP_EOL; // излишек+МХ где по учету д.б.
			$q1++;
		} else {	
			$l0 = str_replace($r1.',','',$l0); // удалим в учете 
			$list1.=$r1."\r\n";
			$q++;
		}
		$l1 = str_replace($r1.',','',$l1); // удалим в факте
		$p1 = strpos($l1,','); //следующий разделитель
		$p++;
		if ($p>5000) exit; // сраховка зацикливания
	}	
	$p0 = strpos($l0,','); //первый разделитель
	while (! $p0===false ) { // проверяем остаток как недостачу с поиском в других МХ по факту
		$q0++;
		$r0 = mb_substr($l0,0,$p0); //вырезаем номер
		$res.= $r0.'-'.db_select('1','`cell`,`units`',"`list` like '%$r0%'").PHP_EOL; // недостаток-МХ где по факту м.б.
		$l0 = str_replace($r0.',','',$l0); // удалим его в остатке
		$p0 = strpos($l0,','); //следующий разделитель
	}
	$say = "По ".$_POST['cell']."<br>";
	$list0 = $res;
	if ($res === '') {
		$say .= "всё совпало:$q cверено:$p"; 
	} else {
		$say .= "cверено:$p совпало:$q излишек+$q1 недостача-$q0"; 
	}	
	return $res;
}
?>