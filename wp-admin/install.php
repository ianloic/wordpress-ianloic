<?php
$_wp_installing = 1;
if (!file_exists('../wp-config.php')) 
    die("There doesn't seem to be a <code>wp-config.php</code> file. I need this before we can get started. Need more help? <a href='http://wordpress.org/docs/faq/#wp-config'>We got it</a>. You can <a href='wp-admin/setup-config.php'>create a <code>wp-config.php</code> file through a web interface</a>, but this doesn't work for all server setups. The safest way is to manually create the file.");

require_once('../wp-config.php');
require('./upgrade-functions.php');

$guessurl = str_replace('/wp-admin/install.php?step=2', '', 'http://' . $HTTP_HOST . $REQUEST_URI);

if (isset($_GET['step']))
	$step = $_GET['step'];
else
	$step = 0;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>WordPress &rsaquo; Installation</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style media="screen" type="text/css">
	<!--
	html {
		background: #eee;
	}
	body {
		background: #fff;
		color: #000;
		font-family: Georgia, "Times New Roman", Times, serif;
		margin-left: 20%;
		margin-right: 20%;
		padding: .2em 2em;
	}
	
	h1 {
		color: #006;
		font-size: 18px;
		font-weight: lighter;
	}
	
	h2 {
		font-size: 16px;
	}
	
	p, li, dt {
		line-height: 140%;
		padding-bottom: 2px;
	}

	ul, ol {
		padding: 5px 5px 5px 20px;
	}
	#logo {
		margin-bottom: 2em;
	}
.step a, .step input {
	font-size: 2em;
}
.step, th {
	text-align: right;
}
#footer {
text-align: center; border-top: 1px solid #ccc; padding-top: 1em; font-style: italic;
}
	-->
	</style>
</head>
<body>
<h1 id="logo"><img alt="WordPress" src="http://static.wordpress.org/logo.png" /></h1>
<?php
// Let's check to make sure WP isn't already installed.
$wpdb->hide_errors();
$installed = $wpdb->get_results("SELECT * FROM $wpdb->users");
if ($installed) die(__('<h1>Already Installed</h1><p>You appear to have already installed WordPress. To reinstall please clear your old database tables first.</p></body></html>'));
$wpdb->show_errors();

