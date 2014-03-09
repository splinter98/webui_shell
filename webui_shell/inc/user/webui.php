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
 * File Name: webui.php
 * 	File containing the webui interaction script
 *
 * File Author:
 * 		Tjores Maes (lordalderaan@gmail.com)
 *
 * Contributors:
 *		asrael...
 */

// Logged in as user: get needed variables
$options=$database->getoptions($_SESSION['userid']);
$actions=$database->getactions($_SESSION['userid']);
$instance=$database->getinstances($_SESSION['userid'],'userid');
if ( empty($instance) )
{
	$database->fail('Configuration error: This user has no valid instance.');
}
$instanceid=$instance['instanceid'];
if ($options['User_Disabled'] === '1')
{
	setcookie('shell_clogin','',1);
	setcookie('shell_cpw','',1);
	$_SESSION=Array('userid'=>$_SESSION['userid']);
	$_SESSION['recentlogout']=time();
	$database->fail('This user has been disabled.');
}
if ($options['Show_Unclaimed_Torrents'] === '1')
{
	$torrents=$database->gettorrents($_SESSION['userid'],$instanceid,true);
}
else
{
	$torrents=$database->gettorrents($_SESSION['userid'],$instanceid);
}
if ( $options['Remember_Last_IP'] === '1' )
{
	$database->upd('users',Array('ip'=>$_SERVER['REMOTE_ADDR']),Array('userid'=>$_SESSION['userid']));
}
$url='http://'.$instance['domain'].':'.$instance['port'].'/gui/'.$file;
// Check for multiple identical query fields (ie: s=a&s=b)
// If so it returns array with all fields, otherwise returns false
$fullrequest=query_array($_SERVER['QUERY_STRING']);
// Filter request before they are forwarded
if (!empty($_REQUEST['hash']))
{
	hashallowed();
}
if (!empty($_REQUEST['action']))
{
	foreach ($fullrequest['action'] as $action_i => $action)
	{
		switch ($action['value'])
		{
			case 'setsetting':
				if ($_REQUEST['s']=='webui.cookie')
				{
					// Webui cookie is always saved alone so no need to check $fullrequest
					// Webui cookie saved to database (per user) instead of utorrent
					/*
					$_SESSION['webui.cookie']=$_REQUEST['v'];
					$database->upd('users',Array('cookie'=>$_REQUEST['v']),Array('userid'=>$_SESSION['userid']));
					*/
					header('Content-type: text/plain; charset=utf-8');
					die(json_encode(Array('build'=>$_SESSION['build'])));
				}
				if ( $options['Change_Settings'] !== '1' )
				{
					$database->fail('Changing settings not allowed for this user.');
				}
			break;
			case 'setprops':
				if ( $options['Set_Torrent_Properties'] !== '1' )
				{
					$database->fail('Changing Torrent Properties not allowed for this user.');
				}
			break;
			case 'start':
			case 'stop':
			case 'pause':
			case 'unpause':
				if ( $options['Start_Stop_Pause_Unpause'] !== '1' )
				{
					$database->fail('Starting, Stopping and Pauzing torrents not allowed for this user.');
				}
			break;
			case 'forcestart':
				if ( $options['Force_Start'] !== '1' )
				{
					$database->fail('Force starting torrents not allowed for this user.');
				}
			break;
			case 'recheck':
				if ( $options['Recheck'] !== '1' )
				{
					$database->fail('Rechecking not allowed for this user.');
				}
			break;
			case 'remove':
				if ( $options['Remove'] !== '1' )
				{
					$database->fail('Removing torrents not allowed for this user.');
				}
				$_SESSION['fulllist']=-1;
				if ( $options['Unassign_on_Remove'] === '1' )
				{
					unassign();
				}
			break;
			case 'removedata':
				if ( $options['Remove_Data'] !== '1' )
				{
					$database->fail('Removing torrents and its data not allowed for this user.');
				}
				$_SESSION['fulllist']=-1;
				if ( $options['Unassign_on_Remove'] === '1' )
				{
					unassign();
				}
			break;
			case 'removetorrent':
				// webapi 3.0
				if ( $options['Remove_Data'] !== '1' )
				{
					$database->fail('Removing torrents and its data not allowed for this user.');
				}
				$_SESSION['fulllist']=-1;
				if ( $options['Unassign_on_Remove'] === '1' )
				{
					unassign();
				}
			break;
			case 'removedatatorrent':
				// webapi 3.0
				if ( $options['Remove_Data'] !== '1' )
				{
					$database->fail('Removing torrents and its data not allowed for this user.');
				}
				$_SESSION['fulllist']=-1;
				if ( $options['Unassign_on_Remove'] === '1' )
				{
					unassign();
				}
			break;
			case 'setprio':
				if ( $options['Set_File_Priority'] !== '1' )
				{
					$database->fail('Changing file priority not allowed for this user.');
				}
			break;
			case 'add-url':
				// Download add-url torrent and add as file.
				if ( $options['Add_Torrents'] !== '1' )
				{
					$database->fail('Adding torrents not allowed for this user.');
				}
				$req_url=false;
				foreach ($fullrequest['s'] as $s_i => $s)
				{
					if ( $s['order'] == $action['order'] + 1 )
					{
						$req_url=$s['value'];
						break;
					}
				}
				if ( $req_url === false )
				{
					$database->fail('No torrent url found.');
				}
				if (stripos($req_url,':COOKIE:') === false)
				{
					$add_url=$req_url;
					$add_cookie='';
				}
				else
				{
					$add_string=explode(':COOKIE:',$req_url);
					$add_url=$add_string[0];
					$add_cookie=$add_string[1];
				}
				$curl_headers=array(
					"Accept-Language: en-us,en;q=0.5",
					"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
					"Keep-Alive: 300",
					"Connection: keep-alive"
				);
				$curl_opts=Array(
					CURLOPT_FRESH_CONNECT => 1,
					CURLOPT_FORBID_REUSE => 1,
					CURLOPT_HEADER => 0,
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL => str_replace(' ','%20',trim($add_url)),
					CURLOPT_SSL_VERIFYPEER => FALSE,
					CURLOPT_FOLLOWLOCATION => 1,
					CURLOPT_USERAGENT => 'BTuTWebuiShell',
					CURLOPT_ENCODING => ''
				);
				if (!empty($add_cookie))
				{
					$curl_opts['CURLOPT_COOKIE']=$add_cookie;
				}
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
				curl_setopt_array($ch, $curl_opts);
				$results = curl_exec ($ch);
				if ( curl_errno($ch) !== 0 )
				{
					$database->fail('cURL error: '.curl_error($ch).' ('.curl_errno($ch).')');
				}
				if (curl_getinfo($ch,CURLINFO_HTTP_CODE) !== 200)
				{
					$database->fail('Adding this torrent failed. The file could not be retrieved from the url.');
				}
				curl_close ($ch);
				unset($ch);
				$torrentfile=$cfg["settings_dir"].'temp.torrent';
				if (file_put_contents($torrentfile,$results) === false)
				{
					$database->fail('Adding this torrent failed. Configuration Error: No read/write access to settings folder.');
				}
				// Remove add-url and use add-file instead.
				unset($fullrequest['action'][$action_i]);
				unset($fullrequest['s'][$s_i]);
				$fullrequest['action'][]=Array('value'=>'add-file','order'=>'9');
			break;
			case 'add-file':
				if ( $options['Add_Torrents'] !== '1' )
				{
					$database->fail('Adding torrents not allowed for this user.');
				}
				if ( !empty($_FILES) && !empty($_FILES['torrent_file']) && file_exists($_FILES['torrent_file']['tmp_name']) )
				{
					$torrentfile=$_FILES['torrent_file']['tmp_name'];
				}
				else
				{
					$database->fail('Adding this torrent failed. Uploaded file unavailable.');
				}
			break;
			case 'getprops':
				if ( $options['View_Torrent_Properties'] !== '1' )
				{
					$database->fail('Viewing Torrent Properties not allowed for this user.');
				}
			break;
			case 'queuebottom':
			case 'queuedown':
			case 'queuetop':
			case 'queueup':
				if ( $options['Allow_Manage_Queue'] !== '1' )
				{
					$database->fail('Changing queue order not allowed for this user.');
				}
			break;
			case 'getxferhist':
				// api 3.0
				if ( $options['Allow_DLUL_Statistics'] !== '1' )
				{
					$database->fail('Viewing Statistics not allowed for this user.');
				}
			break;
			case 'resetxferhist':
				// api 3.0
				if ( $options['Change_Settings'] !== '1' )
				{
					$database->fail('Changing (xferhist) settings not allowed for this user.');
				}
			break;
			case 'rss-remove':
			case 'rss-update':
			case 'filter-remove':
			case 'filter-update':
				// api 3.0
				// Per user RSS settings are planned for 0.8.0
				// Allow or refuse based on Change_Settings for now.
				if ( $options['Change_Settings'] !== '1' )
				{
					$database->fail('Changing (RSS) settings not allowed for this user.');
				}
			break;
			case 'list-dirs':
				// api 3.0
			case 'getversion':	
				// api 3.0
			case 'getsettings':
			case 'getfiles':
				// These actions are forwarded without interference.
			break;
			default:
				// The remaining actions are Unknown. Checking against Unknown actions settings.
				if ( array_key_exists($action['value'],$actions) )
				{
					if ( $actions[$action['value']] !== '1' )
					{
						$database->fail('Unknown action \''.$action['value'].'\' specifically not allowed for this user.');
					}
				}
				elseif ( $options['Allow_Unknown_Actions'] !== '1' )
				{
					$database->fail('Doing Unknown actions not allowed for this user.');
				}
			break;
		}
	}
	if ( isset($torrentfile) )
	{
		// If there is a torrentfile from add-torrent or add-url check against Existing torrents settings
		// This system is not yet waterproof.
		$bdecode=BDecode(file_get_contents($torrentfile));
		if (!is_array($bdecode) || !isset($bdecode['info']) || !isset($bdecode['info']['name']))
		{
			$database->fail('Not a valid torrent file.');
		}
		$hash=strtoupper(sha1(BEncode($bdecode['info'])));
		// Get torrent size
		if (array_key_exists('files',$bdecode['info']))
		{
			$size=0;
			foreach ($bdecode['info']['files'] as $item)
			{
				$size+=$item['length'];
			}
		}
		else
		{
			$size=$bdecode['info']['length'];
		}
		// Get all torrents
		$lasttorrents=$database->gettorrents(0,$instanceid);
		if ( !is_array($lasttorrents) || count($lasttorrents) == 0 )
		{
			if ( $_SESSION['fulllist'] < ( time() - 1800 ) )
			{
				$database->fail('Adding this torrent failed. There is no list of current torrents. Refresh the webui (Press Esc) and try again.');
			}
		}
		// Check Quota Max Torrents
		if ( is_numeric($options['Quota_Max_Torrents']) && $options['Quota_Max_Torrents'] > 0 )
		{
			$quota=0;
			if ( $options['Show_All_Torrents'] === '1' )
			{
				$quota=count($lasttorrents);
			}
			elseif ($options['Show_Unclaimed_Torrents'] === '1')
			{
				foreach($torrents as $torrent)
				{
					if ($torrent['userid'] !== '0')
					{
						$quota++;
					}
				}
			}
			else
			{
				$quota=count($torrents);
			}
			if ( $quota >= $options['Quota_Max_Torrents'] )
			{
				$database->fail('No more then '.$options['Quota_Max_Torrents'].' torrents allowed for this user.'.$quota);
			}
		}
		// Check Quota Max Combinbedsize
		if ( is_numeric($options['Quota_Max_Combinedsize']) && $options['Quota_Max_Combinedsize'] > 0 )
		{
			$quota=0;
			if ( $options['Show_All_Torrents'] === '1' )
			{
				foreach($lasttorrents as $torrent)
				{
					$quota+=$torrent['size'];
				}
			}
			elseif ($options['Show_Unclaimed_Torrents'] === '1')
			{
				foreach($torrents as $torrent)
				{
					if ($torrent['userid'] !== '0')
					{
						$quota+=$torrent['size'];
					}
				}
			}
			else
			{
				foreach($torrents as $torrent)
				{
					$quota+=$torrent['size'];
				}
			}
			if ( $quota + $size > $options['Quota_Max_Combinedsize'] )
			{
				$database->fail('No more then '.bytes($options['Quota_Max_Combinedsize']).' allowed. Loaded '.bytes($quota).' + new '.bytes($size).' = '.bytes($quota+$size).' total.');
			}
		}
		// Check for existing torrent.
		if ( array_key_exists($hash,$lasttorrents) )
		{
			if ( $options['Show_All_Torrents'] === '1' || array_key_exists($hash,$torrents) )
			{
				$database->fail('Adding this torrent failed. This torrent is already loaded.');
			}
			elseif ( $options['Allow_Existing_Torrents'] === '1' )
			{
				$database->upd('torrents',Array('userid'=>$_SESSION['userid'],'instanceid'=>$instanceid,'hash'=>$hash,'name'=>$bdecode['info']['name'],'size'=>$size));
				$_SESSION['fulllist']=-1;
				header('Content-type: text/plain; charset=utf-8');
				die(json_encode(Array('build'=>$_SESSION['build'])));
			}
			else
			{
				$database->fail('Adding this torrent failed. This torrent already exists. Adding existing torrents not allowed for this user.');
			}
		}
		$database->upd('torrents',Array('userid'=>$_SESSION['userid'],'instanceid'=>$instanceid,'hash'=>$hash,'name'=>$bdecode['info']['name'],'size'=>$size));
		$_SESSION['fulllist']=-1;
	}
}

