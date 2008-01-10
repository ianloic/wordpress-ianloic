<?php
if ( isset($_GET['message']) )
	$_GET['message'] = (int) $_GET['message'];
$messages[1] = __('Post updated');
$messages[2] = __('Custom field updated');
$messages[3] = __('Custom field deleted.');
?>
<?php if (isset($_GET['message'])) : ?>
<div id="message" class="updated fade"><p><?php echo wp_specialchars($messages[$_GET['message']]); ?></p></div>
<?php endif; ?>

<form name="post" action="post.php" method="post" id="post">
<?php if ( (isset($mode) && 'bookmarklet' == $mode) || isset($_GET['popupurl']) ): ?>
<input type="hidden" name="mode" value="bookmarklet" />
<?php endif; ?>

<div class="wrap">
<?php

if (0 == $post_ID) {
	$form_action = 'post';
	$temp_ID = -1 * time(); // don't change this formula without looking at wp_write_post()
	$form_extra = "<input type='hidden' id='post_ID' name='temp_ID' value='$temp_ID' />";
	wp_nonce_field('add-post');
} else {
	$post_ID = (int) $post_ID;
	$form_action = 'editpost';
	$form_extra = "<input type='hidden' id='post_ID' name='post_ID' value='$post_ID' />";
	wp_nonce_field('update-post_' .  $post_ID);
}

$form_pingback = '<input type="hidden" name="post_pingback" value="' . (int) get_option('default_pingback_flag') . '" id="post_pingback" />';

$form_prevstatus = '<input type="hidden" name="prev_status" value="' . attribute_escape( $post->post_status ) . '" />';

$form_trackback = '<input type="text" name="trackback_url" style="width: 415px" id="trackback" tabindex="7" value="'. attribute_escape( str_replace("\n", ' ', $post->to_ping) ) .'" />';

if ('' != $post->pinged) {
	$pings = '<p>'. __('Already pinged:') . '</p><ul>';
	$already_pinged = explode("\n", trim($post->pinged));
	foreach ($already_pinged as $pinged_url) {
		$pings .= "\n\t<li>" . wp_specialchars($pinged_url) . "</li>";
	}
	$pings .= '</ul>';
}

$saveasdraft = '<input name="save" type="submit" id="save" tabindex="3" value="' . attribute_escape( __('Save and Continue Editing') ) . '" />';

if (empty($post->post_status)) $post->post_status = 'draft';

?>

<input type="hidden" id="user-id" name="user_ID" value="<?php echo (int) $user_ID ?>" />
<input type="hidden" id="hiddenaction" name="action" value="<?php echo $form_action ?>" />
<input type="hidden" id="originalaction" name="originalaction" value="<?php echo $form_action ?>" />
<input type="hidden" name="post_author" value="<?php echo attribute_escape( $post->post_author ); ?>" />
<input type="hidden" id="post_type" name="post_type" value="post" />

<?php echo $form_extra ?>
<?php if ((isset($post->post_title) && '' == $post->post_title) || (isset($_GET['message']) && 2 > $_GET['message'])) : ?>
<script type="text/javascript">
function focusit() {
	// focus on first input field
	document.post.title.focus();
}
addLoadEvent(focusit);
</script>
<?php endif; ?>
<div id="poststuff">

<div id="titlediv">
<h3><?php _e('Title') ?></h3>
<div class="inside"><input type="text" name="post_title" size="30" tabindex="1" value="<?php echo attribute_escape($post->post_title); ?>" id="title" /></div>
</div>

<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
<h3><?php _e('Post') ?></h3>
<?php the_editor($post->post_content); ?>
</div>

<?php echo $form_pingback ?>
<?php echo $form_prevstatus ?>

<p class="submit">
<?php if ( 'publish' == $post->post_status ) { ?>
<a href="<?php echo clean_url(get_permalink($post->ID)); ?>" class="view-link" target="_blank"><?php _e('View &raquo;'); ?></a>
<?php } elseif ( 'edit' == $action ) { ?>
<a href="<?php echo clean_url(apply_filters('preview_post_link', add_query_arg('preview', 'true', get_permalink($post->ID)))); ?>" class="view-link" target="_blank"><?php _e('Preview &raquo;'); ?></a>
<?php } ?>
<span id="autosave"></span>
<?php echo $saveasdraft; ?>
<input type="submit" name="submit" value="<?php _e('Save'); ?>" style="font-weight: bold;" tabindex="4" />
<?php
if ( !in_array( $post->post_status, array('publish', 'future') ) || 0 == $post_ID ) {
?>
<?php if ( current_user_can('publish_posts') ) : ?>
	<input name="publish" type="submit" id="publish" tabindex="5" accesskey="p" value="<?php _e('Publish') ?>" />
<?php else : ?>
	<input name="publish" type="submit" id="publish" tabindex="5" accesskey="p" value="<?php _e('Submit for Review') ?>" />
<?php endif; ?>
<?php
}
?>
<input name="referredby" type="hidden" id="referredby" value="<?php
if ( !empty($_REQUEST['popupurl']) )
	echo clean_url(stripslashes($_REQUEST['popupurl']));
