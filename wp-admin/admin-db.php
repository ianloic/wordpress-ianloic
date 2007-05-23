<?php

function get_users_drafts( $user_id ) {
	global $wpdb;
	$user_id = (int) $user_id;
	$query = "SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'draft' AND post_author = $user_id ORDER BY ID DESC";
	$query = apply_filters('get_users_drafts', $query);
	return $wpdb->get_results( $query );
}

function get_others_drafts( $user_id ) {
	global $wpdb;
	$user = get_userdata( $user_id );
	$level_key = $wpdb->prefix . 'user_level';

	$editable = get_editable_user_ids( $user_id );

	if( !$editable ) {
		$other_drafts = '';
	} else {
		$editable = join(',', $editable);
		$other_drafts = $wpdb->get_results("SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'draft' AND post_author IN ($editable) AND post_author != '$user_id' ");
	}

	return apply_filters('get_others_drafts', $other_drafts);
}

function get_editable_authors( $user_id ) {
	global $wpdb;

	$editable = get_editable_user_ids( $user_id );

	if( !$editable ) {
		return false;
	} else {
		$editable = join(',', $editable);
		$authors = $wpdb->get_results( "SELECT * FROM $wpdb->users WHERE ID IN ($editable) ORDER BY display_name" );
	}

	return apply_filters('get_editable_authors', $authors);
}

function get_editable_user_ids( $user_id, $exclude_zeros = true ) {
	global $wpdb;

	$user = new WP_User( $user_id );

	if ( ! $user->has_cap('edit_others_posts') ) {
		if ( $user->has_cap('edit_posts') || $exclude_zeros == false )
			return array($user->id);
		else
			return false;
	}

	$level_key = $wpdb->prefix . 'user_level';

	$query = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '$level_key'";
	if ( $exclude_zeros )
		$query .= " AND meta_value != '0'";

	return $wpdb->get_col( $query );
}

function get_author_user_ids() {
	global $wpdb;
	$level_key = $wpdb->prefix . 'user_level';

	$query = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '$level_key' AND meta_value != '0'";

	return $wpdb->get_col( $query );
}

function get_nonauthor_user_ids() {
	global $wpdb;
	$level_key = $wpdb->prefix . 'user_level';

	$query = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '$level_key' AND meta_value = '0'";

	return $wpdb->get_col( $query );
}

function wp_insert_category($catarr) {
	global $wpdb;

	extract($catarr);

	if ( trim( $cat_name ) == '' )
		return 0;

	$cat_ID = (int) $cat_ID;

	// Are we updating or creating?
	if ( !empty ($cat_ID) )
		$update = true;
	else
		$update = false;

	$name = $cat_name;
	$description = $category_description;
	$slug = $category_nicename;
	$parent = $category_parent;

	$name = apply_filters('pre_category_name', $name);

	if ( empty ($slug) )
		$slug = sanitize_title($slug);
	else
		$slug = sanitize_title($slug);
	$slug = apply_filters('pre_category_nicename', $slug);

	if ( empty ($description) )
		$description = '';
	$description = apply_filters('pre_category_description', $description);

	$parent = (int) $parent;
	if ( empty($parent) || !get_category( $parent ) || ($cat_ID && cat_is_ancestor_of($cat_ID, $parent) ) )
		$parent = 0;

	$args = compact('slug', 'parent', 'description');

	if ( $update )
		$cat_ID = wp_update_term($cat_ID, 'category', $args);
	else
		$cat_ID = wp_insert_term($cat_name, 'category', $args);

	return $cat_ID['term_id'];
}

function wp_update_category($catarr) {
	global $wpdb;

	$cat_ID = (int) $catarr['cat_ID'];

	if( $cat_ID == $catarr['category_parent'] )
		return false;

	// First, get all of the original fields
	$category = get_category($cat_ID, ARRAY_A);

	// Escape data pulled from DB.
	$category = add_magic_quotes($category);

	// Merge old and new fields with new fields overwriting old ones.
	$catarr = array_merge($category, $catarr);

	return wp_insert_category($catarr);
}

