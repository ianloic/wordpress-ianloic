<?php

# fix for mozBlog and other cases where '<?xml' isn't on the very first line
$HTTP_RAW_POST_DATA = trim($HTTP_RAW_POST_DATA);

include('../wp-config.php');
include_once(ABSPATH . WPINC . '/class-IXR.php');

// Turn off all warnings and errors.
// error_reporting(0);

$post_default_title = ""; // posts submitted via the xmlrpc interface get that title
$post_default_category = 1; // posts submitted via the xmlrpc interface go into that category

$xmlrpc_logging = 0;

function logIO($io,$msg) {
	global $xmlrpc_logging;
	if ($xmlrpc_logging) {
		$fp = fopen("../xmlrpc.log","a+");
		$date = gmdate("Y-m-d H:i:s ");
		$iot = ($io == "I") ? " Input: " : " Output: ";
		fwrite($fp, "\n\n".$date.$iot.$msg);
		fclose($fp);
	}
	return true;
	}

function starify($string) {
	$i = strlen($string);
	return str_repeat('*', $i);
}

logIO("I", $HTTP_RAW_POST_DATA);



class wp_xmlrpc_server extends IXR_Server {

	function wp_xmlrpc_server() {
		$this->IXR_Server(array(
		  'blogger.getUsersBlogs' => 'this:blogger_getUsersBlogs',
		  'blogger.getUserInfo' => 'this:blogger_getUserInfo',
		  'blogger.getPost' => 'this:blogger_getPost',
		  'blogger.getRecentPosts' => 'this:blogger_getRecentPosts',
		  'blogger.getTemplate' => 'this:blogger_getTemplate',
		  'blogger.setTemplate' => 'this:blogger_setTemplate',
		  'blogger.newPost' => 'this:blogger_newPost',

		  'demo.sayHello' => 'this:sayHello',
		  'demo.addTwoNumbers' => 'this:addTwoNumbers'
		));
	}

	function sayHello($args) {
		return 'Hello!';
	}

	function addTwoNumbers($args) {
		$number1 = $args[0];
		$number2 = $args[1];
		return $number1 + $number2;
	}

	function login_pass_ok($user_login, $user_pass) {
	  if (!user_pass_ok($user_login, $user_pass)) {
	    $this->error = new IXR_Error(403, 'Bad login/pass combination.');
	    return false;
	  }
	  return true;
	}




	/* Blogger API functions
	 * specs on http://plant.blogger.com/api and http://groups.yahoo.com/group/bloggerDev/
	 */


	/* blogger.getUsersBlogs will make more sense once we support multiple blogs */
	function blogger_getUsersBlogs($args) {

	  $user_login = $args[1];
	  $user_pass  = $args[2];

	  if (!$this->login_pass_ok($user_login, $user_pass)) {
	    return $this->error;
	  }

	  $user_data = get_userdatabylogin($user_login);
	  $is_admin = $user_data->user_level > 3;

	  $struct = array(
	    'isAdmin'  => $is_admin,
	    'url'      => get_settings('home') .'/'.get_settings('blogfilename'),
	    'blogid'   => 1,
	    'blogName' => get_settings('blogname')
	  );

	  return array($struct);
	}


	/* blogger.getUsersInfo gives your client some info about you, so you don't have to */
	function blogger_getUserInfo($args) {

	  $user_login = $args[1];
	  $user_pass  = $args[2];

	  if (!$this->login_pass_ok($user_login, $user_pass)) {
	    return $this->error;
	  }

	  $user_data = get_userdatabylogin($user_login);

	  $struct = array(
	    'nickname'  => $user_data->user_nickname,
	    'userid'    => $user_data->ID,
	    'url'       => $user_data->user_url,
	    'email'     => $user_data->user_email,
	    'lastname'  => $user_data->user_lastname,
	    'firstname' => $user_data->user_firstname
	  );

	  return $struct;
	}


	/* blogger.getPost ...gets a post */
	function blogger_getPost($args) {

	  $post_ID    = $args[1];
	  $user_login = $args[2];
	  $user_pass  = $args[3];

	  if (!$this->login_pass_ok($user_login, $user_pass)) {
	    return $this->error;
	  }

	  $user_data = get_userdatabylogin($user_login);
	  $post_data = get_postdata($post_ID);

	  $post_date = mysql2date('Ymd\TH:i:s', $post_data['Date']);

	  $categories = implode(',', wp_get_post_cats(1, $post_ID));

	  $content  = '<title>'.stripslashes($post_data['Title']).'</title>';
	  $content .= '<category>'.$categories.'</category>';
	  $content .= stripslashes($post_data['Content']);
	  
	  $struct = array(
	    'userid'    => $post_data['Author_ID'],
	    'dateCreateed' => mysql2date('Ymd\TH:i:s', $post_data['Date']),
	    'content'     => $content,
	    'postid'  => $post_data['ID']
	  );
	  
	  return $struct;
	}


