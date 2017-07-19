<?php
/**
 * Copyright 2006/2009  Alessandro Morandi  (email : webmaster@simbul.net)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

ini_set('display_errors', '0');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	// Read operation
	ZenphotoBridge::request2function($_GET);
}

/**
 * This class is wrapped around Zenphoto and provides decoupled
 * access to albums and images.
 */
class ZenphotoBridge {
	
	/**
	 * Parse a request (GET) and call the corresponding function,
	 * forwarding the specified parameters.
	 */
	function request2function($request) {
		global $zp_err;
		$zp_err = null;
		
		$function = $request['function'];
		$data_path = $request['data_path'];
		
		ZenphotoBridge::init($data_path);
		
		if (!$zp_err) {
			$out = array();
			if (is_callable(array('ZenphotoBridge', $function))) {
				eval('$out = ZenphotoBridge::'.$function.'($request);');
			} else {
				ZenphotoBridge::error('Function "'.$function.'" not found');
			}
		}
		
		if ($zp_err) {
			ZenphotoBridge::print_output(array('fault' => array('faultString' => $zp_err, 'faultCode' => '0')));
		} else {
			ZenphotoBridge::print_output($out);
		}
	}
	
	/**
	 * Initialize the bridge to Zenphoto.
	 */
	function init($zp_data_path) {
    global $_zp_conf_vars;
    
    // Fallback for old configuration file
    if (file_exists($zp_data_path.'/zp-config.php')) {
      require_once($zp_data_path.'/zp-config.php');
    } else if (file_exists($zp_data_path.'/zenphoto.cfg.php')) {
      eval(str_replace('<?php','',str_replace('?>','',file_get_contents($zp_data_path.'/zenphoto.cfg.php'))));
    } else {
      ZenphotoBridge::error('Cannot read Zenphoto configuration file');
      return;
    }
    $conf = $_zp_conf_vars;
           
		global $zp_db;
		
		$zp_db = new ZenphotoPressDB($conf['mysql_host'], $conf['mysql_user'], $conf['mysql_pass'], $conf['mysql_database'], $conf['mysql_prefix']);
	}
	
	/**
	 * Print the output in the page according to a specific format.
	 * Default is PHP serialization.
	 */
	function print_output($output, $format = 'php') {
		if ($format == 'php')
			echo serialize($output);
	}
	
	/**
	 * 'Throw' an error. Exceptions are not supported by PHP4.
	 */
	function error($msg) {
		global $zp_err;
		
		$zp_err = $msg;
	}
	
	/**
	 * Return whether Zenphoto has mod_rewrite enabled.
	 * Required parameters: -
	 */
	function has_mod_rewrite($params) {
		global $zp_db;
		
		return (bool) $zp_db->querySingle("SELECT value FROM db_options_table WHERE name = 'mod_rewrite'");
	}
	
	/**
	 * Return useful Zenphoto options.
	 * Required parameters: -
	 */
	function get_options($params) {
		global $zp_db;
		
		$query = $zp_db->query("SELECT name, value FROM db_options_table WHERE theme IS NULL AND (name = 'mod_rewrite' OR name = 'thumb_size')");
		
		$out = array('mod_rewrite' => false, 'thumb_size' => '100');
		while ($row = @mysql_fetch_assoc($query)) {
			$out[$row['name']] = $row['value'];
		}
		
		return $out;
	}
	
	/**
	 * Return a single album.
	 * Required parameters: albumid
	 */
	function get_album($params) {
		global $zp_db;

		if (ZenphotoBridge::has_parameters($params, 'albumid')) {
			extract($params);
		} else {
			ZenphotoBridge::error('Missing parameters in call to get_album');
			return;
		}
		
		if (!is_numeric($albumid)) {
			ZenphotoBridge::error('Wrong parameter types in call to get_images');
			return;
		}
		
		$sql = "SELECT title AS name, folder AS url, parentid
			FROM db_albums_table AS a
			WHERE a.id = ".$albumid."
			AND a.password = ''
			AND a.show = 1
			LIMIT 0,1";
		$query = $zp_db->query($sql);
		$album = @mysql_fetch_assoc($query);
		
		// Check whether we are allowed to return this album
		if ($album['parentid']) {
			if (ZenphotoBridge::album_protected_recursive($album['parentid'])) {
				return array();
			}
		}
		
		return $album;
	}
	
