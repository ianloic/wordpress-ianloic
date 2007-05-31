<?php

$wp_taxonomies = array();
$wp_taxonomies['category'] = (object) array('name' => 'category', 'object_type' => 'post', 'hierarchical' => true, 'update_count_callback' => '_update_post_term_count');
$wp_taxonomies['post_tag'] = (object) array('name' => 'post_tag', 'object_type' => 'post', 'hierarchical' => false, 'update_count_callback' => '_update_post_term_count');
$wp_taxonomies['link_category'] = (object) array('name' => 'link_category', 'object_type' => 'link', 'hierarchical' => false);

function is_taxonomy( $taxonomy ) {
	global $wp_taxonomies;

	return isset($wp_taxonomies[$taxonomy]);	
}

function get_taxonomy( $taxonomy ) {
	global $wp_taxonomies;

	if ( ! is_taxonomy($taxonomy) )
		return false;

	return $wp_taxonomies[$taxonomy];
}

function is_taxonomy_hierarchical($taxonomy) {
	if ( ! is_taxonomy($taxonomy) )
		return false;

	$taxonomy = get_taxonomy($taxonomy);
	return $taxonomy->hierarchical;
}

function register_taxonomy( $taxonomy, $object_type, $args = array() ) {
	global $wp_taxonomies;

	$defaults = array('hierarchical' => false, 'update_count_callback' => '');
	$args = wp_parse_args($args, $defaults);

	$args['name'] = $taxonomy;
	$args['object_type'] = $object_type;
	$wp_taxonomies[$taxonomy] = (object) $args;
}

function wp_count_terms( $taxonomy, $args = array() ) {
	global $wpdb;

	$defaults = array('ignore_empty' => false);
	$args = wp_parse_args($args, $defaults);
	extract($args);

	$where = '';
	if ( $ignore_empty )
		$where = 'AND count > 0';

	return $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE taxonomy = '$taxonomy' $where");
}

/**
 * Adds a new term to the database.  Optionally marks it as an alias of an existing term.
 * @param int|string $term The term to add or update.
 * @param string $taxonomy The taxonomy to which to add the term
 * @param int|string $alias_of The id or slug of the new term's alias.
 */
function wp_insert_term( $term, $taxonomy, $args = array() ) {
	global $wpdb;

	if ( ! is_taxonomy($taxonomy) )
		return new WP_Error('invalid_taxonomy', __('Invalid taxonomy'));

	$defaults = array( 'alias_of' => '', 'description' => '', 'parent' => 0, 'slug' => '');
	$args = wp_parse_args($args, $defaults);
	extract($args);

	$name = $term;
	$parent = (int) $parent;

	if ( empty($slug) )
		$slug = sanitize_title($name);
	else
		$slug = sanitize_title($slug);

	$term_group = 0;	
	if ( $alias_of ) {
		$alias = $wpdb->fetch_row("SELECT term_id, term_group FROM $wpdb->terms WHERE slug = '$alias_of'");
		if ( $alias->term_group ) {
			// The alias we want is already in a group, so let's use that one.
			$term_group = $alias->term_group;
		} else {
			// The alias isn't in a group, so let's create a new one and firstly add the alias term to it.
			$term_group = $wpdb->get_var("SELECT MAX(term_group) FROM $wpdb->terms GROUP BY term_group") + 1;
			$wpdb->query("UPDATE $wpdb->terms SET term_group = $term_group WHERE term_id = $alias->term_id");
		}
	}

	if ( ! $term_id = is_term($slug) ) {
		$wpdb->query("INSERT INTO $wpdb->terms (name, slug, term_group) VALUES ('$name', '$slug', '$term_group')");
		$term_id = (int) $wpdb->insert_id;
	}

	if ( empty($slug) ) {
		$slug = sanitize_title($slug, $term_id);
		$wpdb->query("UPDATE $wpdb->terms SET slug = '$slug' WHERE term_id = '$term_id'");
	}
		
	$tt_id = $wpdb->get_var("SELECT tt.term_taxonomy_id FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = '$taxonomy' AND t.term_id = $term_id");

	if ( !empty($tt_id) )
		return array('term_id' => $term_id, 'term_taxonomy_id' => $tt_id);

	$wpdb->query("INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ('$term_id', '$taxonomy', '$description', '$parent', '0')");
	$tt_id = (int) $wpdb->insert_id;

	do_action("create_term", $term_id, $tt_id);
	do_action("create_$taxonomy", $term_id, $tt_id);

	$term_id = apply_filters('term_id_filter', $term_id, $tt_id);

	clean_term_cache($term_id, $taxonomy);

	do_action("created_term", $term_id, $tt_id);
	do_action("created_$taxonomy", $term_id, $tt_id);

	return array('term_id' => $term_id, 'term_taxonomy_id' => $tt_id);
}

