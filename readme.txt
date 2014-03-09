
/*
 * WEBUI SHELL for use with µtorrent and its webui - http://www.utorrent.com
 *
 * Version 0.5.0
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
 * File Name: readme.txt
 * 	File containing the documentation.
 *
 * File Author:
 * 		Tjores Maes (lordalderaan@gmail.com)
 */

MINIMUM SOFTWARE REQUIREMENTS:
µtorrent 1.8
	webui
Apache 1.3 or IIS 6
	mod_rewrite (only for Apache) or Ionic Isapi Rewrite (only for IIS, other IIS rewriters might work too)
PHP 5.2.6
	php_curl
	php_sqlite or php_mysql
		php_pdo (only for sqlite)
	php_gd2 (optional, without it disabled buttons won't be removed from the toolbar.)

__________________________________________________________________________________________

INSTALLATION:

First make sure the above software is properly installed.
See the following websites for help about that because I'm not gonna cover it here:
http://www.utorrent.com
http://httpd.apache.org/
http://www.php.net/

IIS is on the Windows Installation CD.
Ionic Isapi Rewriter is available from http://cheeso.members.winisp.net/IIRF.aspx


Note: It is technically possible to run µtorrent + webui on one machine and Apache/IIS + PHP + Webui Shell on a separate machine. Handy for seed boxes that don't allow you to change the Apache/IIS configuration.

==µtorrent==
In µtorrent make sure the webui is enabled and you have setup a username and password.
For extra security you could add the IP of the computer running the Webui Shell to the restricted list.
This would be 127.0.0.1 if µtorrent and the Webui Shell are on the same computer.
This way you pretty much ensure that the webui is only accessible using the Webui Shell.

==Apache== (skip if you want to use IIS)
You need to open the Apache configuration file.

For Apache on windows this file is usually called httpd.conf and can be found in the conf dir in the Apache install folder.

For Linux the location of this file can vary. Two common locations are:
/etc/apache2/apache2.conf and /usr/local/apache/conf/httpd.conf

Make a backup of the original file before you open it in a text editor of your choice.

To make sure the mod_rewrite module is enabled find the "LoadModule rewrite_module modules/mod_rewrite.so" line.
If the line has a leading # then remove it.

Then go to the end of the file and add the following two lines:
	RewriteEngine On
	RewriteRule ^/gui/?(.*)$ /webui_shell/index.php?shell_file=$1 [QSA]

Some comments about the location of the rewrite lines:
* If you use virtual hosts then you need to put these lines inside the applicable (or all) <virtualhost> directive(s).
* Some Apache installations are configured with a single virtualhost by default.
* Some Apache installations have split the configuration of virtualhosts into separate files.
* Directly below the DocumentRoot line is almost always a good location for the rewrite lines.
* If you cant find any DocumentRoot line you are very likely in the wrong configuration file.
* You can see which files are included into the main config file by looking for Import lines.

This will host the Webui Shell on http://youripordomain/gui
If you want to host it under a different folder you can change the gui bit in the RewriteRule line.
However only use letters and numbers unless you know how the regex syntax works.

Save the configuration file(s) and restart the apache server.
	In windows: Start -> Run -> services.msc [enter] -> select apache and press the restart button.
	In linux: apachectl restart

If it gives an error that you probably did something wrong.
In that case retrace your steps. Check the error.log in the logs folder in the apache install folder.
And otherwise come crying on the applicable thread on the webui forum.


==IIS== (skip if you already use Apache)
Installing IIS is done through Add or Remove Programs in the windows Configuration Panel. Enable Internet Information Services in the Add/Remove Windows Components section (found under Application Server in 2003).

It is recommended to manually install PHP for IIS.
-Choose where you want to install PHP (c: root or program files are common) and create a PHP subfolder.
-Unzip the PHP installation zip into this directory.
-Add the PHP directory to your PATH environmental variable. Right click on My computer->
properties->advanced->Environmental Variables. In the bottom portion labeled "system variables"
double click on "Path." To the FRONT OF THE DIALOGUE BOX add for example "C:\PHP;" without the quotations. click ok three times.
-click start->run. Type in regedit and press enter to open the registry editor. Drill down through HKEY_LOCAL_MACHINE, right click
on software->new->Key. Name the new key PHP. drill down through software, click on the new PHP key. IN the right
hand box right click->new->string value. Name the new string IniFilePath and set the data to C:\PHP
-Close the registry editor
-Go to your administrative tools in your control panel and open Internet Services Manager. It should automatically
connect you to your local machine. Right click on web Service Extensions on the left hand side and click "add a new
web service extension." Enter in php and click add. Browse to php5isapi.dll in the php folder.
Set extension status to allow. click okay.
Right click on website and click properties, go to the documents tab and add index.php as a start document. Click on
the "Home Directory" tab and click "configuration" near the bottom.

To install Ionic Isapi Rewrite follow these steps:
-Choose where you want to install IIR (c: root or program files are common) and create a IIR subfolder.
-Extract IsapiRewrite4.dll from the lib directory in the installation zip and copy it to the folder you just created.
-Create a new text file next to the dll and rename it to IsapiRewrite4.ini
-Use a text editor to open the file and insert the following lines:

RewriteCond %{QUERY_STRING} ^$
RewriteRule ^/gui/(.*)$ /webui_shell/index.php?shell_file=$1 [L]
RewriteRule ^/gui/(.*)\?(.*)$ /webui_shell/index.php?shell_file=$1&$2 [L]

This will host the Webui Shell on http://youripordomain/gui
If you want to host it under a different folder you can change the gui bit in the RewriteRule lines.
However only use letters and numbers unless you know how the regex syntax works.

Save and close.

Open up Internet Services Manager and find the website you plan to install the shell in (XP users only have 1
website). The default web site is named "Default Web Site." Right click on it and go to properties. Click on the
ISAPI Filters tab and click add. Filter name = Ionic Rewriter. Click browse and goto and select the ISAPIRewrite4.dll file.

==PHP==
Check PHP.ini (location can vary) to make sure the needed extensions are enabled:

To enable the curl extension find the "extension=php_curl.dll" line and make sure it has no # in front of it.
If you use windows copy libeay32.dll and ssleay32.dll in the PHP install folder to %windir%\system32 and overwrite if applicable.
http://www.php.net/manual/en/curl.installation.php

To enable the gd extension find the "extension=php_gd2.dll" line and make sure it has no # in front of it.
http://www.php.net/manual/en/image.installation.php

To enable the sqlite extension find the "extension=php_sqlite.dll" line and make sure it has no # in front of it.
http://www.php.net/manual/en/sqlite.installation.php

To enable the pdo extension find the "extension=php_pdo.dll" line and make sure it has no # in front of it.
http://www.php.net/manual/en/pdo.installation.php

To enable the mysql extension find the "extension=php_mysql.dll" line and make sure it has no # in front of it.
If you use windows you might also need to copy libmysql.dll to the system32 folder to get it working (not recommended otherwise).
http://www.php.net/manual/en/mysql.installation.php

==Webui Shell==
Extract the Webui Shell package to the root of your webserver.

This should create the webui_shell folder in the root. Go into it and open the config.php in the text editor of your preference.

Set $cfg['username'] and $cfg['password'], you will use this login info to create and edit the users.
Set $cfg['settings_dir'] to a folder where the settings file will be stored. The IIS user or apache account needs to have read+write access here.

Leave the advanced option alone unless you know what you are doing.

$cfg['theme'] is for the multiple templates. You can set it to shiny if you a slightly more beautiful login but which doesn't work well on mobile devices.
$cfg['langfile'] is for multiple languages. Currently only inc/lang/en.lang.php and inc/lang/hu.lang.php are supported. This feature will be expanded in the future.
$cfg['disablelook'] is for replacing disabled options from the menu with nothing instead of the word 'disabled' (localized).
$cfg['altname'] is where the webui shell is hosted. If you want to change this you also have to change the rewrite rule.
$cfg['md5pass'] allows you to use a md5 string in $cfg['password'] instead of a text password.
	If you set this to true use http://www.adamek.biz/md5-generator.php to generate a md5 of your password and paste that in $cfg['password']
$cfg['bf_max'] and $cfg['bf_age'] set the brute force protection. Default is 10 attempts in 600 seconds. (new in 0.5)
This means that after 10 failed attempts further attempts will be refused until 600 seconds have passed (since the first attempt).
$cfg['max_execution_time'] with a hundreds of torrents and Show_All_Torrents disabled and/or Show_Unclaimed_Torrents enabled the loading times of user might be more then the default PHP timeout. (new in 0.5)
The PHP default is usually only 30 seconds. Use this setting to increase the max loading time to anything you want. 0 would be unlimited but this is not recommended.
If a timeout occurs this will be registered in the Fails page of the admin panel. So keep an eye on that to see if you might want to increase this limit.

$cfg['db_type'] set this to mysql to use a mysql database instead of sqlite. Please note that the $cfg['settings_dir'] is still required (for adding torrents).
If you use a mysql database fill in the info in the $cfg['db_host'], $cfg['db_name'], $cfg['db_user'] and $cfg['db_pass'] fields.
You will have to create an empty database in MySQL yourself.
The performance of mysql is noticeably better then sqlite if there are hundreds of torrents.
Especially users who have Show_All_Torrents disabled or Show_Unclaimed_Torrents enabled will have significantly shorter loading times when using mysql.

$cfg['db_file']='sql_users.dat' is the name of the file that will contain the sqlite database, it will be saved in $cfg['settings_dir'].

Save the file and close it.

__________________________________________________________________________________________

ADMIN PANEL:

Now browse to http://youripordomain/gui/

You should now get a simple login form. (For configuration errors see next section)

To get into the admin panel simply login with the username and password you set in the config file.

The admin panel got a 'slight' makeover since 0.4 so rereading this section is recommended.

An important thing to mention now is that anything you change will be saved immediately after you lose focus of that element (the so called onChange event).
You can lose focus by pressing [Enter], pressing Tab] or clicking somewhere else with the mouse. Tick boxes are changed immediately when you click them.
The text in the element will turn red while the change is being made. Then you will get a confirmation or error at the top of the page.
As long as the text is red it has not yet been saved.


