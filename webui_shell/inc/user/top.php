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
 * File Name: top.php
 * 	File containing the topbar
 *
 * File Author:
 * 		Tjores Maes (lordalderaan@gmail.com)
 *
 * Contributors:
 *		asrael...
 */

header('Content-Type: text/html; charset=utf-8');
$msgstr='';
$html_javascript='';
$html_buttons='';
$html_divs='';
$html_ajax='';

$options=$database->getoptions($_SESSION['userid']);

// Check for allow_password_change option
if ( $options['Allow_Changing_Password'] === '1' )
{
	// Handle password change:
	If ( array_key_exists('do',$_REQUEST) )
	{
		if ( $_REQUEST['do'] === 'changepass' )
		{
			if ( !array_key_exists('oldpass',$_REQUEST) || !array_key_exists('newpass',$_REQUEST) || !array_key_exists('newpass2',$_REQUEST) || empty($_REQUEST['oldpass']) || empty($_REQUEST['newpass']) || empty($_REQUEST['newpass2']) )
			{
				$msgstr='<font color=red>'.$lang['failed'].': '.$lang['pwallfields'].'</font>';
			}
			else
			{
				if ( $_REQUEST['newpass'] !== $_REQUEST['newpass2'] )
				{
					$msgstr='<font color=red>'.$lang['failed'].': '.$lang['pwnew'].'</font>';
				}
				else
				{
					//try old pass
					$login=$database->login('norm',$_SESSION['username'],$_REQUEST['oldpass']);
					if ( $login === false )
					{
						$msgstr='<font color=red>'.$lang['failed'].': '.$lang['pwold'].'</font>';
					}
					else
					{
						if ( $_SESSION['userid'] !== $login['userid'] )
						{
							$msgstr='<font color=red>'.$lang['failed'].': '.$lang['pwuserid'].'</font>';
						}
						else
						{
							// Update password
							$try=$database->upd('users',Array('pw'=>$_REQUEST['newpass']),Array('userid'=>$login['userid']));
							if ($try === false)
							{
								$msgstr='<font color=red>'.$lang['failed'].': '.$lang['pwerror'].'</font>';
							}
							else
							{
								$msgstr='<font color=green>'.$lang['pwsuccess'].'</font>';
							}
						}
					}
				}
			}
		}
	}
	// Add link
	$html_buttons.='<td style="text-align:center;"><a href="#" onclick="showdiv(\'divpass\',true);">'.$lang['changepass'].'</a></td>'."\r\n";
	// Add div
	$html_divs.='<div id="divpass" style="position:absolute;top:16px;left:0px;width:100%;height:28px;display:none;border-top:1px solid black;">'."\r\n";
	$html_divs.='	<table style="border-collapse:collapse;">'."\r\n";
	$html_divs.='	<form method="post" action="shell_top.php">'."\r\n";
	$html_divs.='	<tr>'."\r\n";
	$html_divs.='	<td style="width:240px;height:28px;">'.$lang['oldpass'].'<input type="password" name="oldpass" /></td>'."\r\n";
	$html_divs.='	<td style="width:240px;">'.$lang['newpass1'].'<input type="password" name="newpass" /></td>'."\r\n";
	$html_divs.='	<td style="width:240px;">'.$lang['newpass2'].'<input type="password" name="newpass2" /></td>'."\r\n";
	$html_divs.='	<td><input type="submit" class="NFButton" value="'.$lang['changepass'].'" /><input type="button" class="NFButton" value="'.$lang['cancel'].'" onclick="showdiv(\'divpass\',false);" /></td>'."\r\n";
	$html_divs.='	<input name="do" type="hidden" value="changepass" />'."\r\n";
	$html_divs.='	</form>'."\r\n";
	$html_divs.='	</tr>'."\r\n";
	$html_divs.='	</table>'."\r\n";
	$html_divs.='</div>'."\r\n";
}

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
	// Check for Allow_Diskspace_Info option
	if ( $options['Allow_Diskspace_Info'] === '1' )
	{
		// Only process first torrentdir
		$torrentdir=$torrentdirs[0];
		// Check if torrentdir exists.
		if ( file_exists($torrentdir) )
		{
			// Add link
			$html_buttons.='<td style="text-align:center;"><a href="#" onclick="showdiv(\'divspace\',true);">'.$lang['showspace'].'</a></td>'."\r\n";
			// Add div
			$html_divs.='<div id="divspace" style="position:absolute;top:16px;left:0px;width:100%;height:28px;display:none;border-top:1px solid black;">'."\r\n";
			$html_divs.='	<table style="border-collapse:collapse;">'."\r\n";
			$html_divs.='	<form method="post" action="shell_top.php">'."\r\n";
			$html_divs.='	<tr>'."\r\n";
			$html_divs.='	<td id="diskinfo" style="width:320px;height:28px;"></td>'."\r\n";
			$html_divs.='	<td style="width:250px;"><input type="button" class="NFButton" value="'.$lang['cancel'].'" onclick="showdiv(\'divspace\',false);" /></td>'."\r\n";
			$html_divs.='	</form>'."\r\n";
			$html_divs.='	</tr>'."\r\n";
			$html_divs.='	</table>'."\r\n";
			$html_divs.='</div>'."\r\n";
		}
	}
	if ( $options['Allow_Downloading_Files'] === '1' )
	{
			$html_buttons.='<td style="text-align:center;"><a href="shell_filemain.php" target="_filemain">'.$lang['downloadfiles'].'</a></td>'."\r\n";
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

			// Add link
		    $html_buttons.='<td style="text-align:center;"><a href="#" onclick="showdiv(\'divstat\',true);">'.$lang['statistics'].'</a></td>'."\r\n";
			// Add div
		    $html_divs.='<div id="divstat" style="position:absolute;top:16px;left:0px;width:100%;height:28px;display:none;border-top:1px solid black;">'."\r\n";
		    $html_divs.='	<table style="border-collapse:collapse;">'."\r\n";
		    $html_divs.='	<form method="post" action="shell_top.php">'."\r\n";
		    $html_divs.='	<tr>'."\r\n";
		    $html_divs.='	<td id="statinfo" style="width:320px;height:28px;"></td>'."\r\n";
		    $html_divs.='	<td style="width:250px;"><input type="button" class="NFButton" value="'.$lang['cancel'].'" onclick="showdiv(\'divstat\',false);" /></td>'."\r\n";
		    $html_divs.='	</form>'."\r\n";
		    $html_divs.='	</tr>'."\r\n";
		    $html_divs.='	</table>'."\r\n";
		    $html_divs.='</div>'."\r\n";
        }
      }
    }
}