	/**
	 * Return a nested structure of albums.
	 * The structure reflects the parent-child relationship of the albums.
	 * Unpublished or password-protected albums (and all of their children)
	 * are not returned.
	 * Required parameters: -
	 */
	function get_nested_albums($params) {
		global $zp_db;
		
		$sql = "SELECT id AS value, title AS name, parentid, NULL AS children
			FROM db_albums_table
			WHERE folder <> ''
			AND password = ''
			AND db_albums_table.show = 1
			ORDER BY title ASC";
		$albums = $zp_db->queryAlbums($sql, 'value');
		
		// Build albums tree, possibly with duplicates
		foreach($albums as $id => $album) {
			$p_id = $album->p_id;
			if ($p_id) {
				if (key_exists($p_id, $albums)) {
					$albums[$p_id]->add_child($album);
				}
			}
		}
		// Second pass: normalize & clean
		foreach($albums as $id => $album) {
			if ($album->p_id) {
				// Remove sub-albums: they are already nested in their parents
				unset($albums[$id]);
			} else {
				// Convert ZenphotoPressAlbum to a hash
				$albums[$id] = $album->to_hash();
			}
		}
		return $albums;
	}
	
	/**
	 * Return a list of images.
	 * Required parameters: albumid, order, start, limit
	 */
	function get_images($params) {
		global $zp_db;
		
		$out = array();
		
		if (ZenphotoBridge::has_parameters($params, 'albumid sort start limit')) {
			extract($params);
		} else {
			ZenphotoBridge::error('Missing parameters in call to get_images');
			return;
		}
		
		if (!is_numeric($start) || !is_numeric($limit) || !is_numeric($albumid)) {
			ZenphotoBridge::error('Wrong parameter types in call to get_images');
			return;
		}
		
		$sql_where = "WHERE filename<>'' AND a.password='' AND i.show=1 AND a.show=1";
		if ($albumid != 0) {
		    // Images from a single album:
		    
		    // 1) check permissions for it
			if (ZenphotoBridge::album_protected_recursive($albumid)) {
				// The album is protected: we cannot return it
				return array();
			}
			
			// 2) build query
			$sql_where .= ' AND albumid = ' . $albumid;
		} else {
		    // Images from multiple albums:
		    
		    // 1) get albums with the right permissions
		    $tree = ZenphotoBridge::get_nested_albums(array());
		    $list = trim(ZenphotoBridge::get_album_id_string($tree), ",");
		    
            // 2) build query
            $sql_where .= ' AND albumid IN ('.$list.')';
		}
		
		switch($sort) {
			case 'random':
				$sql_sort = "ORDER BY RAND()";
				break;
			case 'latest':
				$sql_sort = "ORDER BY i.date DESC";
				break;
			case 'sort_order':
			case 'title':
			case 'id':
				$sql_sort = "ORDER BY i.$sort ASC";
				break;
			default:
				$sql_sort = "";
		}
		
		$sql = "SELECT i.id AS id, i.filename AS url, i.title AS name, a.folder AS album_url, a.parentid AS parentid, thumbX, thumbY, thumbW, thumbH
			FROM db_images_table AS i
			LEFT JOIN db_albums_table AS a ON i.albumid = a.id
			$sql_where 
			$sql_sort 
			LIMIT $start, $limit";
		
		$query = $zp_db->query($sql);
		
		while ($image = @mysql_fetch_assoc($query)) {
			if (ZenphotoBridge::is_valid_video($image['url'])) {
				$image['default_thumb'] = 'multimediaDefault.png';
			}
			$out[] = $image;
		}
		
		return $out;
	}
	
	/**
	 * Return the number of images contained in a specific album.
	 * Required parameters: albumid
	 */
	function count_images($params) {
		global $zp_db;
		
		if (ZenphotoBridge::has_parameters($params, 'albumid')) {
			extract($params);
		 } else {
			ZenphotoBridge::error('Missing parameters in call to count_images');
			return;
		}
		
		if (!is_numeric($albumid)) {
			ZenphotoBridge::error('Wrong parameter types in call to get_images');
			return;
		}
		
		$sql = "SELECT COUNT(id)
				FROM db_images_table AS i
				WHERE i.albumid = $albumid
				AND i.filename <> ''
				AND i.show = 1
				";
		$imagesnum = $zp_db->querySingle($sql);
		
		return (int) $imagesnum;
	}
	
	/**
	 * Return whether an album (or any of its ancestors) is protected.
	 * @return true if the album is protected, false otherwise
	 */
	function album_protected_recursive($albumid) {
		global $zp_db;
		
		$sql = "SELECT id, parentid
			FROM db_albums_table AS a
			WHERE a.password='' AND a.show=1 AND a.id=$albumid";
		$query = $zp_db->queryArray($sql);
		if (count($query) == 0) {
			// Parent was not available: it must be protected
			return true;
		} else {
			if ($query[0]['parentid']) {
				return ZenphotoBridge::album_protected_recursive($query[0]['parentid']);
			}
		}
		
		return false;
	}
	
	/**
	 * Return a comma-separated list of ids for the albums in the given tree.
	 * @return A string of comma-separated ids
	 */
	function get_album_id_string($tree) {
	    $out = "";
	    foreach($tree as $value) {
	        $out .= $value['value'].',';
	        if ($value['children']) {
	            // Recurse
	            $out .= ZenphotoBridge::get_album_id_string($value['children']);
	        }
	    }
	    return $out;
	}
	
