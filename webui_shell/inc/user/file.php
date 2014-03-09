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
 * File Name: file.php
 * 	File containing the downloads browser and file download script.
 *
 * File Author:
 * 		Tjores Maes (lordalderaan@gmail.com)
 *
 * Contributors:
 *
 */

$options=$database->getoptions($_SESSION['userid']);
if ( $options['Allow_Downloading_Files'] !== '1' )
{
	$database->fail('Downloading files not allowed for this user.');
}
if ( array_key_exists('shell_file_get',$_REQUEST) && array_key_exists('downloads',$_SESSION) )
{
	$downloads=$_SESSION['downloads'];
	$download=$downloads[$_REQUEST['shell_file_get']];
	switch ($download[1])
	{
		case 'dir':
			$cursub='';
			$html='<html><html><head><title>&#181;Torrent Webui-Shell</title></head><body style="margin:0px;border-top: 1px solid #A7A5A6"><b>&nbsp;'.$lang['exploring'].':</b> '.$download[0].'/'.$cursub.'<br><i>';
			if ( array_key_exists('shell_file_sub',$_REQUEST) )
			{
				$html.='&nbsp;<a href="?shell_file_get='.$_REQUEST['shell_file_get'].'">'.$lang['backto'].' '.$download[0].'</a><br>';
				$cursub=base64_decode($_REQUEST['shell_file_sub']);
				if ( is_file($download[2].'/'.$cursub) )
				{
					$downloadfile = new httpdownload;
					if ($downloadfile->set_byfile($download[2].'/'.$cursub) === false)
					{
						$database->fail('Unable to download the following file: '.$cursub);
					}
					if (!empty($options['Max_Downloading_Speed']))
					{
						$downloadfile->speed=($options['Max_Downloading_Speed']/1024);
					}
					// Unlock the session file.
					session_write_close();
					// Start sending the requested file.
					$downloadfile->download();
					// Die to prevent corruption of the file.
					die();
				}
				elseif ( is_dir($download[2].'/'.$cursub) )
				{
					$cursub.='/';
				}
			}
			$html.='&nbsp;<a href="?">'.$lang['backto'].' '.$lang['torrentlist'].'</a></i><hr>';
			$subs=Array();
			if ( !is_dir($download[2].'/'.$cursub) )
			{
				$database->fail('Expected folder did not exists.');
			}
			$dir=opendir($download[2].'/'.$cursub);
			while (false !== ($file = readdir($dir)))
			{
				if ($file != "." && $file != "..")
				{
					$type='err';
					if ( is_dir($download[2].'/'.$cursub.$file) )
					{
						$type='dir';
					}
					elseif ( is_file($download[2].'/'.$cursub.$file) )
					{
						$type='file';
					}
					$subs[]=Array($file,$type);
				}
			}
			usort($subs, 'sortcmp_downloads');
			foreach ($subs as $sub)
			{
				$html.='&nbsp;<img src="shell_'.$sub[1].'.png">';
				if ( $sub[1] === 'err' )
				{
					$html.=$sub[0];
				}
				else
				{
					$html.='<a href="?shell_file_get='.$_REQUEST['shell_file_get'].'&shell_file_sub='.base64_encode($cursub.$sub[0]).'">'.$sub[0].'</a>';
				}
				$html.='<br>';
			}
			$html.='<hr></body></html>';
			header('Content-Type: text/html; charset=utf-8');
			echo $html;
		break;
		case 'file':
			$downloadfile = new httpdownload;
			if ($downloadfile->set_byfile($download[2]) === false)
			{
				$database->fail('Unable to download the following file: '.$cursub);
			}
			if (!empty($options['Max_Downloading_Speed']))
			{
				$downloadfile->speed=($options['Max_Downloading_Speed']/1024);
			}
			// Unlock the session file.
			session_write_close();
			// Start sending the requested file.
			$downloadfile->download();
			// Die to prevent corruption of the file.
			die();
		break;
		case 'err':
			$database->fail('Unable to download the following file (err): '.$download[2]);
		break;
	}
}
else
{
	$instance=$database->getinstances($_SESSION['userid'],'userid');
	$instanceid=$instance['instanceid'];
	if ( $options['Show_All_Torrents'] === '1' )
	{
		$torrents=$database->gettorrents(0,$instanceid);
	}
	elseif ( $options['Show_Unclaimed_Torrents'] === '1' )
	{
		$torrents=$database->gettorrents($_SESSION['userid'],$instanceid,true);
	}
	else
	{
		$torrents=$database->gettorrents($_SESSION['userid'],$instanceid);
	}
	$torrentnames=Array();
	foreach($torrents as $torrent)
	{
		$torrentnames[$torrent['name']]=$torrent['size'];
	}
	$downloads=Array();
	// Split multi-dir
	if ( strpos($_SESSION['torrentdir'],'|') === false )
	{
		$torrentdirs=Array($_SESSION['torrentdir']);
	}
	else
	{
		$torrentdirs=explode('|',$_SESSION['torrentdir']);
	}
	// Add torrent label subdirs
	if ( count($_SESSION['labels']) > 0 )
		{
		foreach ($torrentdirs as $torrentdir )
		{
			$torrentdir=str_replace('\\','/',$torrentdir);
			if(substr($torrentdir,-1)!=='/')
			{
				$torrentdir.='/';
			}
			foreach ($_SESSION['labels'] as $label)
			{
				$torrentdirs[]=$torrentdir.$label[0];
			}
		}
	}
	if ( is_array($torrentdirs) && count($torrentdirs) > 0 )
	{
		foreach ( $torrentdirs as $torrentdir )
		{
			$torrentdir=str_replace('\\','/',$torrentdir);
			if(substr($torrentdir,-1)!=='/')
			{
				$torrentdir.='/';
			}
			$dir=opendir($torrentdir);
			while (false !== ($file = readdir($dir)))
			{
				if ($file != "." && $file != "..")
				{
					$type='err';
					if ( is_dir($torrentdir.$file) )
					{
						$type='dir';
					}
					elseif ( is_file($torrentdir.$file) )
					{
						$type='file';
					}
					if ( array_key_exists($file,$torrentnames) )
					{
						$downloads[]=Array($file,$type,$torrentdir.$file,$torrentnames[$file]);
					}
				}
			}
			closedir($dir);
		}
	}
	usort($downloads, 'sortcmp_downloads');
	$_SESSION['downloads']=$downloads;
	$html='<html><html><head><title>&#181;Torrent Webui-Shell</title></head><body style="margin:0px;border-top: 1px solid #A7A5A6"><b>&nbsp;'.$lang['downloadablefiles'].'</b><hr><table><tr><td><u><b>'.$lang['filename'].'</b></u></td><td><u><b>'.$lang['torrentsize'].'</b></u></td></tr>';
	foreach ($downloads as $id => $download)
	{
		$html.='<tr><td>&nbsp;<img src="shell_'.$download[1].'.png">';
		if ( $download[1] === 'err' )
		{
			$html.=$download[0];
		}
		else
		{
			$html.='<a href="?shell_file_get='.$id.'">'.$download[0].'</a></td><td align="right"><b>'.bytes($download[3]).'</b>';
		}
		$html.='</td></tr>';
	}
	$html.='</table><hr></body></html>';
	header('Content-Type: text/html; charset=utf-8');
	echo $html;
	print_r($torrentdirs);
}