==Instances==
If you just installed the Webui Shell you need to setup a utorrent instance first so we will start with explaining that panel.

To open this panel you click on the Instances link at the top of the page (obviously).

Note: A utorrent instance is a running copy of utorrent.
Only if you want to control multiple boxes from a single Webui Shell or you are running utorrent multiple times on the same machine do you need to setup more then one instance.

On the left you see a list of instances. If you don't have any yet then you will only see an input box.
To create a new instance simply fill in a name in the input box and press [Enter].
The name of the instance is purely for administrative purposes. So you, as admin, can easily discern between multiple instances.

To delete an Instance you click the X in the delete column and confirm the deletion.

To open (or reload) an instance click on the name in the list.
After you create a new instance the panel will immediately open it.

When you open an instance you get a list of all the settings of the instance.
All of these settings have to be set.

The domain should be the IP (or hostname) of the machine running utorrent.
If utorrent runs on the same machine as the Webui Shell then you can fill in 127.0.0.1
Note that this should NOT be a full URL. It only needs the part that normally is between http:// and the next : or / (whichever comes first).

The port should be the port where utorrent is running on.
If you enabled the webui alternative port in utorrent you should use that, otherwise it should be the utorrent port.

The username and password are those you setup in the webui settings of utorrent.


==Users==
In this panel you setup users for the Webui Shell.

