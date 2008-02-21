<?php
@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
if (!isset($_GET["page"])) require_once('admin.php');
if ( $editing ) {
	if ( user_can_richedit() )
		wp_enqueue_script( 'wp_tiny_mce' );
}

get_admin_page_title();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<title><?php bloginfo('name') ?> &rsaquo; <?php echo wp_specialchars( strip_tags( $title ) ); ?> &#8212; WordPress</title>
<?php 
wp_admin_css( 'css/global' );
wp_admin_css();
?>
<!--[if gte IE 6]>
<?php wp_admin_css( 'css/ie' );
?>
<!endif-->
<script type="text/javascript">
//<![CDATA[
addLoadEvent=(function(){var e=[],t,s,n,i,o,d=document,w=window,r='readyState',c='onreadystatechange',x=function(){n=1;clearInterval(t);while(i=e.shift())i();if(s)s[c]=''};return function(f){if(n)return f();if(!e[0]){d.addEventListener&&d.addEventListener("DOMContentLoaded",x,false);/*@cc_on@*//*@if(@_win32)d.write("<script id=__ie_onload defer src=//0><\/scr"+"ipt>");s=d.getElementById("__ie_onload");s[c]=function(){s[r]=="complete"&&x()};/*@end@*/if(/WebKit/i.test(navigator.userAgent))t=setInterval(function(){/loaded|complete/.test(d[r])&&x()},10);o=w.onload;w.onload=function(){x();o&&o()}}e.push(f)}})();//]]>
</script>
<?php if ( ($parent_file != 'link-manager.php') && ($parent_file != 'options-general.php') ) : ?>
<style type="text/css">* html { overflow-x: hidden; }</style>
<?php endif;
if ( isset($page_hook) )
	do_action('admin_print_scripts-' . $page_hook);
else if ( isset($plugin_page) )
	do_action('admin_print_scripts-' . $plugin_page);
do_action('admin_print_scripts');

if ( isset($page_hook) )
	do_action('admin_head-' . $page_hook);
else if ( isset($plugin_page) )
	do_action('admin_head-' . $plugin_page);
do_action('admin_head');
?>
</head>
<body class="wp-admin <?php echo apply_filters( 'admin_body_class', '' ); ?>">
<div id="wpwrap">
<div id="wpcontent">
<div id="wphead">
<h1><?php bloginfo('name'); ?><span id="viewsite"><a href="<?php echo get_option('home') . '/'; ?>"><?php _e('Visit Site') ?></a></span></h1>
</div>
<div id="user_info"><p><?php printf(__('Howdy, <a href="%1$s">%2$s</a>!'), 'profile.php', $user_identity) ?> | <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?action=logout" title="<?php _e('Log Out') ?>"><?php _e('Log Out'); ?></a> | <?php printf(__('<a href="%s">Help</a>'), 'http://codex.wordpress.org/') ?> | <?php printf(__('<a href="%s">Forums</a>'), 'http://wordpress.org/support/') ?></p></div>

<?php
require(ABSPATH . 'wp-admin/menu-header.php');

if ( $parent_file == 'options-general.php' ) {
	require(ABSPATH . 'wp-admin/options-head.php');
}
?>