	/**
	 * Return whether the $params hash contains the specified parameters
	 */
	function has_parameters($params, $check_string) {
		$checks = explode(" ", $check_string);
		
		foreach($checks as $value) {
			if (!array_key_exists($value, $params)) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Taken verbatim from Zenphoto.
	 */
	function is_valid_video($filename) {
		$ext = strtolower(substr(strrchr($filename, "."), 1));
		return in_array($ext, array('flv','3gp','mov'));
	}
}

/**
 * This is a utility class representing an album.
 */
class ZenphotoPressAlbum {
	var $id;
	var $name;
	var $children;
	var $p_id;
	
	/**
	 * Class constructor. Create a new album from a has of values.
	 * @param $hash	Hash of values
	 */
	function ZenphotoPressAlbum($hash) {
		$this->id = $hash['value'];
		$this->name = $hash['name'];
		$this->p_id = $hash['parentid'];
		$this->children = $hash['children'];
	}
	
	/**
	 * Add an album as a child to this album.
	 * @param $child A ZenphotoPressAlbum instance
	 */
	function add_child($child) {
		$this->children[$child->id] = $child;
	}
	
	/**
	 * Return a hash representation for this album.
	 * This method is the dual of the class constructor.
	 * @return A hash representing the album
	 */
	function to_hash() {
		$out = array();
		$out['value'] = $this->id;
		$out['name'] = $this->name;
		$out['parentid'] = $this->p_id;
		$out['children'] = NULL;
		if ($this->children != NULL) {
			foreach($this->children as $id => $child) {
				$out['children'][$id] = $child->to_hash();
			}
		}
		return $out;
	}
}

/**
 * This is a class to manage all database interactions
 */
class ZenphotoPressDB {
	
	var $host;
	var $username;
	var $password;
	var $database;
	var $prefix;
	
	var $link;
	
	/**
	 * Class constructor. Create a new database connection and store its identifier link
	 * @param $host	Hostname for the connection
	 * @param $username	Username for the connection
	 * @param $password	Password for the connection
	 * @param $database	Database name
	 * @param $prefix	Database tables prefix (if any)
	 */
	function ZenphotopressDB($host, $username, $password, $database, $prefix) {
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
		$this->prefix = $prefix;
		
		if (!$zp_db_link = @mysql_connect($this->host, $this->username, $this->password)) {
			ZenphotoBridge::error('Cannot connect to DB: ' . mysql_error());
			return;
		}
		if (!@mysql_select_db($this->database,$zp_db_link)) {
			ZenphotoBridge::error('Cannot select DB: ' . mysql_error());
			return;
		}
		if (function_exists('mysql_set_charset')) {
		    mysql_set_charset('utf8', $zp_db_link);
	    }
		$this->link = $zp_db_link;
	}
	
	/**
	 * Perform a simple DB query and return the result as a query object
	 * @param $sql	SQL query
	 * @return A MySQL query object
	 */
	function query($sql) {
		$sql = $this->translateTables($sql);
		if (!$query = @mysql_query($sql,$this->link)) {
			ZenphotoBridge::error('Query failed: ' . mysql_error() . ' ~ ' . $sql);
			return;
		}
		
		return $query;
	}
	
	/**
	 * Perform a simple DB query and return the result as an associative array
	 * @param $sql	SQL query
	 * @return An associative array
	 */
	function queryArray($sql) {
		$query = $this->query($sql);
		
		while ($row = @mysql_fetch_assoc($query)) {
			$out[] = $row;
		}
		
		return $out;
	}
	
	/**
	 * Perform a simple DB query and return the result as an associative array of ZenphotoPressAlbum objects.
	 * @param $sql	SQL query
	 * @param $id_key The key that in the query result will represent an ID
	 * @return An associative array
	 */
	function queryAlbums($sql, $id_key) {
		$query = $this->query($sql);
		
		while ($row = @mysql_fetch_assoc($query)) {
			$out[$row[$id_key]] = new ZenphotoPressAlbum($row);
		}
		
		return $out;
	}
	
	/**
	 * Perform a query with a single result. Drop other results, should they be returned.
	 * @param $sql	SQL query
	 * @return	The result of the query
	 */
	function querySingle($sql) {
		$query = $this->query($sql);
		
		$row = mysql_fetch_row($query);
		return $row[0];
	}
	
	/**
	 * Translate the standard table placeholder to the actual name of the table
	 * in a query. Add table prefix if necessary.
	 * @param $sql	SQL query
	 * @return Translated query
	 */
	function translateTables($sql) {
		$sql = preg_replace('/db_([^ ,]+)_table/i', '`'.$this->prefix.'\1`', $sql);
		
		return $sql;
	}
}
?>