$cachedrequest=false;
// $_REQUEST cannot handle multiple identical query fields so use the custom query array instead
if ( $fullrequest !== false && count($fullrequest) > 0 )
{
	$newarray=Array();
	foreach ($fullrequest as $key => $items)
	{
		if ( $key != session_name() && substr($key,0,6) != 'shell_' && $key !== 'Language' && $key != 't')
		{
			if ( $key == 'cid' )
			{
				if ( $items[0]['value'] == $_SESSION['cid'] )
				{
					$cachedrequest=true;
				}
			}
			else
			{
				$newarray[$key]=$items;
			}
		}
	}
	if ( empty($newarray['token']) && !empty($_SESSION['token']) )
	{
		$newarray['token'][]=Array('value'=>$_SESSION['token'],'order'=>100);
	}
	$url.='?'.query_string($newarray);
}
// Quick cleanup of old cookiejars
if ( !file_exists($cfg["settings_dir"].'cookiejar.'.session_id().'.tmp'))
{
	if($cjdir = opendir($cfg["settings_dir"]))
	{
		while($cjfile = readdir($cjdir))
		{
			if( substr($cjfile,0,10) == 'cookiejar.' && substr($cjfile,-4) == '.tmp' )
			{
				if ( filemtime($cfg["settings_dir"].$cjfile) < time() - 86400 )
				{
					unlink($cfg["settings_dir"].$cjfile);
				}
			}
		}
		closedir($cjdir);
	}
}
// Forward to webui
$curl_opts=Array(
	CURLOPT_FRESH_CONNECT => 0,
	CURLOPT_FORBID_REUSE => 0,
	CURLOPT_USERPWD => $instance['username'].':'.$instance['password'],
	CURLOPT_HEADER => 0,
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_URL => $url,
	CURLOPT_SSL_VERIFYPEER => FALSE,
	CURLOPT_FOLLOWLOCATION => 1,
	CURLOPT_COOKIEFILE => $cfg["settings_dir"].'cookiejar.'.session_id().'.tmp',
	CURLOPT_COOKIEJAR => $cfg["settings_dir"].'cookiejar.'.session_id().'.tmp'
);
if ( !empty($torrentfile) )
{
	$curl_opts[CURLOPT_POSTFIELDS]['torrent_file']='@'.$torrentfile;
}
$ch = curl_init();
curl_setopt_array($ch, $curl_opts);	
$results = curl_exec ($ch);
if ( curl_errno($ch) !== 0 )
{
	if ( curl_errno($ch) === 52 )
	{
		$database->fail('Configuration error: Blocked by ipfilter.dat OR cURL error: '.curl_error($ch).' ('.curl_errno($ch).')');
	}
	else
	{
		$database->fail('cURL error: '.curl_error($ch).' ('.curl_errno($ch).')');
	}
}
if (curl_getinfo($ch,CURLINFO_HTTP_CODE) == 400)
{
	// Linux server invalid request issue, simply try agian. See http://forum.utorrent.com/viewtopic.php?id=89538
	$results = curl_exec ($ch);
}
switch (curl_getinfo($ch,CURLINFO_HTTP_CODE))
{
case 404:
	$database->fail('File not found.');
	break;
case 401:
	$database->fail('Configuration Error: Wrong login details.');
	break;
case 300:
case 400:
	$database->fail('Configuration Error: Invalid request.');
	break;
case 200:
	// Valid result from webui.
	// Adapt contenttype from result
	header('Content-type: '.curl_getinfo($ch,CURLINFO_CONTENT_TYPE).'; charset=utf-8');
	$json=Array('build'=>$_SESSION['build'],'torrents'=>Array(),'settings'=>Array());
	if ( curl_getinfo($ch,CURLINFO_CONTENT_TYPE) == 'text/html' )
	{
		// Grab token if present
		preg_match("/<div[^>]*id=[\"']token[\"'][^>]*>([^<]*)<\/div>/",$results,$matches);
		if (isset($matches[1]))
		{
			$_SESSION['token']=$matches[1];
		}
	}
	elseif ( substr($file,-3) == '.js' )
	{
		// Replace /gui/ with Webui-Shell path
		$results=str_replace('/gui',substr($cfg['altname'],0,-1),$results);
	}
	elseif ( curl_getinfo($ch,CURLINFO_CONTENT_TYPE) == 'text/plain' )
	{
		// Process plain text
		// Grab JSON
		$json=json_decode($results,true);
		$_SESSION['build']=$json['build'];
		// Process webui Errors
		if (isset($json['error']))
		{
			$database->fail('WEBUI: '.$json['error']);
		}
		// The webui_shell does not use the Webui API cache. So torrentp, torrentm etc are unexpected.
		if ( isset($json['torrentp']) || isset($json['torrentm']) || isset($json['rssfeedp']) || isset($json['rssfeedm']) || isset($json['rssfilterp']) || isset($json['rssfilterm']) )
		{
			$database->fail('Unexpected torrentp and torrentm. Please contact the Webui-Shell devs.');
		}
		// At this time RSS feeds could contain passkeys and are filtered until more options are provided.
		if (isset($json['rssfeeds']))
		{
			$json['rssfeeds']=Array();
		}
		if (isset($json['rssfilters']))
		{
			$json['rssfilters']=Array();
		}
		// Files list
		if (isset($json['files']))
		{
			// let it pass unmodified
		}
		// Props list
		if (isset($json['props']))
		{
			if ( $options['View_Torrent_Properties'] !== '1' )
			{
				$json['props']=Array();
			}
		}
		// Transfer history
		if (isset($json['transfer_history']))
		{
			if (  $options['Allow_DLUL_Statistics'] !== '1' )
			{
				$json['transfer_history']=Array();
			}
		}
		// Download dirs
		if (isset($json['download-dirs']))
		{
			// api 3.0
			// Unfiltered, need direct access to utorrent to manage anyways, admin decides what users see or not.
			// Per user download dirs are planned for 0.8.0
		}
		// Process the torrent list.
		if (isset($json['torrents']))
		{
			// Save full torrentlist to database for later use (Admin Panel, Existing torrent, Show unclaimed)
			// On: First Load, New Torrent, Removed Torrent, 30 minutes old
			if ( $_SESSION['fulllist'] < ( time() - 1800 ) )
			{
				$database->deltorrent(0,$instanceid,-1);
				foreach ( $json['torrents'] as $item )
				{
					$database->upd('torrents',Array('userid'=>0,'instanceid'=>$instanceid,'hash'=>strtoupper($item[0]),'name'=>$item[2],'size'=>$item[3]));
				}
				if ($options['Show_All_Torrents'] !== '1' || ( is_numeric($options['Quota_Max_Torrents']) && $options['Quota_Max_Torrents'] > 0 ) || ( is_numeric($options['Quota_Max_Combinedsize']) && $options['Quota_Max_Combinedsize'] > 0 ) )
				{
					$database->cleantorrents($_SESSION['userid']);
					if ($options['Show_Unclaimed_Torrents'] === '1')
					{
						$database->checkclaimed($instanceid); // Check claimed torrents.
						$torrents=$database->gettorrents($_SESSION['userid'],$instanceid,true); // Reload Torrents in case of changes to claimed torrents.
					}
				}
				$_SESSION['fulllist']=time();
			}
			// Filter on allowed:
			if ( $options['Show_All_Torrents'] === '1' )
			{
				$t_filtered=$json['torrents'];
			}
			else
			{
				$t_filtered=Array();
				$t_label=Array();
				foreach ($json['torrents'] as $t_item )
				{
					if ( array_key_exists($t_item[0],$torrents) )
					{
						$t_filtered[]=$t_item;
						if (!empty($t_item[11]))
						{
							if (array_key_exists($t_item[11],$t_label))
							{
								$t_label[$t_item[11]]++;
							}
							else
							{
								$t_label[$t_item[11]]=1;
							}
						}
					}
				}
				$json['label']=Array();
				foreach($t_label as $label => $count)
				{
					$json['label'][]=Array($label,$count);
				}
			}
			// Store labels in session:
			$_SESSION['labels']=$json['label'];
			// Compare to cache:
			if ($cachedrequest)
			{
				unset($json['torrents']);
				$t_newcache=Array();
				$json['torrentp']=Array();
				foreach ($t_filtered as $t_item)
				{
					$t_newcache[$t_item[0]]=$t_item;
					if ( array_key_exists($t_item[0],$_SESSION['torrentcache']) )
					{
						if ( count(array_diff_assoc($t_item,$_SESSION['torrentcache'][$t_item[0]])) > 0 )
						{
							$json['torrentp'][]=$t_item;
						}
					}
					else
					{
						$json['torrentp'][]=$t_item;
					}
				}
				$json['torrentm']=Array();
				foreach ($_SESSION['torrentcache'] as $t_hash => $t_item)
				{
					if ( !array_key_exists($t_hash,$t_newcache) )
					{
						$json['torrentm'][]=$t_hash;
					}
				}
				$_SESSION['torrentcache']=$t_newcache;
			}
			else
			{
				$json['torrents']=$t_filtered;
				$_SESSION['torrentcache']=Array();
				foreach($t_filtered as $t_item)
				{
					$_SESSION['torrentcache'][$t_item[0]]=$t_item;
				}
			}
			// Set random torrentc:
			$_SESSION['cid']=rand(1000000000,9999999999);
			$json['torrentc']=$_SESSION['cid'];
		}
		// Process settings file.
		if (isset($json['settings']))
		{
			foreach ($json['settings'] as &$item)
			{
				if ($item[0] == 'webui.cookie' )
				{
					/*
					// Misssing 2.1 webui.cookie fix part 1
					$temp_cookiefound=true;
					// End of fix part 1
					//Replace webui.cookie with user/session cookie (unless that is empty, then take default cookie from the settings)
					if (empty($_SESSION['webui.cookie']))
					{
						$_SESSION['webui.cookie']=$item[2];
					}
					else
					{
						$item[2]=$_SESSION['webui.cookie'];
					}
					if (!empty($_SESSION['newlang']))
					{
						$tempcookie=json_decode($_SESSION['webui.cookie'],true);
						$tempcookie['lang']=$_SESSION['newlang'];
						$_SESSION['webui.cookie']=json_encode($tempcookie);
						$item[2]=$_SESSION['webui.cookie'];
						$database->upd('users',Array('cookie'=>$_SESSION['webui.cookie']),Array('userid'=>$_SESSION['userid']));
						unset($_SESSION['newlang']);
					}
					*/
				}
				elseif ($item[0] == 'webui.enable' || $item[0] == 'gui.graphic_progress' )
				{
					//Let these settings through regardless of authorization.
				}
				elseif ($item[0] == 'webui.port' || $item[0] == 'bind_port')
				{
					if ($options['Change_Settings'] !== '1')
					{
						//Replace with -1 instead of 0.
						$item[2]=-1;
					}
				}
				else
				{
					if ($options['Change_Settings'] !== '1')
					{
					//Replace with 0, false or empty string depending on type.
						switch ($item[1])
						{
						case 0:
							$item[2]=0;
							break;
						case 1:
							$item[2]='false';
							break;
						case 2:
							$item[2]='';
							break;
						}
					}
				}
			}
			/*
			// Misssing 2.1 webui.cookie fix part 2
			if (!isset($temp_cookiefound))
			{
				$json['settings'][]=Array('webui.cookie',2,$_SESSION['webui.cookie']);
			}
			// End of fix part 2
			*/
		}
		$results=json_encode($json);
	}
	if ( substr($file,0,5) == 'lang/' )
	{
		// Process language file or toolbar image based on users options
		if(preg_match("/lang\s*=\s*(\[.*\])[;|,]/ms",$results,$matches)>0)
		{
			$langstrings=json_decode($matches[1]);
			$to_clean=Array();
			$to_disable=Array();
			if ( count($langstrings) == 253 )
			{
				// webui 0.370
				$disablestring=substr($langstrings[227],0,strpos($langstrings[227],'||'));
				if ($options['Change_Settings'] !== '1')
				{
					$to_clean=array_merge($to_clean,Array(89,90,91,92,93,94,95,96,216,217,218,219,220,221,222,223,224,225,226));
					$to_disable=array_merge($to_disable,Array(4));
				}				
				if ( $options['View_Torrent_Properties'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(161));
					// Properties
				}				
				if ( $options['Set_Torrent_Properties'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(159,177,202,203,204,205));
					// Torrent Properties, Label stuff
				}
				if ( $options['Start_Stop_Pause_Unpause'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(160,164,165,208,211,212));
					// Pause, Start, Stop, Pause, Start, Stop
				}
				if ( $options['Force_Start'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(158));
					// Force Start
				}
				if ( $options['Recheck'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(157));
					// Force Re-check
				}
				if ( $options['Remove'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(162,210));
					// Remove, Remove
				}
				if ( $options['Remove_Data'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(163,156));
					// Remove And, Delete Data
				}
				if ( $options['Set_File_Priority'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(152,153,154,155));
					// Priorities
				}
				if ( $options['Add_Torrents'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(207));
					// Add Torrent, Add Torrent from url
				}				
			}
			elseif ( count($langstrings) == 333 )
			{
				// Webui 0.380
				$disablestring=substr($langstrings[307],0,strpos($langstrings[307],'||'));
				if ($options['Change_Settings'] !== '1')
				{
					$to_clean=array_merge($to_clean,Array(114,115,116,117,118,119,120,121,292,293,294,295,296,297,298,299,300,301,302,303,304,305));
					$to_disable=array_merge($to_disable,Array(4));
				}				
				if ( $options['View_Torrent_Properties'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(223));
					// Properties
				}				
				if ( $options['Set_Torrent_Properties'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(151,221,266,267,268,269));
					// Torrent Properties, Label stuff
				}
				if ( $options['Start_Stop_Pause_Unpause'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(273,278,279,222,228,229));
					// Pause, Start, Stop, Pause, Start, Stop
				}
				if ( $options['Force_Start'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(220));
					// Force Start
				}
				if ( $options['Recheck'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(219));
					// Force Re-check
				}
				if ( $options['Remove'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(226,277));
					// Remove, Remove
				}
				if ( $options['Remove_Data'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(227,216,217,218));
					// Remove And, Delete Data
				}
				if ( $options['Set_File_Priority'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(212,213,214,215));
					// Priorities
				}
				if ( $options['Add_Torrents'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(271,272));
					// Add Torrent, Add Torrent from url
				}				
				if ( $options['Allow_Manage_Queue'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(224,225,275,276));
					// Move Down Queue, Move Up Queue, Move Down Queue, Move Up Queue
				}
			}
			elseif ( count($langstrings) == 274 )
			{
				// webui linux
				$disablestring=substr($langstrings[231],0,strpos($langstrings[231],'||'));
				if ($options['Change_Settings'] !== '1')
				{
					$to_clean=array_merge($to_clean,Array(90,91,92,93,94,95,96,220,221,222,223,224,225,226,227,228,229,230));
					$to_disable=array_merge($to_disable,Array(4));
				}				
				if ( $options['View_Torrent_Properties'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(161));
					// Properties
				}				
				if ( $options['Set_Torrent_Properties'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(114,159,204,205,206,207));
					// Torrent Properties, Label stuff
				}
				if ( $options['Start_Stop_Pause_Unpause'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(160,166,167,210,215,216));
					// Pause, Start, Stop, Pause, Start, Stop
				}
				if ( $options['Force_Start'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(158));
					// Force Start
				}
				if ( $options['Recheck'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(157));
					// Force Re-check
				}
				if ( $options['Remove'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(164,214));
					// Remove, Remove
				}
				if ( $options['Remove_Data'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(165,156));
					// Remove And, Delete Data
				}
				if ( $options['Set_File_Priority'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(152,153,154,155));
					// Priorities
				}
				if ( $options['Add_Torrents'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(209));
					// Add Torrent, Add Torrent from url
				}				
				if ( $options['Allow_Manage_Queue'] !== '1' )
				{
					$to_disable=array_merge($to_disable,Array(162,163,212,213));
					// Move Down Queue, Move Up Queue, Move Down Queue, Move Up Queue
				}
			}
			if ( count($to_clean)>0 || count($to_disable)>0 )
			{
				if ($cfg["disablelook"] == 1)
				{
					$disablestring='';
				}
				foreach  ($to_clean as $value)
				{
					$langstrings[$value]='';
				}
				foreach  ($to_disable as $value)
				{
					$langstrings[$value]=$disablestring;
				}
				$results=preg_replace("/lang\s*=\s*(\[.*\])([;|,])/ms",'lang='.json_encode($langstrings).'$2',$results);
			}
		}
	}
	elseif ( $file == 'images/toolbar.png' ) 
	{
		// Process toolbar image based on users options
		if ( $options['Start_Stop_Pause_Unpause'] !== '1' )
		{
			edittoolbar(Array(4,5,6));
		}
		if ( $options['Remove'] !== '1' )
		{
			edittoolbar(Array(3));
		}
		if ( $options['Add_Torrents'] !== '1' )
		{
			edittoolbar(Array(0,1));
		}
		if ( $options['Set_File_Priority'] !== '1' )
		{
			edittoolbar(Array(7,8));
		}
	}
	// return processed result or modified image
	if ( empty($img) )
	{
		echo $results;
	}
	else
	{
		imagepng($img);
	}
	break;
default:
	$database->fail('Invalid response code '.curl_getinfo($ch,CURLINFO_HTTP_CODE));
	break;
}
curl_close ($ch);
unset($ch);
?>
