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
 * 	File containing the login page - shinyasrael theme.
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
body {font:12px/17px Arial, Helvetica, sans-serif; color:#333; background:#ffffff; padding:40px 20px 20px 20px;}
input, textarea, select {font:12px/12px Arial, Helvetica, sans-serif; padding:0;}
.NFText {border:none; vertical-align:middle; font:12px/15px Arial, Helvetica, sans-serif; background:#CCC;}
.NFButton {width:auto; height:26px; color:#000; padding:0 2px; background:#ffffff; cursor:pointer; border:none; font:10px/26px Tahoma, Arial, Helvetica, sans-serif; font-weight:bold; text-transform:uppercase; letter-spacing:1px; vertical-align:middle;}
</style></head>
<body>
<font color="red"><?php print $errorstr ?></font><br>
<form method="POST" action="<?php print $cfg['altname'] ?>">
<table width="600" border="0" align="center">
<tr><td align="center"><img src="http://nivmedia.com/wordpress/wp-content/uploads/2008/08/utorrent.png" alt="&#181;torrent" width="256" height="256"/></td></tr>
<tr><td align="center">
<table width="300" border="0" align="center">
<tr>
	<td align="center" width="50%"><?php print $lang["username"];?></td>
	<td align="center" width="50%"><?php print $lang["password"];?></td>
</tr><tr>
	<td align="center"><input name="shell_login" class="NFText"></td>
	<td align="center"><input type="password" name="shell_pw" class="NFText"></td>
</tr><tr>
	<td align="center"><?php echo $lang["rememberme"];?> <input type="checkbox" name="shell_cookie"></td>
	<td align="center"><select name="shell_lang" class="NFText"><?php
foreach($langs as $c => $l)
{
	echo '<option '.($_SESSION['lang']==$c?'selected ':'').'value="'.$c.'">'.$l['name'].'</option>';
}
?></select></td>
</tr>
</table>
<label>
<input type="submit" value="<?php print $lang["login"]; ?>" class="NFButton">
</label></td></tr>
</table>
</form>
</body>
</html>