function wp_delete_category($cat_ID) {
	global $wpdb;

	$cat_ID = (int) $cat_ID;
	$default = get_option('default_category');

	// Don't delete the default cat
	if ( $cat_ID == $default )
		return 0;

	return wp_delete_term($cat_ID, 'category', "default=$default");
}

function wp_create_category($cat_name) {
	if ( $id = category_exists($cat_name) )
		return $id;

	return wp_insert_category( array('cat_name' => $cat_name) );
}

function wp_create_categories($categories, $post_id = '') {
	$cat_ids = array ();
	foreach ($categories as $category) {
		if ($id = category_exists($category))
			$cat_ids[] = $id;
		else
			if ($id = wp_create_category($category))
				$cat_ids[] = $id;
	}

	if ($post_id)
		wp_set_post_categories($post_id, $cat_ids);

	return $cat_ids;
}

function category_exists($cat_name) {
	return is_term($cat_name, 'category');
}

function tag_exists($tag_name) {
	return is_term($tag_name, 'post_tag');
}

function wp_create_tag($tag_name) {
	if ( $id = tag_exists($tag_name) )
		return $id;

	return wp_insert_term($tag_name, 'post_tag');	
}

function wp_delete_user($id, $reassign = 'novalue') {
	global $wpdb;

	$id = (int) $id;
	$user = get_userdata($id);

	if ($reassign == 'novalue') {
		$post_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_author = $id");

		if ($post_ids) {
			foreach ($post_ids as $post_id)
				wp_delete_post($post_id);
		}

		// Clean links
		$wpdb->query("DELETE FROM $wpdb->links WHERE link_owner = $id");
	} else {
		$reassign = (int) $reassign;
		$wpdb->query("UPDATE $wpdb->posts SET post_author = {$reassign} WHERE post_author = {$id}");
		$wpdb->query("UPDATE $wpdb->links SET link_owner = {$reassign} WHERE link_owner = {$id}");
	}

	// FINALLY, delete user
	do_action('delete_user', $id);

	$wpdb->query("DELETE FROM $wpdb->users WHERE ID = $id");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE user_id = '$id'");

	wp_cache_delete($id, 'users');
	wp_cache_delete($user->user_login, 'userlogins');

	return true;
}

function wp_revoke_user($id) {
	$id = (int) $id;

	$user = new WP_User($id);
	$user->remove_all_caps();
}

