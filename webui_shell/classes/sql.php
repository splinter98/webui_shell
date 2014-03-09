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
 * File Name: sql.php
 * 	File containing the sql class.
 *
 * File Author:
 * 		Tjores Maes (lordalderaan@gmail.com)
 *
 * Contributors:
 *		asrael...
 */

include('inc/adodb5/adodb.inc.php');
class database
{
	public $db;
	public function __construct()
	{
		global $cfg;
		$this->connect();
		$this->checkupdate();
	}
	private function connect()
	{
		global $cfg, $version;
		$this->db=NewADOConnection($cfg["db_type"]);
		if ($cfg["db_type"] == "mysql")
		{
			if ( $this->db->Connect($cfg["db_host"], $cfg["db_user"], $cfg["db_pass"], $cfg["db_name"]) === false )
			{
				if ( $this->db->ErrorNo() === 1049 )
				{
					// Database does not exist. No way to create with adodb?!?!
					die('Configuration Error: Database '.$cfg["db_name"].' does not exists and could not be created.');
				}
				die('Configuration Error: '.$this->db->ErrorMsg());
			}			
			$table=$this->db->GetArray('SHOW TABLES FROM '.$cfg["db_name"].' LIKE \'info\';');
			if ( count($table) !== 1 )
			{
				$this->create();
			}
		}
		else if ($cfg["db_type"] == "sqlite")
		{
			if (!file_exists($cfg["settings_dir"].$cfg["db_file"]))
			{
				$this->db->Connect($cfg["settings_dir"].$cfg["db_file"]);
				if (!file_exists($cfg["settings_dir"].$cfg["db_file"]))
				{
					die('SHELL: Configuration Error: No read/write access to settings folder.');
				}
				$this->create();
			}
			$this->db->Connect($cfg["settings_dir"].$cfg["db_file"]);
		}
		$this->db->SetFetchMode(ADODB_FETCH_ASSOC);
	}
	private function create()
	{
		global $cfg, $version;
		$autoincrement='';
		if ($cfg["db_type"] == "mysql")
		{
			$autoincrement=' AUTO_INCREMENT';
		}
		$this->db->Execute("CREATE TABLE info (attr CHAR(255) PRIMARY KEY, value CHAR(255));");
		$this->db->Execute("CREATE TABLE fails (failid INTEGER PRIMARY KEY$autoincrement, date CHAR(19), ip CHAR(15), userid INTEGER, errorstr BLOB, query BLOB);");
		$this->db->Execute("CREATE TABLE instances (instanceid INTEGER PRIMARY KEY$autoincrement, name CHAR(255) UNIQUE, domain CHAR(255), port INTEGER, username CHAR(255), password CHAR(255), settingsdat CHAR(255));");
		$this->db->Execute("CREATE TABLE users (userid INTEGER PRIMARY KEY$autoincrement, name CHAR(255) UNIQUE, pw CHAR(255), instanceid INTEGER, ip CHAR(15), cookie TEXT, torrentdir CHAR(255));");
		$this->db->Execute("CREATE TABLE options (userid INTEGER, useroption CHAR(255), value CHAR(255), PRIMARY KEY (userid, useroption));");
		$this->db->Execute("CREATE TABLE actions (userid INTEGER, action CHAR(255), value CHAR(255), PRIMARY KEY (userid, action));");
		$this->db->Execute("CREATE TABLE torrents (userid INTEGER, instanceid INTEGER, hash CHAR(255), name CHAR(255), claimed CHAR(1), size BIGINT, PRIMARY KEY (userid, instanceid, hash));");
		$this->db->Execute("CREATE TABLE brute (ip CHAR(15) PRIMARY KEY, timestamp INTEGER, count INTEGER);");
		$this->db->Execute("REPLACE INTO info (attr, value) VALUES ('version',".$this->db->qstr($version).");");
	}
	private function checkupdate()
	{
		global $version,$cfg,$domain,$port,$username,$password;
		$table=$this->db->GetArray("SELECT * FROM info WHERE attr = 'version';");
		if ( count($table) === 1 )
		{
			switch ($table[0]['value'])
			{
			case '0.2':
				// Upgrade SQLite database from 0.2 to 0.3: Add ip and cookie columns to users.
				$this->db->Execute('CREATE TABLE usertemp (userid INTEGER, name CHAR(255), pw CHAR(255), instanceid INTEGER);');
				$this->db->Execute('INSERT INTO usertemp SELECT * FROM users;');
				$this->db->Execute('DROP TABLE users;');
				$this->db->Execute('CREATE TABLE users (userid INTEGER PRIMARY KEY, name CHAR(255) UNIQUE, pw CHAR(255), instanceid INTEGER, ip CHAR(15), cookie CHAR(255));');
				$this->db->Execute('INSERT INTO users (userid, name, pw, instanceid) SELECT * FROM usertemp;');
				$this->db->Execute('DROP TABLE usertemp;');
			case '0.3':
				// Upgrade SQLite database from 0.3 to 0.4: 
				// Add torrentdir column to users.
				$this->db->Execute('CREATE TABLE usertemp (userid INTEGER PRIMARY KEY, name CHAR(255) UNIQUE, pw CHAR(255), instanceid INTEGER, ip CHAR(15), cookie CHAR(255));');
				$this->db->Execute('INSERT INTO usertemp SELECT * FROM users;');
				$this->db->Execute('DROP TABLE users;');
				$this->db->Execute('CREATE TABLE users (userid INTEGER PRIMARY KEY, name CHAR(255) UNIQUE, pw CHAR(255), instanceid INTEGER, ip CHAR(15), cookie CHAR(255), torrentdir CHAR(255));');
				$this->db->Execute('INSERT INTO users (userid, name, pw, instanceid, ip, cookie) SELECT * FROM usertemp;');
				$this->db->Execute('DROP TABLE usertemp;');
				// Rename column option to useroption in options.
				$this->db->Execute('CREATE TABLE optionstemp (userid INTEGER, useroption CHAR(255), value CHAR(255));');
				$this->db->Execute('INSERT INTO optionstemp SELECT * FROM options;');
				$this->db->Execute('DROP TABLE options;');
				$this->db->Execute('CREATE TABLE options (userid INTEGER, useroption CHAR(255), value CHAR(255), PRIMARY KEY (userid, useroption));');
				$this->db->Execute('INSERT INTO options SELECT * FROM optionstemp;');
				$this->db->Execute('DROP TABLE optionstemp;');
			case '0.4':
				if ($cfg["db_type"] == "mysql")
				{
					// Upgrade mysql database from 0.4 to 0.5:
					// Fix broken tables and add size column to torrents table.
					$this->db->Execute('ALTER TABLE options MODIFY userid INTEGER');
					$this->db->Execute('ALTER TABLE actions MODIFY userid INTEGER');
					$this->db->Execute('ALTER TABLE torrents MODIFY userid INTEGER, ADD COLUMN size BIGINT');
					// The only table where this could have lead to problems is the torrents. Remove redundant records.
					$this->db->Execute('DELETE torrents.* FROM torrents LEFT JOIN users ON torrents.userid = users.userid WHERE users.userid IS NULL;');
					// Add bruteforce table.
					$this->db->Execute("CREATE TABLE brute (ip CHAR(15) PRIMARY KEY, timestamp INTEGER, count INTEGER);");
					// Update version
					$this->db->Execute("REPLACE INTO info (attr, value) VALUES ('version',0.5);");
				}
				else if ($cfg["db_type"] == "sqlite")
				{
					// Upgrade SQLite database from 0.4 to 0.5:
					// Add bruteforce table.
					$this->db->Execute("CREATE TABLE brute (ip CHAR(15) PRIMARY KEY, timestamp INTEGER, count INTEGER);");
					// Add size column to torrents table.
					$this->db->Execute("CREATE TABLE torrentstemp (userid INTEGER, instanceid INTEGER, hash CHAR(255), name CHAR(255), claimed CHAR(1), PRIMARY KEY (userid, instanceid, hash));");
					$this->db->Execute('INSERT INTO torrentstemp SELECT * FROM torrents;');
					$this->db->Execute('DROP TABLE torrents;');
					$this->db->Execute("CREATE TABLE torrents (userid INTEGER, instanceid INTEGER, hash CHAR(255), name CHAR(255), claimed CHAR(1), size BIGINT, PRIMARY KEY (userid, instanceid, hash));");
					$this->db->Execute('INSERT INTO users (userid, instanceid, hash, name, claimed) SELECT * FROM usertemp;');
					$this->db->Execute('DROP TABLE usertemp;');
					// Update version
					$this->db->Execute("REPLACE INTO info (attr, value) VALUES ('version',0.5);");
				}
			case '0.5':
				if ($cfg["db_type"] == "mysql")
				{
					// Upgrade mysql database from 0.5 to 0.6
					// Increase max cookie length
					$this->db->Execute("ALTER TABLE `users` CHANGE `cookie` `cookie` TEXT");
					// Add settingsdat column to instances table
					$this->db->Execute("ALTER TABLE `instances` ADD `settingsdat` CHAR( 255 )");
					// Update version
					$this->db->Execute("REPLACE INTO info (attr, value) VALUES ('version',0.6);");
				}	
				else if ($cfg["db_type"] == "sqlite")
				{
					// Upgrade SQLite database from 0.5 to 0.6:
					// Add settingsdat column to instances table
					$this->db->Execute("CREATE TABLE instancestemp (instanceid INTEGER PRIMARY KEY, name CHAR(255) UNIQUE, domain CHAR(255), port INTEGER, username CHAR(255), password CHAR(255));");
					$this->db->Execute('INSERT INTO instancestemp SELECT * FROM instances;');
					$this->db->Execute('DROP TABLE instances;');
					$this->db->Execute("CREATE TABLE instances (instanceid INTEGER PRIMARY KEY, name CHAR(255) UNIQUE, domain CHAR(255), port INTEGER, username CHAR(255), password CHAR(255), settingsdat CHAR(255));");
					$this->db->Execute('INSERT INTO instances (instanceid, name, domain, port, username, password) SELECT * FROM instancestemp;');
					$this->db->Execute('DROP TABLE instancestemp;');					
					// Update version
					$this->db->Execute("REPLACE INTO info (attr, value) VALUES ('version',0.6);");
				}
			case '0.5.1':
				// Devbuild (This case will be removed in 0.6 final)
				$this->db->Execute("REPLACE INTO info (attr, value) VALUES ('version',0.6);");
			case '0.6':
				// Current version
				break;				
			default:
				// Unknown version
				die('SHELL: Configuration Error: Database version '.$table[0]['value'].' unknown.');
			}
		}
		else
		{
			die('SHELL: Configuration Error: Corrupt database.');
		}
	}
	public function getid($name)
	{
		$table=$this->db->GetArray("SELECT * FROM users WHERE name = ".$this->db->qstr($name).";");
		if ( count($table) === 1 )
		{
			return $table[0]['userid'];
		}
		return 0;
	}
	public function getusers()
	{
		$table=$this->db->GetArray("SELECT * FROM users ORDER BY name;");
		$return=Array();
		foreach ($table as $record) {
			$return[$record['userid']]=Array();
			foreach ($record as $key => $value)
			{
				$return[$record['userid']][$key]=$value;
			}
		}
		return $return;
	}
	public function getoptions($userid)
	{
		if (!is_numeric($userid))
		{
			return(Array());
		}

		return($this->db->GetAssoc("SELECT useroption, value FROM options WHERE (userid = $userid);"));
	}
	public function getactions($userid)
	{
		if (!is_numeric($userid))
		{
			return(Array());
		}
		return($this->db->GetAssoc("SELECT action, value FROM actions WHERE (userid = $userid) ORDER BY action;"));
	}
	public function gettorrents($userid,$instanceid,$unclaimed = null)
	{
		if (!is_numeric($userid) || !is_numeric($instanceid))
		{
			return(Array());
		}
		if (isset($unclaimed))
		{
			$sql="SELECT hash, name, size, userid FROM torrents WHERE ((instanceid = $instanceid) AND ((userid = $userid) OR (userid = 0 AND claimed = 0))) ORDER BY name;";
		}
		else
		{
			$sql="SELECT hash, name, size, userid FROM torrents WHERE (userid = $userid AND instanceid = $instanceid) ORDER BY name;";
		}
		return($this->db->GetAssoc($sql));
	}
	public function getfails($userid = null,$last = false)
	{
		if ( $userid === null || $userid == -1 )
		{
			return $this->db->GetArray("SELECT fails.*, users.name FROM fails LEFT JOIN users ON fails.userid = users.userid ORDER BY fails.date;");
		}
		else
		{
			if (is_numeric($userid))
			{
				if ($last)
				{
					return $this->db->GetArray("SELECT * FROM fails WHERE (ip = ".$this->db->qstr($_SERVER['REMOTE_ADDR'])." AND userid = '$userid' AND date >= ".$this->db->qstr(date('Y-m-d H:i:s',time()-30)).") ORDER BY date DESC;");
				}
				else
				{
					return $this->db->GetArray("SELECT * FROM fails WHERE (userid = '$userid') ORDER BY date;");
				}
			}
		}
		return false;
	}
	public function getinstances($id,$search = 'instanceid')
	{
		switch ($search)
		{
			case 'instanceid':
				if (!is_numeric($id))
				{
					return(false);
				}
				if ( $id === 0 ) {
					return $this->db->GetArray("SELECT * FROM instances ORDER BY name;");
				}
				else
				{
					$return = $this->db->GetArray("SELECT * FROM instances WHERE instanceid = $id;");
					if (is_array($return) && count($return) === 1 )
					{
						return $return[0];
					}
				}
			break;
			case 'userid':
				if (!is_numeric($id))
				{
					return(false);
				}
				$table=$this->db->GetArray("SELECT * FROM users WHERE userid = $id;");
				if (is_array($table) && count($table) === 1 )
				{
					$return = $this->db->GetArray("SELECT * FROM instances WHERE instanceid = ".$table[0]['instanceid'].";");
					if (is_array($return) && count($return) === 1 )
					{
						return $return[0];
					}
				}				
			break;
			case 'name':
				$return = $this->db->GetArray("SELECT * FROM instances WHERE name = ".$this->db->qstr($id).";");
				if (is_array($return) && count($return) === 1 )
				{
					return $return[0];
				}
			break;
		}
		return false;
	}
	public function getinfo($attr)
	{
		$table=$this->db->GetArray("SELECT * FROM info WHERE attr = ".$this->db->qstr($attr).";");
		if (is_array($table) && count($table) === 1 )
		{
			return $table[0]['value'];
		}
		return false;
	}
	public function deluser($userid)
	{
		if (!is_numeric($userid))
		{
			return(false);
		}
		$this->db->Execute("DELETE FROM torrents WHERE (userid = $userid);");
		$this->db->Execute("DELETE FROM actions WHERE (userid = $userid);");
		$this->db->Execute("DELETE FROM options WHERE (userid = $userid);");
		$this->db->Execute("DELETE FROM users WHERE (userid = $userid);");
		return(true);
	}
	public function delaction($userid,$action)
	{
		if (!is_numeric($userid))
		{
			return(false);
		}
		if (is_int($action)) {
			$this->db->Execute("DELETE FROM actions WHERE (userid = $userid);");
			return(true);
		}
		$this->db->Execute("DELETE FROM actions WHERE (userid = $userid AND action = ".$this->db->qstr($action).");");
		return(true);
	}
	public function deltorrent($userid,$instanceid,$hash)
	{
		if (!is_numeric($userid) || !is_numeric($instanceid))
		{
			return(false);
		}
		if (is_int($hash)) {
			if ( $instanceid === -1 )
			{
				$this->db->Execute("DELETE FROM torrents WHERE (userid = $userid);");
				return(true);
			}
			$this->db->Execute("DELETE FROM torrents WHERE (userid = $userid AND instanceid = $instanceid);");
			return(true);
		}
		$this->db->Execute("DELETE FROM torrents WHERE (userid = $userid AND instanceid = $instanceid AND hash = ".$this->db->qstr($hash).");");
		return(true);
	}
	public function delfails($userid = null)
	{
		if ( $userid === null || $userid == -1 )
		{
			$this->db->Execute("DELETE FROM fails");
			return true;
		}
		else
		{
			if (is_numeric($userid))
			{
				$this->db->Execute("DELETE FROM fails WHERE (userid = '$userid')");
				return true;
			}
		}
		return false;
	}
	public function delinstance($instanceid)
	{
		if (!is_numeric($instanceid))
		{
			return(false);
		}
		$this->db->Execute("DELETE FROM instances WHERE (instanceid = $instanceid);");
		$this->db->Execute("DELETE FROM torrents WHERE (instanceid = $instanceid);");
		return(true);
	}
	public function delinfo($attr)
	{
		$this->db->Execute("DELETE FROM info WHERE (attr = ".$this->db->qstr($attr).");");
		return(true);
	}
	public function fail($errorstr)
	{
		$userid=0;
		if (isset($_SESSION['userid']) && is_numeric($_SESSION['userid']))
		{
			$userid=$_SESSION['userid'];
		}
		$this->db->Execute("INSERT INTO fails (date,ip,userid,errorstr,query) VALUES (".$this->db->qstr(date('Y-m-d H:i:s')).",".$this->db->qstr($_SERVER['REMOTE_ADDR']).",'$userid',".$this->db->qstr($errorstr).",".$this->db->qstr($_SERVER['QUERY_STRING']).");");
		header('Content-type: text/plain');
		die(json_encode(Array('build'=>(empty($_SESSION['build'])?'0':$_SESSION['build']),'error'=>'SHELL: '.$errorstr)));
	}
	public function cleantorrents($userid)
	{
		global $benchmark;
		global $startmom;
		if (!is_numeric($userid))
		{
			return(false);
		}
		$table=$this->db->GetArray("SELECT * FROM users WHERE userid = $userid;");
		if (is_array($table) && count($table) === 1 )
		{
			$benchmark[]='getuser'.(time()-$startmom);
			// Get torrentlist
			$alltors=$this->db->GetAssoc("SELECT hash, userid, size FROM torrents WHERE (userid = 0 AND instanceid = ".$table[0]['instanceid'].") ORDER BY hash;");			
			$benchmark[]='getall'.(time()-$startmom);
			// Get torrents of current user
			$curtors=$this->db->GetAssoc("SELECT hash, userid, size FROM torrents WHERE (userid = $userid) ORDER BY hash;");
			$benchmark[]='getcur'.(time()-$startmom);
			// Remove torrents of current user that are no longer in the torrentlist.
			foreach ( $curtors as $hash => $torrent )
			{
				if (!array_key_exists($hash,$alltors) )
				{
					$this->db->Execute("DELETE FROM torrents WHERE ( userid = $userid AND instanceid = ".$table[0]['instanceid']." AND hash = '$hash' );");
				}
				elseif (!is_numeric($torrent['size']) && is_numeric($alltors[$hash]['size']))
				{
					$this->db->Execute("UPDATE torrents SET size = ".$alltors[$hash]['size']." WHERE ( userid = $userid AND instanceid = ".$table[0]['instanceid']." AND hash = '$hash' );");
				}
			}
			$benchmark[]='looped'.(time()-$startmom);
		}
		return true;
	}
	public function checkclaimed($instanceid)
	{
		if (!is_numeric($instanceid))
		{
			return(false);
		}
		// Get torrents of all users
		$usrtors=$this->db->GetAssoc("SELECT hash, userid FROM torrents WHERE (userid != 0 AND instanceid = $instanceid) ORDER BY hash;");
		// Get torrentlist
		$alltors_table=$this->db->GetArray("SELECT * FROM torrents WHERE (userid = 0 AND instanceid = $instanceid) ORDER BY hash;");			
		// Set claimed for torrentlist
		foreach ( $alltors_table as $alltor )
		{
			$alltors[$alltor['hash']]=1;
			$claimed=0;
			if ( array_key_exists($alltor['hash'],$usrtors) )
			{
				$claimed=1;
			}
			$this->db->Execute("UPDATE torrents SET claimed = '$claimed' WHERE ( userid = 0 AND instanceid = $instanceid and hash = '".$alltor['hash']."');");
		}
		return true;
	}
	public function unassign($userid,$instanceid,$hash)
	{
		if (!is_numeric($instanceid) || !is_numeric($userid) || empty($hash))
		{
			$this->fail('Unassign failed. Invalid user, instance or hash.');
		}
		// Search for users of this torrent
		$getusers=$this->db->GetArray("SELECT * FROM torrents WHERE (userid != 0 AND userid != $userid AND instanceid = $instanceid AND hash = ".$this->db->qstr($hash)." );");
		if (is_array($getusers) && count($getusers) >= 1 )
		{
			// Other users found. Unassign from user instead of remove from utorrent.
			$this->deltorrent($userid,$instanceid,$hash);
			return true;
		}
		// No other users found. Remove from utorrent.
		return false;
	}
	public function upd($table,$values,$key = null)
	{
		// This function creates new records or updates them.
		// $table argument is the table to modify. 
		// $values argument is a named array with columnname and their new value.
		// $key is an optional named array with the columnnames and values of the key to update. 
		// With the $key absent a new record will be made overwriting any conflicting records.
		//
		// Notes: 
		// The current setup does not discern between datatypes.
		
		if (empty($table) || empty($values))
		{
			return false;
		}
		if ( !empty($key) && is_array($key) )
		{

			$sql="UPDATE ".$this->safe($table)." SET";
			$delim=false;
			foreach ($values as $column => $value)
			{
				if ( $delim )
				{
					$sql.=',';
				}
				$sql.=" ".$this->safe($column)." = ".$this->db->qstr($value)."";
				$delim=true;
			}
			$sql.=' WHERE';
			$delim=false;
			foreach ($key as $column => $value)
			{
				if ( $delim )
				{
					$sql.=' AND';
				}
				$sql.=" ".$this->safe($column)." = ".$this->db->qstr($value)."";
				$delim=true;
			}
			$sql.=';';
			if ($this->db->Execute($sql) === false)
			{
				$this->fail('Update failed. '.$sql.' '.$this->db->ErrorMsg());
			}
			return true;
		}		
		else
		{
			$sql="REPLACE INTO ".$this->safe($table)." (";
			$delim=false;
			foreach ($values as $column => $value)
			{
				if ( $delim )
				{
					$sql.=', ';
				}
				$sql.=$this->safe($column);
				$delim=true;
			}			
			$sql.=') VALUES (';
			$delim=false;
			foreach ($values as $value)
			{
				if ( $delim )
				{
					$sql.=', ';
				}
				$sql.="".$this->db->qstr($value)."";
				$delim=true;
			}
			$sql.=');';
			if ($this->db->Execute($sql) === false)
			{
				$this->fail('Update failed. '.$sql.' '.$this->db->ErrorMsg());
			}
			return true;
		}
	}
	public function login($method,$var1,$var2 = null)
	{
		// This function is the dynamic login function.
		// $method decides the login method used.
		// $var1 and optional $var2 contain the info to verify with.
		// It returns an array with the user info on success and returns false otherwise.
		switch ($method)
		{
			case 'norm':
			case 'auth':
				if (!empty($var1) && !empty($var2))
				{ 
					$users=$this->db->GetArray("SELECT * FROM users WHERE ( name = ".$this->db->qstr($var1)." AND pw = ".$this->db->qstr($var2)." );");
					if ( count($users) === 1 )
					{
						$option=$this->db->GetArray("SELECT * FROM options WHERE ( userid = ". $users[0]['userid']." AND useroption = 'User_Disabled');");
						if (is_array($option) && count($option) === 1 && $option[0]['value'] !== "1" )
						{
							if ( $method == 'auth' ) 
							{
								$option=$this->db->GetArray("SELECT * FROM options WHERE ( userid = ". $users[0]['userid']." AND useroption = 'Enable_HTTP_Auth');");
								if (is_array($option) && count($option) === 1 && $option[0]['value'] === "1" )
								{
									return $users[0];
								}
							}
							else 
							{
								return $users[0];
							}
						}
					}
				}
			break;
			case 'ip':
				$table=$this->db->GetArray("SELECT * FROM users WHERE ( ip = ".$this->db->qstr($var1)." );");
				foreach ( $table as $user )
				{
					$option=$this->db->GetArray("SELECT * FROM options WHERE ( userid = ".$user['userid']." AND useroption = 'Enable_IP_Auth');");
					if (is_array($option) && count($option) === 1 && $option[0]['value'] === "1" )
					{
						return $user;
					}
				}
			break;
			case 'cookie':
				if (!empty($var1) && !empty($var2))
				{ 
					$users=$this->db->GetArray("SELECT * FROM users WHERE ( name = ".$this->db->qstr($var1)." );");
					if ( count($users) === 1 && md5($users[0]['pw']) === $var2 )
					{
						$option=$this->db->GetArray("SELECT * FROM options WHERE ( userid = ". $users[0]['userid']." AND useroption = 'User_Disabled');");
						if (is_array($option) && count($option) === 1 && $option[0]['value'] !== "1" )
						{
							return $users[0];
						}
					}
				}
			break;
		}
		return false;
	}
	public function bruteforce($ip, $reset = null)
	{
		global $cfg;
		$max = 10;
		$age = 600;
		if (array_key_exists('bf_max',$cfg))
		{
			$max=$cfg['bf_max'];
		}
		if (array_key_exists('bf_age',$cfg))
		{
			$age=$cfg['bf_age'];		
		}
		$count=1;
		$timestamp=time();
		if ( $reset )
		{
			$timestamp=time()-$age;
		}
		else
		{
			$table=$this->db->GetArray("SELECT * FROM brute WHERE ip = ".$this->db->qstr($ip).";");
			if ( count($table) === 1 &&  $table[0]['timestamp'] > ( time() - $age ) )
			{
				// Not first try.
				if ( $table[0]['count'] >= $max )
				{
					// Blocked - Too many attempts
					$this->fail("Brute Force Protection. No more then $max login attempts within $age seconds allowed.");
				}
				else
				{
					// Increment counter
					$timestamp=$table[0]['timestamp'];
					$count=$table[0]['count'] + 1;
				}
			}
		}
		$sql="REPLACE INTO brute (ip, timestamp, count) VALUES (".$this->db->qstr($ip).", $timestamp, $count);";
		if ($this->db->Execute($sql) === false)
		{
			$this->fail('Database Error: Brute Force Protection failure. '.$sql.' '.$this->db->ErrorMsg());
		}
	}
	private function safe($str)
	{
		// This function is made to escape table and collumn names. 
		// qstr cannot be used in these cases because it adds quotes which are illegal in table and collumn names.
		return(preg_replace("/[^a-zA-Z_]/",'',$str));
	}
}
?>