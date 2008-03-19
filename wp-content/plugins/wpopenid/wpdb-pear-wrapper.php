<?php

if( class_exists( 'Auth_OpenID_MySQLStore' ) && !class_exists('WP_OpenIDStore')) {
 class WP_OpenIDStore extends Auth_OpenID_MySQLStore {
    function WP_OpenIDStore()
    {
        global $wpdb;

        $conn = new WP_OpenIDConnection( $wpdb );
        parent::Auth_OpenID_MySQLStore(
            $conn,
            $wpdb->prefix . 'openid_settings',
            $wpdb->prefix . 'openid_associations',
            $wpdb->prefix . 'openid_nonces');
    }

    function isError($value)
    {
        return $value === false;
    }

    function blobEncode($blob)
    {
        return $blob;
    }

    function blobDecode($blob)
    {
        return $blob;
    }

    /**
     * WordPress database upgrade functions
     */
    function dbDelta()
    {
        // Create the SQL and call the WP schema upgrade function
        $statements = array(
            $this->sql['nonce_table'],
            $this->sql['assoc_table'],
            $this->sql['settings_table'],
                            );
        $sql = implode(';', $statements);
        dbDelta($sql);
    }


    function dbCleanup() {
    	    
    }

    function setSQL()
    {
        $this->sql['nonce_table'] =
            "
CREATE TABLE %s (
  nonce char(8) UNIQUE,
  expires int(11),
  PRIMARY KEY  (nonce)
)
";

        $this->sql['assoc_table'] =
            "
CREATE TABLE %s (
  server_url blob,
  handle varchar(255),
  secret blob,
  issued int(11),
  lifetime int(11),
  assoc_type varchar(64),
  PRIMARY KEY  (server_url(30),handle)
)
";

        $this->sql['settings_table'] =
            "
CREATE TABLE %s (
  setting varchar(128) UNIQUE,
  value blob,
  PRIMARY KEY  (setting)
)
";

        $this->sql['create_auth'] =
            "INSERT INTO %s VALUES ('auth_key', %%s)";

        $this->sql['get_auth'] =
            "SELECT value FROM %s WHERE setting = 'auth_key'";

        $this->sql['set_assoc'] =
            "REPLACE INTO %s VALUES (%%s, %%s, %%s, %%d, %%d, %%s)";

        $this->sql['get_assocs'] =
            "SELECT handle, secret, issued, lifetime, assoc_type FROM %s ".
            "WHERE server_url = %%s";

        $this->sql['get_assoc'] =
            "SELECT handle, secret, issued, lifetime, assoc_type FROM %s ".
            "WHERE server_url = %%s AND handle = %%s";

        $this->sql['remove_assoc'] =
            "DELETE FROM %s WHERE server_url = %%s AND handle = %%s";

        $this->sql['add_nonce'] =
            "REPLACE INTO %s (nonce, expires) VALUES (%%s, %%d)";

        $this->sql['get_nonce'] =
            "SELECT * FROM %s WHERE nonce = %%s";

        $this->sql['remove_nonce'] =
            "DELETE FROM %s WHERE nonce = %%s";
    }
 }
}

/* 
	WP_OpenIDConnection class implements a PEAR-style database connection using the Wordpress WPDB object.
	Written by Josh Hoyt
	Modified to support setFetchMode() by Alan J Castonguay, 2006-06-16 
 */

