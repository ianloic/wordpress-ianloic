<?php
// Functions to be called in install and upgrade scripts

// First let's set up the tables:

$wp_queries="CREATE TABLE $wpdb->categories (
  cat_ID int(4) NOT NULL auto_increment,
  cat_name varchar(55) NOT NULL default '',
  category_nicename varchar(200) NOT NULL default '',
  category_description text NOT NULL,
  category_parent int(4) NOT NULL default '0',
  PRIMARY KEY  (cat_ID),
  UNIQUE KEY cat_name (cat_name),
  KEY category_nicename (category_nicename)
);
CREATE TABLE $wpdb->comments (
  comment_ID int(11) unsigned NOT NULL auto_increment,
  comment_post_ID int(11) NOT NULL default '0',
  comment_author tinytext NOT NULL,
  comment_author_email varchar(100) NOT NULL default '',
  comment_author_url varchar(200) NOT NULL default '',
  comment_author_IP varchar(100) NOT NULL default '',
  comment_date datetime NOT NULL default '0000-00-00 00:00:00',
  comment_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  comment_content text NOT NULL,
  comment_karma int(11) NOT NULL default '0',
  comment_approved enum('0','1') NOT NULL default '1',
  user_id int(11) NOT NULL default '0',
  PRIMARY KEY  (comment_ID),
  KEY comment_approved (comment_approved),
  KEY comment_post_ID (comment_post_ID)
);
CREATE TABLE $wpdb->linkcategories (
  cat_id int(11) NOT NULL auto_increment,
  cat_name tinytext NOT NULL,
  auto_toggle enum('Y','N') NOT NULL default 'N',
  show_images enum('Y','N') NOT NULL default 'Y',
  show_description enum('Y','N') NOT NULL default 'N',
  show_rating enum('Y','N') NOT NULL default 'Y',
  show_updated enum('Y','N') NOT NULL default 'Y',
  sort_order varchar(64) NOT NULL default 'name',
  sort_desc enum('Y','N') NOT NULL default 'N',
  text_before_link varchar(128) NOT NULL default '<li>',
  text_after_link varchar(128) NOT NULL default '<br />',
  text_after_all varchar(128) NOT NULL default '</li>',
  list_limit int(11) NOT NULL default '-1',
  PRIMARY KEY  (cat_id)
);
CREATE TABLE $wpdb->links (
  link_id int(11) NOT NULL auto_increment,
  link_url varchar(255) NOT NULL default '',
  link_name varchar(255) NOT NULL default '',
  link_image varchar(255) NOT NULL default '',
  link_target varchar(25) NOT NULL default '',
  link_category int(11) NOT NULL default '0',
  link_description varchar(255) NOT NULL default '',
  link_visible enum('Y','N') NOT NULL default 'Y',
  link_owner int(11) NOT NULL default '1',
  link_rating int(11) NOT NULL default '0',
  link_updated datetime NOT NULL default '0000-00-00 00:00:00',
  link_rel varchar(255) NOT NULL default '',
  link_notes mediumtext NOT NULL,
  link_rss varchar(255) NOT NULL default '',
  PRIMARY KEY  (link_id),
  KEY link_category (link_category),
  KEY link_visible (link_visible)
);
CREATE TABLE $wpdb->options (
  option_id int(11) NOT NULL auto_increment,
  blog_id int(11) NOT NULL default '0',
  option_name varchar(64) NOT NULL default '',
  option_can_override enum('Y','N') NOT NULL default 'Y',
  option_type int(11) NOT NULL default '1',
  option_value text NOT NULL,
  option_width int(11) NOT NULL default '20',
  option_height int(11) NOT NULL default '8',
  option_description tinytext NOT NULL,
  option_admin_level int(11) NOT NULL default '1',
  autoload enum('yes','no') NOT NULL default 'yes',
  PRIMARY KEY  (option_id,blog_id,option_name)
);
CREATE TABLE $wpdb->post2cat (
  rel_id int(11) NOT NULL auto_increment,
  post_id int(11) NOT NULL default '0',
  category_id int(11) NOT NULL default '0',
  PRIMARY KEY  (rel_id),
  KEY post_id (post_id,category_id)
);
CREATE TABLE $wpdb->postmeta (
  meta_id int(11) NOT NULL auto_increment,
  post_id int(11) NOT NULL default '0',
  meta_key varchar(255) default NULL,
  meta_value text,
  PRIMARY KEY  (meta_id),
  KEY post_id (post_id),
  KEY meta_key (meta_key)
);
CREATE TABLE $wpdb->posts (
  ID int(10) unsigned NOT NULL auto_increment,
  post_author int(4) NOT NULL default '0',
  post_date datetime NOT NULL default '0000-00-00 00:00:00',
  post_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  post_content text NOT NULL,
  post_title text NOT NULL,
  post_category int(4) NOT NULL default '0',
  post_excerpt text NOT NULL,
  post_lat float default NULL,
  post_lon float default NULL,
  post_status enum('publish','draft','private','static') NOT NULL default 'publish',
  comment_status enum('open','closed','registered_only') NOT NULL default 'open',
  ping_status enum('open','closed') NOT NULL default 'open',
  post_password varchar(20) NOT NULL default '',
  post_name varchar(200) NOT NULL default '',
  to_ping text NOT NULL,
  pinged text NOT NULL,
  post_modified datetime NOT NULL default '0000-00-00 00:00:00',
  post_modified_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  post_content_filtered text NOT NULL,
  post_parent int(11) NOT NULL default '0',
  PRIMARY KEY  (ID),
  KEY post_date (post_date),
  KEY post_date_gmt (post_date_gmt),
  KEY post_name (post_name),
  KEY post_status (post_status)
);
CREATE TABLE $wpdb->users (
  ID int(10) unsigned NOT NULL auto_increment,
  user_login varchar(20) NOT NULL default '',
  user_pass varchar(64) NOT NULL default '',
  user_firstname varchar(50) NOT NULL default '',
  user_lastname varchar(50) NOT NULL default '',
  user_nickname varchar(50) NOT NULL default '',
  user_nicename varchar(50) NOT NULL default '',
  user_icq int(10) unsigned NOT NULL default '0',
  user_email varchar(100) NOT NULL default '',
  user_url varchar(100) NOT NULL default '',
  user_ip varchar(15) NOT NULL default '',
  user_domain varchar(200) NOT NULL default '',
  user_browser varchar(200) NOT NULL default '',
  dateYMDhour datetime NOT NULL default '0000-00-00 00:00:00',
  user_level int(2) unsigned NOT NULL default '0',
  user_aim varchar(50) NOT NULL default '',
  user_msn varchar(100) NOT NULL default '',
  user_yim varchar(50) NOT NULL default '',
  user_idmode varchar(20) NOT NULL default '',
  user_activation_key varchar(60) NOT NULL default '',
  user_status int(11) NOT NULL default '0',
  PRIMARY KEY  (ID),
  UNIQUE KEY user_login (user_login)
);";



function upgrade_all() {
	upgrade_071();
	upgrade_072();
	upgrade_100();
	upgrade_101();
	upgrade_110();
	upgrade_130();

	// Options that should not exist
	$obs_options = array('');
}