/// Quota info
$quota_t=false;
$quota_c=false;
if ( is_numeric($options['Quota_Max_Torrents']) && $options['Quota_Max_Torrents'] > 0 ) 
{
	$quota_t=true;
}
if ( is_numeric($options['Quota_Max_Combinedsize']) && $options['Quota_Max_Combinedsize'] > 0 )
{
	$quota_c=true;
}
if ( $quota_t || $quota_c )
{
	// Add link
	$html_buttons.='<td style="text-align:center;"><a href="#" onclick="showdiv(\'divquota\',true);">'.$lang['showquota'].'</a></td>'."\r\n";
	// Add div
	$html_divs.='<div id="divquota" style="position:absolute;top:16px;left:0px;width:100%;height:28px;display:none;border-top:1px solid black;">'."\r\n";
	$html_divs.='	<table style="border-collapse:collapse;">'."\r\n";
	$html_divs.='	<form method="post" action="shell_top.php">'."\r\n";
	$html_divs.='	<tr>'."\r\n";
	$html_divs.='	<td id="quotainfo" style="width:320px;height:28px;"></td>'."\r\n";
	$html_divs.='	<td style="width:250px;"><input type="button" class="NFButton" value="'.$lang['cancel'].'" onclick="showdiv(\'divquota\',false);" /></td>'."\r\n";
	$html_divs.='	</form>'."\r\n";
	$html_divs.='	</tr>'."\r\n";
	$html_divs.='	</table>'."\r\n";
	$html_divs.='</div>'."\r\n";
}

// Quick estimate of loading times - This is gonna need a lot of tweaking.
$eta=count($database->gettorrents(0,$_SESSION['instanceid']));
if ($options['Show_Unclaimed_Torrents'] === '1')
{
	$eta=round(($eta*count($database->gettorrents($_SESSION['userid'],$_SESSION['instanceid']))*0.0015)+($eta*0.08));
}
else
{
	$eta=round($eta*0.08);
}
?>
<html>
<head>
<script type="text/javascript" src="shell_mootools.js"></script>
<SCRIPT type="text/javascript">
function ajax() {
	dorequest();
	setTimeout("ajax();",3000);
}
function showdiv (divname,toggle) {
	if ( toggle == true )
	{
		parent.document.getElementById("main").rows="45,*";
		closedivs();
		document.getElementById(divname).style.display = "block";
	}
	else
	{
		parent.document.getElementById("main").rows="15,*";
		closedivs();
	}
}
function dorequest () {
	var jsonRequest = new Request.JSON({
		"url":"shell_ajax.php",
		"method":"get",
		"async":"false",
		"onSuccess":function(response){
			for ( prop in response) {
				document.getElementById(prop).innerHTML = response[prop];
			}
		}
	}).send();
}
function closedivs () {
	var divs=document.getElementsByTagName("div");
	for (i=0;i < divs.length;i++) {
		divs[i].style.display = "none";
	}
}
setTimeout("ajax();",<?php echo ($eta*1000);?>);
parent.document.getElementById("main").rows="15,*";
</script>
<style type="text/css">
.NFButton {width:auto; height:26px; color:#000; padding:0 2px; background:#ffffff; cursor:pointer; border:solid 1px grey; font:10px/26px Tahoma, Arial, Helvetica, sans-serif; font-weight:bold; text-transform:uppercase; letter-spacing:1px; vertical-align:middle;}
.NFButtonwoborder {width:auto; height:26px; color:#000; padding:0 2px; background:#ffffff; cursor:pointer; border:none; font:10px/26px Tahoma, Arial, Helvetica, sans-serif; font-weight:bold; text-transform:uppercase; letter-spacing:1px; vertical-align:middle;}
body { margin:0px; }
span { white-space:nowrap; }
td { font-size:11px;font-family:Tahoma,Verdana,Arial,Helvetica,sans-serif;white-space:nowrap;padding:0px 4px 0px 4px; }
</style>
</head>
<body>
<table style="width:100%;border-collapse:collapse;padding:0px;">
<tr>
<td style="width:110px;"><span>&#181;Torrent Webui-Shell</span></td>
<td style="width:160px;"><span><?php echo $msgstr;?></span></td>
<?php echo $html_buttons; ?>
<td style="width:230px;"><span id="logger">Estimated loading time is <?php echo $eta;?> seconds.</span></td>
<td style="width:40px;" align="right"><a target="_top" href="<?php echo $cfg['altname']?>?shell_logout=1"><?php echo $lang['logout'];?></a></td>
</tr>
</table>
<?php echo $html_divs; ?>
</body>
</html>
