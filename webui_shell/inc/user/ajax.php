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
 * File Name: ajax.php
 * 	File containing the ajax (although JSON instead of XML) script
 *
 * File Author:
 * 		Tjores Maes (lordalderaan@gmail.com)
 *
 * Contributors:
 *		asrael...
 */

$response['logger']='';
$fails=$database->getfails($_SESSION['userid'],true);
if (count($fails)>0)
{
	$response['logger']='<font color="red">'.$fails[0]['errorstr'].'</font>';
}
$options=$database->getoptions($_SESSION['userid']);
// Check for Allow_Diskspace_Info option
if ( $options['Allow_Diskspace_Info'] === '1' )
{
	// Split multi-dir
	if ( strpos($_SESSION['torrentdir'],'|') === false )
	{
		$torrentdirs=Array($_SESSION['torrentdir']);
	}
	else
	{
		$torrentdirs=explode('|',$_SESSION['torrentdir']);
	}
	if ( is_array($torrentdirs) && count($torrentdirs) > 0 )
	{
		// Only process first torrentdir
		$torrentdir=$torrentdirs[0];
		// Check if torrentdir exists.
		if ( file_exists($torrentdir) )
		{
			$getdate = date('Y.m.d. H:i:s');
			$totalspace = bytes(disk_total_space($torrentdir));
			$freespace = bytes(disk_free_space($torrentdir));
			$response['diskinfo']='<b>'.$lang['totalspace'].'</b> '.$totalspace.' <b>'.$lang['freespace'].'</b> '.$freespace.' <b>'.$lang['date'].'</b> '.$getdate;
		}
	}
}
/// Quota info
$quota_t=false;
$quota_c=false;
if ( is_numeric($options['Quota_Max_Torrents']) && $options['Quota_Max_Torrents'] > 0 ) 
{
	$instance=$database->getinstances($_SESSION['userid'],'userid');
	$quota=0;
	if ( $options['Show_All_Torrents'] === '1' )
	{
		$torrents=$database->gettorrents(0,$instance['instanceid']);
		$quota=count($torrents);
	}
	elseif ($options['Show_Unclaimed_Torrents'] === '1')
	{
		$torrents=$database->gettorrents($_SESSION['userid'],$instance['instanceid'],true);
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
		$torrents=$database->gettorrents($_SESSION['userid'],$instance['instanceid']);
		$quota=count($torrents);
	}
	$response['quotainfo']='<b>'.$lang['torrents'].'</b> ';
	if ( $quota >= $options['Quota_Max_Torrents'] )
	{
		$response['quotainfo'].='<font color="red">'.$quota.'/'.$options['Quota_Max_Torrents'].'</font>';
	}
	else
	{
		$response['quotainfo'].=$quota.'/'.$options['Quota_Max_Torrents'];
	}	
}
if ( is_numeric($options['Quota_Max_Combinedsize']) && $options['Quota_Max_Combinedsize'] > 0 )
{
	$instance=$database->getinstances($_SESSION['userid'],'userid');
	$quota=0;
	if ( $options['Show_All_Torrents'] === '1' )
	{
		$torrents=$database->gettorrents(0,$instance['instanceid']);
		foreach($torrents as $torrent)
		{
			$quota+=$torrent['size'];
		}
	}
	elseif ($options['Show_Unclaimed_Torrents'] === '1')
	{
		$torrents=$database->gettorrents($_SESSION['userid'],$instance['instanceid'],true);
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
		$torrents=$database->gettorrents($_SESSION['userid'],$instance['instanceid']);
		foreach($torrents as $torrent)
		{
			$quota+=$torrent['size'];
		}
	}
	if(!array_key_exists('quotainfo',$response))
	{
		$response['quotainfo']='<b>'.$lang['size'].'</b> ';
	}
	else
	{
		$response['quotainfo'].=' <b>'.$lang['size'].'</b> ';
	}
	if ( $quota >= $options['Quota_Max_Combinedsize'] )
	{
		$response['quotainfo'].='<font color="red">'.bytes($quota).'/'.bytes($options['Quota_Max_Combinedsize']).'</font> ';
	}
	else
	{
		$response['quotainfo'].=bytes($quota).' '.$lang['of'].' '.bytes($options['Quota_Max_Combinedsize']).' ';
	}
}
/// upload-download statistics based on settings.dat
if ( $options['Allow_DLUL_Statistics'] === '1' )
{
    $getinstance=$database->getinstances($_SESSION['instanceid']);
    $settingsdat=$getinstance['settingsdat'];
    if ( $settingsdat != NULL )
    {
      if ( file_exists($settingsdat) )
      { 
        if ( is_readable($settingsdat) )
        {   
            $decode = new BDecode;
            $readfile = $decode->decodeDict(file_get_contents($settingsdat));
            $td = bytes($readfile[0]["td"]);
            $tu = bytes($readfile[0]["tu"]);
            $getdate = date('Y.m.d. H:i:s');
            $response['statinfo']='<b>'.$lang['totaldown'].'</b> '.$td.' <b>'.$lang['totalup'].'</b> '.$tu.' <b>'.$lang['date'].'</b> '.$getdate;
        }
      }
    }
}


echo json_encode($response);
?>