// .71 stuff

function upgrade_071() {
	global $wpdb;
	maybe_add_column($wpdb->posts, 'post_status', "ALTER TABLE $wpdb->posts ADD `post_status` ENUM('publish','draft','private') NOT NULL");
	maybe_add_column($wpdb->posts, 'comment_status', "ALTER TABLE $wpdb->posts ADD `comment_status` ENUM('open','closed') NOT NULL");
	maybe_add_column($wpdb->posts, 'ping_status', "ALTER TABLE $wpdb->posts ADD `ping_status` ENUM('open','closed') NOT NULL");
	maybe_add_column($wpdb->posts, 'post_password', "ALTER TABLE $wpdb->posts ADD post_password varchar(20) NOT NULL");
}

function upgrade_072() {
	global $wpdb;
	maybe_add_column($wpdb->links, 'link_notes', "ALTER TABLE $wpdb->links ADD COLUMN link_notes MEDIUMTEXT NOT NULL DEFAULT '' ");
	maybe_add_column($wpdb->linkcategories, 'show_images', "ALTER TABLE $wpdb->linkcategories ADD COLUMN show_images enum('Y','N') NOT NULL default 'Y'");
	maybe_add_column($wpdb->linkcategories, 'show_description', "ALTER TABLE $wpdb->linkcategories ADD COLUMN show_description enum('Y','N') NOT NULL default 'Y'");
	maybe_add_column($wpdb->linkcategories, 'show_rating', "ALTER TABLE $wpdb->linkcategories ADD COLUMN show_rating enum('Y','N') NOT NULL default 'Y'");
	maybe_add_column($wpdb->linkcategories, 'show_updated', "ALTER TABLE $wpdb->linkcategories ADD COLUMN show_updated enum('Y','N') NOT NULL default 'Y'");
	maybe_add_column($wpdb->linkcategories, 'sort_order', "ALTER TABLE $wpdb->linkcategories ADD COLUMN sort_order varchar(64) NOT NULL default 'name'");
	maybe_add_column($wpdb->linkcategories, 'sort_desc', "ALTER TABLE $wpdb->linkcategories ADD COLUMN sort_desc enum('Y','N') NOT NULL default 'N'");
	maybe_add_column($wpdb->linkcategories, 'text_before_link', "ALTER TABLE $wpdb->linkcategories ADD COLUMN text_before_link varchar(128) not null default '<li>'");
	maybe_add_column($wpdb->linkcategories, 'text_after_link', "ALTER TABLE $wpdb->linkcategories ADD COLUMN text_after_link  varchar(128) not null default '<br />'");
	maybe_add_column($wpdb->linkcategories, 'text_after_all', "ALTER TABLE $wpdb->linkcategories ADD COLUMN text_after_all  varchar(128) not null default '</li>'");
	maybe_add_column($wpdb->linkcategories, 'list_limit', "ALTER TABLE $wpdb->linkcategories ADD COLUMN list_limit int not null default -1");
	maybe_add_column($wpdb->posts, 'post_lon', "ALTER TABLE $wpdb->posts ADD COLUMN post_lon float");
	maybe_add_column($wpdb->posts, 'post_lat', "ALTER TABLE $wpdb->posts ADD COLUMN post_lat float ");
	maybe_create_table($wpdb->options, "
	CREATE TABLE $wpdb->options (
	  option_id int(11) NOT NULL auto_increment,
	  blog_id int(11) NOT NULL default 0,
	  option_name varchar(64) NOT NULL default '',
	  option_can_override enum('Y','N') NOT NULL default 'Y',
	  option_type int(11) NOT NULL default 1,
	  option_value varchar(255) NOT NULL default '',
	  option_width int NOT NULL default 20,
	  option_height int NOT NULL default 8,
	  option_description tinytext NOT NULL default '',
	  option_admin_level int NOT NULL DEFAULT '1',
	  PRIMARY KEY (option_id, blog_id, option_name)
	)
	");

	// Guess a site URI
$guessurl = preg_replace('|/wp-admin/.*|i', '', 'http://' . $HTTP_HOST . $REQUEST_URI);
	$option_data = array(		//base options from b2cofig
		"1" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (1,'siteurl', 3, '$guessurl', 'siteurl is your blog\'s URL: for example, \'http://example.com/wordpress\'', 8, 30)",
		"2" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (2,'blogfilename', 3, 'index.php', 'blogfilename is the name of the default file for your blog', 8, 20)",
		"3" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (3,'blogname', 3, 'my weblog', 'blogname is the name of your blog', 8, 20)",
		"4" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (4,'blogdescription', 3, 'babblings!', 'blogdescription is the description of your blog', 8, 40)",
		//"INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (6,'search_engine_friendly_urls', 2, '0', 'Querystring Configuration ** (don\'t change if you don\'t know what you\'re doing)', 8, 20)",
		"7" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (7,'new_users_can_blog', 2, '0', 'whether you want new users to be able to post entries once they have registered', 8, 20)",
		"8" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (8,'users_can_register', 2, '1', 'whether you want to allow users to register on your blog', 8, 20)",
		"54" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (54,'admin_email', 3, 'you@example.com', 'Your email (obvious eh?)', 8, 20)",
		// general blog setup
		"9" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (9 ,'start_of_week', 5, '1', 'day at the start of the week', 8, 20)",
		//"INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (10,'use_preview', 2, '1', 'Do you want to use the \'preview\' function', 8, 20)",
		"14" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (14,'use_htmltrans', 2, '1', 'IMPORTANT! set this to false if you are using Chinese, Japanese, Korean, or other double-bytes languages', 8, 20)",
		"15" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (15,'use_balanceTags', 2, '1', 'this could help balance your HTML code. if it gives bad results, set it to false', 8, 20)",
		"16" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (16,'use_smilies', 2, '1', 'set this to true to enable smiley conversion in posts (note: this makes smiley conversion in ALL posts)', 8, 20)",
		"17" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (17,'smilies_directory', 3, 'http://example.com/wp-images/smilies', 'the directory where your smilies are (no trailing slash)', 8, 40)",
		"18" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (18,'require_name_email', 2, '0', 'set this to true to require e-mail and name, or false to allow comments without e-mail/name', 8, 20)",
		"20" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (20,'comments_notify', 2, '1', 'set this to true to let every author be notified about comments on their posts', 8, 20)",
		//rss/rdf feeds
		"21" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (21,'posts_per_rss', 1, '10', 'number of last posts to syndicate', 8, 20)",
		"22" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (22,'rss_language', 3, 'en', 'the language of your blog ( see this: http://backend.userland.com/stories/storyReader$16 )', 8, 20)",
		"23" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (23,'rss_encoded_html', 2, '0', 'for b2rss.php: allow encoded HTML in &lt;description> tag?', 8, 20)",
		"24" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (24,'rss_excerpt_length', 1, '50', 'length (in words) of excerpts in the RSS feed? 0=unlimited note: in b2rss.php, this will be set to 0 if you use encoded HTML', 8, 20)",
		"25" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (25,'rss_use_excerpt', 2, '1', 'use the excerpt field for rss feed.', 8, 20)",
		"29" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (29,'use_trackback', 2, '1', 'set this to false or true, whether you want to allow your posts to be trackback\'able or not note: setting it to false would also disable sending trackbacks', 8, 20)",
		"30" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (30,'use_pingback', 2, '1', 'set this to false or true, whether you want to allow your posts to be pingback\'able or not note: setting it to false would also disable sending pingbacks', 8, 20)",
		//file upload
		"31" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (31,'use_fileupload', 2, '0', 'set this to false to disable file upload, or true to enable it', 8, 20)",
		"32" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (32,'fileupload_realpath', 3, '/home/your/site/wordpress/images', 'enter the real path of the directory where you\'ll upload the pictures \nif you\'re unsure about what your real path is, please ask your host\'s support staff \nnote that the  directory must be writable by the webserver (chmod 766) \nnote for windows-servers users: use forwardslashes instead of backslashes', 8, 40)",
		"33" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (33,'fileupload_url', 3, 'http://example.com/images', 'enter the URL of that directory (it\'s used to generate the links to the uploded files)', 8, 40)",
		"34" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (34,'fileupload_allowedtypes', 3, ' jpg gif png ', 'accepted file types, separated by spaces. example: \'jpg gif png\'', 8, 20)",
		"35" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (35,'fileupload_maxk', 1, '96', 'by default, most servers limit the size of uploads to 2048 KB, if you want to set it to a lower value, here it is (you cannot set a higher value than your server limit)', 8, 20)",
		"36" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (36,'fileupload_minlevel', 1, '1', 'you may not want all users to upload pictures/files, so you can set a minimum level for this', 8, 20)",
		"37" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (37,'fileupload_allowedusers', 3, '', '...or you may authorize only some users. enter their logins here, separated by spaces. if you leave this variable blank, all users who have the minimum level are authorized to upload. example: \'barbara anne george\'', 8, 30)",
		// email settings
		"38" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (38,'mailserver_url', 3, 'mail.example.com', 'mailserver settings', 8, 20)",
		"39" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (39,'mailserver_login', 3, 'login@example.com', 'mailserver settings', 8, 20)",
		"40" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (40,'mailserver_pass', 3, 'password', 'mailserver settings', 8, 20)",
		"41" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (41,'mailserver_port', 1, '110', 'mailserver settings', 8, 20)",
		"42" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (42,'default_category', 1, '1', 'by default posts will have this category', 8, 20)",
		"46" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (46,'use_phoneemail', 2, '0', 'some mobile phone email services will send identical subject & content on the same line if you use such a service, set use_phoneemail to true, and indicate a separator string', 8, 20)",
		
		// default post stuff
		
		"55" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES(55,'default_post_status',    5, 'publish', 'The default state of each new post', 8, 20)",
		"56" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES(56,'default_comment_status', 5, 'open', 'The default state of comments for each new post', 8, 20)",
		"57" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES(57,'default_ping_status',    5, 'open', 'The default ping state for each new post', 8, 20)",
		"58" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES(58,'default_pingback_flag',  5, '1', 'Whether the \'PingBack the URLs in this post\' checkbox should be checked by default', 8, 20)",
		"59" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES(59,'default_post_category',  7, '1', 'The default category for each new post', 8, 20)",
		"83" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES(83,'default_post_edit_rows', 1, '9', 'The number of rows in the edit post form (min 3, max 100)', 8, 5)",

		// original options from options page
		"48" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (48,'posts_per_page', 1, '20','How many posts/days to show on the index page.', 4, 20)",
		"49" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (49,'what_to_show', 5, 'posts','Posts or days', 4, 20)",
		"50" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (50,'archive_mode', 5, 'monthly','Which \'unit\' to use for archives.', 4, 20)",
		"51" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (51,'time_difference', 6, '0', 'if you\'re not on the timezone of your server', 4, 20)",
		"52" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (52,'date_format', 3, 'n/j/Y', 'see note for format characters', 4, 20)",
		"53" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (53,'time_format', 3, 'g:i a', 'see note for format characters', 4, 20)",		"INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (84,'use_geo_positions', 2, '0', 'Turns on the geo url features of WordPress', 8, 20)",
		"85" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (85,'use_default_geourl', 2, '1','enables placement of default GeoURL ICBM location even when no other specified', 8, 20)",
		"86" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (86,'default_geourl_lat ', 8, 0.0, 'The default Latitude ICBM value - <a href=\"http://www.geourl.org/resources.html\" target=\"_blank\">see here</a>', 8, 20)",
		"87" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (87,'default_geourl_lon', 8, 0.0, 'The default Longitude ICBM value', 8, 20)",
		"60" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (60,'links_minadminlevel',             1, '5', 'The minimum admin level to edit links', 8, 10)",
		"61" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (61,'links_use_adminlevels',           2, '1', 'set this to false to have all links visible and editable to everyone in the link manager', 8, 20)",
		"62" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (62,'links_rating_type',               5, 'image', 'Set this to the type of rating indication you wish to use', 8, 10)",
		"63" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (63,'links_rating_char',               3, '*', 'If we are set to \'char\' which char to use.', 8, 5)",
		"64" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (64,'links_rating_ignore_zero',        2, '1', 'What do we do with a value of zero? set this to true to output nothing, 0 to output as normal (number/image)', 8, 20)",
		"65" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (65,'links_rating_single_image',       2, '1', 'Use the same image for each rating point? (Uses links_rating_image[0])', 8, 20)",
		"66" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (66,'links_rating_image0',             3, 'wp-links/links-images/tick.png', 'Image for rating 0 (and for single image)', 8, 40)",
		"67" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (67,'links_rating_image1',             3, 'wp-links/links-images/rating-1.gif', 'Image for rating 1', 8, 40)",
		"68" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (68,'links_rating_image2',             3, 'wp-links/links-images/rating-2.gif', 'Image for rating 2', 8, 40)",
		"69" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (69,'links_rating_image3',             3, 'wp-links/links-images/rating-3.gif', 'Image for rating 3', 8, 40)",
		"70" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (70,'links_rating_image4',             3, 'wp-links/links-images/rating-4.gif', 'Image for rating 4', 8, 40)",
		"71" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (71,'links_rating_image5',             3, 'wp-links/links-images/rating-5.gif', 'Image for rating 5', 8, 40)",
		"72" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (72,'links_rating_image6',             3, 'wp-links/links-images/rating-6.gif', 'Image for rating 6', 8, 40)",
		"73" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (73,'links_rating_image7',             3, 'wp-links/links-images/rating-7.gif', 'Image for rating 7', 8, 40)",
		"74" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (74,'links_rating_image8',             3, 'wp-links/links-images/rating-8.gif', 'Image for rating 8', 8, 40)",
		"75" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (75,'links_rating_image9',             3, 'wp-links/links-images/rating-9.gif', 'Image for rating 9', 8, 40)",
		"77" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (77,'weblogs_xml_url',                 3, 'http://www.weblogs.com/changes.xml', 'Which file to grab from weblogs.com', 8, 40)",
		"78" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (78,'weblogs_cacheminutes',            1, '60', 'cache time in minutes (if it is older than this get a new copy)', 8, 10)",
		"79" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (79,'links_updated_date_format',       3, 'd/m/Y h:i', 'The date format for the updated tooltip', 8, 25)",
		"80" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (80,'links_recently_updated_prepend',  3, '&gt;&gt;', 'The text to prepend to a recently updated link', 8, 10)",
		"81" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (81,'links_recently_updated_append',   3, '&lt;&lt;', 'The text to append to a recently updated link', 8, 20)",
		"82" => "INSERT INTO $wpdb->options (option_id, option_name, option_type, option_value, option_description, option_admin_level, option_width) VALUES (82,'links_recently_updated_time',     1, '120', 'The time in minutes to consider a link recently updated', 8, 20)"
		);

	foreach ($option_data as $option_id => $query) {
		if(!$wpdb->get_var("SELECT * FROM $wpdb->options WHERE option_id = '$option_id'")) {
			$wpdb->query($query);
		}
	}

	    if (file_exists('../wp-links/links.config.php')) {
        include('../wp-links/links.config.php');
    
        // now update the database with those settings
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_minadminlevel           )."' WHERE option_id=60"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_use_adminlevels         )."' WHERE option_id=61"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_rating_type             )."' WHERE option_id=62"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_rating_char             )."' WHERE option_id=63"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_rating_ignore_zero      )."' WHERE option_id=64"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_rating_single_image     )."' WHERE option_id=65"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_rating_image0           )."' WHERE option_id=66"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_rating_image1           )."' WHERE option_id=67"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_rating_image2           )."' WHERE option_id=68"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_rating_image3           )."' WHERE option_id=69"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_rating_image4           )."' WHERE option_id=70"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_rating_image5           )."' WHERE option_id=71"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_rating_image6           )."' WHERE option_id=72"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_rating_image7           )."' WHERE option_id=73"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_rating_image8           )."' WHERE option_id=74"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_rating_image9           )."' WHERE option_id=75"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($weblogs_cache_file            )."' WHERE option_id=76"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($weblogs_xml_url               )."' WHERE option_id=77"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($weblogs_cacheminutes          )."' WHERE option_id=78"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_updated_date_format     )."' WHERE option_id=79"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_recently_updated_prepend)."' WHERE option_id=80"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_recently_updated_append )."' WHERE option_id=81"; $q = $wpdb->query($query);
        $query = "UPDATE $wpdb->options SET option_value='".addslashes($links_recently_updated_time   )."' WHERE option_id=82"; $q = $wpdb->query($query);
    // end if links.config.php exists
    }

}

