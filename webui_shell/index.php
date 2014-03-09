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
 * File Name: index.php
 * 	File containing the session, checks and login scripts and includes the appropriate file.
 *
 * File Author:
 * 		Tjores Maes (lordalderaan@gmail.com)
 *
 * Contributors:
 *		asrael...
 */

ini_set('display_errors','Off');
error_reporting(0);
if (session_id() === "")
{
	session_start();
}
if (extension_loaded('curl') !== true)
{
	die('Configuration error: cURL extension not available.');
}
require_once('config.php');
require_once('functions/functions.php');
require_once('classes/bencode.php');
require_once('classes/class.httpdownload.php');
require_once('classes/sql.php');
//require_once(autolang());
$version='0.6';
// Config checks and adjustments.
if(substr($cfg['altname'],-1)!=='/')
{
	$cfg['altname'].='/';
}
$cfg["settings_dir"]=str_replace('\\','/',$cfg["settings_dir"]);
if(substr($cfg["settings_dir"],-1)!=='/')
{
	$cfg["settings_dir"].='/';
}
if(!file_exists($cfg["settings_dir"]))
{
	mkdir($cfg["settings_dir"]);
	if(!file_exists($cfg["settings_dir"]))
	{
		die('Configuration error: Settings folder invalid or no write access.');
	}
}
if ($cfg["db_type"] == "sqlite") 
{
	if (extension_loaded('sqlite') !== true)
	{
		die('Configuration error: sqlite extension not available.');
	}
}
elseif ($cfg["db_type"] == "mysql")
{
	if (extension_loaded('mysql') !== true)
	{
		die('Configuration error: mysql extension not available.');
	}
}
else
{
	die('Configuration error: Invalid db_type');
}
if (array_key_exists('max_execution_time',$cfg) && is_numeric($cfg['max_execution_time']))
{
	ini_set('max_execution_time',$cfg['max_execution_time']);
}
$file=$_REQUEST['shell_file'];
// Fix for rewriter who do not parse the ? in the original query to a &.
if ( substr($_REQUEST['shell_file'],0,1) === '?' )
{
	$file='';
	$temp=explode('=',substr($_REQUEST['shell_file'],1));
	if(!array_key_exists(1,$temp))
	{
		$temp[1]='';
	}
	$_REQUEST[$temp[0]]=$temp[1];
}
// Forwards or includes.
if (empty($_REQUEST['shell_file']) && $_SERVER['PHP_SELF'] === substr($cfg['altname'],0,-1) )
{
	// No trailing / in the url, redirect to prevent problems.
	header('Location: '.$cfg['altname']);
	die();
}
elseif ($file==='favicon.ico' && file_exists('favicon.ico'))
{
	// favicon.ico
	header('Content-type: image/x-icon');
	die(file_get_contents('favicon.ico'));
}
// Language detection.
if(!is_array($langs=getlangs()))
{
	die($langs);
}
$cfg['defaultlang']=empty($cfg['defaultlang'])?'en':$cfg['defaultlang'];
if (!empty($_REQUEST['shell_lang']) && array_key_exists($_REQUEST['shell_lang'],$langs))
{
	// Request overwrites all.
	$newlang=$_REQUEST['shell_lang'];
	if(empty($_REQUEST['shell_clang']) || ( !empty($_REQUEST['shell_clang']) && $_REQUEST['shell_clang'] !== $_REQUEST['shell_lang'] ))
	{
		$_SESSION['newlang']=$newlang;
	}
}
elseif (empty($_SESSION['lang']))
{
	if(!empty($_COOKIE['shell_clang']) && array_key_exists($_COOKIE['shell_clang'],$langs))
	{
		// Get it from cookie.
		$newlang=$_COOKIE['shell_clang'];
	}
	else
	{
		// Automatic language detection.
		$newlang=autolang($langs,$cfg['defaultlang']);
		$_SESSION['newlang']=$newlang;
	}
}
if (!empty($newlang))
{
	$_SESSION['lang']=$newlang;
	setcookie('shell_clang',$_SESSION['lang'],time()+60*60*24*150);
}
require($langs[$_SESSION['lang']]['file']);
// Logout
if (!empty($_REQUEST['shell_logout']))
{
	setcookie('shell_clogin','',1);
	setcookie('shell_cpw','',1);
	$_SESSION=Array();
	$_SESSION['recentlogout']=time();
	if (isset($_SERVER['PHP_AUTH_USER']))
	{
		die('You still have an HTTP Auth session going. Close all browser screens to logout.');
	}
	header('Location: '.$cfg['altname']);
	die();
}
if($cfg['altname'] != substr($_SERVER['PHP_SELF'],0,strlen($cfg['altname'])))
{
	die('Configuration error: You are not going through the rewriter or the altname is incorrect.<br>You should use <a href="'.$cfg['altname'].'">'.$cfg['altname'].'</a>.');
}
if (!isset($_SESSION['build']))
{
	$_SESSION['build']=0;
}
$database=new database();
register_shutdown_function('catcherror');
// Login procedure.
$loginnow=false;
if (empty($_SESSION['logged']))
{
	$errorstr='';
	$login=false;
	if ( !empty($_REQUEST['shell_login']) && !empty($_REQUEST['shell_pw']) )
	{
		// Form based login
		$database->bruteforce($_SERVER['REMOTE_ADDR']); // Brute Force Protection
		// Check for admin login
		if ( $_REQUEST['shell_login'] === $cfg["username"] && ( ( $cfg["md5pass"] === true && md5($_REQUEST['shell_pw']) === $cfg["password"] ) || ( $cfg["md5pass"] === false && $_REQUEST['shell_pw'] === $cfg["password"] ) ) )
		{
			// Logged in as admin.
			$_SESSION['logged']='admin';
			$_SESSION['logintype']='admin';
		}
		else
		{
			// Try normal login
			$_SESSION['logintype']='norm';
			$login=$database->login($_SESSION['logintype'],$_REQUEST['shell_login'],$_REQUEST['shell_pw']);
		}
		// Error description in case login failed.
		$errorstr=$lang['loginfail'];
	}
	elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
	{
		// Auth based login
		$database->bruteforce($_SERVER['REMOTE_ADDR']); // Brute Force Protection
		// Try auth login
		$_SESSION['logintype']='auth';
		$login=$database->login($_SESSION['logintype'],$_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
		if ( $login === false )
		{
			header("WWW-Authenticate: Basic realm=\"uTorrent\"");
			header("HTTP/1.0 401 Authorization Required");
		}
		// Error description in case login failed.
		$errorstr=$lang['loginfail'];
	}
	elseif ( !empty($_REQUEST['shell_clogin']) && !empty($_REQUEST['shell_cpw']) )
	{
		// Cookie based login
		$database->bruteforce($_SERVER['REMOTE_ADDR']); // Brute Force Protection
		// Check for admin login
		if ( $_REQUEST['shell_clogin'] === $cfg["username"] && ( ( $cfg["md5pass"] === true && md5($_REQUEST['shell_cpw']) === md5($cfg["password"]) ) || ( $cfg["md5pass"] === false && $_REQUEST['shell_cpw'] === md5($cfg["password"]) ) ) )
		{
			// Logged in as admin.
			$_SESSION['logged']='admin';
			$_SESSION['logintype']='admin';
		}
		else
		{
			// Try normal login
			$_SESSION['logintype']='cookie';
			$login=$database->login($_SESSION['logintype'],$_REQUEST['shell_clogin'],$_REQUEST['shell_cpw']);
		}
		// Error description in case login failed.
		$errorstr=$lang['loginfailc'];
	}
	else
	{
		// Try IP login
		$_SESSION['logintype']='ip';
		$login=$database->login($_SESSION['logintype'],$_SERVER['REMOTE_ADDR']);
	}
	if ( $login !== false )
	{
		$database->bruteforce($_SERVER['REMOTE_ADDR'],true); // Brute Force Reset
		// Logged in as user. Set session variables.
		$_SESSION['logged']='user';
		$_SESSION['userid']=$login['userid'];
		$_SESSION['instanceid']=$login['instanceid'];
		$_SESSION['username']=$login['name'];
		$_SESSION['torrentdir']=$login['torrentdir'];
		$_SESSION['webui.cookie']=$login['cookie'];
		$_SESSION['torrentcache']=Array();
		$_SESSION['fulllist']=-1;
		$_SESSION['time']=time();
		$loginnow=true;
		// Check if miniui is enabled and the button is pressed
		$miniui=$database->getinfo('miniui');
		if (!empty($_REQUEST['shell_miniui']) && $miniui)
		{
			// redirect to miniui
			header('Location: '.$cfg['altname'].$miniui.'/index.html');
			die();
		}
	}
	if ( !empty($_SESSION['logged']) && !empty($_REQUEST['shell_cookie']) && $_REQUEST['shell_cookie'] === 'on' )
	{
		setcookie('shell_clogin',$_REQUEST['shell_login'],time()+60*60*24*150);
		setcookie('shell_cpw',md5($_REQUEST['shell_pw']),time()+60*60*24*150);
	}
}
// Check login result
if (empty($_SESSION['logged']))
{
	// Not logged in:
	if ( ( !empty($_REQUEST['list']) || !empty($_REQUEST['action']) || $file=='token.html' ) && ( empty($_SESSION['recentlogout']) || $_SESSION['recentlogout'] < time()-60 ) )
	{
		// Webui Request detected but not logged in. Assume Community Project, request for http auth.
		header('WWW-Authenticate: Basic realm="uTorrent"');
		header('HTTP/1.0 401 Unauthorized');
	}
	elseif ( $file === 'shell_ajax.php' )
	{
		// Admin panel or Topbar AJAX request detected. Session probably expired.
		$_SESSION=Array();
		$_SESSION['recentlogout']=time();
		die(json_encode(Array('logger'=>'<font color="red">['.date("H:i:s").'] '.$lang['expired'].'</font>')));
	}
	// Show login from
	require_once('themes/'.$cfg['theme'].'/intro.php');	
}
elseif ( $_SESSION['logged'] == 'admin' )
{
	// Logged in as admin: Show admin panel
	if ( substr($file,0,6) === 'shell_' )
	{
		// Show special pages (such as top.php)
		if ( file_exists('inc/admin/'.substr($file,6)) )
		{
			require_once('inc/admin/'.substr($file,6));
		}
		elseif ( file_exists('inc/common/'.substr($file,6)) )
		{
			require_once('inc/common/'.substr($file,6));
		}
		else
		{
			$database->fail('File not found.');
		}
	}
	else
	{
		// Show admin main page
		require_once('inc/admin/main.php');
	}	
}
elseif ( $_SESSION['logged'] == 'user' )
{
	// Logged in as user
	if ( $file === '' && $loginnow === true && $_SESSION['logintype'] !== 'auth' )
	{
		$options=$database->getoptions($_SESSION['userid']);
		if ($options['Topbar_Disabled'] === '1')
		{
			require_once('inc/user/webui.php');
		}
		else
		{
			require_once('inc/user/main.php');
		}
	}
	elseif ( $file === '' && count($_GET) === 1 && count($_POST) === 0 )
	{
		$options=$database->getoptions($_SESSION['userid']);
		if ($options['Topbar_Disabled'] === '1')
		{
			require_once('inc/user/webui.php');
		}
		else
		{
			require_once('inc/user/main.php');
		}
	}
	else
	{
		if ( substr($file,0,6) === 'shell_' )
		{
			// Show special pages (such as top.php)
			if ( file_exists('inc/user/'.substr($file,6)) )
			{
				require_once('inc/user/'.substr($file,6));
			}
			elseif ( file_exists('inc/common/'.substr($file,6)) )
			{
				require_once('inc/common/'.substr($file,6));
			}
			else
			{
				$database->fail('File not found.');
			}
		}
		else
		{
			// Token auth
			$options=$database->getoptions($_SESSION['userid']);
			if ( $file != 'token.html' && $file != 'index.html' && $options['Token_Auth'] !== '0' )
			{
				$infocheck=array_merge($_GET,$_POST);
				foreach (array_keys($infocheck) as $key)
				{
					if ( substr($key,0,6) === 'shell_' )
					{
						unset($infocheck[$key]);
					}
				}
				if ( count($infocheck) > 0 && $infocheck['token'] !== $_SESSION['token'])
				{
					$database->fail('Invalid token.');
				}
			}			
			// Show webui
			require_once('inc/user/webui.php');
		}
	}
}
else
{
	$database->fail('Security error. Unknown login status.');
}
?>
