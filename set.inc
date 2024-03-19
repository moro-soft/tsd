<?php											// начальные данные
date_default_timezone_set('Europe/Moscow');		//часовой пояс
$db_seans = new PDO('mysql:host=localhost;dbname=stata', 'webuser', 'webuser'); 
$db_table = "`stata`.`inventar`"; 				// подключения к БД
if(! isset($_GET['super']) )					// супер режим
	$super=false;								// для контролера на ТСД	
else {
	$super=true;								// для мастера в браузере
	$Home.="?super";							// параметр выбора режима
}
$_POST+=$_GET;							// Поддержка обоих методов
if (! isset($_POST['date'	]) ) $_POST['date']	= date("Y-m-d");	// дата операции 
if (! isset($_POST['fact'	]) ) $_POST['fact']	= "1";				// тип пакета: 0-учет, 1-факт, 2-разница, 3-отгрузка, 4-приёмка	
if (! isset($_POST['cell'	]) ) $_POST['cell']	= "";				// МХ ячейка склада
if (! isset($_POST['id'		]) ) $_POST['id']	= "";				// Номер ТС,операции или пакета	
if (! isset($_POST['user'	]) ) $_POST['user']	= "";				// ФИО контролёра
if (! isset($_POST['replace']) ) $_POST['replace'] = "";			// режим замещения
										//чистим пробелы по краям
$_POST['id']	= trim($_POST['id']); 
$_POST['user']	= trim($_POST['user']);

$units = 0;		//шт.ШК
$list1 = '';	if (isset($_POST['list1'])) $list1=$_POST['list1']; //факт.список	
$list0 = '';	if (isset($_POST['list0'])) $list0=$_POST['list0']; //учет.список	
//МХ
$acell=array('A/M1','A/M2','A/M3','B0','B1','B2','B3','B4','B4Z','B5','B6','B7','B8','B9','B10','B11','B12','B13','B14','B15','B16','B17','B18','B19','B20','B21','B25','B27','B28','M1','M2','M3','M4','M5','M6','M9','M10','M11','M12','M13','M14','M17','M18','M19','M20','M21','M22','M23','M24','M25','M26','M27','M28','M29','M33','M33A','M34','M35','M36','M37','M38','M39','M41','M42','M43','M44','M46','M50','M51','2-02BUF','4CONTR','4UST','BUMTARA','GARAGE','KAMEN','KONTORKA','OTVHRAN','PE1','RMZ','SILIKONOVYI','SVX','UPAKOVKA','UPB','ZAV','ZVX1','ZVX2');
$f = array_fill(0, 20, '');  //массив описаний элементов формы
$autofocus	= 'autofocus';
$echo_top_form = "
<div class='list'>
	<textarea id='list1' class='red' name='list1' tabindex=1 rows=12 cols=19 placeholder='$f[2]' $autofocus >$list1</textarea>
	<div>
		<b class='button' onClick='confirmDialog(\"Очистить левый список ШК?\",\"Очистить левый\")'>⨉</b>$f[6]<br><br>
		<b class='button' onClick='confirmDialog(\"Вернуть списки ШК?\",\"Вернуть списки\")''>↺</b> $f[14]<br><br>
		<br>
		<b class='button' onClick='confirmDialog(\"Скопировать список Факт в Учет?\",\"Вправо\")'>↦</b> $f[7]<br><br>
		<b class='button' onClick='confirmDialog(\"Заменить список Факт из Учет?\",\"Влево\")'>↤</b> $f[8]<br><br>
		<b class='button' onClick='confirmDialog(\"Сравнить списки Факт и Учет?\",\"Сравнить\")'>⇄</b> $f[9]
	</div>
	<textarea id='list0' name='list0' readonly rows=12 cols=19 placeholder='$f[3]' >$list0</textarea>
	<div class='w0'>
		<b id='list0x' class='button clear' onClick='confirmDialog(\"Очистить правый список ШК?\",\"Очистить правый\")'>⨉</b>$f[17]
	</div>	
</div>";

// селектор МХ
$so='';	
for ($i= 0; $i < count($acell); $i ++) {$so.=select_option($acell[$i]);}
// параметры отбора
$echo_param = str_replace("value='".$_POST['fact']."'","value='".$_POST['fact']."' checked","
<div class='sel'>
	<div>Отбор: <input id='date' name='date' type='date' size='10' value='".$_POST['date']."' />
	МХ:<select id='cell' name='cell'>".str_replace('>'.$_POST['cell'].'<',' selected >'.$_POST['cell'].'<',$so)."</select>
	</div>
	<div>Тип:<br>
		<label><input id='fact' type='radio' name='fact' value='1'>Факт</label>
		<label><input type='radio' name='fact' value='0'>Учёт</label>
		<label><input type='radio' name='fact' value='2'>Сверка</label>
		<label><input type='radio' name='fact' value='3'>Отгр.</label>
		<label><input type='radio' name='fact' value='4'>Приём</label>
	</div>
	<div>Уточнение: 
		<input id='nomer' name='id' type='text' size='5' value='".$_POST['id']."' placeholder='Номер' />
		<b id='nomerX'	class='button clear'		onClick='confirmDialog(\"Очистить Номер?\",\"Очистить Номер\")'>⨉</b>
		<input id='user' name='user' type='text' size='11' value='".$_POST['user']."' placeholder='ФИО' />
		<b id='userX'	class='button clear' onClick='confirmDialog(\"Очистить ФИО?\",\"Очистить ФИО\")'>⨉</b>
	</div>
</div>"); 

?>