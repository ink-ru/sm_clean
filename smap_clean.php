<?php
/* ==============================================
Код оформляется в соответсвиии с стандартом PSR-2
============================================== */

// TODO брать имя файла карты сайта из robots.txt
// TODO проводить валидацию карты после завершения обработки

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Служебный скрипт</title>
	<meta name="robots" content="noindex">
	<meta name="googlebot" content="noindex">
</head>
<body>
<noindex>
<h1>Чистим карту сайта</h1>
<?

// =========================================

// $aFaile = file_get_contents('http://parfumes.ru/robots.txt');


$use_web = false;
$total = 0;
$found = false;
$need_write = false;
$was_cleaned = false;

$sFileLoc = 'sitemap.xml';

$rFile = 'http://'.$_SERVER['HTTP_HOST'].'/robots.txt';
if($use_web) $sFile = 'http://'.$_SERVER['HTTP_HOST'].'/'.$sFileLoc;
	else $sFile = $sFileLoc;


if(file_exists('robots.txt'))
	{
		$sR = file('robots.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		echo '<p>локальный файл robots.txt найден</p>';
		if(!chk($sR)) die('не удалось открыть robots.txt');
	}
else
	{
	$sR = file($rFile);
	echo '<p>используем HTTP запрос robots.txt</p>';
	if(!chk($sR)) die('не удалось открыть robots.txt'.$rFile);
	}

foreach ($sR as $key => $value)
{
	// if(!stripos($value, 'Disallow')) unset($sR[$key]);
	if(!preg_match('#^\s*Disallow\s*:#i', $value)) unset($sR[$key]);
		else $sR[$key] = preg_replace('#^\s*Disallow\s*:\s*#i', '', $value);
}

$sR = array_unique($sR);
echo '<p>найдено '.sizeof($sR).' правил(а)</p>';

// echo '<pre>';
// echo print_r($sR);
// echo '</pre>';


foreach($sR as $sRule) 
{
    $sRepFrom = array( '*',  '?' );
    $sRepTo =   array( '.*', '\?');

    $sRule = trim(str_replace($sRepFrom,$sRepTo,$sRule));

    if(!strpos($sRule,'$'))
    {
      // $sRule.= '[^>]*';
      $sRule = preg_replace('#([^\*])$#i', "$1.*", $sRule);
    }
    if(!empty($sRule))
    {
	    $sRule = '#'.$sRule.'#i';
	    echo '<p> '.$sRule." </p>";
	    $sRules[] = $sRule;
	}
}

if($use_web)
{
	$sM = file($sFile);
	echo '<p>карта сайта найдена</p>';
	if(!chk($sM)) die('не удалось прочитать'.$sFile);
}
elseif(file_exists($sFile))
{
	//$handle = fopen($sFile, "r+");
	$sM = file($sFile);
	echo '<p>карта сайта найдена</p>';
	if(!chk($sM)) die('не удалось прочитать'.$sFile);
}
else die('не удалось открыть '.$sFile);

echo '<hr>';

foreach ($sM as $k => $v)
{
	if( !preg_match('#<\?xml#i', $v) &&
		!preg_match('#</?urlset#i', $v) &&
		!preg_match('#</?url>#i', $v) &&
		!preg_match('#</?loc>#i', $v)
		)
		{
			$sM[$k] = '';
			unset($sM[$k]);
			$need_write = true;
			$was_cleaned = true;
		}
		else $sMapLines[] = $sM[$k];
}

foreach ($sMapLines as $k => $v)
{
	if($found == 'skip')
	{
		$found = false;
		continue;
	}
		// echo '<p>'.$v;
		foreach ($sRules as $rule)
		{
			if(preg_match('#<loc>#i', $v) && preg_match($rule, $v))
			{
				// echo '<b> - найдено!!!</b>';
				$total++;
				$found = true;
				$need_write = true;
			}
		}
		if($found === false)
		{
			$sMnew[$k]=$v;
			// echo '<p>'.htmlentities($v).'</p>';
		}
		else
		{
			array_pop($sMnew);
			$found = 'skip';
		}
		// echo '</p>';
}


if($total > 0)
{
	echo '<p>Найдено '.$total.'</p>';
}

if($was_cleaned)
{
	echo '<p>Лишние дескрипторы были удалены.</p>';
}

if($need_write)
{
	echo '<hr><hr>';
	$sMnewFile = implode($sMnew);
	rename($sFileLoc, $sFileLoc.'.bak');
	// print_r($sMnewFile);
	echo (file_put_contents($sFileLoc, $sMnewFile)) ? '<p>Новая карта записана</p>' : '<p>Ошибка записи новой карты</p>';
}
else echo 'Все хорошо, делать нечего!';


// =========================================

function chk ($arr)
{
$arr = array_filter($arr);
if (empty($arr)) return false;
	else return true;
}

// fclose($handle);
?>
<noindex>
</body>
</html>


