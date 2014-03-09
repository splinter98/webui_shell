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
 * 	File containing the admin panel ajax (although JSON instead of XML) script
 *
 * File Author:
 * 		Tjores Maes (lordalderaan@gmail.com)
 *
 * Contributors:
 *
 */

// auth options
$auth_options=Array(
'User_Disabled'=>Array('default'=>0,'type'=>'BOOL','desc'=>'Allows you to disable this login without deleting it.'),
'Topbar_Disabled'=>Array('default'=>0,'type'=>'BOOL','desc'=>'This option hides the topbar which contains the logout link and some of the options below.'),
'Allow_Changing_Password'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Allows the user to change his own password.'),
'Allow_Diskspace_Info'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Allows the user to see total and free space on disk where the Torrentdir is located.'),
'Allow_Downloading_Files'=>Array('default'=>0,'type'=>'BOOL','desc'=>'Allows the user to download the files of his torrents through the Webui-Shell.'),
'Max_Downloading_Speed'=>Array('default'=>0,'type'=>'INT','desc'=>'The maximum speed at which users can download files in bytes. 0 for unlimited.'),
'Allow_DLUL_Statistics'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Allows the user to see the overall total download and upload of his utorrent instance. These are the stats you get from Help -> Show Statistics in utorrent itself and are not per user.'),
'Change_Settings'=>Array('default'=>0,'type'=>'BOOL','desc'=>'Allow this user to view and change the utorrent settings.'),
'View_Torrent_Properties'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Allow this user to view torrent properties. Handy when you want to hide passkeys from a guest.'),
'Set_Torrent_Properties'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Allow this user to change torrent properties.'),
'Start_Stop_Pause_Unpause'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Allow this user to start, stop, pause and unpause a torrent.'),
'Force_Start'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Allow this user to force start a torrent.'),
'Recheck'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Allow this user to do a re-check of a torrent.'),
'Remove'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Allow this user to remove torrents from utorrent.'),
'Remove_Data'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Allow this user to remove torrents from utorrent and delete its data.'),
'Set_File_Priority'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Allow this user to set the priority of individual files in torrents.'),
'Add_Torrents'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Allow this user to add new torrents.'),
'Allow_Existing_Torrents'=>Array('default'=>1,'type'=>'BOOL','desc'=>'If you set this to 0 the Shell will refuse to add a torrent that is already running in utorrent. If you set it to 1 and this user adds a torrent that already exists the user will also get all rights over it as if it added the torrent himself. Note that the torrent isn\'t actually changed, it is simply added to the allowed torrent list of that user.'),
'Unassign_on_Remove'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Only unassign a torrent from this user\'s allowed torrent list (instead of removing it from utorrent) if this user is not the only one who has this torrent in its list.'),
'Show_All_Torrents'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Allow this user to see all torrents regardless of whether they are in his torrent list.'),
'Show_Unclaimed_Torrents'=>Array('default'=>0,'type'=>'BOOL','desc'=>'Allow this user to see torrents that are in nobody\'s torrent list. Please note that this can significantly slow loading times.'),
'Allow_Manage_Queue'=>Array('default'=>0,'type'=>'BOOL','desc'=>'Allow this user to change the global queue position of his torrents. Please note that while users can only issue commands on their own torrents this may affect other torrents.'),
'Enable_HTTP_Auth'=>Array('default'=>0,'type'=>'BOOL','desc'=>'This enables authentication through HTTP Auth. This is the same system utorrent itself uses. By enabling this the user can use a variety of Community Projects that otherwise wouldn\'t work through the Webui-Shell.'),
'Enable_IP_Auth'=>Array('default'=>0,'type'=>'BOOL','desc'=>'This enables authentication using IP. By filling in an IP in the IP field anyone coming from that IP will be recognized as this user. By enabling this this user can also use a variety of Community Projects that previously wouldn\'t work but only from the IP specified. WARNING! You won\'t be able to login for any other account from that IP.'),
'Token_Auth'=>Array('default'=>1,'type'=>'BOOL','desc'=>'Require token authentication just like the regular API. To prevent xss attacks.'),
'Remember_Last_IP'=>Array('default'=>0,'type'=>'BOOL','desc'=>'Every time this user logs in his IP will be saved to the IP field in the details section. WARNING!! In combination with Enable IP Auth this locks an IP to this account after the first login. If you leave Enable IP Auth disabled the last IP this user used will be saved in the IP field in the details section. Only one (the last) IP can be saved.'),
'Allow_Unknown_Actions'=>Array('default'=>0,'type'=>'BOOL','desc'=>'This should best be left at 0. It means that the Shell will block any action it sees but doesn\'t know (yet).'),
'Quota_Max_Torrents'=>Array('default'=>0,'type'=>'INT','desc'=>'Set the max number of torrents this user is allowed to have. 0 for unlimited.'),
'Quota_Max_Combinedsize'=>Array('default'=>0,'type'=>'INT','desc'=>'Set the max number of bytes this user is allowed to have torrents running for. 0 for unlimited. New torrents will be refused if the total size of all torrents of this user has running would go over this value. Please note that it is recommended to disable the option \'Remove\' and only allow the option \'Remove_Data\' because removing a torrent without removing the data will allow the user to add new torrents while the drive space hasn\'t actually been freed up.')
);
$response=Array();
$id=empty($_REQUEST['shell_q'])?'error':$_REQUEST['shell_q'];
if (!is_numeric($id))
{
	$response['logger']='<font color="red">['.date("H:i:s").'] Invalid ID</font>';
}
else
{
	switch ($_REQUEST['shell_t'])
	{
		case 'misclist':
			// Save changes
			if(array_key_exists('shell_f',$_REQUEST) && !empty($_REQUEST['shell_f']) && array_key_exists('shell_v',$_REQUEST) && !empty($_REQUEST['shell_v']))
			{
				if(array_key_exists('shell_a',$_REQUEST) && !empty($_REQUEST['shell_a']))
				{
					if ( $_REQUEST['shell_a'] === 'del' )
					{
						$try=$database->delinfo($_REQUEST['shell_f']);
						if ($try === false)
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Deleting '.$_REQUEST['shell_f'].' failed. Database error.</font>';
							break;
						}
						$response['logger']='<font color="green">['.date("H:i:s").'] Deleted '.$_REQUEST['shell_f'].'.</font>';
					}
				}
				else
				{
					$try=$database->upd('info',Array('attr'=>$_REQUEST['shell_f'],'value'=>$_REQUEST['shell_v']));
					if ($try === false)
					{
						$response['logger']='<font color="red">['.date("H:i:s").'] Changing '.xsssanitize($_REQUEST['shell_f']).' to "<b>'.xsssanitize($_REQUEST['shell_v']).'</b>" failed: Database error.</font>';
						break;
					}
					$response['logger']='<font color="green">['.date("H:i:s").'] Changed '.xsssanitize($_REQUEST['shell_f']).' to "<b>'.xsssanitize($_REQUEST['shell_v']).'</b>".</font>';
				}
			}
			// Return info
			$response['item']='<table>';
			$response['item'].='<tr><td><b>Info</b></td><td><b>Value</b></td><td style="text-align: center;"><b>Delete</b></td></tr>';
			$response['item'].='<tr><td>Database version:</td><td>'.$database->getinfo('version').'</td></tr>';
			$response['item'].='<tr><td title="To enable the button fill in the path to the index.html in the webui.zip file. To disable the button delete the value using the X."><a target="_blanc" href="http://forum.utorrent.com/viewtopic.php?id=47167">miniui</a> path:</td>';
			$response['item'].='<td><input style="width:400px;" onkeypress="key(event,this);" onchange="dorequest(\'misclist\',-1,\'miniui\',this.value,this);" value="'.$database->getinfo('miniui').'"></td>';
			$response['item'].='<td style="text-align: center;" onmouseover="mouseOver(this);" onmouseout="mouseOut(this);" onclick="confirm_dorequest(\'Are you sure you want to disable the miniui button?\',\'misclist\',-1,\'miniui\',\''.$database->getinfo('miniui').'\',this,\'del\');">X</td></tr>';
			$response['item'].='</table>';
		break;
		case 'faillist':
			$response['list']=faillist();
		break;
		case 'fails':
			// Save changes
			if(array_key_exists('shell_a',$_REQUEST) && !empty($_REQUEST['shell_a']))
			{
				if ( $_REQUEST['shell_a'] === 'del' )
				{
					$try=$database->delfails($id);
					if ($try === false)
					{
						$response['logger']='<font color="red">['.date("H:i:s").'] Clearing fails failed. Database error.</font>';
						break;
					}
					$response['logger']='<font color="green">['.date("H:i:s").'] Cleared fails for <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b>.</font>';
					$response['list']=faillist();
				}
			}
			// Return fails
			$response['item']='<table>';
			$response['item'].='<tr><td><b>id</b></td><td><b>date</b></td><td><b>ip</b></td><td><b>userid</b></td><td><b>error</b></td><td><b>query</b></td><td><b>user</b></td></tr>';
			$fails=$database->getfails($id);
			foreach ($fails as $fail)
			{
				$response['item'].='<tr>';
				foreach ($fail as $attr => $value)
				{
					$response['item'].='<td>'.xsssanitize($value).'</td>';
				}
				$response['item'].='</tr>';
			}
			$response['item'].='</table>';
		break;
		case 'instancelist':
			$response['list']=instancelist();
		break;
		case 'instances':
			// Save changes
			if(array_key_exists('shell_a',$_REQUEST) && !empty($_REQUEST['shell_a']))
			{
				if ( $_REQUEST['shell_a'] === 'new' )
				{
					if(array_key_exists('shell_v',$_REQUEST) && !empty($_REQUEST['shell_v']))
					{
						if ($database->getinstances($_REQUEST['shell_v'],'name') !== false)
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Creating instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: Instance already exists.</font>';
							break;
						}
						$try=$database->upd('instances',Array('name'=>$_REQUEST['shell_v']));
						if ($try === false)
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Creating instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed. Database error.</font>';
							break;
						}
						$instance=$database->getinstances($_REQUEST['shell_v'],'name');
						if ($instance !== false)
						{
							$response['logger']='<font color="green">['.date("H:i:s").'] Created instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b>.</font>';
							$id=$instance['instanceid'];
							$response['list']=instancelist();
						}
						else
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Creating instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: Couldn\'t find instance after creation.</font>';
							break;
						}
					}
					else
					{
						$response['logger']='<font color="red">['.date("H:i:s").'] Creating instance failed. Invalid name.</font>';
						break;
					}
				}
				elseif ( $_REQUEST['shell_a'] === 'del' )
				{
					if(array_key_exists('shell_v',$_REQUEST) && !empty($_REQUEST['shell_v']))
					{
						$tempid=$database->getinstances($_REQUEST['shell_v'],'name');
						if ($tempid != false)
						{
							if ( $tempid['instanceid'] != $id )
							{
								$response['logger']='<font color="red">['.date("H:i:s").'] Deleting instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: ID mismatch.</font>';
								break;
							}
							$try=$database->delinstance($id);
							if ($try === false)
							{
								$response['logger']='<font color="red">['.date("H:i:s").'] Deleting instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed. Database error.</font>';
								break;
							}
							$response['logger']='<font color="green">['.date("H:i:s").'] Deleted instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b>.</font>';
							$response['list']=instancelist();
							$response['item']='';
							break;
						}
						else
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Deleting instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: Instance doesn\'t exists.</font>';
							break;
						}
					}
					else
					{
						$response['logger']='<font color="red">['.date("H:i:s").'] Deleting instance failed. Invalid name.</font>';
						break;
					}
				}       
				elseif ( $_REQUEST['shell_a'] === 'reset')
				{
					if(array_key_exists('shell_v',$_REQUEST) && !empty($_REQUEST['shell_v']))
					{
						$tempid=$database->getinstances($_REQUEST['shell_v'],'name');
						if ($tempid != false)
						{
							if ( $tempid['instanceid'] != $id )
							{
								$response['logger']='<font color="red">['.date("H:i:s").'] Reseting instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: ID mismatch.</font>';
								break;
							}
							$getinstance=$database->getinstances($id);
							$settingsdat=$getinstance['settingsdat'];
							if ($settingsdat === false)
							{
								$response['logger']='<font color="red">['.date("H:i:s").'] Reseting instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed. Database error.</font>';
								break;
							}
							if ($settingsdat === NULL)
							{
								$response['logger']='<font color="red">['.date("H:i:s").'] Reseting instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: Settings.dat is not set for the instance.</font>';
								break;
							}
							if (!file_exists($settingsdat))
							{
								$response['logger']='<font color="red">['.date("H:i:s").'] Reseting instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: Settings.dat does not exsist or wrong path.</font>';
								break;
							}
							if (!is_readable($settingsdat))
							{
								$response['logger']='<font color="red">['.date("H:i:s").'] Reseting instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: Settings.dat is not readable by network service. Please check permissions.</font>';
								break;
							}
							if (!is_writable($settingsdat))
							{
								$response['logger']='<font color="red">['.date("H:i:s").'] Reseting instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: Settings.dat is not writable by network service. Please check permissions.</font>';
								break;
							}
							$decode = new BDecode;
							$beolvas = $decode->decodeDict(file_get_contents($settingsdat));
							$beolvas[0]["td"]=0;
							$beolvas[0]["tu"]=0;
							$beolvas[0][".fileguard"]=0;
							$encode = new BEncode;
							$encode->encodeDict($beolvas[0],$writeit);
							$fp = fopen($settingsdat, 'w');
							if ($fp === false)
							{
								$response['logger']='<font color="red">['.date("H:i:s").'] Reseting instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed. Could not open with write permission.</font>';
								break;
							}
							$fw = fwrite($fp, $writeit);
							if ($fw === false)
							{
								$response['logger']='<font color="red">['.date("H:i:s").'] Reseting instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed. Something went wront.</font>';
								break;
							}            	            	
							fclose($fp);
							$response['logger']='<font color="green">['.date("H:i:s").'] Reseted instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b>.</font>';
							$response['list']=instancelist();
							$response['item']='';
							break;
						}
						else
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Reseting instance <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: Instance doesn\'t exists.</font>';
							break;
						}
					}
				}

			}
			else
			{
				if(array_key_exists('shell_f',$_REQUEST) && !empty($_REQUEST['shell_f']))
				{
					$try=$database->upd('instances',Array($_REQUEST['shell_f']=>$_REQUEST['shell_v']),Array('instanceid'=>$id));
					if ($try === false)
					{
						$response['logger']='<font color="red">['.date("H:i:s").'] Changing '.xsssanitize($_REQUEST['shell_f']).' to "<b>'.xsssanitize($_REQUEST['shell_v']).'</b>" failed: Database error.</font>';
						break;
					}
					$response['logger']='<font color="green">['.date("H:i:s").'] Changed '.xsssanitize($_REQUEST['shell_f']).' to "<b>'.xsssanitize($_REQUEST['shell_v']).'</b>".</font>';
				}
			}
			// Return instance
			if ( $id == -1 )
			{
				$response['item']='';
				break;
			}
			$instance=$database->getinstances($id);
			$response['item']='<table>';
			foreach ($instance as $attr => $value)
			{
				if ($attr != 'instanceid')
				{
					$response['item'].='<tr><td>'.xsssanitize($attr).':</td><td><input style="width:400px;" onkeypress="key(event,this);" onchange="dorequest(\'instances\','.xsssanitize($id).',\''.xsssanitize($attr).'\',this.value,this);" value="'.xsssanitize($value).'"></td></tr>';
				}
			}
			$response['item'].='</table>';
		break;	
		case 'userlist':
			$response['list']=userlist();
		break;
		case 'users':
			// Save changes
			if(array_key_exists('shell_a',$_REQUEST) && !empty($_REQUEST['shell_a']))
			{
				if ( $_REQUEST['shell_a'] === 'new' )
				{
					if(array_key_exists('shell_v',$_REQUEST) && !empty($_REQUEST['shell_v']))
					{
						if ($database->getid($_REQUEST['shell_v'])>0)
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Creating user <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: User already exists.</font>';
							break;
						}
						$try=$database->upd('users',Array('name'=>$_REQUEST['shell_v']));
						if ($try === false)
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Creating user <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed. Database error.</font>';
							break;
						}
						$id=$database->getid($_REQUEST['shell_v']);
						if ($id>0)
						{
							$response['logger']='<font color="green">['.date("H:i:s").'] Created user <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b>.</font>';
							$response['list']=userlist();
						}
						else
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Creating user <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: Couldn\'t find user after creation.</font>';
							break;
						}
					}
					else
					{
						$response['logger']='<font color="red">['.date("H:i:s").'] Creating user failed. Invalid name.</font>';
						break;
					}
				}
				elseif ( $_REQUEST['shell_a'] === 'del' )
				{
					if(array_key_exists('shell_v',$_REQUEST) && !empty($_REQUEST['shell_v']))
					{
						$tempid=$database->getid($_REQUEST['shell_v']);
						if ($tempid>0)
						{
							if ( $tempid != $id )
							{
								$response['logger']='<font color="red">['.date("H:i:s").'] Deleting user <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: ID mismatch.</font>';
								break;
							}
							$try=$database->deluser($id);
							if ($try === false)
							{
								$response['logger']='<font color="red">['.date("H:i:s").'] Deleting user <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed. Database error.</font>';
								break;
							}
							$response['logger']='<font color="green">['.date("H:i:s").'] Deleted user <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b>.</font>';
							$response['list']=userlist();
							$response['item']='';
							break;
						}
						else
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Deleting user <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: User doesn\'t exists.</font>';
							break;
						}
					}
					else
					{
						$response['logger']='<font color="red">['.date("H:i:s").'] Deleting user failed. Invalid name.</font>';
						break;
					}
				}
			}
			// Return user
			if ( $id == -1 )
			{
				$response['item']='';
				break;
			}
			$response['item']='<table>';
			foreach (Array('details','options','actions','torrents') as $section)
			{
				$response['item'].='<tr><td onmouseover="mouseOver(this);" onmouseout="mouseOut(this);" onclick="if ( toggle(\''.$section.'\') == true ) {dorequest(\''.$section.'\','.xsssanitize($id).')};"><b>'.$section.'</b></td></tr>';
				$response['item'].='<tr><td id="'.$section.'"></td></tr>';
			}
			$response['item'].='</td></tr></table>';
		break;
		case 'details':
			$instances=$database->getinstances(0);
			// Save changes
			if(array_key_exists('shell_f',$_REQUEST) && !empty($_REQUEST['shell_f']))
			{
				$try=$database->upd('users',Array($_REQUEST['shell_f']=>$_REQUEST['shell_v']),Array('userid'=>$id));
				if ($try === false)
				{
					$response['logger']='<font color="red">['.date("H:i:s").'] Changing '.xsssanitize($_REQUEST['shell_f']).' to "<b>'.xsssanitize($_REQUEST['shell_v']).'</b>" failed: Database error.</font>';
					break;
				}
				$response['logger']='<font color="green">['.date("H:i:s").'] Changed '.xsssanitize($_REQUEST['shell_f']).' to "<b>'.xsssanitize($_REQUEST['shell_v']).'</b>".</font>';
			}
			// Return new section
			$users=$database->getusers();
			$user=$users[$id];
			$response['details']='<div><table>';
			foreach ($user as $field => $value)
			{
				if ( $field == 'pw' )
				{
					$response['details'].='<tr><td>'.xsssanitize($field).':</td><td><input style="width:400px;" onkeypress="key(event,this);" onchange="dorequest(\'details\','.xsssanitize($id).',\''.xsssanitize($field).'\',this.value,this);" value="*****"></td></tr>';
				}
				elseif ( $field !== 'userid' && $field !== 'instanceid') 
				{
					$response['details'].='<tr><td>'.xsssanitize($field).':</td><td><input style="width:400px;" onkeypress="key(event,this);" onchange="dorequest(\'details\','.xsssanitize($id).',\''.xsssanitize($field).'\',this.value,this);" value="'.xsssanitize($value).'"></td></tr>';
				}
			}
			// Instance
			$response['details'].='<tr><td>instance:</td><td>';
			$response['details'].='<select style="width:400px;" onchange="dorequest(\'details\','.xsssanitize($id).',\'instanceid\',this.value,this);">';
			$response['details'].='<option value="-1">--no instance--</option>';
			foreach ($instances as $instance) 
			{
				$response['details'].='<option ';
				if ($user['instanceid'] == $instance['instanceid'])
				{
					$response['details'].='SELECTED ';
				}
				$response['details'].='value="'.xsssanitize($instance['instanceid']).'">'.xsssanitize($instance['name']).'</option>';
			}
			$response['details'].='</select></td></tr>';
			$response['details'].='</table></div>';
		break;
		case 'options':
			// Save changes
			if(array_key_exists('shell_f',$_REQUEST) && !empty($_REQUEST['shell_f']))
			{
				if ( !array_key_exists($_REQUEST['shell_f'],$auth_options) )
				{
					$response['logger']='<font color="red">['.date("H:i:s").'] Changing '.xsssanitize($_REQUEST['shell_f']).' to "<b>'.xsssanitize($_REQUEST['shell_v']).'</b>" failed: Unkown option.</font>';
					break;
				}
				if (  $auth_options[$_REQUEST['shell_f']]['type'] === 'BOOL' )
				{
					$value=0;
					if (isset($_REQUEST['shell_v']) && $_REQUEST['shell_v'] == 'true')
					{
						$value=1;
					}
				}
				elseif (  $auth_options[$_REQUEST['shell_f']]['type'] === 'INT' )
				{
					if (!is_numeric($_REQUEST['shell_v']))
					{
						if(preg_match("/^([\d.]+)\s?([egkmptEGKMPT])?([bB])/",$_REQUEST['shell_v'],$matches)==1)
						{
							if(!is_numeric($temp=unbytes($matches[1],$matches[2].$matches[3])))
							{
								$response['logger']='<font color="red">['.date("H:i:s").'] Changing '.xsssanitize($_REQUEST['shell_f']).' to "<b>'.xsssanitize($_REQUEST['shell_v']).'</b>" failed: Not a number.</font>';
								break;
							}
							$_REQUEST['shell_v']=$temp;
						}
						else
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Changing '.xsssanitize($_REQUEST['shell_f']).' to "<b>'.xsssanitize($_REQUEST['shell_v']).'</b>" failed: Not a number.</font>';
							break;
						}
					}
					$value=$_REQUEST['shell_v'];
				}
				else
				{
					$response['logger']='<font color="red">['.date("H:i:s").'] Changing '.xsssanitize($_REQUEST['shell_f']).' to "<b>'.xsssanitize($_REQUEST['shell_v']).'</b>" failed: Unkown type.</font>';
					break;
				}
				$try=$database->upd('options',Array('value'=>$value),Array('userid'=>$_REQUEST['shell_q'],'useroption'=>$_REQUEST['shell_f']));
				if ($try === false)
				{
					$response['logger']='<font color="red">['.date("H:i:s").'] Changing '.xsssanitize($_REQUEST['shell_f']).' to "<b>'.xsssanitize($_REQUEST['shell_v']).'</b>" failed.</font>';
					break;
				}
				$response['logger']='<font color="green">['.date("H:i:s").'] Changed '.xsssanitize($_REQUEST['shell_f']).' to "<b>'.xsssanitize($_REQUEST['shell_v']).'</b>".</font>';
			}
			// Return new section
			$options=$database->getoptions($id);
			$response['options']='<div><table>';
			foreach ($auth_options as $field => $props)
			{
				if(!array_key_exists($field,$options))
				{
					// Add option if not exist
					$database->upd('options',Array('userid'=>$id,'useroption'=>$field,'value'=>$props['default']));
					$options[$field]=$props['default'];
				}
				$response['options'].='<tr><td title="'.xsssanitize($field).': '.xsssanitize($props['desc']).'">'.xsssanitize($field).':</td><td>';
				if ( $props['type'] == 'BOOL' )
				{
					$response['options'].='<input type="checkbox" onclick="dorequest(\'options\','.xsssanitize($id).',\''.xsssanitize($field).'\',this.checked,this);"';
					if ( xsssanitize($options[$field]) == 1 ) 
					{
						$response['options'].=' checked="checked"';
					}
					$response['options'].='>';
				}
				else
				{
					$response['options'].='<input style="width:200px;" onkeypress="key(event,this);" onchange="dorequest(\'options\','.xsssanitize($id).',\''.xsssanitize($field).'\',this.value,this);" value="'.xsssanitize($options[$field]).'">';
				}
				$response['options'].='</td></tr>';
			}
			$response['options'].='</table></div>';
		break;
		case 'actions':
			// Save changes
			if(array_key_exists('shell_f',$_REQUEST) && !empty($_REQUEST['shell_f']))
			{
				if(array_key_exists('shell_a',$_REQUEST) && !empty($_REQUEST['shell_a']))
				{
					if ( $_REQUEST['shell_a'] === 'new' )
					{
						$try=$database->upd('actions',Array('userid'=>$id,'action'=>$_REQUEST['shell_f'],'value'=>$_REQUEST['shell_v']));
						if ($try === false)
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Adding action <b>"'.xsssanitize($_REQUEST['shell_f']).'"</b> failed.</font>';
							break;
						}
						$response['logger']='<font color="green">['.date("H:i:s").'] Added action <b>"'.xsssanitize($_REQUEST['shell_f']).'"</b>.</font>';
					}
					elseif ( $_REQUEST['shell_a'] === 'del' )
					{
						$try=$database->delaction($id,$_REQUEST['shell_f']);
						if ($try === false)
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Deleting action <b>"'.xsssanitize($_REQUEST['shell_f']).'"</b> failed.</font>';
							break;
						}
						$response['logger']='<font color="green">['.date("H:i:s").'] Deleted action <b>"'.xsssanitize($_REQUEST['shell_f']).'"</b>.</font>';
					}
				}
				else
				{
					$value=0;
					if (isset($_REQUEST['shell_v']) && $_REQUEST['shell_v'] == 'true')
					{
						$value=1;
					}
					$try=$database->upd('actions',Array('value'=>$value),Array('userid'=>$_REQUEST['shell_q'],'action'=>$_REQUEST['shell_f']));
					if ($try === false)
					{
						$response['logger']='<font color="red">['.date("H:i:s").'] Changing allow action '.xsssanitize($_REQUEST['shell_f']).' to "<b>'.xsssanitize($_REQUEST['shell_v']).'</b>" failed.</font>';
						break;
					}
					$response['logger']='<font color="green">['.date("H:i:s").'] Changed allow action '.xsssanitize($_REQUEST['shell_f']).' to "<b>'.xsssanitize($_REQUEST['shell_v']).'</b>".</font>';
				}
			}
			// Return new section
			$response['actions']='<div><table>';
			$response['actions'].='<tr><td><b>Action</b></td><td><b>Allow</b></td><td style="text-align: center;"><b>Delete</b></td></tr>';
			foreach ($database->getactions($id) as $field => $value)
			{
				$response['actions'].='<tr><td>'.xsssanitize($field).'</td>';
				$response['actions'].='<td><input type="checkbox" onclick="dorequest(\'actions\','.xsssanitize($id).',\''.xsssanitize($field).'\',this.checked,this);"';
				if ( xsssanitize($value) == 1 ) 
				{
					$response['actions'].=' checked="checked"';
				}
				$response['actions'].='></td><td style="text-align: center;"><span onmouseover="mouseOver(this);" onmouseout="mouseOut(this);" onclick="dorequest(\'actions\','.xsssanitize($id).',\''.xsssanitize($field).'\',this.innerHTML,this,\'del\');">X</span></td></tr>';

			}
			$response['actions'].='<tr><td colspan="3"><input onkeypress="key(event,this);" onchange="dorequest(\'actions\','.xsssanitize($id).',this.value,\'false\',this,\'new\');"></td></tr>';
			$response['actions'].='</table></div>';
		break;
		case 'torrents':
			$database->cleantorrents($id);
			$users=$database->getusers();
			$user=$users[$id];
			$alltorrents=$database->gettorrents(0,$user['instanceid']);
			$torrents=$database->gettorrents($id,$user['instanceid']);
			// Save changes
			if(array_key_exists('shell_v',$_REQUEST) && !empty($_REQUEST['shell_v']))
			{
				if(array_key_exists('shell_a',$_REQUEST) && !empty($_REQUEST['shell_a']))
				{
					if ( $_REQUEST['shell_a'] === 'new' )
					{
						if ( !array_key_exists($_REQUEST['shell_v'],$alltorrents) )
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Adding torrent with hash <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: Unkown hash.</font>';
							break;
						}
						$try=$database->upd('torrents',Array('userid'=>$id,'instanceid'=>$user['instanceid'],'hash'=>$_REQUEST['shell_v'],'name'=>$alltorrents[$_REQUEST['shell_v']]['name'],'size'=>$alltorrents[$_REQUEST['shell_v']]['size']));
						if ($try === false)
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Adding torrent <b>"'.xsssanitize($alltorrents[$_REQUEST['shell_v']]['name']).'"</b> failed.</font>';
							break;
						}
						$response['logger']='<font color="green">['.date("H:i:s").'] Added torrent <b>"'.xsssanitize($alltorrents[$_REQUEST['shell_v']]['name']).'"</b>.</font>';
					}
					elseif ( $_REQUEST['shell_a'] === 'del' )
					{
						if ( !array_key_exists($_REQUEST['shell_v'],$torrents) )
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Unassigning torrent with hash <b>"'.xsssanitize($_REQUEST['shell_v']).'"</b> failed: Unkown hash.</font>';
							break;
						}
						$try=$database->deltorrent($id,$user['instanceid'],$_REQUEST['shell_v']);
						if ($try === false)
						{
							$response['logger']='<font color="red">['.date("H:i:s").'] Unassigning torrent <b>"'.xsssanitize($alltorrents[$_REQUEST['shell_v']]['name']).'"</b> failed.</font>';
							break;
						}
						$response['logger']='<font color="green">['.date("H:i:s").'] Unassigned torrent <b>"'.xsssanitize($alltorrents[$_REQUEST['shell_v']]['name']).'"</b>.</font>';
					}
				}
			}
			// Return new section
			$torrents=$database->gettorrents($id,$user['instanceid']); // Reload changes.
			$response['torrents']='<div><table>';
			$response['torrents'].='<tr><td><b>Name</b></td><td><b>Size</b></td><td style="text-align: center;"><b>Unassign</b></td></tr>';
			$nosize=false;
			foreach($torrents as $hash => $torrent)
			{
				$response['torrents'].='<tr><td title="Hash: '.xsssanitize($hash).'">'.xsssanitize($torrent['name']).'</td><td>'.bytes($torrent['size']).'</td><td style="text-align: center;"><span onmouseover="mouseOver(this);" onmouseout="mouseOut(this);" onclick="dorequest(\'torrents\','.xsssanitize($id).',\'hash\',\''.xsssanitize($hash).'\',this,\'del\');"><b>X</b></span></td></tr>';
			}
			$response['torrents'].='<tr><td colspan=2><select style="width:400px;" onchange="dorequest(\'torrents\','.xsssanitize($id).',\'hash\',this.value,this,\'new\');">';
			$response['torrents'].='<option value="-1">--pick a torrent to assign--</option>';
			foreach($alltorrents as $hash => $torrent)
			{
				if (!is_numeric($torrent['size']))
				{
					$nosize=true;
				}
				if (!array_key_exists($hash,$torrents))
				{
					$response['torrents'].='<option value="'.xsssanitize($hash).'">'.xsssanitize($torrent['name']).'</option>';
				}
			}
			$response['torrents'].='</select></td></tr>';
			$response['torrents'].='</table></div>';
			if ($nosize)
			{
				if(array_key_exists('logger',$response))
				{
					$response['logger'].='<br>';
				}
				$response['logger'].='<font color="red">['.date("H:i:s").'] Some torrents in the torrentlist don\'t have a size please login with this user to fill the sizes.</font>';
			}
		break;
	}
}
echo json_encode($response);

