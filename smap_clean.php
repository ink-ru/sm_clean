#!/usr/bin/php
<?php
/* ===============================================
CopyLeft - https://github.com/ink-ru/sm_clean
Оформление кода см. стандарт PSR-2
=============================================== */
// TODO применить SimpleXMLElement
// TODO реализовать обработку вложенных карт
// TODO проводить валидацию карты после завершения обработки
// TODO Оформить в виде класса
// TODO Добавть ключь cli - HTTP_HOST
// TODO обрабатывать 'Clean-param'
// TODO разбивать однострочные файлы на строки
// TODO вфбирать нужный UserAgnt, например не обрабатыать User-Agent: EmailCollector

set_time_limit(240);

$total = 0;
$found = false;
$need_write = false;
$was_cleaned = false;

// опеределяем среду выполнения
// if(PHP_SAPI === 'cli' || empty($_SERVER['REMOTE_ADDR']))
if(PHP_SAPI === 'cli')
{
	define('MODE', 'console');
	if(count($argv) > 0)
	{
		global $argv; // меняем область видимости для использования в классах

		// получаем имя файла из коммандной строки
		$options = getopt("f:");
		// имя файла карты сайта, переопределяется ниже из robots.txt
		$_GET['sm'] = preg_match('#(\w+\.xml)#i', $options['f'], $fn) ? $fn[1] : 'sitemap.xml';
	}

}
else
{
	// имя файла карты сайта по умолчанию, переопределяется ниже
	if(empty($_GET['sm'])) $_GET['sm'] = 'sitemap.xml';
		// this prevents "Code Injection"
		else $_GET['sm'] = preg_match('#(\w+\.xml)#i', $_GET['sm'], $fn) ? $fn[1] : 'sitemap.xml'; 
	?>
	<!DOCTYPE html>
	<html lang="ru">
	<head>
		<meta charset="UTF-8">
		<meta name="robots" content="noindex">
		<meta name="googlebot" content="noindex">
		<title>Обработка карты сайта XML</title>
	</head>
	<body>
	<noindex>
	<h1>Чистим карту сайта</h1>
	<?
}

if(file_exists('robots.txt'))
{
	$sR = file('robots.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if(!chk($sR)) die('не удалось открыть локальный robots.txt');
	log_push('локальный файл <tt>robots.txt найден</tt>');
}
elseif(!empty($_SERVER['HTTP_HOST']))
{
	$rFile = 'http://'.$_SERVER['HTTP_HOST'].'/robots.txt';
	$sR = file($rFile);
	if(!chk($sR)) die('не удалось открыть robots.txt через CGI'.$rFile);
	log_push('файл <tt>robots.txt</tt> получен через CGI запрос');
}

foreach ($sR as $key => $value)
{
	if(preg_match('#Sitemap\s*:\s*.*[^/]*/(\S+\.\S+)\s*$#i', $value, $fn))
	{
		$_GET['sm'] = $fn[1];
		log_push('Имя файла карты сайта ('.$_GET['sm'].') взято из <tt>robots.txt</tt>');
		unset($sR[$key]);
		continue;
	}
	else
	{
		if(strpos($value, '#'))
		{
			$value = preg_replace('~^([^#]*)#~i', "$1", $value); // comment removing
		}
		
		if(!preg_match('#^\s*Disallow\s*:#i', $value) ||
			preg_match('#^\s*Disallow\s*:\s*/\s*$#i', $value))
		{
			$sR[$key] = ''; // doing unset($sR[$key]);
			continue;
		}
		else $sR[$key] = preg_replace('#^\s*Disallow\s*:\s*#i', '', $value);
	}
}

$sR = array_filter($sR); // удаляем пустые элементы
$sR = array_unique($sR);
log_push('найдено '.sizeof($sR).' правил(а)');

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
	    log_push($sRule);
	    $sRules[] = $sRule;
	}
}

if(file_exists($_GET['sm']))
{
	$sFile = $_GET['sm'];
	$sM = file($sFile);
	if(!chk($sM)) die('не удалось прочитать'.$sFile);
	log_push('карта сайта найдена');
}
elseif(!empty($_SERVER['HTTP_HOST']))
{
	$sFile = 'http://'.$_SERVER['HTTP_HOST'].'/'.$_GET['sm'];
	$sM = @file($sFile);
	if(!chk($sM)) die('не удалось прочитать'.$sFile);
	log_push('карта сайта найдена');
}
else die('не удалось открыть '.$_GET['sm']);

foreach ($sM as $k => $v)
{
	if(preg_match('#</?sitemapindex>#i', $v) ||
		preg_match('#</?sitemap>#i', $v))
	{
		log_push('Ошибка!', 'error');
		die('Этот скрипт пока не может обрабатывать вложенные катры!');
	}

	// удаляем лишние дискрипторы
	if( !preg_match('#<\?xml#i', $v) &&
		!preg_match('#</?urlset#i', $v) &&
		!preg_match('#</?url>#i', $v) &&
		!preg_match('#</?loc>#i', $v) &&
		!preg_match('#</?sitemapindex>#i', $v) &&
		!preg_match('#</?sitemap>#i', $v)
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
		foreach ($sRules as $rule)
		{
			if(preg_match('#<loc>#i', $v) && preg_match($rule, $v))
			{
				$total++;
				$found = true;
				$need_write = true;
			}
		}
		if($found === false)
		{
			$sMnew[$k]=$v;
		}
		else
		{
			array_pop($sMnew);
			$found = 'skip';
		}
}

if($total > 0) log_push('Найдено '.$total);
if($was_cleaned) log_push('Лишние дескрипторы были удалены.');

if($need_write)
{
	if(is_array($sMnew)) $sMnew = array_filter($sMnew); // удаляем пустые элементы
		// else die('записывать нечего');
	$sMnewFile = implode($sMnew);
	rename($_GET['sm'], $_GET['sm'].'.bak');
	
	log_push( (file_put_contents($_GET['sm'], $sMnewFile)) ? 'Новая карта записана' : 'Ошибка записи новой карты' );
}
else log_push('Все хорошо, делать нечего!');
// =========================================

if(!defined('MODE')):
?>
<noindex>
</body>
</html>
<?php
endif;

function log_push ($log_content='', $type='message')
{
	if($type == 'message')
	{
		if(defined('MODE') && MODE == 'console') fwrite(STDOUT, $log_content."\n"); // echo $log_content."\n";
			else echo '<p>'.$log_content."<p>";
	}
	else
	{
		if(defined(MODE) && MODE == 'console')
		{
			fwrite(STDOUT, $log_content."\n"); // echo $log_content."\n";
			fwrite(STDERR, $log_content."\n"); // echo $log_content."\n";
		}
			else echo '<p style="color:red;">'.$log_content."<p>";
	}

	return true;
}

function chk ($arr)
{
	if(is_array($arr))
	{
		$arr = array_filter($arr); // удаляем пустые элементы
		if (empty($arr)) return false;
			else return true;
	}
	else return false;
}

?>
