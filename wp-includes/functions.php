<?php

require_once(dirname(__FILE__).'/functions-compat.php');

if (!function_exists('_')) {
	function _($string) {
		return $string;
	}
}

function get_profile($field, $user = false) {
	global $wpdb;
	if (!$user)
		$user = $wpdb->escape($_COOKIE['wordpressuser_' . COOKIEHASH]);
	return $wpdb->get_var("SELECT $field FROM $wpdb->users WHERE user_login = '$user'");
}

function mysql2date($dateformatstring, $mysqlstring, $use_b2configmonthsdays = 1) {
	global $month, $weekday;
	$m = $mysqlstring;
	if (empty($m)) {
		return false;
	}
	$i = mktime(substr($m,11,2),substr($m,14,2),substr($m,17,2),substr($m,5,2),substr($m,8,2),substr($m,0,4)); 
	if (!empty($month) && !empty($weekday) && $use_b2configmonthsdays) {
		$datemonth = $month[date('m', $i)];
		$dateweekday = $weekday[date('w', $i)];
		$dateformatstring = ' '.$dateformatstring;
		$dateformatstring = preg_replace("/([^\\\])D/", "\\1".backslashit(substr($dateweekday, 0, 3)), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])F/", "\\1".backslashit($datemonth), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])l/", "\\1".backslashit($dateweekday), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])M/", "\\1".backslashit(substr($datemonth, 0, 3)), $dateformatstring);
		$dateformatstring = substr($dateformatstring, 1, strlen($dateformatstring)-1);
	}
	$j = @date($dateformatstring, $i);
	if (!$j) {
	// for debug purposes
	//	echo $i." ".$mysqlstring;
	}
	return $j;
}

function current_time($type, $gmt = 0) {
	switch ($type) {
		case 'mysql':
			if ($gmt) $d = gmdate('Y-m-d H:i:s');
			else $d = gmdate('Y-m-d H:i:s', (time() + (get_settings('gmt_offset') * 3600)));
			return $d;
			break;
		case 'timestamp':
			if ($gmt) $d = time();
			else $d = time() + (get_settings('gmt_offset') * 3600);
			return $d;
			break;
	}
}

function date_i18n($dateformatstring, $unixtimestamp) {
	global $month, $weekday;
	$i = $unixtimestamp; 
	if ((!empty($month)) && (!empty($weekday))) {
		$datemonth = $month[date('m', $i)];
		$dateweekday = $weekday[date('w', $i)];
		$dateformatstring = ' '.$dateformatstring;
		$dateformatstring = preg_replace("/([^\\\])D/", "\\1".backslashit(substr($dateweekday, 0, 3)), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])F/", "\\1".backslashit($datemonth), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])l/", "\\1".backslashit($dateweekday), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])M/", "\\1".backslashit(substr($datemonth, 0, 3)), $dateformatstring);
		$dateformatstring = substr($dateformatstring, 1, strlen($dateformatstring)-1);
	}
	$j = @date($dateformatstring, $i);
	return $j;
	}

function get_weekstartend($mysqlstring, $start_of_week) {
	$my = substr($mysqlstring,0,4);
	$mm = substr($mysqlstring,8,2);
	$md = substr($mysqlstring,5,2);
	$day = mktime(0,0,0, $md, $mm, $my);
	$weekday = date('w',$day);
	$i = 86400;
	while ($weekday > get_settings('start_of_week')) {
		$weekday = date('w',$day);
		$day = $day - 86400;
		$i = 0;
	}
	$week['start'] = $day + 86400 - $i;
	$week['end']   = $day + 691199;
	return $week;
}

function get_lastpostdate($timezone = 'server') {
	global $cache_lastpostdate, $pagenow, $wpdb;
	$add_seconds_blog = get_settings('gmt_offset') * 3600;
	$add_seconds_server = date('Z');
	$now = current_time('mysql', 1);
	if ( !isset($cache_lastpostdate[$timezone]) ) {
		switch(strtolower($timezone)) {
			case 'gmt':
				$lastpostdate = $wpdb->get_var("SELECT post_date_gmt FROM $wpdb->posts WHERE post_date_gmt <= '$now' AND post_status = 'publish' ORDER BY post_date_gmt DESC LIMIT 1");
				break;
			case 'blog':
				$lastpostdate = $wpdb->get_var("SELECT post_date FROM $wpdb->posts WHERE post_date_gmt <= '$now' AND post_status = 'publish' ORDER BY post_date_gmt DESC LIMIT 1");
				break;
			case 'server':
				$lastpostdate = $wpdb->get_var("SELECT DATE_ADD(post_date_gmt, INTERVAL '$add_seconds_server' SECOND) FROM $wpdb->posts WHERE post_date_gmt <= '$now' AND post_status = 'publish' ORDER BY post_date_gmt DESC LIMIT 1");
				break;
		}
		$cache_lastpostdate[$timezone] = $lastpostdate;
	} else {
		$lastpostdate = $cache_lastpostdate[$timezone];
	}
	return $lastpostdate;
}

function get_lastpostmodified($timezone = 'server') {
	global $cache_lastpostmodified, $pagenow, $wpdb;
	$add_seconds_blog = get_settings('gmt_offset') * 3600;
	$add_seconds_server = date('Z');
	$now = current_time('mysql', 1);
	if ( !isset($cache_lastpostmodified[$timezone]) ) {
		switch(strtolower($timezone)) {
			case 'gmt':
				$lastpostmodified = $wpdb->get_var("SELECT post_modified_gmt FROM $wpdb->posts WHERE post_modified_gmt <= '$now' AND post_status = 'publish' ORDER BY post_modified_gmt DESC LIMIT 1");
				break;
			case 'blog':
				$lastpostmodified = $wpdb->get_var("SELECT post_modified FROM $wpdb->posts WHERE post_modified_gmt <= '$now' AND post_status = 'publish' ORDER BY post_modified_gmt DESC LIMIT 1");
				break;
			case 'server':
				$lastpostmodified = $wpdb->get_var("SELECT DATE_ADD(post_modified_gmt, INTERVAL '$add_seconds_server' SECOND) FROM $wpdb->posts WHERE post_modified_gmt <= '$now' AND post_status = 'publish' ORDER BY post_modified_gmt DESC LIMIT 1");
				break;
		}
		$lastpostdate = get_lastpostdate($timezone);
		if ($lastpostdate > $lastpostmodified) {
			$lastpostmodified = $lastpostdate;
		}
		$cache_lastpostmodified[$timezone] = $lastpostmodified;
	} else {
		$lastpostmodified = $cache_lastpostmodified[$timezone];
	}
	return $lastpostmodified;
}

function get_lastcommentmodified($timezone = 'server') {
	global $tablecomments, $cache_lastcommentmodified, $pagenow, $wpdb;
	$add_seconds_blog = get_settings('gmt_offset') * 3600;
	$add_seconds_server = date('Z');
	$now = current_time('mysql', 1);
	if ( !isset($cache_lastcommentmodified[$timezone]) ) {
		switch(strtolower($timezone)) {
			case 'gmt':
				$lastcommentmodified = $wpdb->get_var("SELECT comment_date_gmt FROM $tablecomments WHERE comment_date_gmt <= '$now' ORDER BY comment_date_gmt DESC LIMIT 1");
				break;
			case 'blog':
				$lastcommentmodified = $wpdb->get_var("SELECT comment_date FROM $tablecomments WHERE comment_date_gmt <= '$now' ORDER BY comment_date_gmt DESC LIMIT 1");
				break;
			case 'server':
				$lastcommentmodified = $wpdb->get_var("SELECT DATE_ADD(comment_date_gmt, INTERVAL '$add_seconds_server' SECOND) FROM $tablecomments WHERE comment_date_gmt <= '$now' ORDER BY comment_date_gmt DESC LIMIT 1");
				break;
		}
		$cache_lastcommentmodified[$timezone] = $lastcommentmodified;
	} else {
		$lastcommentmodified = $cache_lastcommentmodified[$timezone];
	}
	return $lastcommentmodified;
}

function user_pass_ok($user_login,$user_pass) {
	global $cache_userdata;
	if ( empty($cache_userdata[$user_login]) ) {
		$userdata = get_userdatabylogin($user_login);
	} else {
		$userdata = $cache_userdata[$user_login];
	}
	return (md5($user_pass) == $userdata->user_pass);
}

function get_currentuserinfo() { // a bit like get_userdata(), on steroids
	global $user_login, $userdata, $user_level, $user_ID, $user_nickname, $user_email, $user_url, $user_pass_md5;
	// *** retrieving user's data from cookies and db - no spoofing

	if (isset($_COOKIE['wordpressuser_' . COOKIEHASH])) 
		$user_login = $_COOKIE['wordpressuser_' . COOKIEHASH];
	$userdata = get_userdatabylogin($user_login);
	$user_level = $userdata->user_level;
	$user_ID = $userdata->ID;
	$user_nickname = $userdata->user_nickname;
	$user_email = $userdata->user_email;
	$user_url = $userdata->user_url;
	$user_pass_md5 = md5($userdata->user_pass);
}

function get_userdata($userid) {
	global $wpdb, $cache_userdata;
	$userid = (int) $userid;
	if ( empty($cache_userdata[$userid]) ) {
        $cache_userdata[$userid] = 
            $wpdb->get_row("SELECT * FROM $wpdb->users WHERE ID = '$userid'");
	} 

    return $cache_userdata[$userid];
}

function get_userdatabylogin($user_login) {
	global $cache_userdata, $wpdb;
	if ( !empty($user_login) && empty($cache_userdata["$user_login"]) ) {
		$user = $wpdb->get_row("SELECT * FROM $wpdb->users WHERE user_login = '$user_login'");
		$cache_userdata["$user_login"] = $user;
	} else {
		$user = $cache_userdata["$user_login"];
	}
	return $user;
}

function get_userid($user_login) {
	global $cache_userdata, $wpdb;
	if ( !empty($user_login) && empty($cache_userdata["$user_login"]) ) {
		$user_id = $wpdb->get_var("SELECT ID FROM $wpdb->users WHERE user_login = '$user_login'");

		$cache_userdata["$user_login"] = $user_id;
	} else {
		$user_id = $cache_userdata["$user_login"];
	}
	return $user_id;
}

function get_usernumposts($userid) {
	global $wpdb;
	return $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_author = '$userid'");
}

