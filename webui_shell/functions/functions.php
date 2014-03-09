<?php
/*
 * WEBUI-SHELL for use with utorrent and its webui - http://www.utorrent.com
 *
 * Version 0.7.0
 *
 * Copyright (C) 2008 Tjores Maes
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * File Name: functions.php
 * 	File containing the global functions.
 *
 * File Author:
 * 		Tjores Maes (lordalderaan@gmail.com)
 *
 * Contributors:
 *		asrael...
 */

function xsssanitize ($str)
{
	global $non_sgml_chars;
	if (empty($non_sgml_chars))
	{
		for ($i = 0; $i <= 31; $i++)
		{
			if ($i == 9 || $i == 13 || $i == 10) continue;
			$non_sgml_chars[chr($i)] = '';
		}
		for ($i = 127; $i <= 159; $i++)
		{
			$non_sgml_chars[utf8_encode(chr($i))] = '';
		}
	}
	$str = iconv('UTF-8', 'UTF-8//IGNORE', $str);
	$str = strtr($str, $non_sgml_chars);
	$str = htmlspecialchars($str);
	return $str;
}
function hashallowed ()
{
	global $options, $torrents, $fullrequest, $database;
	if ( $options['Show_All_Torrents'] !== '1' )
	{
		foreach ($fullrequest['hash'] as $hash)
		{
			if (!array_key_exists($hash['value'],$torrents))
			{
				$database->fail('Changing one of these torrents not allowed for this user.');
			}
		}
	}
}
function unassign ()
{
	global $options, $fullrequest, $database, $torrents;
	if ($fullrequest)
	{
		foreach ($fullrequest['hash'] as $hash)
		{
			if($database->unassign($_SESSION['userid'],$_SESSION['instanceid'],$_REQUEST['hash']))
			{
				unset($fullrequest['hash'][$hash]);
			}
		}
		if( count($fullrequest['hash']) == 0 )
		{
			unset($fullrequest['hash']);
			unset($fullrequest['action']);
		}
	}
	else
	{
		if($database->unassign($_SESSION['userid'],$_SESSION['instanceid'],$_REQUEST['hash']))
		{
			unset($_REQUEST['hash']);
			unset($_REQUEST['action']);
		}
	}
	// Reload torrentslist in case of changes
	if ($options['Show_Unclaimed_Torrents'] === '1')
	{
		$torrents=$database->gettorrents($_SESSION['userid'],$_SESSION['instanceid'],true);
	}
	else
	{
		$torrents=$database->gettorrents($_SESSION['userid'],$_SESSION['instanceid']);
	}
}
function edittoolbar ($to_clean)
{
	global $file, $results, $img, $img_trans;
	if (extension_loaded('gd') === true)
	{
		if ( $file == 'images/toolbar.png' )
		{
			if ( empty($img) )
			{
				$img=imagecreatefromstring($results);
				imagealphablending($img,false);
				imagesavealpha($img,true);
				$img_trans=imagecolorallocatealpha($img,255,255,255,127);
			}
			foreach ($to_clean as $value)
			{
				imagefilledrectangle($img,$value*24,0,$value*24+23,23,$img_trans);
			}
		}
	}
}
function bytes($value)
{
	if(!is_numeric($value))
	{
		return '?? B';
	}
	if($value==0)
	{
		return '0 B';
	}
	$suffix=array('B','KB','MB','GB','TB','PB','EB');
	$exp=floor(log($value)/log(1024));
	return round($value/pow(1024, $exp),2).' '.$suffix[$exp];
}
function unbytes($value,$suf)
{
	if(!is_numeric($value))
	{
		return '?';
	}
	if($value==0)
	{
		return '0';
	}
	$suffix=array('B','KB','MB','GB','TB','PB','EB');
	if(($exp = array_search(strtoupper($suf),$suffix)) === false)
	{
		return '?';
	}
	return $value * pow(1024,$exp);
}
function sortcmp_downloads($a, $b) 
{ 
     return strcmp($a[1].$a[0], $b[1].$b[0]); 
}
function catcherror()
{
	global $database;
	$error=error_get_last();
	if ($error !== null && is_array($error))
	{
		switch ($error['type'])
		{
			case E_WARNING:
			case E_NOTICE:
			case E_USER_WARNING:
			case E_USER_NOTICE:
			case E_STRICT:
				// These errors didn't halt the script and shouldn't be displayed to the user or in the fails admin panel.
			break;
			default:
				// Script was halted by an error. Display error to user and in fails admin panel.
				if(!empty($database))
				{
					$database->fail($error['message']);
				}
				else
				{
					die(json_encode(Array('build'=>(empty($_SESSION['build'])?'0':$_SESSION['build']),'error'=>'SHELL: '.$errorstr)));
				}
		}
	}
}
function query_array($query_string)
{
	if(empty($query_string))
	{
		return(false);
	}
	$return=Array();
	$lines=explode('&',$query_string);
	$i=0;
	$double=false;
	foreach($lines as $line)
	{
		$pos=strpos($line,'=');
		if ( $pos > 0 )
		{
			$field=substr($line,0,$pos);
			$value=substr($line,$pos+1);
			if ( $field != session_name() && substr($field,0,6) != 'shell_' && $field !== 'Language' && $field != 't' )
			{
				$return[$field][]=Array('value'=>urldecode($value),'order'=>$i);
				$i++;
			}
		}
	}
	return($return);
}
function query_string($query_array)
{
	if(!is_array($query_array) || count($query_array) == 0 )
	{
		return('');
	}
	$temp=Array();
	foreach($query_array as $field => $props)
	{
		foreach ($props as $prop)
		{
			$temp[$prop['order']]=$field.'='.urlencode($prop['value']);
		}
	}
	ksort($temp);
	return(implode('&',$temp));
}
function getlangs()
{
	if (!file_exists('inc/lang/'))
	{
		return('Configuration Error: Language folder does not exist.');
	}
	$d=dir('inc/lang/');
	$langs=Array();
	while (($f=$d->read()) !== false)
	{
		if(is_file('inc/lang/'.$f) && substr($f,-9)==='.lang.php' && ($name=getlangname($f))!==false)
		{
			$langs[substr($f,0,-9)]=Array('file'=>'inc/lang/'.$f,'name'=>$name);
		}
	}
	return($langs);
}
function getlangname($f)
{
	require('inc/lang/'.$f);
	return(empty($lang['name'])?false:$lang['name']);
}
function autolang($langs,$default)
{
	$acceptlangs=explode(',',str_replace(' ','',$_SERVER['HTTP_ACCEPT_LANGUAGE']));
	$lq=0;
	$bq=0;
	foreach($acceptlangs as $acceptlang)
	{
		if(($p=strpos($acceptlang,';q='))===false)
		{
			// No quality defaults to 1.
			$q=1;
			$l=$acceptlang;
		}
		else
		{
			// Split quality and language.
			$q=floatval(substr($acceptlang,$p+3));
			$l=substr($acceptlang,0,$p);
		}
		if($q>$lq && array_key_exists($l,$langs))
		{
			// If file exists and quality is higher then current file then assign new file.
			$lq=$q;
			$lang=$l;
		}
		// If this is a sublanguage get main language.
		if(($bp=strpos($acceptlang,'-'))!==false)
		{
			// If main language file exists and quality is higher then the current fallback file then assign new fallback file.
			$bl=substr($acceptlang,0,$bp);
			if ( $q>$bq && array_key_exists($bl,$langs) )
			{
				$bq=$q;
				$blang=$bl;
			}
		}
	}
	// If language detected return it.
	if ($lq!==0)
	{
		return($lang);
	}
	// If fallback language detected return it.
	if ($bq!==0)
	{
		return($blang);
	}
	// If default language exsts return it
	if (array_key_exists($default,$langs))
	{
		return($default);
	}
	// Else return 'en' for English
	return('en');
}
?>