function userlist()
{
	global $database;
	$return='<b>Userlist:</b><br><div><table>';
	$return.='<tr><td><b>User</b></td><td style="text-align: center;"><b>Delete</b></td></tr>';
	$users=$database->getusers();
	foreach ($users as $id => $user)
	{
		$return.='<tr><td onmouseover="mouseOver(this);" onmouseout="mouseOut(this);" onclick="select(this);dorequest(\'users\','.xsssanitize($id).');">'.xsssanitize($user['name']).'</td><td style="text-align: center;" onmouseover="mouseOver(this);" onmouseout="mouseOut(this);" onclick="confirm_dorequest(\'Are you sure you want to delete user &#34;'.xsssanitize($user['name']).'&#34;?\',\'users\','.xsssanitize($id).',\'name\',\''.xsssanitize($user['name']).'\',this,\'del\');"><b>X</b></td></tr>';
	}
	$return.='<tr><td colspan="2" onclick="select(this);dorequest(\'users\',-1);"><input onkeypress="key(event,this);" onchange="dorequest(\'users\',-1,\'name\',this.value,this,\'new\');"></td></tr></table></div>';
	return $return;
}
function instancelist()
{
	global $database;
	$return='<b>Instances:</b><br><div><table>';
	$return.='<tr><td><b>Instance</b></td><td style="text-align: center;"><b>Delete</b></td><td style="text-align: center;"><b>Reset<br>statistics</b></td></tr>';
	$instances=$database->getinstances(0);
	foreach ($instances as $instance)
	{
		$return.='<tr><td onmouseover="mouseOver(this);" onmouseout="mouseOut(this);" onclick="select(this);dorequest(\'instances\','.xsssanitize($instance['instanceid']).');">'.xsssanitize($instance['name']).'</td><td style="text-align: center;" onmouseover="mouseOver(this);" onmouseout="mouseOut(this);" onclick="confirm_dorequest(\'Are you sure you want to delete instance &#34;'.xsssanitize($instance['name']).'&#34;?\',\'instances\','.xsssanitize($instance['instanceid']).',\'name\',\''.xsssanitize($instance['name']).'\',this,\'del\');"><b>X</b></td>';
		$return.='<td style="text-align: center;" onmouseover="mouseOver(this);" onmouseout="mouseOut(this);" onclick="confirm_dorequest(\'Are you sure you want to reset download/upload statistics for instance &#34;'.xsssanitize($instance['name']).'&#34;?\n\nPlease note that you should exit uTorrent before reseting or it will not be effective.\',\'instances\','.xsssanitize($instance['instanceid']).',\'name\',\''.xsssanitize($instance['name']).'\',this,\'reset\');"><b>X</b></td></tr>';
  }
	$return.='<tr><td colspan="2" onclick="select(this);dorequest(\'users\',-1);"><input onkeypress="key(event,this);" onchange="dorequest(\'instances\',-1,\'name\',this.value,this,\'new\');"></td></tr></table></div>';
	return $return;
}
function faillist()
{
	global $database;
	$return='<b>Fails per User:</b><br><div><table>';
	$return.='<tr><td><b>User</b></td><td style="text-align: center;"><b>Clear</b></td></tr>';
	$return.='<tr><td onmouseover="mouseOver(this);" onmouseout="mouseOut(this);" onclick="select(this);dorequest(\'fails\',-1);">All users</td><td style="text-align: center;" onmouseover="mouseOver(this);" onmouseout="mouseOut(this);" onclick="confirm_dorequest(\'Are you sure you want to clear all fails?\',\'fails\',-1,\'name\',\'All users\',this,\'del\');"><b>X</b></td></tr>';
	$users=$database->getusers();
	foreach ($users as $id => $user)
	{
		$return.='<tr><td onmouseover="mouseOver(this);" onmouseout="mouseOut(this);" onclick="select(this);dorequest(\'fails\','.xsssanitize($id).');">'.xsssanitize($user['name']).'</td><td style="text-align: center;" onmouseover="mouseOver(this);" onmouseout="mouseOut(this);" onclick="confirm_dorequest(\'Are you sure you want to clear fails for user &#34;'.xsssanitize($user['name']).'&#34;?\',\'fails\','.xsssanitize($id).',\'name\',\''.xsssanitize($user['name']).'\',this,\'del\');"><b>X</b></td></tr>';
	}
	$return.='</table></div>';
	return $return;
}
?>