function wp_insert_link($linkdata) {
	global $wpdb, $current_user;

	extract($linkdata);

	$update = false;

	if ( !empty($link_id) )
		$update = true;

	$link_id = (int) $link_id;

	if( trim( $link_name ) == '' )
		return 0;
	$link_name = apply_filters('pre_link_name', $link_name);

	if( trim( $link_url ) == '' )
		return 0;
	$link_url = apply_filters('pre_link_url', $link_url);

	if ( empty($link_rating) )
		$link_rating = 0;
	else
		$link_rating = (int) $link_rating;

	if ( empty($link_image) )
		$link_image = '';
	$link_image = apply_filters('pre_link_image', $link_image);

	if ( empty($link_target) )
		$link_target = '';
	$link_target = apply_filters('pre_link_target', $link_target);

	if ( empty($link_visible) )
		$link_visible = 'Y';
	$link_visibile = preg_replace('/[^YNyn]/', '', $link_visible);

	if ( empty($link_owner) )
		$link_owner = $current_user->id;
	else
		$link_owner = (int) $link_owner;

	if ( empty($link_notes) )
		$link_notes = '';
	$link_notes = apply_filters('pre_link_notes', $link_notes);

	if ( empty($link_description) )
		$link_description = '';
	$link_description = apply_filters('pre_link_description', $link_description);

	if ( empty($link_rss) )
		$link_rss = '';
	$link_rss = apply_filters('pre_link_rss', $link_rss);

	if ( empty($link_rel) )
		$link_rel = '';
	$link_rel = apply_filters('pre_link_rel', $link_rel);

	// Make sure we set a valid category
	if (0 == count($link_category) || !is_array($link_category)) {
		$link_category = array(get_option('default_link_category'));
	}

	if ( $update ) {
		$wpdb->query("UPDATE $wpdb->links SET link_url='$link_url',
			link_name='$link_name', link_image='$link_image',
			link_target='$link_target',
			link_visible='$link_visible', link_description='$link_description',
			link_rating='$link_rating', link_rel='$link_rel',
			link_notes='$link_notes', link_rss = '$link_rss'
			WHERE link_id='$link_id'");
	} else {
		$wpdb->query("INSERT INTO $wpdb->links (link_url, link_name, link_image, link_target, link_description, link_visible, link_owner, link_rating, link_rel, link_notes, link_rss) VALUES('$link_url','$link_name', '$link_image', '$link_target', '$link_description', '$link_visible', '$link_owner', '$link_rating', '$link_rel', '$link_notes', '$link_rss')");
		$link_id = (int) $wpdb->insert_id;
	}

	wp_set_link_cats($link_id, $link_category);

	if ( $update )
		do_action('edit_link', $link_id);
	else
		do_action('add_link', $link_id);

	return $link_id;
}

function wp_update_link($linkdata) {
	global $wpdb;

	$link_id = (int) $linkdata['link_id'];

	$link = get_link($link_id, ARRAY_A);

	// Escape data pulled from DB.
	$link = add_magic_quotes($link);

	// Passed link category list overwrites existing category list if not empty.
	if ( isset($linkdata['link_category']) && is_array($linkdata['link_category'])
			 && 0 != count($linkdata['link_category']) )
		$link_cats = $linkdata['link_category'];
	else
		$link_cats = $link['link_category'];

	// Merge old and new fields with new fields overwriting old ones.
	$linkdata = array_merge($link, $linkdata);
	$linkdata['link_category'] = $link_cats;

	return wp_insert_link($linkdata);
}

function wp_delete_link($link_id) {
	global $wpdb;

	do_action('delete_link', $link_id);

	$categories = wp_get_link_cats($link_id);
	if( is_array( $categories ) ) {
		foreach ( $categories as $category ) {
			$wpdb->query("UPDATE $wpdb->categories SET link_count = link_count - 1 WHERE cat_ID = '$category'");
			wp_cache_delete($category, 'category');
			do_action('edit_category', $cat_id);
		}
	}

	$wpdb->query("DELETE FROM $wpdb->link2cat WHERE link_id = '$link_id'");
	return $wpdb->query("DELETE FROM $wpdb->links WHERE link_id = '$link_id'");
	
	do_action('deleted_link', $link_id);
}

function wp_get_link_cats($link_id = 0) {

	$cats = get_object_terms($link_id, 'link_category', 'get=ids');

	return array_unique($cats);
}

function wp_set_link_cats($link_id = 0, $link_categories = array()) {
	// If $link_categories isn't already an array, make it one:
	if (!is_array($link_categories) || 0 == count($link_categories))
		$link_categories = array(get_option('default_link_category'));

	$link_categories = array_map('intval', $link_categories);
	$link_categories = array_unique($link_categories);

	wp_set_object_terms($link_id, $link_categories, 'link_category');
}	// wp_set_link_cats()

function post_exists($title, $content = '', $post_date = '') {
	global $wpdb;

	if (!empty ($post_date))
		$post_date = "AND post_date = '$post_date'";

	if (!empty ($title))
		return $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_title = '$title' $post_date");
	else
		if (!empty ($content))
			return $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_content = '$content' $post_date");

	return 0;
}

function comment_exists($comment_author, $comment_date) {
	global $wpdb;

	return $wpdb->get_var("SELECT comment_post_ID FROM $wpdb->comments
			WHERE comment_author = '$comment_author' AND comment_date = '$comment_date'");
}

?>