if (  class_exists('Auth_OpenID_DatabaseConnection') && !class_exists('WP_OpenIDConnection') ) {
  class WP_OpenIDConnection extends Auth_OpenID_DatabaseConnection {
	var $fetchmode = ARRAY_A;  // to fix PHP Fatal error:  Cannot use object of type stdClass as array in /usr/local/php5/lib/php/Auth/OpenID/SQLStore.php on line 495
	
	function WP_OpenIDConnection(&$wpdb) {
		$this->wpdb =& $wpdb;
	}
	function _fmt($sql, $args) {
		$interp = new MySQLInterpolater($this->wpdb->dbh);
		return $interp->interpolate($sql, $args);
	}
	function query($sql, $args) {
		return $this->wpdb->query($this->_fmt($sql, $args));
	}
	function getOne($sql, $args=null) {
		if($args==null) $args = array();
		return $this->wpdb->get_var($this->_fmt($sql, $args));
	}
	function getRow($sql, $args) {
		return $this->wpdb->get_row($this->_fmt($sql, $args), $this->fetchmode);
	}
	function getAll($sql, $args) {
		return $this->wpdb->get_results($this->_fmt($sql, $args), $this->fetchmode);
	}

	/* This function translates fetch mode constants PEAR=>WPDB
	 * DB_FETCHMODE_ASSOC   => ARRAY_A
	 * DB_FETCHMODE_ORDERED => ARRAY_N
	 * DB_FETCHMODE_OBJECT  => OBJECT  (default)
	 */
	function setFetchMode( $mode ) {
		if( DB_FETCHMODE_ASSOC == $mode ) $this->fetchmode = ARRAY_A;
		if( DB_FETCHMODE_ORDERED == $mode ) $this->fetchmode = ARRAY_N;
		if( DB_FETCHMODE_OBJECT == $mode ) $this->fetchmode = OBJECT;
	}
  }
}



/**
 * Object for doing SQL substitution
 *
 * The internal state should be consistent across calls, so feel free
 * to re-use this object for more than one formatting operation.
 *
 * Allowed formats:
 *  %s -> string substitution (binary allowed)
 *  %d -> integer substitution
 */


if  ( !class_exists('Interpolater') ) {
class Interpolater {

	/**
	 * The pattern to use for substitution
	 */
	var $pattern = '/%([sd])/';
	
    /**
     * Constructor
     *
     * Just sets the initial state to empty
     */
	function Interpolater() {
		$this->values = false;
	}

    /**
     * Escape a string for an SQL engine.
     *
     * Override this function to customize string escaping.
     *
     * @param string $s The string to escape
     * @return string $escaped The escaped string
     */
	function escapeString($s) {
		return addslashes($s);
	}

    /**
     * Perform one replacement on a value
     *
     * Dispatch to the approprate format function
     *
     * @param array $matches The matches from this object's pattern
     *     with preg_match
     * @return string $escaped An appropriately escaped value
     * @access private
     */
	function interpolate1($matches) {
		if (!$this->values) {
			trigger_error('Not enough values for format string', E_USER_ERROR);
		}
		$value = array_shift($this->values);
		if (is_null($value)) {
			return 'NULL';
		}
		return call_user_func(array($this, 'format_' . $matches[1]), $value);
	}

    /**
     * Format and quote a string for use in an SQL query
     *
     * @param string $value The string to escape. It may contain any
     *     characters.
     * @return string $escaped The escaped string
     * @access private
     */

	function format_s($value) {
		if (get_magic_quotes_gpc()) {
			$value = stripslashes($value);
		}
		$val_esc = $this->escapeString($value);
		return "'$val_esc'";
	}

    /**
     * Format an integer for use in an SQL query
     *
     * @param integer $value The number to use in the query
     * @return string $escaped The number formatted as a string
     * @access private
     */
	function format_d($value) {
		$val_int = (integer)$value;
		return (string)$val_int;
	}

    /**
     * Create an escaped query given this format string and these
     * values to substitute
     *
     * @param string $format_string A string to match
     * @param array $values The values to substitute into the format string
     */
	function interpolate($format_string, $values) {
		$matches = array();
		$this->values = $values;
		$callback = array(&$this, 'interpolate1');
		$s = preg_replace_callback($this->pattern, $callback, $format_string);
		if ($this->values) {
			trigger_error('Too many values for format string: ' . $format_string . " => " . implode(', ', $this->values), E_USER_ERROR);
		}
		$this->values = false;
		return $s;
	}
}
}

/**
 * Interpolate MySQL queries
 */
if  ( class_exists('Interpolater') && !class_exists('MySQLInterpolater') ) {
	class MySQLInterpolater extends Interpolater {
		function MySQLInterpolater($dbconn=false) {
			$this->dbconn = $dbconn;
			$this->values = false;
		}
	
		function escapeString($s) {
			if ($this->dbconn === false) {
				return mysql_real_escape_string($s);
			} else {
				return mysql_real_escape_string($s, $this->dbconn);
			}
		}
	}
}

?>