function upgrade_100() {
	global $wpdb;
	maybe_add_column($wpdb->posts, 'post_name', "ALTER TABLE `$wpdb->posts` ADD `post_name` VARCHAR(200) NOT NULL");
	maybe_add_column($wpdb->posts, 'to_ping', "ALTER TABLE $wpdb->posts ADD `to_ping` TEXT NOT NULL");
	maybe_add_column($wpdb->posts, 'pinged', "ALTER TABLE $wpdb->posts ADD `pinged` TEXT NOT NULL");
	maybe_add_column($wpdb->posts, 'post_modified', "ALTER TABLE $wpdb->posts ADD `post_modified` DATETIME NOT NULL");
	maybe_add_column($wpdb->posts, 'post_content_filtered', "ALTER TABLE $wpdb->posts ADD `post_content_filtered` TEXT NOT NULL");
	maybe_add_column($wpdb->categories, 'category_nicename', "ALTER TABLE `$wpdb->categories` ADD `category_nicename` VARCHAR(200) NOT NULL");	
	maybe_add_column($wpdb->categories, 'category_description', "ALTER TABLE `$wpdb->categories` ADD `category_description` TEXT NOT NULL");
	maybe_add_column($wpdb->categories, 'category_parent', "ALTER TABLE `$wpdb->categories` ADD `category_parent` INT(4) NOT NULL");
	maybe_add_column($wpdb->links, 'link_rss', "ALTER TABLE `$wpdb->links` ADD `link_rss` VARCHAR( 255 ) NOT NULL;");
	maybe_add_column($wpdb->users, 'user_description', "ALTER TABLE `$wpdb->users` ADD `user_description` TEXT NOT NULL");
	maybe_add_column($wpdb->comments, 'comment_approved', "ALTER TABLE $wpdb->comments ADD COLUMN comment_approved ENUM('0', '1') DEFAULT '1' NOT NULL");

	// Create indicies
	add_clean_index($wpdb->posts, 'post_name');
	add_clean_index($wpdb->categories, 'category_nicename');
	add_clean_index($wpdb->comments, 'comment_approved');


	// Options stuff
	if (!$wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = 'comment_moderation'")) {
		$wpdb->query("INSERT INTO $wpdb->options
			(option_id, blog_id, option_name, option_can_override, option_type, option_value, option_width, option_height, option_description, option_admin_level)
			VALUES 
			('0', '0', 'comment_moderation', 'Y', '5',' none', 20, 8, 'If enabled, comments will only be shown after they have been approved.', 8)");
	}

	if (!$wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = 'moderation_notify'")) {
		$wpdb->query("INSERT INTO $wpdb->options 
			(option_id, blog_id, option_name, option_can_override, option_type, option_value, option_width, option_height, option_description, option_admin_level) 
			VALUES 
			('0', '0', 'moderation_notify' , 'Y', '2', '1', 20, 8, 'Set this to true if you want to be notified about new comments that wait for approval', 8)");
	}
	
	// Get the title and ID of every post, post_name to check if it already has a value
	$posts = $wpdb->get_results("SELECT ID, post_title, post_name FROM $wpdb->posts WHERE post_name = ''");
	if ($posts) {
		foreach($posts as $post) {
			if ('' == $post->post_name) { 
				$newtitle = sanitize_title($post->post_title);
				$wpdb->query("UPDATE $wpdb->posts SET post_name = '$newtitle' WHERE ID = '$post->ID'");
			}
		}
	}
	
	$categories = $wpdb->get_results("SELECT cat_ID, cat_name, category_nicename FROM $wpdb->categories");
	foreach ($categories as $category) {
		if ('' == $category->category_nicename) { 
			$newtitle = sanitize_title($category->cat_name);
			$wpdb->query("UPDATE $wpdb->categories SET category_nicename = '$newtitle' WHERE cat_ID = '$category->cat_ID'");
		}
	}
	
	if (!$wpdb->get_var("SELECT option_name FROM $wpdb->options WHERE option_name = 'permalink_structure'")) { // If it's not already there
		$wpdb->query("INSERT INTO `$wpdb->options` 
			(`option_id`, `blog_id`, `option_name`, `option_can_override`, `option_type`, `option_value`, `option_width`, `option_height`, `option_description`, `option_admin_level`) 
			VALUES 
			('', '0', 'permalink_structure', 'Y', '3', '', '20', '8', 'How the permalinks for your site are constructed. See <a href=\"options-permalink.php\">permalink options page</a> for necessary mod_rewrite rules and more information.', '8');");
		}
		
	if (!$wpdb->get_var("SELECT option_name FROM $wpdb->options WHERE option_name = 'gzipcompression'")) { // If it's not already there
		$wpdb->query("INSERT INTO `$wpdb->options` 
			(`option_id`, `blog_id`, `option_name`, `option_can_override`, `option_type`, `option_value`, `option_width`, `option_height`, `option_description`, `option_admin_level`) 
			VALUES 
			('', '0', 'gzipcompression', 'Y', '2', '0', '20', '8', 'Whether your output should be gzipped or not. Enable this if you don&#8217;t already have mod_gzip running.', '8');");
}
	if (!$wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = 'hack_file'")) {
		$wpdb->query("INSERT INTO `$wpdb->options` 
			( `option_id` , `blog_id` , `option_name` , `option_can_override` , `option_type` , `option_value` , `option_width` , `option_height` , `option_description` , `option_admin_level` )
			VALUES 
			('', '0', 'hack_file', 'Y', '2', '0', '20', '8', 'Set this to true if you plan to use a hacks file. This is a place for you to store code hacks that won&#8217;t be overwritten when you upgrade. The file must be in your wordpress root and called <code>my-hacks.php</code>', '8')");
	}

	// fix upload users description
	$wpdb->query("UPDATE $wpdb->options SET option_description = '...or you may authorize only some users. enter their logins here, separated by spaces. if you leave this variable blank, all users who have the minimum level are authorized to upload. example: \'barbara anne george\'' WHERE option_id = 37");
	// and file types
	$wpdb->query("UPDATE $wpdb->options SET option_description = 'accepted file types, separated by spaces. example: \'jpg gif png\'' WHERE option_id = 34");
	// add link to php date format. this could be to a wordpress.org page in the future
	$wpdb->query("UPDATE $wpdb->options SET option_description = 'see <a href=\"http://php.net/date\">help</a> for format characters' WHERE option_id = 52");
	$wpdb->query("UPDATE $wpdb->options SET option_description = 'see <a href=\"http://php.net/date\">help</a> for format characters' WHERE option_id = 53");
	$wpdb->query("UPDATE $wpdb->options SET option_value = REPLACE(option_value, 'wp-links/links-images/', 'wp-images/links/')
                                                      WHERE option_name LIKE 'links_rating_image%'
                                                      AND option_value LIKE 'wp-links/links-images/%'");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name = 'comment_allowed_tags'");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name = 'use_preview'");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name = 'search_engine_friendly_urls'");
	// Multiple categories
	maybe_create_table($wpdb->post2cat, "
		CREATE TABLE `$wpdb->post2cat` (
		`rel_id` INT NOT NULL AUTO_INCREMENT ,
		`post_id` INT NOT NULL ,
		`category_id` INT NOT NULL ,
		PRIMARY KEY ( `rel_id` ) ,
		INDEX ( `post_id` , `category_id` )
		)
		");

	$done_ids = $wpdb->get_results("SELECT DISTINCT post_id FROM $wpdb->post2cat");
	if ($done_ids) :
		foreach ($done_ids as $done_id) :
			$done_posts[] = $done_id->post_id;
		endforeach;
		$catwhere = ' AND ID NOT IN (' . implode(',', $done_posts) . ')';
	else:
		$catwhere = '';
	endif;
	
	$allposts = $wpdb->get_results("SELECT ID, post_category FROM $wpdb->posts WHERE post_category != '0' $catwhere");
	if ($allposts) :
		foreach ($allposts as $post) {
			// Check to see if it's already been imported
			$cat = $wpdb->get_row("SELECT * FROM $wpdb->post2cat WHERE post_id = $post->ID AND category_id = $post->post_category");
			if (!$cat && 0 != $post->post_category) { // If there's no result
				$wpdb->query("
					INSERT INTO $wpdb->post2cat
					(post_id, category_id)
					VALUES
					('$post->ID', '$post->post_category')
					");
			}
		}
	endif;
}

function upgrade_101() {
	global $wpdb;

	// Less intrusive default
	$wpdb->query("ALTER TABLE `$wpdb->linkcategories` CHANGE `show_description` `show_description` ENUM( 'Y', 'N' ) DEFAULT 'N' NOT NULL"); 
	
	// Clean up indices, add a few
	add_clean_index($wpdb->posts, 'post_name');
	add_clean_index($wpdb->posts, 'post_status');
	add_clean_index($wpdb->categories, 'category_nicename');
	add_clean_index($wpdb->comments, 'comment_approved');
	add_clean_index($wpdb->comments, 'comment_post_ID');
	add_clean_index($wpdb->links , 'link_category');
	add_clean_index($wpdb->links , 'link_visible');
}


function upgrade_110() {
  global $wpdb;
	
	maybe_add_column($wpdb->comments, 'user_id', "ALTER TABLE `$wpdb->comments` ADD `user_id` INT DEFAULT '0' NOT NULL ;");
	maybe_add_column($wpdb->users, 'user_activation_key', "ALTER TABLE `$wpdb->users` ADD `user_activation_key` VARCHAR( 60 ) NOT NULL ;");
	maybe_add_column($wpdb->users, 'user_status', "ALTER TABLE `$wpdb->users` ADD `user_status` INT DEFAULT '0' NOT NULL ;");
	$wpdb->query("ALTER TABLE `$wpdb->posts` CHANGE `comment_status` `comment_status` ENUM( 'open', 'closed', 'registered_only' ) DEFAULT 'open' NOT NULL");

	maybe_add_column($wpdb->users, 'user_nicename', "ALTER TABLE `$wpdb->users` ADD `user_nicename` VARCHAR(50) DEFAULT '' NOT NULL ;");
	maybe_add_column($wpdb->posts, 'post_date_gmt', "ALTER TABLE $wpdb->posts ADD post_date_gmt DATETIME NOT NULL AFTER post_date");
	maybe_add_column($wpdb->posts, 'post_modified_gmt', "ALTER TABLE $wpdb->posts ADD post_modified_gmt DATETIME NOT NULL AFTER post_modified");
	maybe_add_column($wpdb->comments, 'comment_date_gmt', "ALTER TABLE $wpdb->comments ADD comment_date_gmt DATETIME NOT NULL AFTER comment_date");

    // Set user_nicename.
	$users = $wpdb->get_results("SELECT ID, user_nickname, user_nicename FROM $wpdb->users");
	foreach ($users as $user) {
		if ('' == $user->user_nicename) { 
			$newname = sanitize_title($user->user_nickname);
			$wpdb->query("UPDATE $wpdb->users SET user_nicename = '$newname' WHERE ID = '$user->ID'");
		}
	}

	// Convert passwords to MD5 and update table appropiately

	$user_table = $wpdb->get_row("DESCRIBE $wpdb->users user_pass");
	if ($user_table->Type != 'varchar(32)') {
		$wpdb->query("ALTER TABLE $wpdb->users MODIFY user_pass varchar(64) not null");
	}
	
	$query = 'SELECT ID, user_pass from '.$wpdb->users;
	foreach ($wpdb->get_results($query) as $row) {
		if (!preg_match('/^[A-Fa-f0-9]{32}$/', $row->user_pass)) {
			   $wpdb->query('UPDATE '.$wpdb->users.' SET user_pass = MD5(\''.$row->user_pass.'\') WHERE ID = \''.$row->ID.'\'');
		}
	}

	// Add blog_charset option
	if(!$wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = 'blog_charset'")) {
		$wpdb->query("INSERT INTO $wpdb->options (option_name, option_type, option_value, option_admin_level) VALUES ('blog_charset', 3, 'utf-8', 8)");
	}

	// Get the GMT offset, we'll use that later on
	$all_options = get_alloptions_110();

	$time_difference = $all_options->time_difference;

	$server_time = time()+date('Z');
	$weblogger_time = $server_time + $time_difference*3600;
	$gmt_time = time();

	$diff_gmt_server = ($gmt_time - $server_time) / 3600;
	$diff_weblogger_server = ($weblogger_time - $server_time) / 3600;
	$diff_gmt_weblogger = $diff_gmt_server - $diff_weblogger_server;
	$gmt_offset = -$diff_gmt_weblogger;

	// Add a gmt_offset option, with value $gmt_offset
	if (!get_settings('gmt_offset')) {
		if(!$wpdb->get_var("SELECT * FROM $wpdb->options WHERE option_name = 'gmt_offset'")) {
			$wpdb->query("INSERT INTO $wpdb->options (option_name, option_type, option_value, option_description, option_admin_level) VALUES ('gmt_offset', 8, $gmt_offset, 'The difference in hours between GMT and your timezone', 8)");
		}

	}

	// Check if we already set the GMT fields (if we did, then
	// MAX(post_date_gmt) can't be '0000-00-00 00:00:00'
	// <michel_v> I just slapped myself silly for not thinking about it earlier
	$got_gmt_fields = ($wpdb->get_var("SELECT MAX(post_date_gmt) FROM $wpdb->posts") == '0000-00-00 00:00:00') ? false : true;

	if (!$got_gmt_fields) {

		// Add or substract time to all dates, to get GMT dates
		$add_hours = intval($diff_gmt_weblogger);
		$add_minutes = intval(60 * ($diff_gmt_weblogger - $add_hours));
		$wpdb->query("UPDATE $wpdb->posts SET post_date_gmt = DATE_ADD(post_date, INTERVAL '$add_hours:$add_minutes' HOUR_MINUTE)");
		$wpdb->query("UPDATE $wpdb->posts SET post_modified = post_date");
		$wpdb->query("UPDATE $wpdb->posts SET post_modified_gmt = DATE_ADD(post_modified, INTERVAL '$add_hours:$add_minutes' HOUR_MINUTE) WHERE post_modified != '0000-00-00 00:00:00'");
		$wpdb->query("UPDATE $wpdb->comments SET comment_date_gmt = DATE_ADD(comment_date, INTERVAL '$add_hours:$add_minutes' HOUR_MINUTE)");
		$wpdb->query("UPDATE $wpdb->users SET dateYMDhour = DATE_ADD(dateYMDhour, INTERVAL '$add_hours:$add_minutes' HOUR_MINUTE)");
	}

	// post-meta
	maybe_create_table($wpdb->postmeta, "
	CREATE TABLE $wpdb->postmeta (
	  meta_id int(11) NOT NULL auto_increment,
	  post_id int(11) NOT NULL default 0,
	  meta_key varchar(255),
	  meta_value text,
	  PRIMARY KEY (meta_id),
	  INDEX (post_id),
	  INDEX (meta_key)
	)
	");

	// First we need to enlarge option_value so it can hold larger values:
	$wpdb->query("ALTER TABLE `$wpdb->options` CHANGE `option_value` `option_value` TEXT NOT NULL");
	
	// Now an option for blog pinging
	if(!$wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = 'ping_sites'")) {
		$wpdb->query("INSERT INTO $wpdb->options (option_name, option_type, option_value, option_admin_level) VALUES ('ping_sites', 3, 'http://rpc.pingomatic.com/', 8)");
	}
	
	// Option for using the advanced edit screen by default
	if(!$wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = 'advanced_edit'")) {
		$wpdb->query("INSERT INTO $wpdb->options (option_name, option_type, option_value, option_admin_level) VALUES ('advanced_edit', 5, '0', 8)");
	}
	// Fix for CVS versions
	$wpdb->query("UPDATE $wpdb->options SET option_type = '5' WHERE option_name = 'advanced_edit'");
	
	// Now an option for moderation words
	if(!$wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = 'moderation_keys'")) {
		$wpdb->query("INSERT INTO $wpdb->options (option_name, option_type, option_value, option_admin_level) VALUES ('moderation_keys', 3, '', 8)");
	}

	// Option for plugins
	if(!$wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = 'active_plugins'")) {
		$wpdb->query("INSERT INTO $wpdb->options (option_name, option_type, option_value, option_admin_level) VALUES ('active_plugins', 3, '', 8)");
	}

	// Option for max # of links per comment
	if(!$wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = 'comment_max_links'")) {
		$wpdb->query("INSERT INTO $wpdb->options (option_name, option_type, option_value, option_admin_level) VALUES ('comment_max_links', 3, '5', 8)");
	}

	// Option for different blog URL
	if(!$wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = 'home'")) {
		$wpdb->query("INSERT INTO $wpdb->options (option_name, option_type, option_value, option_admin_level) VALUES ('home', 3, '', 8)");
	}

	// Option for category base
	if(!$wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = 'category_base'")) {
		$wpdb->query("INSERT INTO $wpdb->options (option_name, option_type, option_value, option_admin_level) VALUES ('category_base', 3, '', 8)");
	}

	// Delete unused options
	$unusedoptions = array ('blodotgsping_url', 'bodyterminator', 'emailtestonly', 'phoneemail_separator', 'smilies_directory', 'subjectprefix', 'use_bbcode', 'use_blodotgsping', 'use_phoneemail', 'use_quicktags', 'use_weblogsping', 'weblogs_cache_file');
	foreach ($unusedoptions as $option) :
		delete_option($option);
	endforeach;

	// Forward-thinking
	$wpdb->query("ALTER TABLE `$wpdb->posts` CHANGE `post_status` `post_status` ENUM( 'publish', 'draft', 'private', 'static' ) DEFAULT 'publish' NOT NULL");
	maybe_add_column($wpdb->posts, 'post_parent', "ALTER TABLE `$wpdb->posts` ADD `post_parent` INT NOT NULL ;");


	$wpdb->query("ALTER TABLE `$wpdb->comments` CHANGE `comment_author_url` `comment_author_url` VARCHAR( 200 ) NOT NULL");
}

function upgrade_130() {
    global $wpdb;

	if(!$wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = 'default_email_category'")) {
        $wpdb->query("INSERT INTO $wpdb->options (option_name, option_type, option_value, option_description, option_admin_level) VALUES('default_email_category', 1, '1', 'by default posts by email will have this category', 8)");
    }

	if(!$wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = 'recently_edited'")) {
        $wpdb->query("INSERT INTO $wpdb->options (option_name, option_type, option_value, option_admin_level) VALUES ('recently_edited', 3, '', 8)");
    }

	maybe_add_column($wpdb->options, 'autoload', "ALTER TABLE `$wpdb->options` ADD `autoload` ENUM( 'yes', 'no' ) NOT NULL ;");

	// Set up a few options not to load by default
	$fatoptions = array( 'moderation_keys', 'recently_edited' );
	foreach ($fatoptions as $fatoption) :
		$wpdb->query("UPDATE $wpdb->options SET `autoload` = 'no' WHERE option_name = '$fatoption'");
	endforeach;

    // Remove extraneous backslashes.
	$posts = $wpdb->get_results("SELECT ID, post_title, post_content, post_excerpt FROM $wpdb->posts");
	if ($posts) {
		foreach($posts as $post) {
            $post_content = addslashes(deslash($post->post_content));
            $post_title = addslashes(deslash($post->post_title));
            $post_excerpt = addslashes(deslash($post->post_excerpt));
            $wpdb->query("UPDATE $wpdb->posts SET post_title = '$post_title', post_content = '$post_content', post_excerpt = '$post_excerpt' WHERE ID = '$post->ID'");
		}
	}

    // Remove extraneous backslashes.
	$comments = $wpdb->get_results("SELECT comment_ID, comment_author, comment_content FROM $wpdb->comments");
	if ($comments) {
		foreach($comments as $comment) {
            $comment_content = addslashes(deslash($comment->comment_content));
            $comment_author = addslashes(deslash($comment->comment_author));
            $wpdb->query("UPDATE $wpdb->comments SET comment_content = '$comment_content', comment_author = '$comment_author' WHERE comment_ID = '$comment->comment_ID'");
		}
	}

    // Remove extraneous backslashes.
	$links = $wpdb->get_results("SELECT link_id, link_name, link_description FROM $wpdb->links");
	if ($links) {
		foreach($links as $link) {
            $link_name = addslashes(deslash($link->link_name));
            $link_description = addslashes(deslash($link->link_description));
            $wpdb->query("UPDATE $wpdb->links SET link_name = '$link_name', link_description = '$link_description' WHERE link_id = '$link->link_id'");
		}
	}

    // The "paged" option for what_to_show is no more.
    if ($wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'what_to_show'") == 'paged') {
        $wpdb->query("UPDATE $wpdb->options SET option_value = 'posts' WHERE option_name = 'what_to_show'");
    }

	if ( !is_array( get_settings('active_plugins') ) ) {
		$plugins = explode("\n", trim(get_settings('active_plugins')) );
		update_option('active_plugins', $plugins);
	}

	// Obsolete tables
	$wpdb->query('DROP TABLE IF EXISTS ' . $table_prefix . 'optionvalues');
	$wpdb->query('DROP TABLE IF EXISTS ' . $table_prefix . 'optiontypes');
	$wpdb->query('DROP TABLE IF EXISTS ' . $table_prefix . 'optiongroups');
	$wpdb->query('DROP TABLE IF EXISTS ' . $table_prefix . 'optiongroup_options');
}

// The functions we use to actually do stuff

// General
function maybe_create_table($table_name, $create_ddl) {
    global $wpdb;
    foreach ($wpdb->get_col("SHOW TABLES",0) as $table ) {
        if ($table == $table_name) {
            return true;
        }
    }
    //didn't find it try to create it.
    $q = $wpdb->query($create_ddl);
    // we cannot directly tell that whether this succeeded!
    foreach ($wpdb->get_col("SHOW TABLES",0) as $table ) {
        if ($table == $table_name) {
            return true;
        }
    }
    return false;
}

function drop_index($table, $index) {
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->query("ALTER TABLE `$table` DROP INDEX `$index`");
	// Now we need to take out all the extra ones we may have created
	for ($i = 0; $i < 25; $i++) {
		$wpdb->query("ALTER TABLE `$table` DROP INDEX `{$index}_$i`");
	}
	$wpdb->show_errors();
	return true;
}

function add_clean_index($table, $index) {
	global $wpdb;
	drop_index($table, $index);
	$wpdb->query("ALTER TABLE `$table` ADD INDEX ( `$index` )");
	return true;
}

/**
 ** maybe_add_column()
 ** Add column to db table if it doesn't exist.
 ** Returns:  true if already exists or on successful completion
 **           false on error
 */
function maybe_add_column($table_name, $column_name, $create_ddl) {
    global $wpdb, $debug;
    foreach ($wpdb->get_col("DESC $table_name", 0) as $column ) {
        if ($debug) echo("checking $column == $column_name<br />");
        if ($column == $column_name) {
            return true;
        }
    }
    //didn't find it try to create it.
    $q = $wpdb->query($create_ddl);
    // we cannot directly tell that whether this succeeded!
    foreach ($wpdb->get_col("DESC $table_name", 0) as $column ) {
        if ($column == $column_name) {
            return true;
        }
    }
    return false;
}


// get_alloptions as it was for 1.2.
function get_alloptions_110() {
	global $wpdb;
	if ($options = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options")) {
		foreach ($options as $option) {
			// "When trying to design a foolproof system, 
			//  never underestimate the ingenuity of the fools :)" -- Dougal
			if ('siteurl' == $option->option_name) $option->option_value = preg_replace('|/+$|', '', $option->option_value);
			if ('home' == $option->option_name) $option->option_value = preg_replace('|/+$|', '', $option->option_value);
			if ('category_base' == $option->option_name) $option->option_value = preg_replace('|/+$|', '', $option->option_value);
			$all_options->{$option->option_name} = stripslashes($option->option_value);
		}
	}
	return $all_options;
}

function deslash($content) {
    // Note: \\\ inside a regex denotes a single backslash.

    // Replace one or more backslashes followed by a single quote with
    // a single quote.
    $content = preg_replace("/\\\+'/", "'", $content);

    // Replace one or more backslashes followed by a double quote with
    // a double quote.
    $content = preg_replace('/\\\+"/', '"', $content);

    // Replace one or more backslashes with one backslash.
    $content = preg_replace("/\\\+/", "\\", $content);

    return $content;
}

function dbDelta($queries, $execute = true) {
	global $wpdb;
	
	// Seperate individual queries into an array
	if( !is_array($queries) ) {
		$queries = explode( ';', $queries );
		if('' == $queries[count($queries) - 1]) array_pop($queries);
	}
	
	$cqueries = array(); // Creation Queries
	$iqueries = array(); // Insertion Queries
	$for_update = array();
	
	// Create a tablename index for an array ($cqueries) of queries
	foreach($queries as $qry) {
		if(preg_match("|CREATE TABLE ([^ ]*)|", $qry, $matches)) {
			$cqueries[strtolower($matches[1])] = $qry;
			$for_update[$matches[1]] = 'Created table '.$matches[1];
		}
		else if(preg_match("|CREATE DATABASE ([^ ]*)|", $qry, $matches)) {
			array_unshift($cqueries, $qry);
		}
		else if(preg_match("|INSERT INTO ([^ ]*)|", $qry, $matches)) {
			$iqueries[] = $qry;
		}
		else if(preg_match("|UPDATE ([^ ]*)|", $qry, $matches)) {
			$iqueries[] = $qry;
		}
		else {
			// Unrecognized query type
		}
	}	

	// Check to see which tables and fields exist
	if($tables = $wpdb->get_col('SHOW TABLES;')) {
		// For every table in the database
		foreach($tables as $table) {
			// If a table query exists for the database table...
			if( array_key_exists(strtolower($table), $cqueries) ) {
				// Clear the field and index arrays
				unset($cfields);
				unset($indices);
				// Get all of the field names in the query from between the parens
				preg_match("|\((.*)\)|ms", $cqueries[strtolower($table)], $match2);
				$qryline = trim($match2[1]);

				// Separate field lines into an array
				$flds = explode("\n", $qryline);

				//echo "<hr/><pre>\n".print_r(strtolower($table), true).":\n".print_r($cqueries, true)."</pre><hr/>";
				
				// For every field line specified in the query
				foreach($flds as $fld) {
					// Extract the field name
					preg_match("|^([^ ]*)|", trim($fld), $fvals);
					$fieldname = $fvals[1];
					
					// Verify the found field name
					$validfield = true;
					switch(strtolower($fieldname))
					{
					case '':
					case 'primary':
					case 'index':
					case 'fulltext':
					case 'unique':
					case 'key':
						$validfield = false;
						$indices[] = trim(trim($fld), ", \n");
						break;
					}
					$fld = trim($fld);
					
					// If it's a valid field, add it to the field array
					if($validfield) {
						$cfields[strtolower($fieldname)] = trim($fld, ", \n");
					}
				}
				
				// Fetch the table column structure from the database
				$tablefields = $wpdb->get_results("DESCRIBE {$table};");
								
				// For every field in the table
				foreach($tablefields as $tablefield) {				
					// If the table field exists in the field array...
					if(array_key_exists(strtolower($tablefield->Field), $cfields)) {
						// Get the field type from the query
						preg_match("|".$tablefield->Field." ([^ ]*( unsigned)?)|i", $cfields[strtolower($tablefield->Field)], $matches);
						$fieldtype = $matches[1];

						// Is actual field type different from the field type in query?
						if($tablefield->Type != $fieldtype) {
							// Add a query to change the column type
							$cqueries[] = "ALTER TABLE {$table} CHANGE COLUMN {$tablefield->Field} " . $cfields[strtolower($tablefield->Field)];
							$for_update[$table.'.'.$tablefield->Field] = "Changed type of {$table}.{$tablefield->Field} from {$tablefield->Type} to {$fieldtype}";
						}
						
						// Get the default value from the array
							//echo "{$cfields[strtolower($tablefield->Field)]}<br>";
						if(preg_match("| DEFAULT '(.*)'|i", $cfields[strtolower($tablefield->Field)], $matches)) {
							$default_value = $matches[1];
							if($tablefield->Default != $default_value)
							{
								// Add a query to change the column's default value
								$cqueries[] = "ALTER TABLE {$table} ALTER COLUMN {$tablefield->Field} SET DEFAULT '{$default_value}'";
								$for_update[$table.'.'.$tablefield->Field] = "Changed default value of {$table}.{$tablefield->Field} from {$tablefield->Default} to {$default_value}";
							}
						}

						// Remove the field from the array (so it's not added)
						unset($cfields[strtolower($tablefield->Field)]);
					}
					else {
						// This field exists in the table, but not in the creation queries?
					}
				}

				// For every remaining field specified for the table
				foreach($cfields as $fieldname => $fielddef) {
					// Push a query line into $cqueries that adds the field to that table
					$cqueries[] = "ALTER TABLE {$table} ADD COLUMN $fielddef";
					$for_update[$table.'.'.$fieldname] = 'Added column '.$table.'.'.$fieldname;
				}
				
				// Index stuff goes here
				// Fetch the table index structure from the database
				$tableindices = $wpdb->get_results("SHOW INDEX FROM {$table};");
				
				if($tableindices) {
					// Clear the index array
					unset($index_ary);

					// For every index in the table
					foreach($tableindices as $tableindex) {
						// Add the index to the index data array
						$keyname = $tableindex->Key_name;
						$index_ary[$keyname]['columns'][] = array('fieldname' => $tableindex->Column_name, 'subpart' => $tableindex->Sub_part);
						$index_ary[$keyname]['unique'] = ($tableindex->Non_unique == 0)?true:false;
					}

					// For each actual index in the index array
					foreach($index_ary as $index_name => $index_data) {
						// Build a create string to compare to the query
						$index_string = '';
						if($index_name == 'PRIMARY') {
							$index_string .= 'PRIMARY ';
						}
						else if($index_data['unique']) {
							$index_string .= 'UNIQUE ';
						}
						$index_string .= 'KEY ';
						if($index_name != 'PRIMARY') {
							$index_string .= $index_name;
						}
						$index_columns = '';
						// For each column in the index
						foreach($index_data['columns'] as $column_data) {					
							if($index_columns != '') $index_columns .= ',';
							// Add the field to the column list string
							$index_columns .= $column_data['fieldname'];
							if($column_data['subpart'] != '') {
								$index_columns .= '('.$column_data['subpart'].')';
							}
						}
						// Add the column list to the index create string 
						$index_string .= ' ('.$index_columns.')';

						if(!(($aindex = array_search($index_string, $indices)) === false)) {
							unset($indices[$aindex]);
							//echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">{$table}:<br/>Found index:".$index_string."</pre>\n";
						}
						//else echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">{$table}:<br/><b>Did not find index:</b>".$index_string."<br/>".print_r($indices, true)."</pre>\n";
					}
				}

				// For every remaining index specified for the table
				foreach($indices as $index) {
					// Push a query line into $cqueries that adds the index to that table
					$cqueries[] = "ALTER TABLE {$table} ADD $index";
					$for_update[$table.'.'.$fieldname] = 'Added index '.$table.' '.$index;
				}

				// Remove the original table creation query from processing
				unset($cqueries[strtolower($table)]);
				unset($for_update[strtolower($table)]);
			} else {
				// This table exists in the database, but not in the creation queries?
			}
		}
	}

	$allqueries = array_merge($cqueries, $iqueries);
	if($execute) {
		foreach($allqueries as $query) {
			//echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">".print_r($query, true)."</pre>\n";
			$wpdb->query($query);
		}
	}

	return $for_update;
}

function make_db_current() {
	global $wp_queries;

	$alterations = dbDelta($wp_queries);
	echo "<ol>\n";
	foreach($alterations as $alteration) echo "<li>$alteration</li>\n";
	echo "</ol>\n";
}

function rename_field($table, $field, $new) {
//	ALTER TABLE `wp_users` CHANGE `ID` `user_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT 
}

function remove_field($table, $field) {
	global $wpdb;
// ALTER TABLE `wp_users` DROP `user_domain` 
}

?>