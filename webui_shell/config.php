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
 * File Name: config.php
 * 	File containing the configuration variables.
 * 
 * File Author:
 * 		Tjores Maes (lordalderaan@gmail.com)
 *
 * Contributors:
 *		asrael...
 */

//  MANDATORY CONFIRGURATION:
$cfg['username'] = 'user'; // Admin login
$cfg['password'] = 'pass'; // Admin password

$cfg['settings_dir'] = 'c:/webui_shell/'; // Folder for user settings and a temp for uploaded torrents - Note: The Apache/IIS user needs to have read/write access here.




// ADVANCED GENERAL OPTIONS:
$cfg['theme'] = 'simple'; // simple or shiny (the latter does not do well on mobile devices)
$cfg['defaultlang'] = 'en'; // Default language (if auto-language fails)
$cfg['disablelook'] = 0; // if a function is disabled, should it write "disabled" (0) or write nothing (1)
$cfg['altname'] = '/gui/'; // mod_rewrite name for the shell
$cfg['md5pass'] = false; // The admin pass is a md5 string. Use http://www.adamek.biz/md5-generator.php to generate a md5 from your password.
$cfg['bf_max'] = 10; // BruteForce protection: Max number of login attempts 
$cfg['bf_age'] = 600; // BruteForce protection: Time to remember login attempts. (After this time the counter is reset to zero)
$cfg['max_execution_time'] = 90; // Increase the maximum execution time in case of page timeout errors.

// ADVANCED DATABASE OPTIONS:
$cfg['db_type'] = 'sqlite'; // sqlite or mysql
// mysql specific settings
$cfg['db_host'] = '127.0.0.1'; // host 
$cfg['db_name'] = 'utorrent'; // name of database, you have to create this database yourself.
$cfg['db_user'] = 'utorrent'; // user
$cfg['db_pass'] = 'password'; // pass
// sqlite specific settings
$cfg['db_file']='sql_users.dat';

?>