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
 * 	File containing the admin topbar
 *
 * File Author:
 * 		Tjores Maes (lordalderaan@gmail.com)
 *
 * Contributors:
 *
 */

?><html>
<head>
<style type="text/css">
body { margin:0px; }
span { white-space:nowrap; }
td { font-size:11px;font-family:Tahoma,Verdana,Arial,Helvetica,sans-serif;padding:0px 4px 0px 4px; }
</style>
</head>
<body>
<table style="width:100%;border-collapse:collapse;padding:0px;">
<tr>
<td style="width:270px;"><span>&#181;Torrent Webui-Shell - <b>Admin Panel</b></span></td>
<td style="text-align:center;"><a href="shell_panel.php?p=user" target="panel">Users</a></td>
<td style="text-align:center;"><a href="shell_panel.php?p=instance" target="panel">Instances</a></td>
<td style="text-align:center;"><a href="shell_panel.php?p=fail" target="panel">Fails</a></td>
<td style="text-align:center;"><a href="shell_panel.php?p=misc" target="panel">Misc</a></td>
<td style="text-align:center;"><a href="http://trac.utorrent.com/trac/wiki/Webui-Shell" target="_blank">Help</a></td>
<td style="width:270px;" align="right"><a target="_top" href="<?php echo $cfg['altname']?>?shell_logout=1"><?php echo $lang['logout'];?></a></td>
</tr>
</table>
</body>
</html>