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
 * File Name: intro.php
 * 	File containing the login page - simple theme.
 *
 * File Author:
 * 		asrael...
 *
 * Contributors:
 *		Tjores Maes (lordalderaan@gmail.com)
 */

include("config.php");
header('Content-Type: text/html; charset=utf-8');
?>
<html><head><title>&#181;Torrent Webui-Shell Login</title><link href="./favicon.ico" rel="SHORTCUT ICON" type="image/x-icon" />
<style>
body {font:Arial, Helvetica, sans-serif; color:#333; background:#ffffff;}
input, textarea, select {font: Arial, Helvetica, sans-serif; padding:0;}
.NFText {border:none; vertical-align:middle; font:Arial, Helvetica, sans-serif; background:#CCC;}
.NFButton {width:auto; height:26px; color:#000; padding:0 2px; background:#ffffff; cursor:pointer; border:solid 1px grey; font:10px/26px Tahoma, Arial, Helvetica, sans-serif; font-weight:bold; text-transform:uppercase; letter-spacing:1px; vertical-align:middle;}
</style></head>
<body>
<font color="red"><?php echo $errorstr ?></font><br>
<b>uTorrent Webui-Shell Login</b>
<form method="POST" action="<?php echo $cfg['altname'] ?>">
<table border="0">
<tr>
	<td align="center"><?php echo $lang["username"];?></td>
	<td><input name="shell_login" class="NFText" style="width:100%;"></td>
</tr><tr>
	<td align="center"><?php echo $lang["password"];?></td>
	<td><input type="password" name="shell_pw" class="NFText" style="width:100%;"></td>
</tr><tr>
	<td align="center"><?php echo $lang["language"];?></td>
	<td>
		<select name="shell_lang" class="NFText" style="width:100%;"><?php
foreach($langs as $c => $l)
{
	echo '<option '.($_SESSION['lang']==$c?'selected ':'').'value="'.$c.'">'.$l['name'].'</option>';
}
?></select>
	</td>
</tr><tr>
	<td align="center"><?php echo $lang["rememberme"];?></td>
	<td><input type="checkbox" name="shell_cookie"></td>
</tr><tr>
	<td align="center"></td>
	<td align="center"><input type="submit" value="<?php echo $lang["login"]; ?>" class="NFButton">&nbsp;<?php if ($database->getinfo('miniui')) { echo '<input type="submit" name="shell_miniui" value="MiniUI" class="NFButton">'; }?></td>
</tr>
</table>
</form>
</body>
</html>