To open this panel you click on the Users link at the top of the page (obviously).

On the left you see a list of users. If you don't have any yet then you will only see an input box.
To create a new user simply fill in the username for the new user in the input box and press [Enter].

To delete a user you click the X in the delete column and confirm the deletion. Please note that this cannot be undone.

To open (or reload) a user click on the username in the list.
After you create a new instance the panel will immediately open it.

When you open a user you get four section headers.
You can open the settings that belong to that section by clicking on the section header.

The details section has the following options:
name - Username to login with.
pw - Pasword to login with.
ip - Used by Enable_IP_Auth and Remember_Last_IP options.
torrentdir - Used by Allow_Diskspace_Info and Allow_Downloading_Files options. This should be the path as seen from the Webui Shell.
	If the Webui Shell doesn't run on the same machine one should (in theory) be able to use network paths.
instance - Here you pick the instance for this user. This is a required field. A user can only be assigned to one instance. But of course an instance can have multiple users.

The options section has a description of each option when you hover over it. But I will also list them here:
User_Disabled - Allows you to disable this user without deleting it.
Allow_Changing_Password - Allows the user to change his own password.
Allow_Diskspace_Info - Allows the user to see total and free space on disk where the (first) Torrentdir is located.
Allow_Downloading_Files - Allows the user to download the files of his torrents through the Webui Shell. (new in 0.5)
	This requires you to setup the Torrentdir and this should point to the default download dir.
	If you also use a "Move completed downloads to:" dir you should set that one. If you want you can use both (or even more) by separating the different dirs using a | (don't use no spaces).
	For example: C:\Completed Downloads|C:\Downloading
	How this works: The webuishell will crawl through the dirs you supply and match the file and folder names to the names of torrents the user is allowed to see.
	If the names match the file is downloadable and the folder is explorable (allowing download of ALL files in it regardless of whether they are part of the torrent!).
Change_Settings - Allow this user to view and change the utorrent settings.
View_Torrent_Properties - Allow this user to view torrent properties. Handy when you want to hide passkeys from a guest. (new in 0.5)
Set_Torrent_Properties - Allow this user to change torrent properties.
Start_Stop_Pause_Unpause - Allow this user to start, stop, pause and unpause a torrent.
Force_Start - Allow this user to force start a torrent.
Recheck - Allow this user to do a re-check of a torrent.
Remove - Allow this user to remove torrents from utorrent.
Remove_Data - Allow this user to remove torrents from utorrent and delete its data.
Set_File_Priority - Allow this user to set the priority of individual files in torrents.
Add_Torrents - Allow this user to add new torrents.
Allow_Existing_Torrents - If you set this to 0 the Shell will refuse to add a torrent that is already running in utorrent.
	If you set it to 1 and this user adds a torrent that already exists the user will also gets al rights over it as if it added the torrent himself.
	Note that the torrent isn't actually changed, it is simply added to the allowed torrent list of that user.
Show_All_Torrents - Allow this user to see all torrents regardless of whether they are in his torrent list. (Enabling this allows for the fastest loading times)
Show_Unclaimed_Torrents - Allow this user to see torrents that are in nobody's torrent list.
	Please note that this can significantly slow loading times. (This can really slow loading times down.)
Enable_HTTP_Auth - This enables authentication through HTTP Auth.
	This is the same system utorrent itself uses. By enabling this the user can use a variety of Community Efforts that otherwise wouldn't work through the Webui Shell.
Enable_IP_Auth - This enables authentication using IP.
	By filling in an IP in the IP field ANYONE coming from that IP will be recognized as this user.
	By enabling this the user can also use a variety of Community Efforts that previously wouldn't work but only from the IP specified.
Remember_Last_IP - Every time this user logs in his IP will be saved to the IP field.
	In combination with Enable IP Auth a user will only have to login once from any machine and will automatically be logged in when he returns.
	It also (regardless of Enable_IP_Auth) allows you to view the IP this user logged in from the last time in this admin panel because the ip field in the details of this user will be updated every time the user logs in.
Allow_Unknown_Actions - This should best be left at 0. It means that the Shell will block any action it doesn't know (yet).
Quota_Max_Torrents - Set the max number of torrents this user is allowed to have running at the same time. 0 for unlimited.
Quota_Max_Combinedsize - Set the max number of bytes this user is allowed to have torrents running for. 0 for unlimited.
	New torrents will be refused if the total size of all torrents of this user has running would go over this value.
	Please note that it is recommended to disable the option 'Remove' and only allow the option 'Remove_Data' because removing a torrent without removing the data will allow the user to add new torrents while the drive space hasn't actually been freed up.

The actions section is for advanced users only.
This allows you to manually allow or block actions that the Webui Shell doesn't know (yet).
The webui backend might have options that aren't public yet or in the future this webui might not be up to date with the newest actions.
For more info about this see the thread on the µtorrent forums: http://forum.utorrent.com/viewtopic.php?id=46830
To create a new action simply fill in the action in the input box and press [Enter].
You can then allow it or disallowed it using the tick box.
To delete an action you click the X next in the delete column.

The torrents section allows you to manage which torrents the user can see.
This list is irrelevant for this user if Show_All_Torrents is enabled.
However if other users on this instance have Show_Unclaimed_Torrents enabled then torrents listed here count as Claimed.

To add a torrent open the pull-down list and pick a torrent. It will be added immediately.
The pull-down list is updated every time a user gets it from (this instance of) µtorrent.
So to update (or fill if it is empty) this list login with this user or a user on the same instance.

To remove a torrent from this user click the X in the Delete column.


==Fails==
In this panel you can see blocked actions and error messages users had.

To open this panel you click on the Fails link at the top of the page (obviously).

On the left you see a list of users and an All users line.

To clear the list of fails for a user click on the X in the Clear column and confirm the clearing.

To view (or reload) the fails of a user click on the username in the list.

For example if a user tries to add a torrent while he/she isn't allowed to the webui will add a line on this page.
This is mostly for debugging purposes but maybe handy if you want to keep an eye on the things your users do and try.


==Misc==
In this panel has miscellaneous options.

To open this panel you click on the Misc link at the top of the page (obviously).

This panel shows the current database version and allows you to setup the miniui.

This is to support the MiniUI found here: http://forum.utorrent.com/viewtopic.php?id=47167
If you replaced the normal webui with the MiniUI you don't need to use this option and the Webui Shell will work excellently with the MiniUI.
However if you put the contents of the miniui.zip in a subdirectory of the webui.zip (as explain in it's installation notes, I also provide a ready to use webui.zip somewhere in that thread) then Enable this.
Fill in the exact name of the subdir and users now get the Login to MiniUI button next to the normal login button on the login screen.
This button will take them directly to the MiniUI when they login while the normal login button will still take them to the normal webui.
To disable the MiniUI button clear the subdir by pressing the X in the Delete column.


__________________________________________________________________________________________

COMMON PROBLEMS:
When logging in as a user I see {"build":0,"error":"SHELL: cURL error: couldn't connect to host"}
This happens when the Webui Shell cannot connect to µtorrent+webui.
Make sure that µtorrent is running and that the domain and port of that user's instance are correct.

When logging in as a user I see {"build":0,"error":"SHELL: Configuration Error: Wrong login details."}
This happens when the Webui Shell reached µtorrent+webui but the login information was rejected.
Make sure the username and password of that user's instance are correct.

When logging in as a user I see {"build":0,"error":"SHELL: Configuration Error: Invalid request."}
This happens when the Webui Shell reached µtorrent but got a Invalid request error.
Make sure the webui is enabled in µtorrent.
Make sure that if you enabled the webui alternative port in µtorrent that you used that port in that user's instance.
Make sure that the restricted list in µtorrent is either empty or that the IP of the computer running the Webui Shell is in the list.

If you have any questions, requests or feedback please contact me on the forums. I will not reply to emails.
http://forum.utorrent.com/viewtopic.php?id=46830
