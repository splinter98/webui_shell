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
 * File Name: panel.php
 * 	File containing the admin panel.
 *
 * File Author:
 * 		Tjores Maes (lordalderaan@gmail.com)
 *
 * Contributors:
 *
 */

?>
<html>
<head>
<style type="text/css">
div { background: #FFE9AE; border: 1px solid red; padding: 2px; margin: 5px;}
</style>
<script type="text/javascript" src="shell_mootools.js"></script>
<SCRIPT type="text/javascript">
function mouseOver(o) {
	o.style.textDecoration='underline';
}
function mouseOut(o) {
	o.style.textDecoration='';
}
function select(o) {
	if ( typeof lastselect !== "undefined" ) {
		lastselect.style.fontWeight='normal';
	}
	lastselect = o;
	o.style.fontWeight='bold';
}
function toggle(id) {
	var td = document.getElementById(id);
	if ( td.innerHTML == '' )
	{
		td.innerHTML = '<div>Loading...</div>';
		return true;
	}
	else 
	{
		td.innerHTML = '';
		return false;
	}
}
function dorequest(t,q,f,v,o,a) {
	var str = "shell_t="+t+"&shell_q="+q;
	if ( typeof f !== "undefined" ) {
		str = str+"&shell_f="+f;
	}
	if ( typeof o !== "undefined" ) {
		o.style.color = 'red';
	}
	if ( typeof v !== "undefined" ) {
		str = str+"&shell_v="+encodeURIComponent(v);
	}
	if ( typeof a !== "undefined" ) {
		str = str+"&shell_a="+a;
	}
	var jsonRequest = new Request.JSON({
		"url":"shell_ajax.php",
		"method":"get",
		"async":"false",
		"onSuccess":function(response){
			for ( prop in response) {
				if (document.getElementById(prop)) {
					document.getElementById(prop).innerHTML = response[prop];
				}
			}
		}
	}).send(str);
}
function confirm_dorequest(d,t,q,f,v,o,a) {
	if (confirm(d) == true) {
		dorequest(t,q,f,v,o,a);
	}
}
function key(e,o)
{
	// Fix for Enter key not triggering OnChange in IE and Opera
	var key = 0;
	if (window.event)
	{
		key = e.keyCode;
	}
	else if (e.which)
	{
		key = e.which;
	}
	if ( key == 13 )
	{
		o.blur();
		return false;
	}
	return true;
}
</SCRIPT>
</head>
<body>
<div id="logger"></div>
<div id="list" style="float: left;"></div>
<div id="item" style="float: left;"></div>
</body>
<SCRIPT type="text/javascript">
	dorequest('<?php echo $_REQUEST['p'];?>list',-1);
</SCRIPT>
</html>

