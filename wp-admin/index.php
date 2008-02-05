<?php

require_once('admin.php');

require( './includes/dashboard.php' );

wp_dashboard_setup();

function index_js() {
?>
<script type="text/javascript">
	jQuery(function() {
		jQuery('#dashboard_incoming_links div.dashboard-widget-content').not( '.dashboard-widget-control' ).load('index-extra.php?jax=incominglinks');
		jQuery('#dashboard_primary div.dashboard-widget-content').not( '.dashboard-widget-control' ).load('index-extra.php?jax=devnews');
		jQuery('#dashboard_secondary div.dashboard-widget-content').not( '.dashboard-widget-control' ).load('index-extra.php?jax=planetnews');
		jQuery('#dashboard_plugins div.dashboard-widget-content').not( '.dashboard-widget-control' ).html( 'TODO' );
	});
</script>
<?php
}
add_action( 'admin_head', 'index_js' );

wp_enqueue_script( 'jquery' );

$title = __('Dashboard');
$parent_file = 'index.php';
require_once('admin-header.php');

$today = current_time('mysql', 1);
?>

<div class="wrap">

<h2><?php _e('Dashboard'); ?></h2>

<div id="rightnow">
<h3 class="reallynow"><?php _e('Right Now'); ?> <a href="post-new.php" class="rbutton"><?php _e('Write a New Post'); ?></a> <a href="page-new.php" class="rbutton"><?php _e('Write a New Page'); ?></a></h3>

<?php
$num_posts = wp_count_posts('post', 'publish');

$num_pages = wp_count_posts('page', 'publish');

$num_drafts = wp_count_posts('post', 'draft');

$num_future = wp_count_posts('post', 'future');

$num_comments = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = '1'");

$num_cats  = wp_count_terms('category');

$num_tags = wp_count_terms('post_tag');

$post_type_texts = array();

if ( $num_posts ) {
	$post_type_texts[] = '<a href="edit.php">'.sprintf( __ngettext( '%d post', '%d posts', $num_posts ), number_format_i18n( $num_posts ) ).'</a>';
}
if ( $num_pages ) {
	$post_type_texts[] = '<a href="edit-pages.php">'.sprintf( __ngettext( '%d page', '%d pages', $num_pages ), number_format_i18n( $num_pages ) ).'</a>';
}
if ( $num_drafts ) {
	$post_type_texts[] = '<a href="edit.php?post_status=draft">'.sprintf( __ngettext( '%d draft', '%d drafts', $num_drafts ), number_format_i18n( $num_drafts ) ).'</a>';
}
if ( $num_future ) {
	$post_type_texts[] = '<a href="edit.php?post_status=future">'.sprintf( __ngettext( '%d scheduled post', '%d scheduled posts', $num_future ), number_format_i18n( $num_future ) ).'</a>';
}

$cats_text = '<a href="categories.php">'.sprintf( __ngettext( '%d category', '%d categories', $num_cats ), number_format_i18n( $num_cats ) ).'</a>';
$tags_text = sprintf( __ngettext( '%d tag', '%d tags', $num_tags ), number_format_i18n( $num_tags ) );

$post_type_text = implode(', ', $post_type_texts);

// There is always a category
$sentence = sprintf( __( 'You have %1$s, contained within %2$s and %3$s.' ), $post_type_text, $cats_text, $tags_text );

?>
<p class="youhave"><?php echo $sentence; ?></p>
<?php
$ct = current_theme_info();
$sidebars_widgets = wp_get_sidebars_widgets();
$num_widgets = array_reduce( $sidebars_widgets, create_function( '$prev, $curr', 'return $prev+count($curr);' ) );
$widgets_text = sprintf( __ngettext( '%d widget', '%d widgets', $num_widgets ), $num_widgets );
?>
<p><?php printf( __( 'You are using %1$s theme with %2$s.' ), $ct->title, "<a href='widgets.php'>$widgets_text</a>" ); ?> <a href="themes.php" class="rbutton"><?php _e('Change Theme'); ?></a> You're using BetaPress TODO.</p>
<?php do_action( 'rightnow_end' ); ?>
<?php do_action( 'activity_box_end' ); ?>
</div><!-- rightnow -->

<?php wp_dashboard(); ?>

</div><!-- wrap -->

<?php require('./admin-footer.php'); ?>