function wp_delete_object_term_relationships( $object_id, $taxonomies ) {
	global $wpdb;

	$object_id = (int) $object_id;

	if ( !is_array($taxonomies) )
		$taxonomies = array($taxonomies);

	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_object_terms($object_id, $taxonomy, 'fields=tt_ids');
		$in_terms = "'" . implode("', '", $terms) . "'";
		$wpdb->query("DELETE FROM $wpdb->term_relationships WHERE object_id = '$object_id' AND term_taxonomy_id IN ($in_terms)");

		wp_update_term_count($terms, $taxonomy);
	}
	
	// TODO clear the cache
}

/**
 * Removes a term from the database.
 */
function wp_delete_term( $term, $taxonomy, $args = array() ) {
	global $wpdb;

	$term = (int) $term;

	if ( ! $ids = is_term($term, $taxonomy) )
		return false;
	$tt_id = $ids['term_taxonomy_id'];

	$defaults = array();
	$args = wp_parse_args($args, $defaults);
	extract($args);

	if ( isset($default) ) {
		$default = (int) $default;
		if ( ! is_term($default, $taxonomy) )
			unset($default);
	}

	// Update children to point to new parent
	if ( is_taxonomy_hierarchical($taxonomy) ) {
		$term_obj = get_term($term, $taxonomy);
		$parent = $term_obj->parent;

		$wpdb->query("UPDATE $wpdb->term_taxonomy SET parent = '$parent' WHERE parent = '$term_obj->term_id' AND taxonomy = '$taxonomy'");
	}

	$objects = $wpdb->get_col("SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = '$tt_id'");

	foreach ( (array) $objects as $object ) {
		$terms = get_object_terms($object, $taxonomy, 'fields=ids');
		if ( 1 == count($terms) && isset($default) )
			$terms = array($default);
		else
			$terms = array_diff($terms, array($term));
		wp_set_object_terms($object, $terms, $taxonomy);
	}

	$wpdb->query("DELETE FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = '$tt_id'");

	// Delete the term if no taxonomies use it.
	if ( !$wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE term_id = '$term'") )
		$wpdb->query("DELETE FROM $wpdb->terms WHERE term_id = '$term'");

	clean_term_cache($term, $taxonomy);

	do_action("delete_$taxonomy", $term, $tt_id);

	return true;
}

function wp_update_term( $term, $taxonomy, $args = array() ) {
	global $wpdb;

	if ( ! is_taxonomy($taxonomy) )
		return new WP_Error('invalid_taxonomy', __('Invalid taxonomy'));

	$term_id = (int) $term;

	// First, get all of the original args
	$term = get_term ($term_id, $taxonomy, ARRAY_A);

	// Escape data pulled from DB.
	$term = add_magic_quotes($term);

	// Merge old and new args with new args overwriting old ones.
	$args = array_merge($term, $args);

	$defaults = array( 'alias_of' => '', 'description' => '', 'parent' => 0, 'slug' => '');
	$args = wp_parse_args($args, $defaults);
	extract($args);

	$parent = (int) $parent;

	if ( empty($slug) )
		$slug = sanitize_title($name);
	else
		$slug = sanitize_title($slug);

	$term_group = 0;	
	if ( $alias_of ) {
		$alias = $wpdb->fetch_row("SELECT term_id, term_group FROM $wpdb->terms WHERE slug = '$alias_of'");
		if ( $alias->term_group ) {
			// The alias we want is already in a group, so let's use that one.
			$term_group = $alias->term_group;
		} else {
			// The alias isn't in a group, so let's create a new one and firstly add the alias term to it.
			$term_group = $wpdb->get_var("SELECT MAX() term_group FROM $wpdb->terms GROUP BY term_group") + 1;
			$wpdb->query("UPDATE $wpdb->terms SET term_group = $term_group WHERE term_id = $alias->term_id");
		}
	}

	$wpdb->query("UPDATE $wpdb->terms SET name = '$name', slug = '$slug', term_group = '$term_group' WHERE term_id = '$term_id'");

	if ( empty($slug) ) {
		$slug = sanitize_title($slug, $term_id);
		$wpdb->query("UPDATE $wpdb->terms SET slug = '$slug' WHERE term_id = '$term_id'");
	}
		
	$tt_id = $wpdb->get_var("SELECT tt.term_taxonomy_id FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = '$taxonomy' AND t.term_id = $term_id");

	$wpdb->query("UPDATE $wpdb->term_taxonomy SET term_id = '$term_id', taxonomy = '$taxonomy', description = '$description', parent = '$parent', count = 0 WHERE term_taxonomy_id = '$tt_id'");

	do_action("edit_term", $term_id, $tt_id);
	do_action("edit_$taxonomy", $term_id, $tt_id);

	$term_id = apply_filters('term_id_filter', $term_id, $tt_id);

	clean_term_cache($term_id, $taxonomy);

	do_action("edited_term", $term_id, $tt_id);
	do_action("edited_$taxonomy", $term_id, $tt_id);

	return array('term_id' => $term_id, 'term_taxonomy_id' => $tt_id);
}

function wp_update_term_count( $terms, $taxonomy ) {
	global $wpdb;

	if ( empty($terms) )
		return false;

	if ( !is_array($terms) )
		$terms = array($terms);

	$terms = array_map('intval', $terms);

	$taxonomy = get_taxonomy($taxonomy);
	if ( isset($taxonomy->update_count_callback) )
		return call_user_func($taxonomy->update_count_callback, $terms);

	// Default count updater
	$count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = '$term'");
	$wpdb->query("UPDATE $wpdb->term_taxonomy SET count = '$count' WHERE term_taxonomy_id = '$term'");

	return true;
}

/**
 * Returns the index of a defined term, or 0 (false) if the term doesn't exist.
 */
function is_term($term, $taxonomy = '') {
	global $wpdb;

	if ( is_int($term) ) {
		$where = "t.term_id = '$term'";
	} else {
		if ( ! $term = sanitize_title($term) )
			return 0;
		$where = "t.slug = '$term'";
	}

	$term_id = $wpdb->get_var("SELECT term_id FROM $wpdb->terms as t WHERE $where");

	if ( empty($taxonomy) || empty($term_id) )
		return $term_id;

	return $wpdb->get_row("SELECT tt.term_id, tt.term_taxonomy_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy as tt ON tt.term_id = t.term_id WHERE $where AND tt.taxonomy = '$taxonomy'", ARRAY_A);
}
	
/**
 * Given an array of terms, returns those that are defined term slugs.  Ignores integers.
 * @param array $terms The term slugs to check for a definition.
 */
function get_defined_terms($terms) {
	global $wpdb;

	foreach ( $terms as $term ) {
		if ( !is_int($term) )
			$searches[] = $term;
	}

	$terms = "'" . implode("', '", $searches) . "'";
	return $wpdb->get_col("SELECT slug FROM $wpdb->terms WHERE slug IN ($terms)");
}
	
/**
 * Relates an object (post, link etc) to a term and taxonomy type.  Creates the term and taxonomy
 * relationship if it doesn't already exist.  Creates a term if it doesn't exist (using the slug).
 * @param int $object_id The object to relate to.
 * @param array|int|string $term The slug or id of the term.
 * @param array|string $taxonomy The context in which to relate the term to the object.
 */
function wp_set_object_terms($object_id, $terms, $taxonomy, $append = false) {
	global $wpdb;

	$object_id = (int) $object_id;

	if ( ! is_taxonomy($taxonomy) )
		return false;
	
	if ( !is_array($terms) )
		$terms = array($terms);

	if ( ! $append )
		$old_terms =  get_object_terms($object_id, $taxonomy, 'fields=tt_ids');

	$tt_ids = array();
	$term_ids = array();

	foreach ($terms as $term) {
		if ( !$id = is_term($term, $taxonomy) )
			$id = wp_insert_term($term, $taxonomy);
		$term_ids[] = $id['term_id'];
		$id = $id['term_taxonomy_id'];
		$tt_ids[] = $id;

		if ( $wpdb->get_var("SELECT term_taxonomy_id FROM $wpdb->term_relationships WHERE object_id = '$object_id' AND term_taxonomy_id = '$id'") )
			continue;
		$wpdb->query("INSERT INTO $wpdb->term_relationships (object_id, term_taxonomy_id) VALUES ('$object_id', '$id')");
	}

	wp_update_term_count($tt_ids, $taxonomy);

	if ( ! $append ) {
		$delete_terms = array_diff($old_terms, $tt_ids);
		if ( $delete_terms ) {
			$delete_terms = "'" . implode("', '", $delete_terms) . "'";
			$wpdb->query("DELETE FROM $wpdb->term_relationships WHERE term_taxonomy_id IN ($delete_terms)");
			$wpdb->query("UPDATE $wpdb->term_taxonomy SET count = count - 1 WHERE term_taxonomy_id IN ($delete_terms)");
			wp_update_term_count($delete_terms, $taxonomy);
		}
	}

	// TODO clean old_terms. Need term_id instead of tt_id
	clean_term_cache($term_ids, $taxonomy);

	return $tt_ids;
}

function get_objects_in_term( $terms, $taxonomies, $args = array() ) {
	global $wpdb;

	if ( !is_array( $terms) )
		$terms = array($terms);

	if ( !is_array($taxonomies) )
		$taxonomies = array($taxonomies);

	$defaults = array('order' => 'ASC');
	$args = wp_parse_args( $args, $defaults );
	extract($args);

	$terms = array_map('intval', $terms);

	$taxonomies = "'" . implode("', '", $taxonomies) . "'";
	$terms = "'" . implode("', '", $terms) . "'";

	$object_ids = $wpdb->get_col("SELECT tr.object_id FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN ($taxonomies) AND tt.term_id IN ($in_terms) ORDER BY tr.object_id $order");

	if ( ! $object_ids )
		return array();

	return $object_ids;
}

/**
 * Returns the terms associated with the given object(s), in the supplied taxonomies.
 * @param int|array $object_id The id of the object(s)) to retrieve for.
 * @param string|array $taxonomies The taxonomies to retrieve terms from.
 * @return array The requested term data.	 	 	 
 */
function get_object_terms($object_ids, $taxonomies, $args = array()) {
	global $wpdb;
	error_log("Objects: " . var_export($object_ids, true), 0);
	if ( !is_array($taxonomies) )
		$taxonomies = array($taxonomies);

	if ( !is_array($object_ids) )
		$object_ids = array($object_ids);
	$object_ids = array_map('intval', $object_ids);

	$defaults = array('orderby' => 'name', 'order' => 'ASC', 'fields' => 'all');
	$args = wp_parse_args( $args, $defaults );
	extract($args);

	if ( 'count' == $orderby )
		$orderby = 'tt.count';
	else if ( 'name' == $orderby )
		$orderby = 't.name';

	$taxonomies = "'" . implode("', '", $taxonomies) . "'";
	$object_ids = implode(', ', $object_ids);

	if ( 'all' == $fields )
		$select_this = 't.*, tt.*';
	else if ( 'ids' == $fields )
		$select_this = 't.term_id';
	else if ( 'all_with_object_id' == $fields )
		$select_this = 't.*, tt.*, tr.object_id';

	$query = "SELECT $select_this FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id INNER JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN ($taxonomies) AND tr.object_id IN ($object_ids) ORDER BY $orderby $order";

	if ( 'all' == $fields || 'all_with_object_id' == $fields ) {
		$terms = $wpdb->get_results($query);
		update_term_cache($terms);
	} else if ( 'ids' == $fields ) {
		$terms = $wpdb->get_col($query);
	} else if ( 'tt_ids' == $fields ) {
		$terms = $wpdb->get_col("SELECT tr.term_taxonomy_id FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tr.object_id IN ($object_ids) AND tt.taxonomy IN ($taxonomies) ORDER BY tr.term_taxonomy_id $order");
	}

	if ( ! $terms )
		return array();

	return $terms;
}

function &get_terms($taxonomies, $args = '') {
	global $wpdb;

	$single_taxonomy = false;
	if ( !is_array($taxonomies) ) {
		$single_taxonomy = true;
		$taxonomies = array($taxonomies);
	}

	$in_taxonomies = "'" . implode("', '", $taxonomies) . "'";

	$defaults = array('orderby' => 'name', 'order' => 'ASC',
		'hide_empty' => true, 'exclude' => '', 'include' => '',
		'number' => '', 'fields' => 'all', 'slug' => '', 'parent' => '',
		'hierarchical' => true, 'child_of' => 0, 'get' => '');
	$args = wp_parse_args( $args, $defaults );
	$args['number'] = (int) $args['number'];
	if ( ! $single_taxonomy ) {
		$args['child_of'] = 0;
		$args['hierarchical'] = false;
	} else if ( !is_taxonomy_hierarchical($taxonomies[0]) ) {
		$args['child_of'] = 0;
		$args['hierarchical'] = false;
	}
	if ( 'all' == $args['get'] ) {
		$args['child_of'] = 0;
		$args['hide_empty'] = 0;
		$args['hierarchical'] = false;
	}
	extract($args);

	if ( $child_of ) {
		$hierarchy = _get_term_hierarchy($taxonomies[0]);
		if ( !isset($hierarchy[$child_of]) )
			return array();
	}

	$key = md5( serialize( $args ) . serialize( $taxonomies ) );
	if ( $cache = wp_cache_get( 'get_terms', 'terms' ) ) {
		if ( isset( $cache[ $key ] ) )
			return apply_filters('get_terms', $cache[$key], $taxonomies, $args);
	}

	if ( 'count' == $orderby )
		$orderby = 'tt.count';
	else if ( 'name' == $orderby )
		$orderby = 't.name';
	else
		$orderby = 't.term_id';

	$where = '';
	$inclusions = '';
	if ( !empty($include) ) {
		$exclude = '';
		$interms = preg_split('/[\s,]+/',$include);
		if ( count($interms) ) {
			foreach ( $interms as $interm ) {
				if (empty($inclusions))
					$inclusions = ' AND ( t.term_id = ' . intval($interm) . ' ';
				else
					$inclusions .= ' OR t.term_id = ' . intval($interm) . ' ';
			}
		}
	}

	if ( !empty($inclusions) )
		$inclusions .= ')';
	$where .= $inclusions;

	$exclusions = '';
	if ( !empty($exclude) ) {
		$exterms = preg_split('/[\s,]+/',$exclude);
		if ( count($exterms) ) {
			foreach ( $exterms as $exterm ) {
				if (empty($exclusions))
					$exclusions = ' AND ( t.term_id <> ' . intval($exterm) . ' ';
				else
					$exclusions .= ' AND t.term_id <> ' . intval($exterm) . ' ';
			}
		}
	}

	if ( !empty($exclusions) )
		$exclusions .= ')';
	$exclusions = apply_filters('list_terms_exclusions', $exclusions, $args );
	$where .= $exclusions;

	if ( !empty($slug) ) {
		$slug = sanitize_title($slug);
		$where = " AND t.slug = '$slug'";
	}

	if ( !empty($parent) ) {
		$parent = (int) $parent;
		$where = " AND tt.parent = '$parent'";
	}

	if ( $hide_empty && !$hierarchical )
		$where .= ' AND tt.count > 0';

	if ( !empty($number) )
		$number = 'LIMIT ' . $number;
	else
		$number = '';

	if ( 'all' == $fields )
		$select_this = 't.*, tt.*';
	else if ( 'ids' == $fields )
		$select_this = 't.term_id';

	$query = "SELECT $select_this FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ($in_taxonomies) $where ORDER BY $orderby $order $number";

	if ( 'all' == $fields ) {
		$terms = $wpdb->get_results($query);
		update_term_cache($terms);
	} else if ( 'ids' == $fields ) {
		$terms = $wpdb->get_col($query);
	}

	if ( empty($terms) )
		return array();

	if ( $child_of || $hierarchical ) {
		$children = _get_term_hierarchy($taxonomies[0]);
		if ( ! empty($children) )
			$terms = & _get_term_children($child_of, $terms, $taxonomies[0]);
	}

	/*
	// Update category counts to include children.
	if ( $pad_counts )
		_pad_category_counts($type, $categories);

	// Make sure we show empty categories that have children.
	if ( $hierarchical && $hide_empty ) {
		foreach ( $categories as $k => $category ) {
			if ( ! $category->{'link' == $type ? 'link_count' : 'category_count'} ) {
				$children = _get_cat_children($category->cat_ID, $categories);
				foreach ( $children as $child )
					if ( $child->{'link' == $type ? 'link_count' : 'category_count'} )
						continue 2;

				// It really is empty
				unset($categories[$k]);
			}
		}
	}
	reset ( $categories );
	*/

	$cache[ $key ] = $terms;
	wp_cache_set( 'get_terms', $cache, 'term' );

	$terms = apply_filters('get_terms', $terms, $taxonomies, $args);
	return $terms;
}

function &get_term(&$term, $taxonomy, $output = OBJECT) {
	global $wpdb;

	if ( empty($term) )
		return null;

	if ( is_object($term) ) {
		wp_cache_add($term->term_id, $term, $taxonomy);
		$_term = $term;
	} else {
		$term = (int) $term;
		if ( ! $_term = wp_cache_get($term, $taxonomy) ) {
			$_term = $wpdb->get_row("SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = '$taxonomy' AND t.term_id = '$term' LIMIT 1");
			wp_cache_add($term, $_term, $taxonomy);
		}
	}

	$_term = apply_filters('get_term', $_term, $taxonomy);
	$_term = apply_filters("get_$taxonomy", $_term, $taxonomy);

	if ( $output == OBJECT ) {
		return $_term;
	} elseif ( $output == ARRAY_A ) {
		return get_object_vars($_term);
	} elseif ( $output == ARRAY_N ) {
		return array_values(get_object_vars($_term));
	} else {
		return $_term;
	}
}

function get_term_by($field, $value, $taxonomy, $output = OBJECT) {
	global $wpdb;

	if ( ! is_taxonomy($taxonomy) )
		return false;

	if ( 'slug' == $field ) {
		$field = 't.slug';
		$value = sanitize_title($field);
		if ( empty($value) )
			return false;
	} else if ( 'name' == $field ) {
		// Assume already escaped
		$field = 't.name';
	} else {
		$field = 't.term_id';
		$value = (int) $value;
	}

	$term = $wpdb->get_row("SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = '$taxonomy' AND $field = '$value' LIMIT 1");
	if ( !$term )
		return false;

	wp_cache_add($term->term_id, $term, $taxonomy);

	if ( $output == OBJECT ) {
		return $term;
	} elseif ( $output == ARRAY_A ) {
		return get_object_vars($term);
	} elseif ( $output == ARRAY_N ) {
		return array_values(get_object_vars($term));
	} else {
		return $term;
	}
}

function get_term_children( $term, $taxonomy ) {
	$terms = _get_term_hierarchy($taxonomy);

	if ( ! isset($terms[$term]) )
		return array();

	$children = $terms[$term];

	foreach ( $terms[$term] as $child ) {
		if ( isset($terms[$child]) )
			$children = array_merge($children, get_term_children($child, $taxonomy));
	}

	return $children;
}

function update_term_cache($terms, $taxonomy = '') {
	foreach ( $terms as $term ) {
		$term_taxonomy = $taxonomy;
		if ( empty($term_taxonomy) )
			$term_taxonomy = $term->taxonomy;

		wp_cache_add($term->term_id, $term, $term_taxonomy);
	}
}

function clean_term_cache($ids, $taxonomy) {
	if ( !is_array($ids) )
		$ids = array($ids);

	foreach ( $ids as $id ) {
		wp_cache_delete($id, $taxonomy);
	}

	wp_cache_delete('all_ids', $taxonomy);
	wp_cache_delete('get', $taxonomy);
	delete_option("{$taxonomy}_children");
	wp_cache_delete('get_terms', 'terms');
}

function _get_term_hierarchy($taxonomy) {
	// TODO Make sure taxonomy is hierarchical
	$children = get_option("{$taxonomy}_children");
	if ( is_array($children) )
		return $children;

	$children = array();
	$terms = get_terms($taxonomy, 'get=all');
	foreach ( $terms as $term ) {
		if ( $term->parent > 0 )
			$children[$term->parent][] = $term->term_id;
	}
	update_option("{$taxonomy}_children", $children);

	return $children;
}

function &_get_term_children($term_id, $terms, $taxonomy) {
	if ( empty($terms) )
		return array();

	$term_list = array();
	$has_children = _get_term_hierarchy($taxonomy);

	if  ( ( 0 != $term_id ) && ! isset($has_children[$term_id]) )
		return array();

	foreach ( $terms as $term ) {
		$use_id = false;
		if ( !is_object($term) ) {
			$term = get_term($term, $taxonomy);
			$use_id = true;
		}

		if ( $term->term_id == $term_id )
			continue;

		if ( $term->parent == $term_id ) {
			if ( $use_id )
				$term_list[] = $term->term_id;
			else
				$term_list[] = $term;

			if ( !isset($has_children[$term->term_id]) )
				continue;

			if ( $children = _get_term_children($term->term_id, $terms, $taxonomy) )
				$term_list = array_merge($term_list, $children);
		}
	}

	return $term_list;
}

//
// Default callbacks
//

function _update_post_term_count( $terms ) {
	global $wpdb;

	foreach ( $terms as $term ) {
		$count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status = 'publish' AND post_type = 'post' AND term_taxonomy_id = '$term'");
		$wpdb->query("UPDATE $wpdb->term_taxonomy SET count = '$count' WHERE term_taxonomy_id = '$term'");
	}
}

?>