// examine a url (supposedly from this blog) and try to
// determine the post ID it represents.
function url_to_postid($url = '') {
	global $wpdb;

	$siteurl = get_settings('home');
	// Take a link like 'http://example.com/blog/something'
	// and extract just the '/something':
	$uri = preg_replace("#$siteurl#i", '', $url);

	// on failure, preg_replace just returns the subject string
	// so if $uri and $siteurl are the same, they didn't match:
	if ($uri == $siteurl) 
		return 0;
		
	// First, check to see if there is a 'p=N' to match against:
	preg_match('#[?&]p=(\d+)#', $uri, $values);
	$p = intval($values[1]);
	if ($p) return $p;
	
	// Match $uri against our permalink structure
	$permalink_structure = get_settings('permalink_structure');
	
	// Matt's tokenizer code
	$rewritecode = array(
		'%year%',
		'%monthnum%',
		'%day%',
		'%hour%',
		'%minute%',
		'%second%',
		'%postname%',
		'%post_id%'
	);
	$rewritereplace = array(
		'([0-9]{4})?',
		'([0-9]{1,2})?',
		'([0-9]{1,2})?',
		'([0-9]{1,2})?',
		'([0-9]{1,2})?',
		'([0-9]{1,2})?',
		'([_0-9a-z-]+)?',
		'([0-9]+)?'
	);

	// Turn the structure into a regular expression
	$matchre = str_replace('/', '/?', $permalink_structure);
	$matchre = str_replace($rewritecode, $rewritereplace, $matchre);

	// Extract the key values from the uri:
	preg_match("#$matchre#",$uri,$values);

	// Extract the token names from the structure:
	preg_match_all("#%(.+?)%#", $permalink_structure, $tokens);

	for($i = 0; $i < count($tokens[1]); $i++) {
		$name = $tokens[1][$i];
		$value = $values[$i+1];

		// Create a variable named $year, $monthnum, $day, $postname, or $post_id:
		$$name = $value;
	}
	
	// If using %post_id%, we're done:
	if (intval($post_id)) return intval($post_id);

	// Otherwise, build a WHERE clause, making the values safe along the way:
	if ($year) $where .= " AND YEAR(post_date) = '" . intval($year) . "'";
	if ($monthnum) $where .= " AND MONTH(post_date) = '" . intval($monthnum) . "'";
	if ($day) $where .= " AND DAYOFMONTH(post_date) = '" . intval($day) . "'";
	if ($hour) $where .= " AND HOUR(post_date) = '" . intval($hour) . "'";
	if ($minute) $where .= " AND MINUTE(post_date) = '" . intval($minute) . "'";
	if ($second) $where .= " AND SECOND(post_date) = '" . intval($second) . "'";
	if ($postname) $where .= " AND post_name = '" . $wpdb->escape($postname) . "' ";

	// We got no indication, so we return false:
	if (!strlen($where)) {
		return false;
	}

	// Run the query to get the post ID:
	$id = intval($wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE 1 = 1 " . $where));

	return $id;
}


/* Options functions */

function get_settings($setting) {
  global $wpdb, $cache_settings, $cache_nonexistantoptions;
	if ( strstr($_SERVER['REQUEST_URI'], 'wp-admin/install.php') )
		return false;

	if ( empty($cache_settings) )
		$cache_settings = get_alloptions();

	if ( empty($cache_nonexistantoptions) )
		$cache_nonexistantoptions = array();

	if ('home' == $setting && '' == $cache_settings->home)
		return $cache_settings->siteurl;

	if ( isset($cache_settings->$setting) ) :
		return $cache_settings->$setting;
	else :
		// for these cases when we're asking for an unknown option
		if ( isset($cache_nonexistantoptions[$setting]) )
			return false;

		$option = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = '$setting'");

		if (!$option) :
			$cache_nonexistantoptions[$setting] = true;
			return false;
		endif;
	  
		if (@ $kellogs =  unserialize($option) ) return $kellogs;
		else return $option;
	endif;
}

function get_option($option) {
	return get_settings($option);
}

function form_option($option) {
	echo htmlspecialchars( get_option($option) );
}

function get_alloptions() {
	global $wpdb, $wp_queries;
	$wpdb->hide_errors();
	if (!$options = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE autoload = 'yes'")) {
		include_once(ABSPATH . '/wp-admin/upgrade-functions.php');
		make_db_current_silent();
		$options = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options");
	}
	$wpdb->show_errors();

	foreach ($options as $option) {
		// "When trying to design a foolproof system, 
		//  never underestimate the ingenuity of the fools :)" -- Dougal
		if ('siteurl' == $option->option_name) $option->option_value = preg_replace('|/+$|', '', $option->option_value);
		if ('home' == $option->option_name) $option->option_value = preg_replace('|/+$|', '', $option->option_value);
		if ('category_base' == $option->option_name) $option->option_value = preg_replace('|/+$|', '', $option->option_value);
	if (@ $value =  unserialize($option->option_value) )
		$all_options->{$option->option_name} = $value;
	else $value = $option->option_value;
		$all_options->{$option->option_name} = $value;
	}
	return $all_options;
}

function update_option($option_name, $newvalue) {
	global $wpdb, $cache_settings;
	if ( is_array($newvalue) || is_object($value) )
		$newvalue = serialize($newvalue);

	$newvalue = trim($newvalue); // I can't think of any situation we wouldn't want to trim

    // If the new and old values are the same, no need to update.
    if ($newvalue == get_settings($option_name)) {
        return true;
    }

	$newvalue = $wpdb->escape($newvalue);
	$wpdb->query("UPDATE $wpdb->options SET option_value = '$newvalue' WHERE option_name = '$option_name'");
	$cache_settings = get_alloptions(); // Re cache settings
	return true;
}


// thx Alex Stapleton, http://alex.vort-x.net/blog/
function add_option($name, $value = '', $description = '') {
	global $wpdb;
	if ( is_array($value) || is_object($value) )
		$value = serialize($value);

	if( !$wpdb->get_var("SELECT option_name FROM $wpdb->options WHERE option_name = '$name'") ) {
		$name = $wpdb->escape($name);
		$value = $wpdb->escape($value);
		$description = $wpdb->escape($description);
		$wpdb->query("INSERT INTO $wpdb->options (option_name, option_value, option_description) VALUES ('$name', '$value', '$description')");

		if($wpdb->insert_id) {
			global $cache_settings;
			$cache_settings->{$name} = $value;
		}
	}
	return;
}

function delete_option($name) {
	global $wpdb;
	// Get the ID, if no ID then return
	$option_id = $wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = '$name'");
	if (!$option_id) return false;
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name = '$name'");
	return true;
}

function add_post_meta($post_id, $key, $value, $unique = false) {
	global $wpdb;
	
	if ($unique) {
		if( $wpdb->get_var("SELECT meta_key FROM $wpdb->postmeta WHERE meta_key
= '$key' AND post_id = '$post_id'") ) {
			return false;
		}
	}

	$wpdb->query("INSERT INTO $wpdb->postmeta
                                (post_id,meta_key,meta_value) 
                                VALUES ('$post_id','$key','$value')
                        ");
	
	return true;
}

function delete_post_meta($post_id, $key, $value = '') {
	global $wpdb;

	if (empty($value)) {
		$meta_id = $wpdb->get_var("SELECT meta_id FROM $wpdb->postmeta WHERE
post_id = '$post_id' AND meta_key = '$key'");
	} else {
		$meta_id = $wpdb->get_var("SELECT meta_id FROM $wpdb->postmeta WHERE
post_id = '$post_id' AND meta_key = '$key' AND meta_value = '$value'");
	}

	if (!$meta_id) return false;

	if (empty($value)) {
		$wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id = '$post_id'
AND meta_key = '$key'");
	} else {
		$wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id = '$post_id'
AND meta_key = '$key' AND meta_value = '$value'");
	}
        
	return true;
}

function get_post_meta($post_id, $key, $single = false) {
	global $wpdb, $post_meta_cache;

	if (isset($post_meta_cache[$post_id][$key])) {
		if ($single) {
			return $post_meta_cache[$post_id][$key][0];
		} else {
			return $post_meta_cache[$post_id][$key];
		}
	}

	$metalist = $wpdb->get_results("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '$post_id' AND meta_key = '$key'", ARRAY_N);

	$values = array();
	if ($metalist) {
		foreach ($metalist as $metarow) {
			$values[] = $metarow[0];
		}
	}

	if ($single) {
		if (count($values)) {
			return $values[0];
		} else {
			return '';
		}
	} else {
		return $values;
	}
}

function update_post_meta($post_id, $key, $value, $prev_value = '') {
	global $wpdb, $post_meta_cache;

		if(! $wpdb->get_var("SELECT meta_key FROM $wpdb->postmeta WHERE meta_key
= '$key' AND post_id = '$post_id'") ) {
			return false;
		}

	if (empty($prev_value)) {
		$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = '$value' WHERE
meta_key = '$key' AND post_id = '$post_id'");
	} else {
		$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = '$value' WHERE
meta_key = '$key' AND post_id = '$post_id' AND meta_value = '$prev_value'");
	}

	return true;
}

function get_postdata($postid) {
	global $post, $wpdb;

	$post = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE ID = '$postid'");
	
	$postdata = array (
		'ID' => $post->ID, 
		'Author_ID' => $post->post_author, 
		'Date' => $post->post_date, 
		'Content' => $post->post_content, 
		'Excerpt' => $post->post_excerpt, 
		'Title' => $post->post_title, 
		'Category' => $post->post_category,
		'post_status' => $post->post_status,
		'comment_status' => $post->comment_status,
		'ping_status' => $post->ping_status,
		'post_password' => $post->post_password,
		'to_ping' => $post->to_ping,
		'pinged' => $post->pinged,
		'post_name' => $post->post_name
	);
	return $postdata;
}

function get_commentdata($comment_ID,$no_cache=0,$include_unapproved=false) { // less flexible, but saves DB queries
	global $postc,$id,$commentdata, $wpdb;
	if ($no_cache) {
		$query = "SELECT * FROM $wpdb->comments WHERE comment_ID = '$comment_ID'";
		if (false == $include_unapproved) {
		    $query .= " AND comment_approved = '1'";
		}
    		$myrow = $wpdb->get_row($query, ARRAY_A);
	} else {
		$myrow['comment_ID']=$postc->comment_ID;
		$myrow['comment_post_ID']=$postc->comment_post_ID;
		$myrow['comment_author']=$postc->comment_author;
		$myrow['comment_author_email']=$postc->comment_author_email;
		$myrow['comment_author_url']=$postc->comment_author_url;
		$myrow['comment_author_IP']=$postc->comment_author_IP;
		$myrow['comment_date']=$postc->comment_date;
		$myrow['comment_content']=$postc->comment_content;
		$myrow['comment_karma']=$postc->comment_karma;
        $myrow['comment_approved']=$postc->comment_approved;
		if (strstr($myrow['comment_content'], '<trackback />')) {
			$myrow['comment_type'] = 'trackback';
		} elseif (strstr($myrow['comment_content'], '<pingback />')) {
			$myrow['comment_type'] = 'pingback';
		} else {
			$myrow['comment_type'] = 'comment';
		}
	}
	return $myrow;
}

function get_catname($cat_ID) {
	global $cache_catnames, $wpdb;
	if ( !$cache_catnames ) {
        $results = $wpdb->get_results("SELECT * FROM $wpdb->categories") or die('Oops, couldn\'t query the db for categories.');
		foreach ($results as $post) {
			$cache_catnames[$post->cat_ID] = $post->cat_name;
		}
	}
	$cat_name = $cache_catnames[$cat_ID];
	return $cat_name;
}

function gzip_compression() {
	if ( strstr($_SERVER['PHP_SELF'], 'wp-admin') ) return false;
	if ( !get_settings('gzipcompression') ) return false;

	if( extension_loaded('zlib') ) {
		ob_start('ob_gzhandler');
	}
}


// functions to count the page generation time (from phpBB2)
// ( or just any time between timer_start() and timer_stop() )

function timer_stop($display = 0, $precision = 3) { //if called like timer_stop(1), will echo $timetotal
	global $timestart, $timeend;
	$mtime = microtime();
	$mtime = explode(' ',$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$timeend = $mtime;
	$timetotal = $timeend-$timestart;
	if ($display)
		echo number_format($timetotal,$precision);
	return $timetotal;
}

function weblog_ping($server = '', $path = '') {

	global $wp_version;
	include_once (ABSPATH . WPINC . '/class-IXR.php');

	// using a timeout of 3 seconds should be enough to cover slow servers
	$client = new IXR_Client($server, ((!strlen(trim($path)) || ('/' == $path)) ? false : $path));
	$client->timeout = 3;
	$client->useragent .= ' -- WordPress/'.$wp_version;

	// when set to true, this outputs debug messages by itself
	$client->debug = false;
	$client->query('weblogUpdates.ping', get_settings('blogname'), get_settings('home'));

}

function generic_ping($post_id = 0) {
	$services = get_settings('ping_sites');
	$services = preg_replace("|(\s)+|", '$1', $services); // Kill dupe lines
	$services = trim($services);
	if ('' != $services) {
		$services = explode("\n", $services);
		foreach ($services as $service) {
			weblog_ping($service);
		}
	}

	return $post_id;
}

add_action('publish_post', 'generic_ping');

// Send a Trackback
function trackback($trackback_url, $title, $excerpt, $ID) {
	global $wpdb;
	$title = urlencode($title);
	$excerpt = urlencode($excerpt);
	$blog_name = urlencode(get_settings('blogname'));
	$tb_url = $trackback_url;
	$url = urlencode(get_permalink($ID));
	$query_string = "title=$title&url=$url&blog_name=$blog_name&excerpt=$excerpt";
	$trackback_url = parse_url($trackback_url);
	$http_request  = 'POST ' . $trackback_url['path'] . ($trackback_url['query'] ? '?'.$trackback_url['query'] : '') . " HTTP/1.0\r\n";
	$http_request .= 'Host: '.$trackback_url['host']."\r\n";
	$http_request .= 'Content-Type: application/x-www-form-urlencoded; charset='.get_settings('blog_charset')."\r\n";
	$http_request .= 'Content-Length: '.strlen($query_string)."\r\n";
	$http_request .= "\r\n";
	$http_request .= $query_string;
	if ( '' == $trackback_url['port'] )
		$trackback_url['port'] = 80;
	$fs = @fsockopen($trackback_url['host'], $trackback_url['port'], $errno, $errstr, 4);
	@fputs($fs, $http_request);
/*
	$debug_file = 'trackback.log';
	$fp = fopen($debug_file, 'a');
	fwrite($fp, "\n*****\nRequest:\n\n$http_request\n\nResponse:\n\n");
	while(!@feof($fs)) {
		fwrite($fp, @fgets($fs, 4096));
	}
	fwrite($fp, "\n\n");
	fclose($fp);
*/
	@fclose($fs);

	$wpdb->query("UPDATE $wpdb->posts SET pinged = CONCAT(pinged, '\n', '$tb_url') WHERE ID = '$ID'");
	$wpdb->query("UPDATE $wpdb->posts SET to_ping = REPLACE(to_ping, '$tb_url', '') WHERE ID = '$ID'");
	return $result;
}

function make_url_footnote($content) {
	preg_match_all('/<a(.+?)href=\"(.+?)\"(.*?)>(.+?)<\/a>/', $content, $matches);
	$j = 0;
	for ($i=0; $i<count($matches[0]); $i++) {
		$links_summary = (!$j) ? "\n" : $links_summary;
		$j++;
		$link_match = $matches[0][$i];
		$link_number = '['.($i+1).']';
		$link_url = $matches[2][$i];
		$link_text = $matches[4][$i];
		$content = str_replace($link_match, $link_text.' '.$link_number, $content);
		$link_url = (strtolower(substr($link_url,0,7)) != 'http://') ? get_settings('home') . $link_url : $link_url;
		$links_summary .= "\n".$link_number.' '.$link_url;
	}
	$content = strip_tags($content);
	$content .= $links_summary;
	return $content;
}


function xmlrpc_getposttitle($content) {
	global $post_default_title;
	if (preg_match('/<title>(.+?)<\/title>/is', $content, $matchtitle)) {
		$post_title = $matchtitle[0];
		$post_title = preg_replace('/<title>/si', '', $post_title);
		$post_title = preg_replace('/<\/title>/si', '', $post_title);
	} else {
		$post_title = $post_default_title;
	}
	return $post_title;
}
	
function xmlrpc_getpostcategory($content) {
	global $post_default_category;
	if (preg_match('/<category>(.+?)<\/category>/is', $content, $matchcat)) {
		$post_category = trim($matchcat[1], ',');
		$post_category = explode(',', $post_category);
	} else {
		$post_category = $post_default_category;
	}
	return $post_category;
}

function xmlrpc_removepostdata($content) {
	$content = preg_replace('/<title>(.+?)<\/title>/si', '', $content);
	$content = preg_replace('/<category>(.+?)<\/category>/si', '', $content);
	$content = trim($content);
	return $content;
}

function debug_fopen($filename, $mode) {
	global $debug;
	if ($debug == 1) {
		$fp = fopen($filename, $mode);
		return $fp;
	} else {
		return false;
	}
}

function debug_fwrite($fp, $string) {
	global $debug;
	if ($debug == 1) {
		fwrite($fp, $string);
	}
}

function debug_fclose($fp) {
	global $debug;
	if ($debug == 1) {
		fclose($fp);
	}
}

function do_enclose( $content, $post_ID ) {
	global $wp_version, $wpdb;
	include_once (ABSPATH . WPINC . '/class-IXR.php');

	// original code by Mort (http://mort.mine.nu:8080)
	$log = debug_fopen(ABSPATH . '/pingback.log', 'a');
	$post_links = array();
	debug_fwrite($log, 'BEGIN '.date('YmdHis', time())."\n");

	$pung = get_pung($post_ID);

	// Variables
	$ltrs = '\w';
	$gunk = '/#~:.?+=&%@!\-';
	$punc = '.:?\-';
	$any = $ltrs . $gunk . $punc;

	// Step 1
	// Parsing the post, external links (if any) are stored in the $post_links array
	// This regexp comes straight from phpfreaks.com
	// http://www.phpfreaks.com/quickcode/Extract_All_URLs_on_a_Page/15.php
	preg_match_all("{\b http : [$any] +? (?= [$punc] * [^$any] | $)}x", $content, $post_links_temp);

	// Debug
	debug_fwrite($log, 'Post contents:');
	debug_fwrite($log, $content."\n");
	
	// Step 2.
	// Walking thru the links array
	// first we get rid of links pointing to sites, not to specific files
	// Example:
	// http://dummy-weblog.org
	// http://dummy-weblog.org/
	// http://dummy-weblog.org/post.php
	// We don't wanna ping first and second types, even if they have a valid <link/>

	foreach($post_links_temp[0] as $link_test) :
		if ( !in_array($link_test, $pung) ) : // If we haven't pung it already
			$test = parse_url($link_test);
			if (isset($test['query']))
				$post_links[] = $link_test;
			elseif(($test['path'] != '/') && ($test['path'] != ''))
				$post_links[] = $link_test;
		endif;
	endforeach;

	foreach ($post_links as $url){
                if( $url != '' && in_array($url, $pung) == false ) {
                    set_time_limit( 60 ); 
                    $file = str_replace( "http://", "", $url );
                    $host = substr( $file, 0, strpos( $file, "/" ) );
                    $file = substr( $file, strpos( $file, "/" ) );
                    $headers = "HEAD $file HTTP/1.1\r\nHOST: $host\r\n\r\n";
                    $port    = 80;
                    $timeout = 3;
                    $fp = fsockopen($host, $port, $err_num, $err_msg, $timeout);
                    if( $fp ) {
                        fputs($fp, $headers );
                        $response = '';
                        while ( !feof($fp) && strpos( $response, "\r\n\r\n" ) == false )
                            $response .= fgets($fp, 2048);
                        fclose( $fp );
                    } else {
                        $response = '';
                    }
                    if( $response != '' ) {
                        $len = substr( $response, strpos( $response, "Content-Length:" ) + 16 );
                        $len = substr( $len, 0, strpos( $len, "\n" ) );
                        $type = substr( $response, strpos( $response, "Content-Type:" ) + 14 );
                        $type = substr( $type, 0, strpos( $type, "\n" ) + 1 );
                        $allowed_types = array( "video", "audio", "image" );
                        if( in_array( substr( $type, 0, strpos( $type, "/" ) ), $allowed_types ) ) {
                            $meta_value = "$url\n$len\n$type\n";
                            $query = "INSERT INTO `".$wpdb->postmeta."` ( `meta_id` , `post_id` , `meta_key` , `meta_value` )
                                VALUES ( NULL, '$post_ID', 'enclosure' , '".$meta_value."')";
                            $wpdb->query( $query );
                            add_ping( $post_ID, $url );
                        }
                    }
                }
        }
}

function pingback($content, $post_ID) {
	global $wp_version, $wpdb;
	include_once (ABSPATH . WPINC . '/class-IXR.php');

	// original code by Mort (http://mort.mine.nu:8080)
	$log = debug_fopen(ABSPATH . '/pingback.log', 'a');
	$post_links = array();
	debug_fwrite($log, 'BEGIN '.date('YmdHis', time())."\n");

	$pung = get_pung($post_ID);

	// Variables
	$ltrs = '\w';
	$gunk = '/#~:.?+=&%@!\-';
	$punc = '.:?\-';
	$any = $ltrs . $gunk . $punc;

	// Step 1
	// Parsing the post, external links (if any) are stored in the $post_links array
	// This regexp comes straight from phpfreaks.com
	// http://www.phpfreaks.com/quickcode/Extract_All_URLs_on_a_Page/15.php
	preg_match_all("{\b http : [$any] +? (?= [$punc] * [^$any] | $)}x", $content, $post_links_temp);

	// Debug
	debug_fwrite($log, 'Post contents:');
	debug_fwrite($log, $content."\n");
	
	// Step 2.
	// Walking thru the links array
	// first we get rid of links pointing to sites, not to specific files
	// Example:
	// http://dummy-weblog.org
	// http://dummy-weblog.org/
	// http://dummy-weblog.org/post.php
	// We don't wanna ping first and second types, even if they have a valid <link/>

	foreach($post_links_temp[0] as $link_test) :
		if ( !in_array($link_test, $pung) ) : // If we haven't pung it already
			$test = parse_url($link_test);
			if (isset($test['query']))
				$post_links[] = $link_test;
			elseif(($test['path'] != '/') && ($test['path'] != ''))
				$post_links[] = $link_test;
		endif;
	endforeach;

	foreach ($post_links as $pagelinkedto){
		debug_fwrite($log, "Processing -- $pagelinkedto\n");
		$pingback_server_url = discover_pingback_server_uri($pagelinkedto, 2048);

		if ($pingback_server_url) {
                        set_time_limit( 60 ); 
			 // Now, the RPC call
			debug_fwrite($log, "Page Linked To: $pagelinkedto \n");
			debug_fwrite($log, 'Page Linked From: ');
			$pagelinkedfrom = get_permalink($post_ID);
			debug_fwrite($log, $pagelinkedfrom."\n");

			// using a timeout of 3 seconds should be enough to cover slow servers
			$client = new IXR_Client($pingback_server_url);
			$client->timeout = 3;
			$client->useragent .= ' -- WordPress/' . $wp_version;

			// when set to true, this outputs debug messages by itself
			$client->debug = false;
			$client->query('pingback.ping', array($pagelinkedfrom, $pagelinkedto)); 
			
			if ( !$client->query('pingback.ping', array($pagelinkedfrom, $pagelinkedto) ) )
				debug_fwrite($log, "Error.\n Fault code: ".$client->getErrorCode()." : ".$client->getErrorMessage()."\n");
			else
				add_ping( $post_ID, $pagelinkedto );
		}
	}

	debug_fwrite($log, "\nEND: ".time()."\n****************************\n");
	debug_fclose($log);
}

function discover_pingback_server_uri($url, $timeout_bytes = 2048) {

	$byte_count = 0;
	$contents = '';
	$headers = '';
	$pingback_str_dquote = 'rel="pingback"';
	$pingback_str_squote = 'rel=\'pingback\'';
	$x_pingback_str = 'x-pingback: ';
	$pingback_href_original_pos = 27;

	extract(parse_url($url));

	if (!isset($host)) {
		// Not an URL. This should never happen.
		return false;
	}

	$path  = (!isset($path)) ? '/'        : $path;
	$path .= (isset($query)) ? '?'.$query : '';
	$port  = (isset($port))  ? $port      : 80;

	// Try to connect to the server at $host
	$fp = fsockopen($host, $port, $errno, $errstr, 3);
	if (!$fp) {
		// Couldn't open a connection to $host;
		return false;
	}

	// Send the GET request
	$request = "GET $path HTTP/1.1\r\nHost: $host\r\nUser-Agent: WordPress/$wp_version PHP/" . phpversion() . "\r\n\r\n";
	ob_end_flush();
	fputs($fp, $request);

	// Let's check for an X-Pingback header first
	while (!feof($fp)) {
		$line = fgets($fp, 512);
		if (trim($line) == '') {
			break;
		}
		$headers .= trim($line)."\n";
		$x_pingback_header_offset = strpos(strtolower($headers), $x_pingback_str);
		if ($x_pingback_header_offset) {
			// We got it!
			preg_match('#x-pingback: (.+)#is', $headers, $matches);
			$pingback_server_url = trim($matches[1]);
			return $pingback_server_url;
		}
		if(strpos(strtolower($headers), 'content-type: ')) {
			preg_match('#content-type: (.+)#is', $headers, $matches);
			$content_type = trim($matches[1]);
		}
	}

	if (preg_match('#(image|audio|video|model)/#is', $content_type)) {
		// Not an (x)html, sgml, or xml page, no use going further
		return false;
	}

	while (!feof($fp)) {
		$line = fgets($fp, 1024);
		$contents .= trim($line);
		$pingback_link_offset_dquote = strpos($contents, $pingback_str_dquote);
		$pingback_link_offset_squote = strpos($contents, $pingback_str_squote);
		if ($pingback_link_offset_dquote || $pingback_link_offset_squote) {
			$quote = ($pingback_link_offset_dquote) ? '"' : '\'';
			$pingback_link_offset = ($quote=='"') ? $pingback_link_offset_dquote : $pingback_link_offset_squote;
			$pingback_href_pos = @strpos($contents, 'href=', $pingback_link_offset);
			$pingback_href_start = $pingback_href_pos+6;
			$pingback_href_end = @strpos($contents, $quote, $pingback_href_start);
			$pingback_server_url_len = $pingback_href_end - $pingback_href_start;
			$pingback_server_url = substr($contents, $pingback_href_start, $pingback_server_url_len);
			// We may find rel="pingback" but an incomplete pingback URI
			if ($pingback_server_url_len > 0) {
				// We got it!
				return $pingback_server_url;
			}
		}
		$byte_count += strlen($line);
		if ($byte_count > $timeout_bytes) {
			// It's no use going further, there probably isn't any pingback
			// server to find in this file. (Prevents loading large files.)
			return false;
		}
	}

	// We didn't find anything.
	return false;
}


/* wp_set_comment_status:
   part of otaku42's comment moderation hack
   changes the status of a comment according to $comment_status.
   allowed values:
   hold   : set comment_approve field to 0
   approve: set comment_approve field to 1
   delete : remove comment out of database
   
   returns true if change could be applied
   returns false on database error or invalid value for $comment_status
 */
function wp_set_comment_status($comment_id, $comment_status) {
    global $wpdb;

    switch($comment_status) {
		case 'hold':
			$query = "UPDATE $wpdb->comments SET comment_approved='0' WHERE comment_ID='$comment_id' LIMIT 1";
		break;
		case 'approve':
			$query = "UPDATE $wpdb->comments SET comment_approved='1' WHERE comment_ID='$comment_id' LIMIT 1";
		break;
		case 'delete':
			$query = "DELETE FROM $wpdb->comments WHERE comment_ID='$comment_id' LIMIT 1";
		break;
		default:
			return false;
    }
    
    if ($wpdb->query($query)) {
		do_action('wp_set_comment_status', $comment_id);
		return true;
    } else {
		return false;
    }
}


/* wp_get_comment_status
   part of otaku42's comment moderation hack
   gets the current status of a comment

   returned values:
   "approved"  : comment has been approved
   "unapproved": comment has not been approved
   "deleted   ": comment not found in database

   a (boolean) false signals an error
 */
function wp_get_comment_status($comment_id) {
    global $wpdb;
    
    $result = $wpdb->get_var("SELECT comment_approved FROM $wpdb->comments WHERE comment_ID='$comment_id' LIMIT 1");
    if ($result == NULL) {
        return "deleted";
    } else if ($result == "1") {
        return "approved";
    } else if ($result == "0") {
        return "unapproved";
    } else {
        return false;
    }
}

function wp_notify_postauthor($comment_id, $comment_type='comment') {
    global $wpdb;
    global $querystring_start, $querystring_equal, $querystring_separator;
    
    $comment = $wpdb->get_row("SELECT * FROM $wpdb->comments WHERE comment_ID='$comment_id' LIMIT 1");
    $post = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE ID='$comment->comment_post_ID' LIMIT 1");
    $user = $wpdb->get_row("SELECT * FROM $wpdb->users WHERE ID='$post->post_author' LIMIT 1");

    if ('' == $user->user_email) return false; // If there's no email to send the comment to

	$comment_author_domain = gethostbyaddr($comment->comment_author_IP);

	$blogname = get_settings('blogname');
	
	if ('comment' == $comment_type) {
		$notify_message  = "New comment on your post #$comment->comment_post_ID \"".$post->post_title."\"\r\n\r\n";
		$notify_message .= "Author : $comment->comment_author (IP: $comment->comment_author_IP , $comment_author_domain)\r\n";
		$notify_message .= "E-mail : $comment->comment_author_email\r\n";
		$notify_message .= "URI    : $comment->comment_author_url\r\n";
		$notify_message .= "Whois  : http://ws.arin.net/cgi-bin/whois.pl?queryinput=$comment->comment_author_IP\r\n";
		$notify_message .= "Comment:\r\n".$comment->comment_content."\r\n\r\n";
		$notify_message .= "You can see all comments on this post here: \r\n";
		$subject = '[' . $blogname . '] Comment: "' .$post->post_title.'"';
	} elseif ('trackback' == $comment_type) {
		$notify_message  = "New trackback on your post #$comment_post_ID \"".$post->post_title."\"\r\n\r\n";
		$notify_message .= "Website: $comment->comment_author (IP: $comment->comment_author_IP , $comment_author_domain)\r\n";
		$notify_message .= "URI    : $comment->comment_author_url\r\n";
		$notify_message .= "Excerpt: \n".$comment->comment_content."\r\n\r\n";
		$notify_message .= "You can see all trackbacks on this post here: \r\n";
		$subject = '[' . $blogname . '] Trackback: "' .$post->post_title.'"';
	} elseif ('pingback' == $comment_type) {
		$notify_message  = "New pingback on your post #$comment_post_ID \"".$post->post_title."\"\r\n\r\n";
		$notify_message .= "Website: $comment->comment_author\r\n";
		$notify_message .= "URI    : $comment->comment_author_url\r\n";
		$notify_message .= "Excerpt: \n[...] $original_context [...]\r\n\r\n";
		$notify_message .= "You can see all pingbacks on this post here: \r\n";
		$subject = '[' . $blogname . '] Pingback: "' .$post->post_title.'"';
	}
	$notify_message .= get_permalink($comment->comment_post_ID) . '#comments';

	if ('' == $comment->comment_author_email || '' == $comment->comment_author) {
		$from = "From: \"$blogname\" <wordpress@" . $_SERVER['SERVER_NAME'] . '>';
	} else {
		$from = 'From: "' . $comment->comment_author . "\" <$comment->comment_author_email>";
	}

	$message_headers = "MIME-Version: 1.0\r\n"
		. "$from\r\n"
		. "Content-Type: text/plain; charset=\"" . get_settings('blog_charset') . "\"\r\n";

	@wp_mail($user->user_email, $subject, $notify_message, $message_headers);
   
    return true;
}

/* wp_notify_moderator
   notifies the moderator of the blog (usually the admin)
   about a new comment that waits for approval
   always returns true
 */
function wp_notify_moderator($comment_id) {
    global $wpdb;
    global $querystring_start, $querystring_equal, $querystring_separator;

    if( get_settings( "moderation_notify" ) == 0 )
        return true; 
    
    $comment = $wpdb->get_row("SELECT * FROM $wpdb->comments WHERE comment_ID='$comment_id' LIMIT 1");
    $post = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE ID='$comment->comment_post_ID' LIMIT 1");
    $user = $wpdb->get_row("SELECT * FROM $wpdb->users WHERE ID='$post->post_author' LIMIT 1");

    $comment_author_domain = gethostbyaddr($comment->comment_author_IP);
    $comments_waiting = $wpdb->get_var("SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'");

    $notify_message  = "A new comment on the post #$comment->comment_post_ID \"".$post->post_title."\" is waiting for your approval\r\n\r\n";
    $notify_message .= "Author : $comment->comment_author (IP: $comment->comment_author_IP , $comment_author_domain)\r\n";
    $notify_message .= "E-mail : $comment->comment_author_email\r\n";
    $notify_message .= "URL    : $comment->comment_author_url\r\n";
    $notify_message .= "Whois  : http://ws.arin.net/cgi-bin/whois.pl?queryinput=$comment->comment_author_IP\r\n";
    $notify_message .= "Comment:\r\n".$comment->comment_content."\r\n\r\n";
    $notify_message .= "To approve this comment, visit: " . get_settings('siteurl') . "/wp-admin/post.php?action=mailapprovecomment&p=".$comment->comment_post_ID."&comment=$comment_id\r\n";
    $notify_message .= "To delete this comment, visit: " . get_settings('siteurl') . "/wp-admin/post.php?action=confirmdeletecomment&p=".$comment->comment_post_ID."&comment=$comment_id\r\n";
    $notify_message .= "Currently $comments_waiting comments are waiting for approval. Please visit the moderation panel:\r\n";
    $notify_message .= get_settings('siteurl') . "/wp-admin/moderation.php\r\n";

    $subject = '[' . get_settings('blogname') . '] Please approve: "' .$post->post_title.'"';
    $admin_email = get_settings("admin_email");
    $from  = "From: $admin_email";

    $message_headers = "MIME-Version: 1.0\r\n"
    	. "$from\r\n"
    	. "Content-Type: text/plain; charset=\"" . get_settings('blog_charset') . "\"\r\n";

    @wp_mail($admin_email, $subject, $notify_message, $message_headers);
    
    return true;
}


function start_wp($use_wp_query = false) {
  global $post, $id, $postdata, $authordata, $day, $preview, $page, $pages, $multipage, $more, $numpages, $wp_query;
	global $pagenow;

	if ($use_wp_query) {
	  $post = $wp_query->next_post();
	} else {
	  $wp_query->next_post();
	}

	if (!$preview) {
		$id = $post->ID;
	} else {
		$id = 0;
		$postdata = array (
			'ID' => 0,
			'Author_ID' => $_GET['preview_userid'],
			'Date' => $_GET['preview_date'],
			'Content' => $_GET['preview_content'],
			'Excerpt' => $_GET['preview_excerpt'],
			'Title' => $_GET['preview_title'],
			'Category' => $_GET['preview_category'],
			'Notify' => 1
			);
	}
	$authordata = get_userdata($post->post_author);

	$day = mysql2date('d.m.y', $post->post_date);
	$currentmonth = mysql2date('m', $post->post_date);
	$numpages = 1;
	if (!$page)
		$page = 1;
	if (isset($p))
		$more = 1;
	$content = $post->post_content;
	if (preg_match('/<!--nextpage-->/', $post->post_content)) {
		if ($page > 1)
			$more = 1;
		$multipage = 1;
		$content = $post->post_content;
		$content = str_replace("\n<!--nextpage-->\n", '<!--nextpage-->', $content);
		$content = str_replace("\n<!--nextpage-->", '<!--nextpage-->', $content);
		$content = str_replace("<!--nextpage-->\n", '<!--nextpage-->', $content);
		$pages = explode('<!--nextpage-->', $content);
		$numpages = count($pages);
	} else {
		$pages[0] = $post->post_content;
		$multipage = 0;
	}
	return true;
}

function is_new_day() {
	global $day, $previousday;
	if ($day != $previousday) {
		return(1);
	} else {
		return(0);
	}
}

// Filters: these are the core of WP's plugin architecture

function apply_filters($tag, $string, $filter = true) {
	global $wp_filter;
	if (isset($wp_filter['all'])) {
		foreach ($wp_filter['all'] as $priority => $functions) {
			if (isset($wp_filter[$tag][$priority]))
				$wp_filter[$tag][$priority] = array_merge($wp_filter['all'][$priority], $wp_filter[$tag][$priority]);
			else
				$wp_filter[$tag][$priority] = array_merge($wp_filter['all'][$priority], array());
			$wp_filter[$tag][$priority] = array_unique($wp_filter[$tag][$priority]);
		}

	}

	if (isset($wp_filter[$tag])) {
		ksort($wp_filter[$tag]);
		foreach ($wp_filter[$tag] as $priority => $functions) {
			if (!is_null($functions)) {
				foreach($functions as $function) {
					if ($filter)
						$string = call_user_func($function, $string);
					else
						call_user_func($function, $string);
				}
			}
		}
	}
	return $string;
}

function add_filter($tag, $function_to_add, $priority = 10) {
	global $wp_filter;
	// So the format is wp_filter['tag']['array of priorities']['array of functions']
	if (!@in_array($function_to_add, $wp_filter[$tag]["$priority"])) {
		$wp_filter[$tag]["$priority"][] = $function_to_add;
	}
	return true;
}

function remove_filter($tag, $function_to_remove, $priority = 10) {
	global $wp_filter;
	if (@in_array($function_to_remove, $wp_filter[$tag]["$priority"])) {
		foreach ($wp_filter[$tag]["$priority"] as $function) {
			if ($function_to_remove != $function) {
				$new_function_list[] = $function;
			}
		}
		$wp_filter[$tag]["$priority"] = $new_function_list;
	}
	//die(var_dump($wp_filter));
	return true;
}

// The *_action functions are just aliases for the *_filter functions, they take special strings instead of generic content

function do_action($tag, $string) {
	apply_filters($tag, $string, false);
	return $string;
}

function add_action($tag, $function_to_add, $priority = 10) {
	add_filter($tag, $function_to_add, $priority);
}

function remove_action($tag, $function_to_remove, $priority = 10) {
	remove_filter($tag, $function_to_remove, $priority);
}

function using_index_permalinks($permalink_structure = '') {
    if (empty($permalink_structure)) {
        $permalink_structure = get_settings('permalink_structure');
	
        if (empty($permalink_structure)) {
            return false;
        }
    }

    // If the index is not in the permalink, we're using mod_rewrite.
    if (preg_match('#^/*index.php#', $permalink_structure)) {
      return true;
    }
    
    return false;
}

function preg_index($number, $matches = '') {
    $match_prefix = '$';
    $match_suffix = '';
    
    if (! empty($matches)) {
        $match_prefix = '$' . $matches . '['; 
        $match_suffix = ']';
    }        
    
    return "$match_prefix$number$match_suffix";        
}


function get_page_uri($page) {
	global $wpdb;
	$page = $wpdb->get_row("SELECT ID, post_name, post_parent FROM $wpdb->posts WHERE ID = '$page'");

	$uri = urldecode($page->post_name);

	// A page cannot be it's own parent.
	if ($page->post_parent == $page->ID) {
		return $uri;
	}

	while ($page->post_parent != 0) {
		$page = $wpdb->get_row("SELECT post_name, post_parent FROM $wpdb->posts WHERE ID = '$page->post_parent'");
		$uri = urldecode($page->post_name) . "/" . $uri;
	}

	return $uri;
}

function page_rewrite_rules() {
	$uris = get_settings('page_uris');

	$rewrite_rules = array();
        if( is_array( $uris ) )
        {
            foreach ($uris as $uri => $pagename) {
							$rewrite_rules += array($uri . '/?$' => "index.php?pagename=" . urldecode($pagename));
            }
        }

	return $rewrite_rules;
}

function get_date_permastruct($permalink_structure = '') {
    if (empty($permalink_structure)) {
        $permalink_structure = get_settings('permalink_structure');
	
        if (empty($permalink_structure)) {
            return false;
        }
    }
		
    $front = substr($permalink_structure, 0, strpos($permalink_structure, '%'));    
		// The date permalink must have year, month, and day separated by slashes.
		$endians = array('%year%/%monthnum%/%day%', '%day%/%monthnum%/%year%', '%monthnum%/%day%/%year%');

		$date_structure = '';

		foreach ($endians as $endian) {
			if (false !== strpos($permalink_structure, $endian)) {
				$date_structure = $front . $endian;
				break;
			}
		} 

		if (empty($date_structure)) {
				$date_structure = $front . '%year%/%monthnum%/%day%';
		}

		return $date_structure;
}

function get_year_permastruct($permalink_structure = '') {
	$structure = get_date_permastruct($permalink_structure);

	if (empty($structure)) {
		return false;
	}

	$structure = str_replace('%monthnum%', '', $structure);
	$structure = str_replace('%day%', '', $structure);

	$structure = preg_replace('#/+#', '/', $structure);

	return $structure;
}

function get_month_permastruct($permalink_structure = '') {
	$structure = get_date_permastruct($permalink_structure);

	if (empty($structure)) {
		return false;
	}

	$structure = str_replace('%day%', '', $structure);

	$structure = preg_replace('#/+#', '/', $structure);

	return $structure;
}

function get_day_permastruct($permalink_structure = '') {
	return get_date_permastruct($permalink_structure);
}

function generate_rewrite_rules($permalink_structure = '', $matches = '', $forcomments = false) {
	$rewritecode = 
		array(
					'%year%',
					'%monthnum%',
					'%day%',
					'%hour%',
					'%minute%',
					'%second%',
					'%postname%',
					'%post_id%',
					'%category%',
					'%author%',
					'%pagename%',
					'%search%'
					);

	$rewritereplace = 
		array(
					'([0-9]{4})',
					'([0-9]{1,2})',
					'([0-9]{1,2})',
					'([0-9]{1,2})',
					'([0-9]{1,2})',
					'([0-9]{1,2})',
					'([^/]+)',
					'([0-9]+)',
					'(.+?)',
					'([^/]+)',
					'([^/]+)',
					'(.+)'
					);

	$queryreplace = 
		array (
					 'year=',
					 'monthnum=',
					 'day=',
					 'hour=',
					 'minute=',
					 'second=',
					 'name=',
					 'p=',
					 'category_name=',
					 'author_name=',
					 'pagename=',
					 's='
					 );

	$feedregex2 = '(feed|rdf|rss|rss2|atom)/?$';
	$feedregex = 'feed/' . $feedregex2;

	$trackbackregex = 'trackback/?$';
	$pageregex = 'page/?([0-9]{1,})/?$';

	$front = substr($permalink_structure, 0, strpos($permalink_structure, '%'));    
	preg_match_all('/%.+?%/', $permalink_structure, $tokens);

	$num_tokens = count($tokens[0]);

	$index = 'index.php';
	$feedindex = $index;
	$trackbackindex = $index;
	for ($i = 0; $i < $num_tokens; ++$i) {
		if (0 < $i) {
			$queries[$i] = $queries[$i - 1] . '&';
		}
             
		$query_token = str_replace($rewritecode, $queryreplace, $tokens[0][$i]) . preg_index($i+1, $matches);
		$queries[$i] .= $query_token;
	}

	$structure = $permalink_structure;
	if ($front != '/') {
		$structure = str_replace($front, '', $structure);
	}
	$structure = trim($structure, '/');
	$dirs = explode('/', $structure);
	$num_dirs = count($dirs);

	$front = preg_replace('|^/+|', '', $front);

	$post_rewrite = array();
	$struct = $front;
	for ($j = 0; $j < $num_dirs; ++$j) {
		$struct .= $dirs[$j] . '/';
		$struct = ltrim($struct, '/');
		$match = str_replace($rewritecode, $rewritereplace, $struct);
		$num_toks = preg_match_all('/%.+?%/', $struct, $toks);
		$query = $queries[$num_toks - 1];

		$pagematch = $match . $pageregex;
		$pagequery = $index . '?' . $query . '&paged=' . preg_index($num_toks + 1, $matches);

		$feedmatch = $match . $feedregex;
		$feedquery = $feedindex . '?' . $query . '&feed=' . preg_index($num_toks + 1, $matches);

		$feedmatch2 = $match . $feedregex2;
		$feedquery2 = $feedindex . '?' . $query . '&feed=' . preg_index($num_toks + 1, $matches);

		if ($forcomments) {
			$feedquery .= '&withcomments=1';
			$feedquery2 .= '&withcomments=1';
		}
				
		$rewrite = array($feedmatch => $feedquery, $feedmatch2 => $feedquery2, $pagematch => $pagequery);

		if ($num_toks) {
			$post = 0;
			if (strstr($struct, '%postname%') || strstr($struct, '%post_id%')
					|| (strstr($struct, '%year%') &&  strstr($struct, '%monthnum%') && strstr($struct, '%day%') && strstr($struct, '%hour%') && strstr($struct, '%minute') && strstr($struct, '%second%'))) {
				$post = 1;
				$trackbackmatch = $match . $trackbackregex;
				$trackbackquery = $trackbackindex . '?' . $query . '&tb=1';
				$match = $match . '?([0-9]+)?/?$';
				$query = $index . '?' . $query . '&page=' . preg_index($num_toks + 1, $matches);
			} else {
				$match .= '?$';
				$query = $index . '?' . $query;
			}
				        
			$rewrite = $rewrite + array($match => $query);

			if ($post) {
				$rewrite = array($trackbackmatch => $trackbackquery) + $rewrite;
			}
		}

		$post_rewrite = $rewrite + $post_rewrite;
	}

	return $post_rewrite;
}

/* rewrite_rules
 * Construct rewrite matches and queries from permalink structure.
 * matches - The name of the match array to use in the query strings.
 *           If empty, $1, $2, $3, etc. are used.
 * Returns an associate array of matches and queries.
 */
function rewrite_rules($matches = '', $permalink_structure = '') {
	$rewrite = array();

	if (empty($permalink_structure)) {
		$permalink_structure = get_settings('permalink_structure');
        
		if (empty($permalink_structure)) {
			return $rewrite;
		}
	}

	$front = substr($permalink_structure, 0, strpos($permalink_structure, '%'));
	$index = 'index.php';
	$prefix = '';
	if (using_index_permalinks($permalink_structure)) {
		$prefix = $index . '/';
	}

	// Post
	$post_rewrite = generate_rewrite_rules($permalink_structure, $matches);

	// Date
	$date_rewrite = generate_rewrite_rules(get_date_permastruct($permalink_structure), $matches);
		
	// Root
	$root_rewrite = generate_rewrite_rules($prefix . '/', $matches);

	// Comments
	$comments_rewrite = generate_rewrite_rules($prefix . 'comments', $matches, true);

	// Search
	$search_structure = $prefix . "search/%search%";
	$search_rewrite = generate_rewrite_rules($search_structure, $matches);

	// Categories
	if ( '' == get_settings('category_base') )
		$category_structure = $front . 'category/';
	else
		$category_structure = get_settings('category_base') . '/';

	$category_structure = $category_structure . '%category%';
	$category_rewrite = generate_rewrite_rules($category_structure, $matches);

	// Authors
	$author_structure = $front . 'author/%author%';
	$author_rewrite = generate_rewrite_rules($author_structure, $matches);

	// Pages
	$page_rewrite = page_rewrite_rules();

	// Deprecated style static pages
	$page_structure = $prefix . 'site/%pagename%';
	$old_page_rewrite = generate_rewrite_rules($page_structure, $matches);

	// Put them together.
	$rewrite = $page_rewrite + $root_rewrite + $comments_rewrite + $old_page_rewrite + $search_rewrite + $category_rewrite + $author_rewrite + $date_rewrite + $post_rewrite;

	$rewrite = apply_filters('rewrite_rules_array', $rewrite);
	return $rewrite;
}

function mod_rewrite_rules ($permalink_structure) {
	$site_root = str_replace('http://', '', trim(get_settings('siteurl')));
	$site_root = preg_replace('|([^/]*)(.*)|i', '$2', $site_root);
	if ('/' != substr($site_root, -1)) $site_root = $site_root . '/';
    
	$home_root = str_replace('http://', '', trim(get_settings('home')));
	$home_root = preg_replace('|([^/]*)(.*)|i', '$2', $home_root);
	if ('/' != substr($home_root, -1)) $home_root = $home_root . '/';

	$rules = "<IfModule mod_rewrite.c>\n";
	$rules .= "RewriteEngine On\n";
	$rules .= "RewriteBase $home_root\n";
	$rewrite = rewrite_rules('', $permalink_structure);

	$num_rules = count($rewrite);
	$rules .= "RewriteCond %{REQUEST_FILENAME} -f [OR]\n" .
		"RewriteCond %{REQUEST_FILENAME} -d\n" .
		"RewriteRule ^.*$ - [S=$num_rules]\n";

	foreach ($rewrite as $match => $query) {
		// Apache 1.3 does not support the reluctant (non-greedy) modifier.
		$match = str_replace('.+?', '.+', $match);

		// If the match is unanchored and greedy, prepend rewrite conditions
		// to avoid infinite redirects and eclipsing of real files.
		if ($match == '(.+)/?$' || $match == '([^/]+)/?$' ) {
			//nada.
		}

		if (strstr($query, 'index.php')) {
			$rules .= 'RewriteRule ^' . $match . ' ' . $home_root . $query . " [QSA,L]\n";
		} else {
			$rules .= 'RewriteRule ^' . $match . ' ' . $site_root . $query . " [QSA,L]\n";
		}
	}
	$rules .= "</IfModule>\n";

	$rules = apply_filters('rewrite_rules', $rules);

	return $rules;
}

function get_posts($args) {
	global $wpdb;
	parse_str($args, $r);
	if (!isset($r['numberposts'])) $r['numberposts'] = 5;
	if (!isset($r['offset'])) $r['offset'] = 0;
	// The following not implemented yet
	if (!isset($r['category'])) $r['category'] = '';
	if (!isset($r['orderby'])) $r['orderby'] = '';
	if (!isset($r['order'])) $r['order'] = '';

	$now = current_time('mysql');

	$posts = $wpdb->get_results("SELECT DISTINCT * FROM $wpdb->posts WHERE post_date <= '$now' AND (post_status = 'publish') GROUP BY $wpdb->posts.ID ORDER BY post_date DESC LIMIT " . $r['offset'] . ',' . $r['numberposts']);

    update_post_caches($posts);
	
	return $posts;
}

function check_comment($author, $email, $url, $comment, $user_ip, $user_agent) {
	global $wpdb;

	if (1 == get_settings('comment_moderation')) return false; // If moderation is set to manual

	if ( (count(explode('http:', $comment)) - 1) >= get_settings('comment_max_links') )
		return false; // Check # of external links

	// Comment whitelisting:
	if ( 1 == get_settings('comment_whitelist')) {
		if( $author != '' && $email != '' ) {
		    $ok_to_comment = $wpdb->get_var("SELECT comment_approved FROM $wpdb->comments WHERE comment_author_email = '$email' and comment_approved = '1' ");
		    if ( 1 == $ok_to_comment && false === strpos( $email, get_settings('moderation_keys')) )
			return true;
		} else {
			return false;
		}
	}

	// Useless numeric encoding is a pretty good spam indicator:
	// Extract entities:
	if (preg_match_all('/&#(\d+);/',$comment,$chars)) {
		foreach ($chars[1] as $char) {
			// If it's an encoded char in the normal ASCII set, reject
			if ($char < 128)
				return false;
		}
	}

	$mod_keys = trim( get_settings('moderation_keys') );
	if ('' == $mod_keys )
		return true; // If moderation keys are empty
	$words = explode("\n", $mod_keys );

	foreach ($words as $word) {
		$word = trim($word);

		// Skip empty lines
		if (empty($word)) { continue; }

		// Do some escaping magic so that '#' chars in the 
		// spam words don't break things:
		$word = preg_replace('/(\\\\|#)/','\\\\$1',$word);
		
		$pattern = "#$word#i"; 
		if ( preg_match($pattern, $author) ) return false;
		if ( preg_match($pattern, $email) ) return false;
		if ( preg_match($pattern, $url) ) return false;
		if ( preg_match($pattern, $comment) ) return false;
		if ( preg_match($pattern, $user_ip) ) return false;
		if ( preg_match($pattern, $user_agent) ) return false;
	}

	return true;
}

function query_posts($query) {
    global $wp_query;

    return $wp_query->query($query);
}

function update_post_caches($posts) {
    global $category_cache, $comment_count_cache, $post_meta_cache;
    global $wpdb;

    // No point in doing all this work if we didn't match any posts.
    if (! $posts) {
        return;
    }

    // Get the categories for all the posts
    foreach ($posts as $post) {
        $post_id_list[] = $post->ID;
    }
    $post_id_list = implode(',', $post_id_list);

    $dogs = $wpdb->get_results("SELECT DISTINCT
        ID, category_id, cat_name, category_nicename, category_description, category_parent
        FROM $wpdb->categories, $wpdb->post2cat, $wpdb->posts
        WHERE category_id = cat_ID AND post_id = ID AND post_id IN ($post_id_list)");
        
    if (!empty($dogs)) {
        foreach ($dogs as $catt) {
					$category_cache[$catt->ID][$catt->category_id] = $catt;
        }
    }

    // Do the same for comment numbers
    $comment_counts = $wpdb->get_results("SELECT ID, COUNT( comment_ID ) AS ccount
        FROM $wpdb->posts
        LEFT JOIN $wpdb->comments ON ( comment_post_ID = ID  AND comment_approved =  '1')
        WHERE post_status =  'publish' AND ID IN ($post_id_list)
        GROUP BY ID");
    
    if ($comment_counts) {
        foreach ($comment_counts as $comment_count) {
            $comment_count_cache["$comment_count->ID"] = $comment_count->ccount;
        }
    }

    // Get post-meta info
    if ( $meta_list = $wpdb->get_results("SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta  WHERE post_id IN($post_id_list) ORDER BY post_id, meta_key", ARRAY_A) ) {
		
        // Change from flat structure to hierarchical:
        $post_meta_cache = array();
        foreach ($meta_list as $metarow) {
            $mpid = $metarow['post_id'];
            $mkey = $metarow['meta_key'];
            $mval = $metarow['meta_value'];
			
            // Force subkeys to be array type:
            if (!isset($post_meta_cache[$mpid]) || !is_array($post_meta_cache[$mpid]))
                $post_meta_cache[$mpid] = array();
            if (!isset($post_meta_cache[$mpid]["$mkey"]) || !is_array($post_meta_cache[$mpid]["$mkey"]))
                $post_meta_cache[$mpid]["$mkey"] = array();
			
            // Add a value to the current pid/key:
            $post_meta_cache[$mpid][$mkey][] = $mval;
        }
    }
}

function update_category_cache() {
    global $cache_categories, $wpdb;
    $dogs = $wpdb->get_results("SELECT * FROM $wpdb->categories");
    foreach ($dogs as $catt) {
        $cache_categories[$catt->cat_ID] = $catt;
    }
}

function update_user_cache() {
    global $cache_userdata, $wpdb;

    if ( $users = $wpdb->get_results("SELECT * FROM $wpdb->users WHERE user_level > 0") ) :
		foreach ($users as $user) :
			$cache_userdata[$user->ID] = $user;
		endforeach;
		return true;
	else: 
		return false;
	endif;
}

function wp_head() {
	do_action('wp_head', '');
}

function is_single ($post = '') {
	global $wp_query;

	if (! $wp_query->is_single) {
		return false;
	}

	if (empty($post)) {
		return true;
	}

	$post_obj = $wp_query->get_queried_object();

	if ($post == $post_obj->ID) {
		return true;
	} else if ($post == $post_obj->post_title) {
		return true;
	} else if ($post == $post_obj->post_name) {
		return true;
	}

	return false;
}

function is_page ($page = '') {
	global $wp_query;

	if (! $wp_query->is_page) {
		return false;
	}

	if (empty($page)) {
		return true;
	}

	$page_obj = $wp_query->get_queried_object();
		
	if ($page == $page_obj->ID) {
		return true;
	} else if ($page == $page_obj->post_title) {
		return true;
	} else if ($page == $page_obj->post_name) {
		return true;
	}

	return false;
}

function is_archive () {
    global $wp_query;

    return $wp_query->is_archive;
}

function is_date () {
    global $wp_query;

    return $wp_query->is_date;
}

function is_year () {
    global $wp_query;

    return $wp_query->is_year;
}

function is_month () {
    global $wp_query;

    return $wp_query->is_month;
}

function is_day () {
    global $wp_query;

    return $wp_query->is_day;
}

function is_time () {
    global $wp_query;

    return $wp_query->is_time;
}

function is_author ($author = '') {
	global $wp_query;

	if (! $wp_query->is_author) {
		return false;
	}

	if (empty($author)) {
		return true;
	}

	$author_obj = $wp_query->get_queried_object();
		
	if ($author == $author_obj->ID) {
		return true;
	} else if ($author == $author_obj->user_nickname) {
		return true;
	} else if ($author == $author_obj->user_nicename) {
		return true;
	}

	return false;
}

function is_category ($category = '') {
	global $wp_query;

	if (! $wp_query->is_category) {
		return false;
	}

	if (empty($category)) {
		return true;
	}

	$cat_obj = $wp_query->get_queried_object();
		
	if ($category == $cat_obj->cat_ID) {
		return true;
	} else if ($category == $cat_obj->cat_name) {
		return true;
	} else if ($category == $cat_obj->category_nicename) {
		return true;
	}

	return false;
}

function is_search () {
    global $wp_query;

    return $wp_query->is_search;
}

function is_feed () {
    global $wp_query;

    return $wp_query->is_feed;
}

function is_home () {
    global $wp_query;

    return $wp_query->is_home;
}

function is_404 () {
    global $wp_query;

    return $wp_query->is_404;
}

function is_paged () {
    global $wp_query;

    return $wp_query->is_paged;
}

function get_query_var($var) {
  global $wp_query;

  return $wp_query->get($var);
}

function have_posts() {
    global $wp_query;

    return $wp_query->have_posts();
}

function the_post() {
    start_wp(true);
}

function get_stylesheet() {
	return apply_filters('stylesheet', get_settings('stylesheet'));
}

function get_template() {
	return apply_filters('template', get_settings('template'));
}

function get_template_directory() {
	$template = get_template();

	if (empty($template) || $template == 'default') {
		$template = ABSPATH . "wp-content";
	} else {
		$template = ABSPATH . "wp-content/themes/$template";
	}

	return $template;
}

function get_theme_data($theme_file) {
	$theme_data = implode('', file($theme_file));
	preg_match("|Theme Name:(.*)|i", $theme_data, $theme_name);
	preg_match("|Theme URI:(.*)|i", $theme_data, $theme_uri);
	preg_match("|Description:(.*)|i", $theme_data, $description);
	preg_match("|Author:(.*)|i", $theme_data, $author_name);
	preg_match("|Author URI:(.*)|i", $theme_data, $author_uri);
	preg_match("|Template:(.*)|i", $theme_data, $template);
	if ( preg_match("|Version:(.*)|i", $theme_data, $version) )
		$version = $version[1];
	else
		$version ='';

	$description = wptexturize($description[1]);

	$name = $theme_name[1];
	$name = trim($name);
	$theme = $name;
	if ('' != $theme_uri[1] && '' != $name) {
		$theme = __("<a href='{$theme_uri[1]}' title='Visit theme homepage'>{$theme}</a>");
	}

	if ('' == $author_uri[1]) {
		$author = $author_name[1];
	} else {
		$author = __("<a href='{$author_uri[1]}' title='Visit author homepage'>{$author_name[1]}</a>");
	}

	return array('Name' => $name, 'Title' => $theme, 'Description' => $description, 'Author' => $author, 'Version' => $version, 'Template' => $template[1]);
}

function get_themes() {
	global $wp_themes;
	global $wp_broken_themes;

	if (isset($wp_themes)) {
		return $wp_themes;
	}

	$themes = array();
	$wp_broken_themes = array();
	$theme_loc = 'wp-content/themes';
	$theme_root = ABSPATH . $theme_loc;

	// Files in wp-content/themes directory
	$themes_dir = @ dir($theme_root);
	if ($themes_dir) {
		while(($theme_dir = $themes_dir->read()) !== false) {
			if (is_dir($theme_root . '/' . $theme_dir)) {
				if ($theme_dir == '.' || $theme_dir == '..') {
					continue;
				}
				$stylish_dir = @ dir($theme_root . '/' . $theme_dir);
				$found_stylesheet = false;
				while(($theme_file = $stylish_dir->read()) !== false) {
					if ( $theme_file == 'style.css' ) {
						$theme_files[] = $theme_dir . '/' . $theme_file;
						$found_stylesheet = true;
						break;
					}
				}
				if (!$found_stylesheet) {
					$wp_broken_themes[$theme_dir] = array('Name' => $theme_dir, 'Title' => $theme_dir, 'Description' => __('Stylesheet is missing.'));
				}
			}
		}
	}

	$default_files = array('wp-comments.php', 'wp-comments-popup.php', 'wp-comments-post.php', 'wp-footer.php', 'wp-header.php', 'wp-sidebar.php', 'footer.php', 'header.php', 'sidebar.php');

	// Get the files for the default template.
	$default_template_files = array();
	{
		// Find the index.
		if (file_exists(ABSPATH  .'wp-content/index.php')) {
			$default_template_files[] = 'wp-content/index.php';
		} else {
			$default_template_files[] = 'index.php';
		}
		
		$dirs = array('', 'wp-content');
		foreach ($dirs as $dir) {
			$template_dir = @ dir(ABSPATH . $dir);
			while(($file = $template_dir->read()) !== false) {
				if ( !preg_match('|^\.+$|', $file) && in_array($file, $default_files)) 
					$default_template_files[] = trim("$dir/$file", '/');
			}
		}
	}

	// Get the files for the default stylesheet.
	$default_stylesheet_files = array();
	{
		$stylesheet_dir = @ dir(ABSPATH);
		while(($file = $stylesheet_dir->read()) !== false) {
			if ( !preg_match('|^\.+$|', $file) && preg_match('|\.css$|', $file)) 
				$default_stylesheet_files[] = "$file";
		}
	}
	
	// The default theme always exists.
	$themes['Default'] = array('Name' => 'Default', 'Title' => 'WordPress Default', 'Description' => 'The default theme included with WordPress.', 'Author' => 'Dave Shea', 'Version' => '1.3', 'Template' => 'default', 'Stylesheet' => 'default', 'Template Files' => $default_template_files, 'Stylesheet Files' => $default_stylesheet_files, 'Template Dir' => '/', 'Stylesheet Dir' => '/', 'Parent Theme' => '');

	if (!$themes_dir || !$theme_files) {
		return $themes;
	}

	sort($theme_files);

	foreach($theme_files as $theme_file) {
		$theme_data = get_theme_data("$theme_root/$theme_file");
	  
		$name = $theme_data['Name']; 
		$title = $theme_data['Title'];
		$description = wptexturize($theme_data['Description']);
		$version = $theme_data['Version'];
		$author = $theme_data['Author'];
		$template = $theme_data['Template'];
		$stylesheet = dirname($theme_file);

		if (empty($name)) {
			$name = dirname($theme_file);
			$title = $name;
		}

		if (empty($template)) {
			if (file_exists(dirname("$theme_root/$theme_file/index.php"))) {
				$template = dirname($theme_file);
			} else {
				continue;
			}
		}

		$template = trim($template);

		if (($template != 'default') && (! file_exists("$theme_root/$template/index.php"))) {
			$wp_broken_themes[$name] = array('Name' => $name, 'Title' => $title, 'Description' => __('Template is missing.'));
			continue;
		}
		
		$stylesheet_files = array();
		if ($stylesheet != 'default') {
			$stylesheet_dir = @ dir("$theme_root/$stylesheet");
			if ($stylesheet_dir) {
				while(($file = $stylesheet_dir->read()) !== false) {
					if ( !preg_match('|^\.+$|', $file) && preg_match('|\.css$|', $file) ) 
						$stylesheet_files[] = "$theme_loc/$stylesheet/$file";
				}
			}
		} else {
			$stylesheet_files = $default_stylesheet_files;
		}

		$template_files = array();		
		if ($template != 'default') {
			$template_dir = @ dir("$theme_root/$template");
			if ($template_dir) {
				while(($file = $template_dir->read()) !== false) {
					if ( !preg_match('|^\.+$|', $file) && preg_match('|\.php$|', $file) ) 
						$template_files[] = "$theme_loc/$template/$file";
				}
			}
		} else {
			$template_files = $default_template_files;
		}

		$template_dir = dirname($template_files[0]);
		$stylesheet_dir = dirname($stylesheet_files[0]);

		if (empty($template_dir)) $template_dir = '/';
		if (empty($stylesheet_dir)) $stylesheet_dir = '/';
		
		$themes[$name] = array('Name' => $name, 'Title' => $title, 'Description' => $description, 'Author' => $author, 'Version' => $version, 'Template' => $template, 'Stylesheet' => $stylesheet, 'Template Files' => $template_files, 'Stylesheet Files' => $stylesheet_files, 'Template Dir' => $template_dir, 'Stylesheet Dir' => $stylesheet_dir);
	}

	// Resolve theme dependencies.
	$theme_names = array_keys($themes);

	foreach ($theme_names as $theme_name) {
		$themes[$theme_name]['Parent Theme'] = '';
		if ($themes[$theme_name]['Stylesheet'] != $themes[$theme_name]['Template']) {
			foreach ($theme_names as $parent_theme_name) {
				if (($themes[$parent_theme_name]['Stylesheet'] == $themes[$parent_theme_name]['Template']) && ($themes[$parent_theme_name]['Template'] == $themes[$theme_name]['Template'])) {
					$themes[$theme_name]['Parent Theme'] = $themes[$parent_theme_name]['Name'];
					break;
				}
			}
		}
	}

	$wp_themes = $themes;

	return $themes;
}

function get_theme($theme) {
	$themes = get_themes();

	if (array_key_exists($theme, $themes)) {
		return $themes[$theme];
	}

	return NULL;
}

function get_current_theme() {
	$themes = get_themes();
	$theme_names = array_keys($themes);
	$current_template = get_settings('template');
	$current_stylesheet = get_settings('stylesheet');
	$current_theme = 'Default';

	if ($themes) {
		foreach ($theme_names as $theme_name) {
			if ($themes[$theme_name]['Stylesheet'] == $current_stylesheet &&
					$themes[$theme_name]['Template'] == $current_template) {
				$current_theme = $themes[$theme_name]['Name'];
			}
		}
	}

	return $current_theme;
}

function get_page_template() {
	global $wp_query;

	$id = $wp_query->post->ID;	
	$template_dir = get_template_directory();
	$default = "$template_dir/page.php";

	$template = get_post_meta($id, '_wp_page_template', true);

	if (empty($template) || ($template == 'default')) {
		return $default;
	}

	if (file_exists("$template_dir/$template")) {
		return "$template_dir/$template";
	}

	return $default;
}

// Borrowed from the PHP Manual user notes. Convert entities, while
// preserving already-encoded entities:
function htmlentities2($myHTML) {
	$translation_table=get_html_translation_table (HTML_ENTITIES,ENT_QUOTES);
	$translation_table[chr(38)] = '&';
	return preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/","&amp;" , strtr($myHTML, $translation_table));
}


function wp_mail($to, $subject, $message, $headers = '', $more = '') {
        if( $headers == '' ) {
                $headers = "MIME-Version: 1.0\r\n" .
                           "From: " . $to . " <" . $to . ">\r\n" .
                           "Content-Type: text/plain; charset=\"" . get_settings('blog_charset') . "\"\r\n";
        }
	if ( function_exists('mb_send_mail') )
                return mb_send_mail($to, $subject, $message, $headers, $more);
	else
		return mail($to, $subject, $message, $headers, $more);
}

function wp_login($username, $password, $already_md5 = false) {
	global $wpdb, $error;

	if ( !$username )
		return false;

	if ( !$password ) {
		$error = __('<strong>Error</strong>: The password field is empty.');
		return false;
	}

	$login = $wpdb->get_row("SELECT ID, user_login, user_pass FROM $wpdb->users WHERE user_login = '$username'");

	if (!$login) {
		$error = __('<strong>Error</strong>: Wrong login.');
		return false;
	} else {
		// If the password is already_md5, it has been double hashed.
		// Otherwise, it is plain text.
		if ( ($already_md5 && $login->user_login == $username && md5($login->user_pass) == $password) || ($login->user_login == $username && $login->user_pass == md5($password)) ) {
			return true;
		} else {
			$error = __('<strong>Error</strong>: Incorrect password.');
			$pwd = '';
			return false;
		}
	}
}

function is_plugin_page() {
	global $plugin_page;

	if (isset($plugin_page)) {
		return true;
	}

	return false;
}

/*
add_query_arg: Returns a modified querystring by adding
a single key & value or an associative array.
Setting a key value to emptystring removes the key.
Omitting oldquery_or_uri uses the $_SERVER value.

Parameters:
add_query_arg(newkey, newvalue, oldquery_or_uri) or
add_query_arg(associative_array, oldquery_or_uri)
*/
function add_query_arg() {
	$ret = '';
	if(is_array(func_get_arg(0))) {
		$uri = @func_get_arg(1);
	}
	else {
		if (@func_num_args() < 3) {
			$uri = $_SERVER['REQUEST_URI'];
		} else {
			$uri = @func_get_arg(2);
		}
	}

	if (strstr($uri, '?')) {
		$parts = explode('?', $uri, 2);
		if (1 == count($parts)) {
			$base = '?';
			$query = $parts[0];
		}
		else {
			$base = $parts[0] . '?';
			$query = $parts[1];
		}
	}
	else {
		$base = $uri . '?';
		$query = '';
	}
	parse_str($query, $qs);
	if (is_array(func_get_arg(0))) {
		$kayvees = func_get_arg(0);
		$qs = array_merge($qs, $kayvees);
	}
	else
    {
			$qs[func_get_arg(0)] = func_get_arg(1);
    }

	foreach($qs as $k => $v)
    {
			if($v != '')
        {
					if($ret != '') $ret .= '&';
					$ret .= "$k=$v";
        }
    }
	$ret = $base . $ret;   
	return trim($ret, '?');
}

function remove_query_arg($key, $query) {
	add_query_arg($key, '', $query);
}
?>