else if ( url_to_postid(wp_get_referer()) == $post_ID )
	echo 'redo';
else
	echo clean_url(stripslashes(wp_get_referer()));
?>" /></p>

<div id="tagsdiv" class="postbox <?php echo postbox_classes('tagsdiv'); ?>">
<h3><?php _e('Tags'); ?></h3>
<div class="inside">
<p id="jaxtag"><input type="text" name="tags_input" class="tags-input" id="tags-input" size="40" tabindex="3" value="<?php echo get_tags_to_edit( $post_ID ); ?>" /></p>
<p id="tagchecklist"></p>
</div>
</div>

<div id="categorydiv" class="postbox <?php echo postbox_classes('categorydiv'); ?>">
<h3><?php _e('Categories') ?></h3>
<div class="inside">

<div id="category-adder" class="wp-hidden-children">
	<h4><a id="category-add-toggle" href="#category-add"><?php _e( '+ Add New Category' ); ?></a></h4>
	<p id="category-add" class="wp-hidden-child">
		<input type="text" name="newcat" id="newcat" class="form-required" value="<?php _e( 'New category name' ); ?>" />
		<?php wp_dropdown_categories( array( 'hide_empty' => 0, 'name' => 'newcat_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => __('Parent category') ) ); ?>
		<a id="category-add-sumbit" class="add:categorychecklist:categorydiv button" href="<?php echo wp_nonce_url( '', 'add-category' ); ?>"><?php _e( 'Add' ); ?></a>
		<span id="category-ajax-response"></span>
	</p>
</div>

<ul id="category-tabs">
	<li class="ui-tabs-selected"><a href="#categories-all"><?php _e( 'All Categories' ); ?></a></li>
	<li><a href="#categories-pop"><?php _e( 'Most Used' ); ?></a></li>
</ul>

<div id="categories-all" class="ui-tabs-panel">
	<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
		<?php dropdown_categories(); ?>
	</ul>
</div>

<div id="categories-pop" class="ui-tabs-panel">
	<ul id="categorychecklist-pop" class="categorychecklist form-no-clear">
		<?php wp_popular_categories_checklist(); ?>
	</ul>
</div>

</div>
</div>

<?php do_action('edit_form_advanced'); ?>

<?php
if (current_user_can('upload_files') && false) {
	$uploading_iframe_ID = (int) (0 == $post_ID ? $temp_ID : $post_ID);
	$uploading_iframe_src = wp_nonce_url("upload.php?style=inline&amp;tab=upload&amp;post_id=$uploading_iframe_ID", 'inlineuploading');
	$uploading_iframe_src = apply_filters('uploading_iframe_src', $uploading_iframe_src);
	if ( false != $uploading_iframe_src )
		echo '<iframe id="uploading" name="uploading" frameborder="0" src="' . $uploading_iframe_src . '">' . __('This feature requires iframe support.') . '</iframe>';
}
?>

<h2><?php _e('Advanced Options'); ?></h2>

<div id="postexcerpt" class="postbox <?php echo postbox_classes('postexcerpt'); ?>">
<h3><?php _e('Optional Excerpt') ?></h3>
<div class="inside"><textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt"><?php echo $post->post_excerpt ?></textarea></div>
</div>

<div id="trackbacksdiv" class="postbox <?php echo postbox_classes('trackbacksdiv'); ?>">
<h3><?php _e('Trackbacks') ?></h3>
<div class="inside">
<?php _e('Send trackbacks to:'); ?> <?php echo $form_trackback; ?> (<?php _e('Separate multiple URLs with spaces'); ?>)
<?php
if ( ! empty($pings) )
	echo $pings;
?>
</div>
</div>

<div id="postcustom" class="postbox <?php echo postbox_classes('postcustom'); ?>">
<h3><?php _e('Custom Fields') ?></h3>
<div class="inside">
<div id="postcustomstuff">
<table cellpadding="3">
<?php
$metadata = has_meta($post_ID);
list_meta($metadata);
?>

</table>
<?php
	meta_form();
?>
<div id="ajax-response"></div>
</div>
</div>
</div>

<?php do_action('dbx_post_advanced'); ?>

<div id="commentstatusdiv" class="postbox <?php echo postbox_classes('commentstatusdiv'); ?>">
<h3><?php _e('Discussion') ?></h3>
<div class="inside">
<input name="advanced_view" type="hidden" value="1" />
<label for="comment_status" class="selectit">
<input name="comment_status" type="checkbox" id="comment_status" value="open" <?php checked($post->comment_status, 'open'); ?> />
<?php _e('Allow Comments') ?></label>
<label for="ping_status" class="selectit"><input name="ping_status" type="checkbox" id="ping_status" value="open" <?php checked($post->ping_status, 'open'); ?> /> <?php _e('Allow Pings') ?></label>
</div>
</div>

<div id="passworddiv" class="postbox <?php echo postbox_classes('passworddiv'); ?>">
<h3><?php _e('Post Password') ?></h3>
<div class="inside">
<input name="post_password" type="text" size="13" id="post_password" value="<?php echo attribute_escape( $post->post_password ); ?>" />
</div>
</div>

<div id="slugdiv" class="postbox <?php echo postbox_classes('slugdiv'); ?>">
<h3><?php _e('Post Slug') ?></h3>
<div class="inside">
<input name="post_name" type="text" size="13" id="post_name" value="<?php echo attribute_escape( $post->post_name ); ?>" />
</div>
</div>

<div id="poststatusdiv" class="postbox <?php echo postbox_classes('poststatusdiv'); ?>">
<h3><?php _e('Post Status') ?></h3>
<div class="inside">
<?php if ( current_user_can('publish_posts') ) : ?>
<label for="post_status_publish" class="selectit"><input id="post_status_publish" name="post_status" type="radio" value="publish" <?php checked($post->post_status, 'publish'); checked($post->post_status, 'future'); ?> /> <?php _e('Published') ?></label>
<?php endif; ?>
<label for="post_status_pending" class="selectit"><input id="post_status_pending" name="post_status" type="radio" value="pending" <?php checked($post->post_status, 'pending'); ?> /> <?php _e('Pending Review') ?></label>
  <label for="post_status_draft" class="selectit"><input id="post_status_draft" name="post_status" type="radio" value="draft" <?php checked($post->post_status, 'draft'); ?> /> <?php _e('Draft') ?></label>
  <label for="post_status_private" class="selectit"><input id="post_status_private" name="post_status" type="radio" value="private" <?php checked($post->post_status, 'private'); ?> /> <?php _e('Private') ?></label>
</div>
</div>

<?php if ( current_user_can('edit_posts') ) : ?>
<div id="posttimestampdiv" class="postbox <?php echo postbox_classes('posttimestampdiv'); ?>">
<h3><?php _e('Post Timestamp'); ?></h3>
<div class="inside"><?php touch_time(($action == 'edit')); ?></div>
</div>
<?php endif; ?>

<?php
$authors = get_editable_user_ids( $current_user->id ); // TODO: ROLE SYSTEM
if ( $post->post_author && !in_array($post->post_author, $authors) )
	$authors[] = $post->post_author;
if ( $authors && count( $authors ) > 1 ) :
?>
<div id="authordiv" class="postbox <?php echo postbox_classes('authordiv'); ?>">
<h3><?php _e('Post Author'); ?></h3>
<div class="inside">
<?php wp_dropdown_users( array('include' => $authors, 'name' => 'post_author_override', 'selected' => empty($post_ID) ? $user_ID : $post->post_author) ); ?>
</div>
</div>
<?php endif; ?>

<?php do_action('dbx_post_sidebar'); ?>

</div>

<?php if ('edit' == $action) : $delete_nonce = wp_create_nonce( 'delete-post_' . $post_ID ); ?>
<input name="deletepost" class="button delete" type="submit" id="deletepost" tabindex="10" value="<?php echo ( 'draft' == $post->post_status ) ? __('Delete this draft') : __('Delete this post'); ?>" <?php echo "onclick=\"if ( confirm('" . js_escape(sprintf( ('draft' == $post->post_status) ? __("You are about to delete this draft '%s'\n  'Cancel' to stop, 'OK' to delete.") : __("You are about to delete this post '%s'\n  'Cancel' to stop, 'OK' to delete."), $post->post_title )) . "') ) { document.forms.post._wpnonce.value = '$delete_nonce'; return true;}return false;\""; ?> />
<?php endif; ?>

</div>

</div>

</form>