switch($step) {

	case 0:
?>
<p>Welcome to WordPress installation. We&#8217;re now going to go through a few steps to get you up and running with the latest in personal publishing platforms. You may want to peruse the <a href="../readme.html">ReadMe documentation</a> at your leisure.</p>
<h2 class="step"><a href="install.php?step=1">First Step &raquo;</a></h2>
<?php
	break;

	case 1:
?>
<h1>First Step</h1>
<p>Before we begin we need a little bit of information. Don't worry, you can always change these later. </p>

<form name="setup" id="setup" method="post" action="install.php?step=2">
<table width="100%">
<tr>
<th width="33%">Weblog title:</th>
<td><input name="weblog_title" type="text" id="weblog_title" size="25" /></td>
</tr>
<tr>
	<th>Your e-mail:</th>
	<td><input name="admin_email" type="text" id="admin_email" size="25" /></td>
</tr>
</table>
<p><em>Double-check that email address before continuing.</em></p>
<h2 class="step">
	<input type="submit" name="Submit" value="Continue to Second Step &raquo;" />
</h2>
</form>

<?php
	break;
	case 2:
?>
<h1>Second Step</h1>
<p>Now we&#8217;re going to create the database tables and fill them with some default data.</p>


<?php
flush();

// Set everything up
make_db_current_silent();
populate_options();

// Fill in the data we gathered
$weblog_title = addslashes(stripslashes(stripslashes($_POST['weblog_title'])));
$admin_email = addslashes(stripslashes(stripslashes($_POST['admin_email'])));

$wpdb->query("UPDATE $wpdb->options SET option_value = '$weblog_title' WHERE option_name = 'blogname'");
$wpdb->query("UPDATE $wpdb->options SET option_value = '$admin_email' WHERE option_name = 'admin_email'");

// Now drop in some default links
$wpdb->query("INSERT INTO $wpdb->linkcategories (cat_id, cat_name) VALUES (1, 'Blogroll')");
$wpdb->query("INSERT INTO $wpdb->links (link_url, link_name, link_category) VALUES ('http://blog.carthik.net/index.php', 'Carthik', 1);");
$wpdb->query("INSERT INTO $wpdb->links (link_url, link_name, link_category) VALUES ('http://blogs.linux.ie/xeer/', 'Donncha', 1);");
$wpdb->query("INSERT INTO $wpdb->links (link_url, link_name, link_category) VALUES ('http://zengun.org/weblog/', 'Michel', 1);");
$wpdb->query("INSERT INTO $wpdb->links (link_url, link_name, link_category) VALUES ('http://boren.nu/', 'Ryan', 1);");
$wpdb->query("INSERT INTO $wpdb->links (link_url, link_name, link_category) VALUES ('http://photomatt.net/', 'Matt', 1);");
$wpdb->query("INSERT INTO $wpdb->links (link_url, link_name, link_category) VALUES ('http://zed1.com/journalized/', 'Mike', 1);");
$wpdb->query("INSERT INTO $wpdb->links (link_url, link_name, link_category) VALUES ('http://www.alexking.org/', 'Alex', 1);");
$wpdb->query("INSERT INTO $wpdb->links (link_url, link_name, link_category) VALUES ('http://dougal.gunters.org/', 'Dougal', 1);");

// Default category
$wpdb->query("INSERT INTO $wpdb->categories (cat_ID, cat_name) VALUES ('0', 'Uncategorized')");

// First post
$now = date('Y-m-d H:i:s');
$now_gmt = gmdate('Y-m-d H:i:s');
$wpdb->query("INSERT INTO $wpdb->posts (post_author, post_date, post_date_gmt, post_content, post_title, post_category, post_modified, post_modified_gmt) VALUES ('1', '$now', '$now_gmt', 'Welcome to WordPress. This is your first post. Edit or delete it, then start blogging!', 'Hello world!', '0', '$now', '$now_gmt')");

$wpdb->query( "INSERT INTO $wpdb->post2cat (`rel_id`, `post_id`, `category_id`) VALUES (1, 1, 0)" );

// Default comment
$wpdb->query("INSERT INTO $wpdb->comments (comment_post_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_date_gmt, comment_content) VALUES ('1', 'Mr WordPress', 'mr@wordpress.org', 'http://wordpress.org', '127.0.0.1', '$now', '$now_gmt', 'Hi, this is a comment.<br />To delete a comment, just log in, and view the posts\' comments, there you will have the option to edit or delete them.')");

// Set up admin user
$random_password = substr(md5(uniqid(microtime())), 0, 6);
$wpdb->query("INSERT INTO $wpdb->users (ID, user_login, user_pass, user_nickname, user_email, user_level, user_idmode) VALUES ( '1', 'admin', MD5('$random_password'), 'Administrator', '$admin_email', '10', 'nickname')");

$from = 'From: '.$_POST['weblog_title'].' <wordpress@'.$_SERVER['SERVER_NAME'].'>';
$message_headers = "$from";

mail($admin_email, 'New WordPress Blog', "Your new WordPress blog has been successfully set up at:

$guessurl

You can log in to the administrator account with the following information:

Username: admin
Password: $random_password

We hope you enjoy your new weblog. Thanks!

--The WordPress Team
http://wordpress.org/
", $message_headers);

upgrade_all();
?>

<p><em>Finished!</em></p>

<p>Now you can <a href="../wp-login.php">log in</a> with the <strong>login</strong>
  "<code>admin</code>" and <strong>password</strong> "<code><?php echo $random_password; ?></code>".</p>
<p><strong><em>Note that password</em></strong> carefully! It is a <em>random</em>
  password that was generated just for you. If you lose it, you
  will have to delete the tables from the database yourself, and re-install WordPress. So to review:
</p>
<dl>
<dt>Login</dt>
<dd><code>admin</code></dd>
<dt>Password</dt>
<dd><code><?php echo $random_password; ?></code></dd>
<dt>Login address</dt>
<dd><a href="../wp-login.php">wp-login.php</a></dd>
</dl>
<p>Were you expecting more steps? Sorry to disappoint. All done! :)</p>
<?php
	break;
}
?>
<p id="footer"><a href="http://wordpress.org/">WordPress</a>, personal publishing platform.</p>
</body>
</html>