	/* blogger.getRecentPosts ...gets recent posts */
	function blogger_getRecentPosts($args) {

	  global $wpdb;

	  $blog_ID    = $args[1]; /* though we don't use it yet */
	  $user_login = $args[2];
	  $user_pass  = $args[3];
	  $num_posts  = $args[4];

	  if ($num_posts > 0) {
	    $limit = " LIMIT $num_posts";
	  } else {
	    $limit = '';
	  }

	  if (!$this->login_pass_ok($user_login, $user_pass)) {
	    return $this->error;
	  }

	  $sql = "SELECT * FROM $wpdb->posts ORDER BY post_date DESC".$limit;
	  $result = $wpdb->get_results($sql);

	  if (!$result) {
	    $this->error = new IXR_Error(500, 'Either there are no posts, or something went wrong.');
	    return $this->error;
	  }

	  $i = 0;
	  foreach ($result as $row) {
	    $post_data = array(
	      'ID' => $row->ID,
	      'Author_ID' => $row->post_author,
	      'Date' => $row->post_date,
	      'Content' => $row->post_content,
	      'Title' => $row->post_title,
	      'Category' => $row->post_category
	    );

	    $categories = implode(',', wp_get_post_cats(1, $post_data['ID']));
	    $post_date = mysql2date("Ymd\TH:i:s", $post_data['Date']);

	    $content  = '<title>'.stripslashes($post_data['Title']).'</title>';
	    $content .= '<category>'.$categories.'</category>';
	    $content .= stripslashes($post_data['Content']);

	    $author_data = get_userdata($post_data['Author_ID']);

	    switch($author_data['user_idmode']) {
	    case 'nickname':
	      $author_name = $author_data['user_nickname'];
	    case 'login':
	      $author_name = $author_data['user_login'];
	      break;
	    case 'firstname':
	      $author_name = $author_data['user_firstname'];
	      break;
	    case 'lastname':
	      $author_name = $author_data['user_lastname'];
	      break;
	    case 'namefl':
	      $author_name = $author_data['user_firstname']." ".$author_data['user_lastname'];
	      break;
	    case 'namelf':
	      $author_name = $author_data['user_lastname']." ".$author_data['user_firstname'];
	      break;
	    default:
	      $author_name = $author_data['user_nickname'];
	      break;
	    }

	    $struct[$i] = array(
	      'authorName' => $author_name,
	      'userid' => $post_data['Author_ID'],
	      'dateCreated' => $post_date,
	      'content' => $content,
	      'postid' => $post_data['ID'],
	      'category' => $categories
	    );

	    $i++;
	  }

	  $recent_posts = array();
	  for ($j=0; $j<count($struct); $j++) {
	    array_push($recent_posts, $struct[$j]);
	  }

	  return $recent_posts;
	}


	/* blogger.getTemplate returns your blog_filename */
	function blogger_getTemplate($args) {

	  $blog_ID    = $args[1];
	  $user_login = $args[2];
	  $user_pass  = $args[3];
	  $template   = $args[4]; /* could be 'main' or 'archiveIndex', but we don't use it */

	  if (!$this->login_pass_ok($user_login, $user_pass)) {
	    return $this->error;
	  }

	  $user_data = get_userdatabylogin($user_login);

	  if ($user_data->user_level < 3) {
	    return new IXR_Error(401, 'Sorry, users whose level is less than 3, can not edit the template.');
	  }

	  /* warning: here we make the assumption that the weblog's URI is on the same server */
	  $filename = get_settings('home').'/'.get_settings('blogfilename');
	  $filename = preg_replace('#http://.+?/#', $_SERVER['DOCUMENT_ROOT'].'/', $filename);
	  
	  $f = fopen($filename, 'r');
	  $content = fread($f, filesize($filename));
	  fclose($f);

	  /* so it is actually editable with a windows/mac client */
	  $content = str_replace("\n", "\r\n", $content); 

	  return $content;
	}


	/* blogger.setTemplate updates the content of blog_filename */
	function blogger_setTemplate($args) {

	  $blog_ID    = $args[1];
	  $user_login = $args[2];
	  $user_pass  = $args[3];
	  $content    = $args[4];
	  $template   = $args[5]; /* could be 'main' or 'archiveIndex', but we don't use it */

	  if (!$this->login_pass_ok($user_login, $user_pass)) {
	    return $this->error;
	  }

	  $user_data = get_userdatabylogin($user_login);

	  if ($user_data->user_level < 3) {
	    return new IXR_Error(401, 'Sorry, users whose level is less than 3, can not edit the template.');
	  }

	  /* warning: here we make the assumption that the weblog's URI is on the same server */
	  $filename = get_settings('home').'/'.get_settings('blogfilename');
	  $filename = preg_replace('#http://.+?/#', $_SERVER['DOCUMENT_ROOT'].'/', $filename);
	  
	  if ($f = fopen($filename, 'w+')) {
	    fwrite($f, $content);
	    fclose($f);
	  } else {
	    return new IXR_Error(500, 'Either the file is not writable, or something wrong happened. The file has not been updated.');
	  }

	  return true;
	}


	/* blogger.newPost ...creates a new post */
	function blogger_newPost($args) {

	  global $wpdb;

	  $blog_ID    = $args[1]; /* though we don't use it yet */
	  $user_login = $args[2];
	  $user_pass  = $args[3];
	  $content    = $args[4];
	  $publish    = $args[5];

	  if (!$this->login_pass_ok($user_login, $user_pass)) {
	    return $this->error;
	  }

	  $user_data = get_userdatabylogin($user_login);
	  if ($user_data->user_level < 1) {
	    return new IXR_Error(401, 'Sorry, level 0 users can not post');
	  }

	  $post_status = ($publish) ? 'publish' : 'draft';

	  $post_author = $user_data->ID;

	  $post_title = addslashes(xmlrpc_getposttitle($content));
	  $post_category = xmlrpc_getpostcategory($content);

	  $content = xmlrpc_removepostdata($content);
	  $post_content = format_to_post($content);

	  $post_date = current_time('mysql');
	  $post_date_gmt = current_time('mysql', 1);

	  $post_data = compact('blog_ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_category', 'post_status');

	  $post_ID = wp_insert_post($post_data);

	  if (!$post_ID) {
	    return new IXR_Error(500, 'Sorry, your entry could not be posted. Something wrong happened.');
	  }

	  logIO('O', "Posted ! ID: $post_ID");

	  return $post_ID;
	}
}

$wp_xmlrpc_server = new wp_xmlrpc_server();

?>