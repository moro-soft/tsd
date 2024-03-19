<?php //Печатная форма
$id = $_POST['id'];
$user = $_POST['user'];
$l1 = list_clear(db_select($_POST['fact']));
$l0 = list_clear(db_select(0,'`list`,`units`', 'true'));
$res = "";
$alist = array();
$p2=0;
if ( strpos($l1,"	") > 0 )	$l1 = preg_filter("/[	]+[^\,]\,/",',',$l1);	//удалим лишнее кроме 1 колнки с ШК	
$loop = true;
$p1 = strpos($l1,','); //первый разделитель
if ( $p1 === false ) $loop = false;
while ($loop) { 
	$kol="0";
	$r1 = mb_substr($l1, 0, $p1);					// вырезаем номер,
	$p0 = strpos($l0, $r1);
	if ( $p0 === false ) $gr='нет';					// нет данных для группы
	else {											// есть данные, формируем строку группы
		$p00 = strpos(mb_substr($l0, $p0), ',');	// конец строки					
		$r0 = mb_substr($l0, $p0, $p00);			// шк+доп
		$ar0 = explode('	', $r0);				// массив доп.колонок
		if ( count($ar0) > 2 )  {
				$gr = str_replace($ar0[0].'	', '',str_replace($ar0[1].'	', '', $r0));
				$kol=$ar0[1];						// в группу добавить шк с весом
		} else {
			$gr='нет';								// нет данных для группы
			if ( count($ar0) == 2 ) $kol = $ar0[1]; // шк без группы с весом
		}	
	}
	if (array_key_exists($gr, $alist))				// проверим наличие группы
		array_push($alist[$gr], array($r1, $kol) );	// в группу добавить шк с весом
	else 
		$alist[$gr] = array(array($r1, $kol));		// добавить  группу и шк с весом
	$l1 = str_replace($r1.',', '', $l1);			// удалим в факте
	$p1 = strpos($l1, ',');							//следующий разделитель
	$p2+=1;
	if ( $p1 === false or $p2>5000 ) $loop = false; // сраховка зацикливания
}
echo '<!DOCTYPE html>
<html lang="ru"><head>
<title>АО "Сегежский ЦБК" Segezha group</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<style type="text/css">
body {background: #EEEEEE; margin: auto; width:640px}
table {border: solid 1px; border-spacing: 0px;}
div,td,th,h1,hr {background: #FFFFFF; font-family: "Verdana"; font-size: 14px;} 
th,h1 {font-weight: bold;}
th,td { text-align: right; border: solid 1px;padding: 3px;}
@media print { 	h1 {page-break-before:always}  }
</style>
</head>
<body>';
ksort($alist);
$it=0;
$kt=0;
foreach ($alist as $key => $arr)  {
	$key_gr = str_replace('F4',' товарная',$key);
	$key_gr = str_replace('F3',' некондиция',$key_gr);
	$key_gr = str_replace('F2',' полуфабрикат',$key_gr);
	$key_gr = str_replace('	301110','	СЦБК',$key_gr);
	$key_gr = str_replace('	301140','	СУ',$key_gr);
	$key_gr = str_replace('	КГ','',$key_gr);
	$key_gr = str_replace('ая','ая ',$key_gr);
	$key_gr = str_replace('магам','мага м',$key_gr);
	$key_gr = str_replace('		','	',$key_gr);
	$key_gr = str_replace('	','<br>',$key_gr);
	echo '<div><h1></h1><p></p><p></p>';
	echo "<h3>".( $_POST['fact']=='1' ? "Инвентаризационная" : "Весовая" )." ведомость №__ $id от ".$_POST['date']."</h3><p></p>
	<p>Ячейка: ".$_POST['cell']."</p>
	<p>Заказ:<br>$key_gr</p>
	<table><tr><th>Штрихкод</th><th> Номер рулона<br> кол-во (шт.)</th><th>Вес брутто (кг)</th></tr>";
	$k=0;
	for ($i= 0; $i < count($arr); $i ++) {
		echo "<tr><td>".$arr[$i][0]."</td><td>".($i+1)."</td><td>".$arr[$i][1]."</td></tr>";
		$k+=($arr[$i][1]);
	}
	//$k=str_replace("."," ",$k);
	echo "<tr><td>Итого:</td><td>$i (".plural::asString($i).") рул.	</td><td>$k</td></tr></table>
	<p>$k (".plural::asString($k).") кг.</p>					
	<p>Ответственные лица		:	_____/___________</p>
	<p>______________________	:	_____/___________<u> $user </u></p>
	<p>______________________	:	_____/___________</p>
	</div>";
	$it+=$i;
	$kt+=$k;
}
echo "<hr><p>Всего по ячейке: ".$_POST['cell'].", № $id, $user: $it рул. $kt кг. </p></body>";

class Plural {
 
    const MALE = 1;
    const FEMALE = 2;
    const NEUTRAL = 3;
   
    protected static $_digits = array(
        self::MALE => array('ноль', 'один', 'два', 'три', 'четыре','пять', 'шесть', 'семь', 'восемь', 'девять'),
        self::FEMALE => array('ноль', 'одна', 'две', 'три', 'четыре','пять', 'шесть', 'семь', 'восемь', 'девять'),
        self::NEUTRAL => array('ноль', 'одно', 'два', 'три', 'четыре','пять', 'шесть', 'семь', 'восемь', 'девять')
        );
   
    protected static $_ths = array(
        0 => array('','',''),
        1=> array('тысяча', 'тысячи', 'тысяч'),   
        2 => array('миллион', 'миллиона', 'миллионов'),
        3 => array('миллиард','миллиарда','миллиардов'),
        4 => array('триллион','триллиона','триллионов'),
        5 => array('квадриллион','квадриллиона','квадриллионов')
        );
   
    protected static $_ths_g = array(
		self::NEUTRAL, 
		self::FEMALE, 
		self::MALE, 
		self::MALE, 
		self::MALE, 
		self::MALE
		); // hack 4 thsds
   
    protected static $_teens = array(
        0=>'десять',
        1=>'одиннадцать',
        2=>'двенадцать',
        3=>'тринадцать',
        4=>'четырнадцать',
        5=>'пятнадцать',
        6=>'шестнадцать',
        7=>'семнадцать',
        8=>'восемнадцать',
        9=>'девятнадцать'
        );
 
    protected static $_tens = array(
        2=>'двадцать',
        3=>'тридцать',
        4=>'сорок',
        5=>'пятьдесят',
        6=>'шестьдесят',
        7=>'семьдесят',
        8=>'восемьдесят',
        9=>'девяносто'
        );
   
    protected static $_hundreds = array(
        1=>'сто',
        2=>'двести',
        3=>'триста',
        4=>'четыреста',
        5=>'пятьсот',
        6=>'шестьсот',
        7=>'семьсот',
        8=>'восемьсот',
        9=>'девятьсот'
        );
   
    protected function _ending($value, array $endings = array()) {
        $result = '';
        if ($value < 2) $result = $endings[0];
        elseif ($value < 5) $result = $endings[1];
        else $result = $endings[2];
       
        return $result;
    }
   
    protected function _triade($value, $mode = self::MALE, array $endings = array()) {
        $result = '';
        if ($value == 0) { return $result; }
        $triade = str_split(str_pad($value,3,'0',STR_PAD_LEFT));
        if ($triade[0]!=0) { $result.= (self::$_hundreds[$triade[0]].' '); }
        if ($triade[1]==1) { $result.= (self::$_teens[$triade[2]].' '); }
        elseif(($triade[1]!=0)) { $result.= (self::$_tens[$triade[1]].' '); }
        if (($triade[2]!=0)&&($triade[1]!=1)) { $result.= (self::$_digits[$mode][$triade[2]].' '); }
        if ($value!=0) { $ends = ($triade[1]==1?'1':'').$triade[2]; $result.= self::_ending($ends,$endings).' '; }
        return $result;
    }
   
    public function asString($value, $mode = self::MALE, array $endings = array()) {
        if ($value==0) return self::$_digits[self::MALE][0];
		if (empty($endings)) { $endings = array('','',''); }
        $result = '';
        $steps = ceil(strlen($value)/3);
        $sv = str_pad($value, $steps*3, '0', STR_PAD_LEFT);
        for ($i=0; $i<$steps; $i++) {
            $triade = substr($sv, $i*3, 3);
            $iter = $steps - $i;
            $ends = ($iter!=1)?(self::$_ths[$iter-1]):($endings);
            $gender = ($iter!=1)?(self::$_ths_g[$iter-1]):($mode);
            $result.= self::_triade($triade,$gender, $ends);
        }
        return $result;
    }
   
}
?>