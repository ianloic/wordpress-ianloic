<?php
/**
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 */

//
// Taxonomy Registration
//

/**
 * Default Taxonomy Objects
 * @since 2.3
 * @global array $wp_taxonomies
 */
$wp_taxonomies = array();
$wp_taxonomies['category'] = (object) array('name' => 'category', 'object_type' => 'post', 'hierarchical' => true, 'update_count_callback' => '_update_post_term_count');
$wp_taxonomies['post_tag'] = (object) array('name' => 'post_tag', 'object_type' => 'post', 'hierarchical' => false, 'update_count_callback' => '_update_post_term_count');
$wp_taxonomies['link_category'] = (object) array('name' => 'link_category', 'object_type' => 'link', 'hierarchical' => false);

/**
 * get_object_taxonomies() - Return all of the taxonomy names that are of $object_type
 *
 * It appears that this function can be used to find all of the names inside of
 * $wp_taxonomies global variable.
 *
 * <code><?php $taxonomies = get_object_taxonomies('post'); ?></code>
 * Should result in <code>Array('category', 'post_tag')</code>
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 * 
 * @uses $wp_taxonomies
 *
 * @param string $object_type Name of the type of taxonomy object
 * @return array The names of all taxonomy of $object_type.
 */
function get_object_taxonomies($object_type) {
	global $wp_taxonomies;

	$taxonomies = array();
	foreach ( $wp_taxonomies as $taxonomy ) {
		if ( $object_type == $taxonomy->object_type )
			$taxonomies[] = $taxonomy->name;
	}

	return $taxonomies;
}

/**
 * get_taxonomy() - Returns the taxonomy object of $taxonomy.
 *
 * The get_taxonomy function will first check that the parameter string given
 * is a taxonomy object and if it is, it will return it.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @uses $wp_taxonomies
 * @uses is_taxonomy() Checks whether taxonomy exists
 *
 * @param string $taxonomy Name of taxonomy object to return
 * @return object|bool The Taxonomy Object or false if $taxonomy doesn't exist
 */
function get_taxonomy( $taxonomy ) {
	global $wp_taxonomies;

	if ( ! is_taxonomy($taxonomy) )
		return false;

	return $wp_taxonomies[$taxonomy];
}

/**
 * is_taxonomy() - Checks that the taxonomy name exists
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 * 
 * @uses $wp_taxonomies
 *
 * @param string $taxonomy Name of taxonomy object
 * @return bool Whether the taxonomy exists or not.
 */
function is_taxonomy( $taxonomy ) {
	global $wp_taxonomies;

	return isset($wp_taxonomies[$taxonomy]);
}

/**
 * is_taxonomy_hierarchical() - Whether the taxonomy object is hierarchical
 *
 * Checks to make sure that the taxonomy is an object first. Then Gets the object, and finally
 * returns the hierarchical value in the object.
 *
 * A false return value might also mean that the taxonomy does not exist.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @uses is_taxonomy() Checks whether taxonomy exists
 * @uses get_taxonomy() Used to get the taxonomy object
 *
 * @param string $taxonomy Name of taxonomy object
 * @return bool Whether the taxonomy is hierarchical
 */
function is_taxonomy_hierarchical($taxonomy) {
	if ( ! is_taxonomy($taxonomy) )
		return false;

	$taxonomy = get_taxonomy($taxonomy);
	return $taxonomy->hierarchical;
}

/**
 * register_taxonomy() - Create or modify a taxonomy object.
 *
 * A simple function for creating or modifying a taxonomy object based on the parameters given.
 * The function will accept an array (third optional parameter), along with strings for the
 * taxonomy name and another string for the object type.
 *
 * The function keeps a default set, allowing for the $args to be optional but allow the other
 * functions to still work. It is possible to overwrite the default set, which contains two
 * keys: hierarchical and update_count_callback.
 *
 * Nothing is returned, so expect error maybe or use is_taxonomy() to check whether taxonomy exists.
 *
 * Optional $args contents:
 * hierarachical - has some defined purpose at other parts of the API and is a boolean value.
 * update_count_callback - works much like a hook, in that it will be called when the count is updated.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 * @uses $wp_taxonomies Inserts new taxonomy object into the list
 * 
 * @param string $taxonomy Name of taxonomy object
 * @param string $object_type Name of the object type for the taxonomy object.
 * @param array|string $args See above description for the two keys values.
 */
function register_taxonomy( $taxonomy, $object_type, $args = array() ) {
	global $wp_taxonomies;

	$defaults = array('hierarchical' => false, 'update_count_callback' => '');
	$args = wp_parse_args($args, $defaults);

	$args['name'] = $taxonomy;
	$args['object_type'] = $object_type;
	$wp_taxonomies[$taxonomy] = (object) $args;
}

//
// Term API
//

/**
 * get_objects_in_term() - Return object_ids of valid taxonomy and term
 *
 * The strings of $taxonomies must exist before this function will continue. On failure of finding
 * a valid taxonomy, it will return an WP_Error class, kind of like Exceptions in PHP 5, except you
 * can't catch them. Even so, you can still test for the WP_Error class and get the error message.
 *
 * The $terms aren't checked the same as $taxonomies, but still need to exist for $object_ids to
 * be returned.
 *
 * It is possible to change the order that object_ids is returned by either using PHP sort family
 * functions or using the database by using $args with either ASC or DESC array. The value should
 * be in the key named 'order'.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @uses $wpdb
 * @uses wp_parse_args() Creates an array from string $args.
 *
 * @param string|array $terms String of term or array of string values of terms that will be used
 * @param string|array $taxonomies String of taxonomy name or Array of string values of taxonomy names
 * @param array|string $args Change the order of the object_ids, either ASC or DESC
 * @return WP_Error|array If the taxonomy does not exist, then WP_Error will be returned. On success
 *	the array can be empty meaning that there are no $object_ids found or it will return the $object_ids found.
 */
function get_objects_in_term( $terms, $taxonomies, $args = array() ) {
	global $wpdb;

	if ( !is_array( $terms) )
		$terms = array($terms);

	if ( !is_array($taxonomies) )
		$taxonomies = array($taxonomies);

	foreach ( $taxonomies as $taxonomy ) {
		if ( ! is_taxonomy($taxonomy) )
			return new WP_Error('invalid_taxonomy', __('Invalid Taxonomy'));
	}

	$defaults = array('order' => 'ASC');
	$args = wp_parse_args( $args, $defaults );
	extract($args, EXTR_SKIP);

	$order = ( 'desc' == strtolower($order) ) ? 'DESC' : 'ASC';

	$terms = array_map('intval', $terms);

	$taxonomies = "'" . implode("', '", $taxonomies) . "'";
	$terms = "'" . implode("', '", $terms) . "'";

	$object_ids = $wpdb->get_col("SELECT tr.object_id FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN ($taxonomies) AND tt.term_id IN ($terms) ORDER BY tr.object_id $order");

	if ( ! $object_ids )
		return array();

	return $object_ids;
}

/**
 * get_term() - Get all Term data from database by Term ID.
 *
 * The usage of the get_term function is to apply filters to a term object.
 * It is possible to get a term object from the database before applying the
 * filters.
 *
 * $term ID must be part of $taxonomy, to get from the database. Failure, might be
 * able to be captured by the hooks. Failure would be the same value as $wpdb returns for the
 * get_row method.
 *
 * There are two hooks, one is specifically for each term, named 'get_term', and the second is
 * for the taxonomy name, 'term_$taxonomy'. Both hooks gets the term object, and the taxonomy
 * name as parameters. Both hooks are expected to return a Term object.
 *
 * 'get_term' hook - Takes two parameters the term Object and the taxonomy name. Must return
 * term object. Used in @see get_term() as a catch-all filter for every $term.
 *
 * 'get_$taxonomy' hook - Takes two parameters the term Object and the taxonomy name. Must return
 * term object. $taxonomy will be the taxonomy name, so for example, if 'category', it would be
 * 'get_category' as the filter name. Useful for custom taxonomies or plugging into default taxonomies.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @uses $wpdb
 *
 * @param int|object $term If integer, will get from database. If object will apply filters and return $term.
 * @param string $taxonomy Taxonomy name that $term is part of.
 * @param string $output Constant OBJECT, ARRAY_A, or ARRAY_N
 * @param string $filter {@internal Missing Description}}
 * @return mixed|null|WP_Error Term Row from database. Will return null if $term is empty. If taxonomy does not
 * exist then WP_Error will be returned.
 */
function &get_term($term, $taxonomy, $output = OBJECT, $filter = 'raw') {
	global $wpdb;

	if ( empty($term) )
		return null;

	if ( ! is_taxonomy($taxonomy) )
		return new WP_Error('invalid_taxonomy', __('Invalid Taxonomy'));

	if ( is_object($term) ) {
		wp_cache_add($term->term_id, $term, $taxonomy);
		$_term = $term;
	} else {
		$term = (int) $term;
		if ( ! $_term = wp_cache_get($term, $taxonomy) ) {
			$_term = $wpdb->get_row( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND t.term_id = %s LIMIT 1", $taxonomy, $term) );
			wp_cache_add($term, $_term, $taxonomy);
		}
	}
	
	$_term = apply_filters('get_term', $_term, $taxonomy);
	$_term = apply_filters("get_$taxonomy", $_term, $taxonomy);
	$_term = sanitize_term($_term, $taxonomy, $filter);

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

/**
 * get_term_by() - Get all Term data from database by Term field and data.
 *
 * Warning: $value is not escaped for 'name' $field. You must do it yourself, if required.
 *
 * The default $field is 'id', therefore it is possible to also use null for field, but not
 * recommended that you do so.
 *
 * If $value does not exist, the return value will be false. If $taxonomy exists and $field
 * and $value combinations exist, the Term will be returned.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @uses $wpdb
 *
 * @param string $field Either 'slug', 'name', or 'id'
 * @param string|int $value Search for this term value
 * @param string $taxonomy Taxonomy Name
 * @param string $output Constant OBJECT, ARRAY_A, or ARRAY_N
 * @param string $filter {@internal Missing Description}}
 * @return mixed Term Row from database. Will return false if $taxonomy does not exist or $term was not found.
 */
function get_term_by($field, $value, $taxonomy, $output = OBJECT, $filter = 'raw') {
	global $wpdb;

	if ( ! is_taxonomy($taxonomy) )
		return false;

	if ( 'slug' == $field ) {
		$field = 't.slug';
		$value = sanitize_title($value);
		if ( empty($value) )
			return false;
	} else if ( 'name' == $field ) {
		// Assume already escaped
		$field = 't.name';
	} else {
		$field = 't.term_id';
		$value = (int) $value;
	}

	$term = $wpdb->get_row( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND $field = %s LIMIT 1", $taxonomy, $value) );
	if ( !$term )
		return false;

	wp_cache_add($term->term_id, $term, $taxonomy);

	$term = sanitize_term($term, $taxonomy, $filter);

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

/**
 * get_term_children() - Merge all term children into a single array.
 *
 * This recursive function will merge all of the children of $term into
 * the same array. Only useful for taxonomies which are hierarchical.
 *
 * Will return an empty array if $term does not exist in $taxonomy.
 * 
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @uses $wpdb
 * @uses _get_term_hierarchy()
 * @uses get_term_children() Used to get the children of both $taxonomy and the parent $term
 *
 * @param string $term Name of Term to get children
 * @param string $taxonomy Taxonomy Name
 * @return array|WP_Error List of Term Objects. WP_Error returned if $taxonomy does not exist
 */
function get_term_children( $term, $taxonomy ) {
	if ( ! is_taxonomy($taxonomy) )
		return new WP_Error('invalid_taxonomy', __('Invalid Taxonomy'));

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

/**
 * get_term_field() - Get sanitized Term field
 * 
 * Does checks for $term, based on the $taxonomy. The function is for
 * contextual reasons and for simplicity of usage. @see sanitize_term_field() for
 * more information.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @uses sanitize_term_field() Passes the return value in sanitize_term_field on success.
 *
 * @param string $field Term field to fetch
 * @param int $term Term ID
 * @param string $taxonomy Taxonomy Name
 * @param string $context {@internal Missing Description}}
 * @return mixed Will return an empty string if $term is not an object or if $field is not set in $term.
 */
function get_term_field( $field, $term, $taxonomy, $context = 'display' ) {
	$term = (int) $term;
	$term = get_term( $term, $taxonomy );
	if ( is_wp_error($term) )
		return $term;

	if ( !is_object($term) )
		return '';

	if ( !isset($term->$field) )
		return '';

	return sanitize_term_field($field, $term->$field, $term->term_id, $taxonomy, $context);
}

/**
 * get_term_to_edit() - Sanitizes Term for editing
 *
 * Return value is @see sanitize_term() and usage is for sanitizing the term
 * for editing. Function is for contextual and simplicity.
 * 
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @uses sanitize_term() Passes the return value on success
 *
 * @param int|object $id Term ID or Object
 * @param string $taxonomy Taxonomy Name
 * @return mixed|null|WP_Error Will return empty string if $term is not an object.
 */
function get_term_to_edit( $id, $taxonomy ) {
	$term = get_term( $id, $taxonomy );

	if ( is_wp_error($term) )
		return $term;

	if ( !is_object($term) )
		return '';

	return sanitize_term($term, $taxonomy, 'edit');
}

/**
 * get_terms() - Retrieve the terms in taxonomy or list of taxonomies.
 *
 * You can fully inject any customizations to the query before it is sent, as well as control
 * the output with a filter.
 *
 * The 'get_terms' filter will be called when the cache has the term and will pass the found
 * term along with the array of $taxonomies and array of $args. This filter is also called
 * before the array of terms is passed and will pass the array of terms, along with the $taxonomies
 * and $args.
 *
 * The 'list_terms_exclusions' filter passes the compiled exclusions along with the $args.
 *
 * The list that $args can contain, which will overwrite the defaults.
 * orderby - Default is 'name'. Can be name, count, or nothing (will use term_id).
 * order - Default is ASC. Can use DESC.
 * hide_empty - Default is true. Will not return empty $terms.
 * fields - Default is all.
 * slug - Any terms that has this value. Default is empty string.
 * hierarchical - Whether to return hierarchical taxonomy. Default is true.
 * name__like - Default is empty string.
 *
 * The argument 'pad_counts' will count all of the children along with the $terms.
 *
 * The 'get' argument allows for overwriting 'hide_empty' and 'child_of', which can be done by
 * setting the value to 'all', instead of its default empty string value.
 *
 * The 'child_of' argument will be used if you use multiple taxonomy or the first $taxonomy
 * isn't hierarchical or 'parent' isn't used. The default is 0, which will be translated to
 * a false value. If 'child_of' is set, then 'child_of' value will be tested against
 * $taxonomy to see if 'child_of' is contained within. Will return an empty array if test
 * fails.
 *
 * If 'parent' is set, then it will be used to test against the first taxonomy. Much like
 * 'child_of'. Will return an empty array if the test fails.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @uses $wpdb
 * @uses wp_parse_args() Merges the defaults with those defined by $args and allows for strings.
 *
 * @param string|array Taxonomy name or list of Taxonomy names
 * @param string|array $args The values of what to search for when returning terms
 * @return array|WP_Error List of Term Objects and their children. Will return WP_Error, if any of $taxonomies do not exist.
 */
function &get_terms($taxonomies, $args = '') {
	global $wpdb;

	$single_taxonomy = false;
	if ( !is_array($taxonomies) ) {
		$single_taxonomy = true;
		$taxonomies = array($taxonomies);
	}

	foreach ( $taxonomies as $taxonomy ) {
		if ( ! is_taxonomy($taxonomy) )
			return new WP_Error('invalid_taxonomy', __('Invalid Taxonomy'));
	}

	$in_taxonomies = "'" . implode("', '", $taxonomies) . "'";

	$defaults = array('orderby' => 'name', 'order' => 'ASC',
		'hide_empty' => true, 'exclude' => '', 'include' => '',
		'number' => '', 'fields' => 'all', 'slug' => '', 'parent' => '',
		'hierarchical' => true, 'child_of' => 0, 'get' => '', 'name__like' => '',
		'pad_counts' => false);
	$args = wp_parse_args( $args, $defaults );
	$args['number'] = absint( $args['number'] );
	if ( !$single_taxonomy || !is_taxonomy_hierarchical($taxonomies[0]) ||
		'' != $args['parent'] ) {
		$args['child_of'] = 0;
		$args['hierarchical'] = false;
		$args['pad_counts'] = false;
	}

	if ( 'all' == $args['get'] ) {
		$args['child_of'] = 0;
		$args['hide_empty'] = 0;
		$args['hierarchical'] = false;
		$args['pad_counts'] = false;
	}
	extract($args, EXTR_SKIP);

	if ( $child_of ) {
		$hierarchy = _get_term_hierarchy($taxonomies[0]);
		if ( !isset($hierarchy[$child_of]) )
			return array();
	}

	if ( $parent ) {
		$hierarchy = _get_term_hierarchy($taxonomies[0]);
		if ( !isset($hierarchy[$parent]) )
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
		$where .= " AND t.slug = '$slug'";
	}

	if ( !empty($name__like) )
		$where .= " AND t.name LIKE '{$name__like}%'";

	if ( '' != $parent ) {
		$parent = (int) $parent;
		$where .= " AND tt.parent = '$parent'";
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
	else if ( 'names' == $fields )
		$select_this == 't.name';

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

	// Update term counts to include children.
	if ( $pad_counts )
		_pad_term_counts($terms, $taxonomies[0]);

	// Make sure we show empty categories that have children.
	if ( $hierarchical && $hide_empty ) {
		foreach ( $terms as $k => $term ) {
			if ( ! $term->count ) {
				$children = _get_term_children($term->term_id, $terms, $taxonomies[0]);
				foreach ( $children as $child )
					if ( $child->count )
						continue 2;

				// It really is empty
				unset($terms[$k]);
			}
		}
	}
	reset ( $terms );

	$cache[ $key ] = $terms;
	wp_cache_set( 'get_terms', $cache, 'terms' );

	$terms = apply_filters('get_terms', $terms, $taxonomies, $args);
	return $terms;
}

/**
 * is_term() - Check if Term exists
 *
 * Returns the index of a defined term, or 0 (false) if the term doesn't exist.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @uses $wpdb
 *
 * @param int|string $term The term to check
 * @param string $taxonomy The taxonomy name to use
 * @return mixed Get the term id or Term Object, if exists.
 */
function is_term($term, $taxonomy = '') {
	global $wpdb;

	if ( is_int($term) ) {
		if ( 0 == $term )
			return 0;
		$where = $wpdb->prepare( "t.term_id = %d", $term );
	} else {
		if ( ! $term = sanitize_title($term) )
			return 0;
		$where = $wpdb->prepare( "t.slug = %s", $term );
	}

	if ( !empty($taxonomy) )
		return $wpdb->get_row("SELECT tt.term_id, tt.term_taxonomy_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy as tt ON tt.term_id = t.term_id WHERE $where AND tt.taxonomy = '$taxonomy'", ARRAY_A);

	return $wpdb->get_var("SELECT term_id FROM $wpdb->terms as t WHERE $where");
}

/**
 * sanitize_term() - Sanitize Term all fields
 *
 * Relys on @see sanitize_term_field() to sanitize the term. The difference
 * is that this function will sanitize <strong>all</strong> fields. The context
 * is based on @see sanitize_term_field().
 *
 * The $term is expected to be either an array or an object.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @uses sanitize_term_field Used to sanitize all fields in a term
 *
 * @param array|object $term The term to check
 * @param string $taxonomy The taxonomy name to use
 * @param string $context Default is 'display'.
 * @return array|object Term with all fields sanitized
 */
function sanitize_term($term, $taxonomy, $context = 'display') {
	$fields = array('term_id', 'name', 'description', 'slug', 'count', 'parent', 'term_group');

	$do_object = false;
	if ( is_object($term) )
		$do_object = true;

	foreach ( $fields as $field ) {
		if ( $do_object )
			$term->$field = sanitize_term_field($field, $term->$field, $term->term_id, $taxonomy, $context);
		else
			$term[$field] = sanitize_term_field($field, $term[$field], $term['term_id'], $taxonomy, $context);
	}

	return $term;
}

/**
 * sanitize_term_field() - {@internal Missing Short Description}}
 *
 * {@internal Missing Long Description}}
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @uses $wpdb
 *
 * @param string $field Term field to sanitize
 * @param string $value Search for this term value
 * @param int $term_id Term ID
 * @param string $taxonomy Taxonomy Name
 * @param string $context Either edit, db, display, attribute, or js.
 * @return mixed sanitized field
 */
function sanitize_term_field($field, $value, $term_id, $taxonomy, $context) {
	if ( 'parent' == $field  || 'term_id' == $field || 'count' == $field
		|| 'term_group' == $field )
		$value = (int) $value;

	if ( 'edit' == $context ) {
		$value = apply_filters("edit_term_$field", $value, $term_id, $taxonomy);
		$value = apply_filters("edit_${taxonomy}_$field", $value, $term_id);
		if ( 'description' == $field )
			$value = format_to_edit($value);
		else
			$value = attribute_escape($value);
	} else if ( 'db' == $context ) {
		$value = apply_filters("pre_term_$field", $value, $taxonomy);
		$value = apply_filters("pre_${taxonomy}_$field", $value);
		// Back compat filters
		if ( 'slug' == $field )
			$value = apply_filters('pre_category_nicename', $value);
			
	} else if ( 'rss' == $context ) {
		$value = apply_filters("term_${field}_rss", $value, $taxonomy);
		$value = apply_filters("${taxonomy}_$field_rss", $value);
	} else {
		// Use display filters by default.
		$value = apply_filters("term_$field", $value, $term_id, $taxonomy, $context);
		$value = apply_filters("${taxonomy}_$field", $value, $term_id, $context);
	}

	if ( 'attribute' == $context )
		$value = attribute_escape($value);
	else if ( 'js' == $context )
		$value = js_escape($value);

	return $value;
}

/**
 * wp_count_terms() - Count how many terms are in Taxonomy
 *
 * Default $args is 'ignore_empty' which can be <code>'ignore_empty=true'</code> or
 * <code>array('ignore_empty' => true);</code>.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @uses $wpdb
 * @uses wp_parse_args() Turns strings into arrays and merges defaults into an array.
 *
 * @param string $taxonomy Taxonomy name
 * @param array|string $args Overwrite defaults
 * @return int How many terms are in $taxonomy
 */
function wp_count_terms( $taxonomy, $args = array() ) {
	global $wpdb;

	$defaults = array('ignore_empty' => false);
	$args = wp_parse_args($args, $defaults);
	extract($args, EXTR_SKIP);

	$where = '';
	if ( $ignore_empty )
		$where = 'AND count > 0';

	$taxonomy = $wpdb->escape( $taxonomy );
	return $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE taxonomy = '$taxonomy' $where");
}

/**
 * wp_delete_object_term_relationships() - {@internal Missing Short Description}}
 *
 * {@internal Missing Long Description}}
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 * @uses $wpdb
 *
 * @param int $object_id The term Object Id that refers to the term
 * @param string|array $taxonomy List of Taxonomy Names or single Taxonomy name.
 */
function wp_delete_object_term_relationships( $object_id, $taxonomies ) {
	global $wpdb;

	$object_id = (int) $object_id;

	if ( !is_array($taxonomies) )
		$taxonomies = array($taxonomies);

	foreach ( $taxonomies as $taxonomy ) {
		$terms = wp_get_object_terms($object_id, $taxonomy, 'fields=tt_ids');
		$in_terms = "'" . implode("', '", $terms) . "'";
		$wpdb->query("DELETE FROM $wpdb->term_relationships WHERE object_id = '$object_id' AND term_taxonomy_id IN ($in_terms)");
		wp_update_term_count($terms, $taxonomy);
	}
}

/**
 * wp_delete_term() - Removes a term from the database.
 *
 * {@internal Missing Long Description}}
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 * @uses $wpdb
 *
 * @param int $term Term ID
 * @param string $taxonomy Taxonomy Name
 * @param array|string $args Change Default
 * @return bool Returns false if not term; true if completes delete action.
 */
function wp_delete_term( $term, $taxonomy, $args = array() ) {
	global $wpdb;

	$term = (int) $term;

	if ( ! $ids = is_term($term, $taxonomy) )
		return false;
	$tt_id = $ids['term_taxonomy_id'];

	$defaults = array();
	$args = wp_parse_args($args, $defaults);
	extract($args, EXTR_SKIP);

	if ( isset($default) ) {
		$default = (int) $default;
		if ( ! is_term($default, $taxonomy) )
			unset($default);
	}

	// Update children to point to new parent
	if ( is_taxonomy_hierarchical($taxonomy) ) {
		$term_obj = get_term($term, $taxonomy);
		if ( is_wp_error( $term_obj ) )
			return $term_obj;
		$parent = $term_obj->parent;

		$wpdb->update( $wpdb->term_taxonomy, compact( $parent ), array( 'parent' => $term_obj->term_id) + compact( $taxonomy ) );
	}

	$objects = $wpdb->get_col( $wpdb->prepare( "SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $tt_id ) );

	foreach ( (array) $objects as $object ) {
		$terms = wp_get_object_terms($object, $taxonomy, 'fields=ids');
		if ( 1 == count($terms) && isset($default) )
			$terms = array($default);
		else
			$terms = array_diff($terms, array($term));
		$terms = array_map('intval', $terms);
		wp_set_object_terms($object, $terms, $taxonomy);
	}

	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", $tt_id ) );

	// Delete the term if no taxonomies use it.
	if ( !$wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE term_id = %d", $term) ) )
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->terms WHERE term_id = %d", $term) );

	clean_term_cache($term, $taxonomy);

	do_action('delete_term', $term, $tt_id, $taxonomy);
	do_action("delete_$taxonomy", $term, $tt_id);

	return true;
}

/**
 * wp_get_object_terms() - Returns the terms associated with the given object(s), in the supplied taxonomies.
 *
 * {@internal Missing Long Description}}
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 * @uses $wpdb
 *
 * @param int|array $object_id The id of the object(s)) to retrieve.
 * @param string|array $taxonomies The taxonomies to retrieve terms from.
 * @param array|string $args Change what is returned
 * @return array|WP_Error The requested term data or empty array if no terms found. WP_Error if $taxonomy does not exist.
 */
function wp_get_object_terms($object_ids, $taxonomies, $args = array()) {
	global $wpdb;

	if ( !is_array($taxonomies) )
		$taxonomies = array($taxonomies);

	foreach ( $taxonomies as $taxonomy ) {
		if ( ! is_taxonomy($taxonomy) )
			return new WP_Error('invalid_taxonomy', __('Invalid Taxonomy'));
	}

	if ( !is_array($object_ids) )
		$object_ids = array($object_ids);
	$object_ids = array_map('intval', $object_ids);

	$defaults = array('orderby' => 'name', 'order' => 'ASC', 'fields' => 'all');
	$args = wp_parse_args( $args, $defaults );
	extract($args, EXTR_SKIP);

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
	else if ( 'names' == $fields ) 
		$select_this = 't.name';
	else if ( 'all_with_object_id' == $fields )
		$select_this = 't.*, tt.*, tr.object_id';

	$query = "SELECT $select_this FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id INNER JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN ($taxonomies) AND tr.object_id IN ($object_ids) ORDER BY $orderby $order";

	if ( 'all' == $fields || 'all_with_object_id' == $fields ) {
		$terms = $wpdb->get_results($query);
		update_term_cache($terms);
	} else if ( 'ids' == $fields || 'names' == $fields ) {
		$terms = $wpdb->get_col($query);
	} else if ( 'tt_ids' == $fields ) {
		$terms = $wpdb->get_col("SELECT tr.term_taxonomy_id FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tr.object_id IN ($object_ids) AND tt.taxonomy IN ($taxonomies) ORDER BY tr.term_taxonomy_id $order");
	}

	if ( ! $terms )
		return array();

	return $terms;
}

/**
 * wp_insert_term() - Adds a new term to the database. Optionally marks it as an alias of an existing term.
 *
 * {@internal Missing Long Description}}
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 * @uses $wpdb
 *
 * @param int|string $term The term to add or update.
 * @param string $taxonomy The taxonomy to which to add the term
 * @param array|string $args Change the values of the inserted term
 * @return array|WP_Error The Term ID and Term Taxonomy ID
 */
function wp_insert_term( $term, $taxonomy, $args = array() ) {
	global $wpdb;

	if ( ! is_taxonomy($taxonomy) )
		return new WP_Error('invalid_taxonomy', __('Invalid taxonomy'));

	if ( is_int($term) && 0 == $term )
		return new WP_Error('invalid_term_id', __('Invalid term ID'));

	$defaults = array( 'alias_of' => '', 'description' => '', 'parent' => 0, 'slug' => '');
	$args = wp_parse_args($args, $defaults);
	$args['name'] = $term;
	$args['taxonomy'] = $taxonomy;
	$args = sanitize_term($args, $taxonomy, 'db');
	extract($args, EXTR_SKIP);

	// expected_slashed ($name)
	$name = stripslashes($name);

	if ( empty($slug) )
		$slug = sanitize_title($name);

	$term_group = 0;
	if ( $alias_of ) {
		$alias = $wpdb->get_row( $wpdb->prepare( "SELECT term_id, term_group FROM $wpdb->terms WHERE slug = %s", $alias_of) );
		if ( $alias->term_group ) {
			// The alias we want is already in a group, so let's use that one.
			$term_group = $alias->term_group;
		} else {
			// The alias isn't in a group, so let's create a new one and firstly add the alias term to it.
			$term_group = $wpdb->get_var("SELECT MAX(term_group) FROM $wpdb->terms GROUP BY term_group") + 1;
			$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->terms SET term_group = %d WHERE term_id = %d", $term_group, $alias->term_id ) );
		}
	}

	if ( ! $term_id = is_term($slug) ) {
		$wpdb->insert( $wpdb->terms, compact( 'name', 'slug', 'term_group' ) );
		$term_id = (int) $wpdb->insert_id;
	} else if ( is_taxonomy_hierarchical($taxonomy) && !empty($parent) ) {
		// If the taxonomy supports hierarchy and the term has a parent, make the slug unique
		// by incorporating parent slugs.
		$slug = wp_unique_term_slug($slug, (object) $args);
		$wpdb->insert( $wpdb->terms, compact( 'name', 'slug', 'term_group' ) );
		$term_id = (int) $wpdb->insert_id;
	}

	if ( empty($slug) ) {
		$slug = sanitize_title($slug, $term_id);
		$wpdb->update( $wpdb->terms, compact( 'slug' ), compact( 'term_id' ) );
	}

	$tt_id = $wpdb->get_var( $wpdb->prepare( "SELECT tt.term_taxonomy_id FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = %s AND t.term_id = %d", $taxonomy, $term_id ) );

	if ( !empty($tt_id) )
		return array('term_id' => $term_id, 'term_taxonomy_id' => $tt_id);

	$wpdb->insert( $wpdb->term_taxonomy, compact( 'term_id', 'taxonomy', 'description', 'parent') + array( 'count' => 0 ) );
	$tt_id = (int) $wpdb->insert_id;

	do_action("create_term", $term_id, $tt_id);
	do_action("create_$taxonomy", $term_id, $tt_id);

	$term_id = apply_filters('term_id_filter', $term_id, $tt_id);

	clean_term_cache($term_id, $taxonomy);

	do_action("created_term", $term_id, $tt_id);
	do_action("created_$taxonomy", $term_id, $tt_id);

	return array('term_id' => $term_id, 'term_taxonomy_id' => $tt_id);
}

/**
 * wp_set_object_terms() - {@internal Missing Short Description}}
 * 
 * Relates an object (post, link etc) to a term and taxonomy type.  Creates the term and taxonomy
 * relationship if it doesn't already exist.  Creates a term if it doesn't exist (using the slug).
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 * @uses $wpdb
 *
 * @param int $object_id The object to relate to.
 * @param array|int|string $term The slug or id of the term.
 * @param array|string $taxonomy The context in which to relate the term to the object.
 * @param bool $append If false will delete difference of terms.
 * @return array|WP_Error Affected Term IDs
 */
function wp_set_object_terms($object_id, $terms, $taxonomy, $append = false) {
	global $wpdb;

	$object_id = (int) $object_id;

	if ( ! is_taxonomy($taxonomy) )
		return new WP_Error('invalid_taxonomy', __('Invalid Taxonomy'));

	if ( !is_array($terms) )
		$terms = array($terms);

	if ( ! $append )
		$old_terms =  wp_get_object_terms($object_id, $taxonomy, 'fields=tt_ids');

	$tt_ids = array();
	$term_ids = array();

	foreach ($terms as $term) {
		if ( !$id = is_term($term, $taxonomy) )
			$id = wp_insert_term($term, $taxonomy);
		if ( is_wp_error($id) )
			return $id;
		$term_ids[] = $id['term_id'];
		$id = $id['term_taxonomy_id'];
		$tt_ids[] = $id;

		if ( $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id = %d", $object_id, $id ) ) )
			continue;
		$wpdb->insert( $wpdb->term_relationships, array( 'object_id' => $object_id, 'term_taxonomy_id' => $id ) );
	}

	wp_update_term_count($tt_ids, $taxonomy);

	if ( ! $append ) {
		$delete_terms = array_diff($old_terms, $tt_ids);
		if ( $delete_terms ) {
			$in_delete_terms = "'" . implode("', '", $delete_terms) . "'";
			$wpdb->query("DELETE FROM $wpdb->term_relationships WHERE object_id = '$object_id' AND term_taxonomy_id IN ($in_delete_terms)");
			wp_update_term_count($delete_terms, $taxonomy);
		}
	}

	return $tt_ids;
}

/**
 * wp_unique_term_slug() - Will make slug unique, if it isn't already
 * 
 * The $slug has to be unique global to every taxonomy, meaning that one taxonomy
 * term can't have a matching slug with another taxonomy term. Each slug has to be
 * globally unique for every taxonomy.
 *
 * The way this works is that if the taxonomy that the term belongs to is heirarchical
 * and has a parent, it will append that parent to the $slug.
 *
 * If that still doesn't return an unique slug, then it try to append a number until
 * it finds a number that is truely unique.
 * 
 * The only purpose for $term is for appending a parent, if one exists.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 * @uses $wpdb
 *
 * @param string $slug The string that will be tried for a unique slug
 * @param object $term The term object that the $slug will belong too
 * @return string Will return a true unique slug.
 */
function wp_unique_term_slug($slug, $term) {
	global $wpdb;

	// If the taxonomy supports hierarchy and the term has a parent, make the slug unique
	// by incorporating parent slugs.
	if ( is_taxonomy_hierarchical($term->taxonomy) && !empty($term->parent) ) {
		$the_parent = $term->parent;
		while ( ! empty($the_parent) ) {
			$parent_term = get_term($the_parent, $term->taxonomy);
			if ( is_wp_error($parent_term) || empty($parent_term) )
				break;
				$slug .= '-' . $parent_term->slug;
			if ( empty($parent_term->parent) )
				break;
			$the_parent = $parent_term->parent;
		}
	}

	// If we didn't get a unique slug, try appending a number to make it unique.
	if ( $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM $wpdb->terms WHERE slug = %s", $slug ) ) ) {
		$num = 2;
		do {
			$alt_slug = $slug . "-$num";
			$num++;
			$slug_check = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM $wpdb->terms WHERE slug = %s", $alt_slug ) );
		} while ( $slug_check );
		$slug = $alt_slug;
	}

	return $slug;
}

/**
 * wp_update_term() - {@internal Missing Short Description}}
 *
 * {@internal Missing Long Description}}
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 * @uses $wpdb
 *
 * @param int $term The ID of the term
 * @param string $taxonomy The context in which to relate the term to the object.
 * @param array|string $args Overwrite defaults
 * @return array Returns Term ID and Taxonomy Term ID
 */
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
	$args = sanitize_term($args, $taxonomy, 'db');
	extract($args, EXTR_SKIP);

	$empty_slug = false;
	if ( empty($slug) ) {
		$empty_slug = true;
		$slug = sanitize_title($name);
	}

	if ( $alias_of ) {
		$alias = $wpdb->get_row( $wpdb->prepare( "SELECT term_id, term_group FROM $wpdb->terms WHERE slug = %s", $alias_of) );
		if ( $alias->term_group ) {
			// The alias we want is already in a group, so let's use that one.
			$term_group = $alias->term_group;
		} else {
			// The alias isn't in a group, so let's create a new one and firstly add the alias term to it.
			$term_group = $wpdb->get_var("SELECT MAX(term_group) FROM $wpdb->terms GROUP BY term_group") + 1;
			$wpdb->update( $wpdb->terms, compact('term_group'), array( 'term_id' => $alias->term_id ) );
		}
	}

	// Check for duplicate slug
	$id = $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM $wpdb->terms WHERE slug = %s", $slug ) );
	if ( $id && ($id != $term_id) ) {
		// If an empty slug was passed, reset the slug to something unique.
		// Otherwise, bail.
		if ( $empty_slug )
			$slug = wp_unique_term_slug($slug, (object) $args);
		else
			return new WP_Error('duplicate_term_slug', sprintf(__('The slug "%s" is already in use by another term'), $slug));
	}

	$wpdb->update($wpdb->terms, compact( 'name', 'slug', 'term_group' ), compact( 'term_id' ) );

	if ( empty($slug) ) {
		$slug = sanitize_title($name, $term_id);
		$wpdb->update( $wpdb->terms, compact( 'slug' ), compact( 'term_id' ) );
	}

	$tt_id = $wpdb->get_var( $wpdb->prepare( "SELECT tt.term_taxonomy_id FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = %s AND t.term_id = %d", $taxonomy, $term_id) );

	$wpdb->update( $wpdb->term_taxonomy, compact( 'term_id', 'taxonomy', 'description', 'parent' ), array( 'term_taxonomy_id' => $tt_id ) );

	do_action("edit_term", $term_id, $tt_id);
	do_action("edit_$taxonomy", $term_id, $tt_id);

	$term_id = apply_filters('term_id_filter', $term_id, $tt_id);

	clean_term_cache($term_id, $taxonomy);

	do_action("edited_term", $term_id, $tt_id);
	do_action("edited_$taxonomy", $term_id, $tt_id);

	return array('term_id' => $term_id, 'term_taxonomy_id' => $tt_id);
}

/**
 * wp_update_term_count() - Updates the amount of terms in taxonomy
 * 
 * If there is a taxonomy callback applyed, then it will be called for updating the count.
 *
 * The default action is to count what the amount of terms have the relationship of term ID.
 * Once that is done, then update the database.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 * @uses $wpdb
 *
 * @param int|array $terms The ID of the terms
 * @param string $taxonomy The context of the term.
 * @return bool If no terms will return false, and if successful will return true.
 */
function wp_update_term_count( $terms, $taxonomy ) {
	global $wpdb;

	if ( empty($terms) )
		return false;

	if ( !is_array($terms) )
		$terms = array($terms);

	$terms = array_map('intval', $terms);

	$taxonomy = get_taxonomy($taxonomy);
	if ( !empty($taxonomy->update_count_callback) ) {
		call_user_func($taxonomy->update_count_callback, $terms);
	} else {
		// Default count updater
		foreach ($terms as $term) {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term) );
			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
		}

	}

	clean_term_cache($terms);

	return true;
}

//
// Cache
//

/**
 * clean_object_term_cache() - {@internal Missing Short Description}}
 *
 * {@internal Missing Long Description}}
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @see get_object_taxonomies() for more on $object_type
 *
 * @param int|array $object_ids {@internal Missing Description}}
 * @param string $object_type {@internal Missing Description}}
 */
function clean_object_term_cache($object_ids, $object_type) {
	if ( !is_array($object_ids) )
		$object_ids = array($object_ids);

	foreach ( $object_ids as $id )
		wp_cache_delete($id, 'object_terms');

	do_action('clean_object_term_cache', $object_ids, $object_type);
}

/**
 * clean_term_cache() - {@internal Missing Short Description}}
 *
 * {@internal Missing Long Description}}
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 * @uses $wpdb
 *
 * @param int|array $ids {@internal Missing Description}}
 * @param string $taxonomy Can be empty and will assume tt_ids, else will use for context.
 */
function clean_term_cache($ids, $taxonomy = '') {
	global $wpdb;

	if ( !is_array($ids) )
		$ids = array($ids);

	$taxonomies = array();
	// If no taxonomy, assume tt_ids.
	if ( empty($taxonomy) ) {
		$tt_ids = implode(', ', $ids);
		$terms = $wpdb->get_results("SELECT term_id, taxonomy FROM $wpdb->term_taxonomy WHERE term_taxonomy_id IN ($tt_ids)");
		foreach ( (array) $terms as $term ) {
			$taxonomies[] = $term->taxonomy;
			wp_cache_delete($term->term_id, $term->taxonomy);
		}
		$taxonomies = array_unique($taxonomies);
	} else {
		foreach ( $ids as $id ) {
			wp_cache_delete($id, $taxonomy);
		}
		$taxonomies = array($taxonomy);
	}

	foreach ( $taxonomies as $taxonomy ) {
		wp_cache_delete('all_ids', $taxonomy);
		wp_cache_delete('get', $taxonomy);
		delete_option("{$taxonomy}_children");
	}

	wp_cache_delete('get_terms', 'terms');

	do_action('clean_term_cache', $ids, $taxonomy);
}

/**
 * get_object_term_cache() - {@internal Missing Short Description}}
 *
 * {@internal Missing Long Description}}
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @param int|array $ids {@internal Missing Description}}
 * @param string $taxonomy {@internal Missing Description}}
 * @return bool|array Empty array if $terms found, but not $taxonomy. False if nothing is in cache for $taxonomy and $id.
 */
function &get_object_term_cache($id, $taxonomy) {
	$terms = wp_cache_get($id, 'object_terms');
	if ( false !== $terms ) {
		if ( isset($terms[$taxonomy]) )
			return $terms[$taxonomy];
		else
			return array();
	}

	return false;
}

/**
 * get_object_term_cache() - {@internal Missing Short Description}}
 *
 * {@internal Missing Long Description}}
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 * @uses $wpdb
 *
 * @param string|array $object_ids {@internal Missing Description}}
 * @param string $object_type {@internal Missing Description}}
 * @return null|array Null value is given with empty $object_ids.
 */
function update_object_term_cache($object_ids, $object_type) {
	global $wpdb;

	if ( empty($object_ids) )
		return;

	if ( !is_array($object_ids) )
		$object_ids = explode(',', $object_ids);

	$object_ids = array_map('intval', $object_ids);

	$ids = array();
	foreach ( (array) $object_ids as $id ) {
		if ( false === wp_cache_get($id, 'object_terms') )
			$ids[] = $id;
	}

	if ( empty( $ids ) )
		return false;

	$terms = wp_get_object_terms($ids, get_object_taxonomies($object_type), 'fields=all_with_object_id');

	$object_terms = array();
	foreach ( (array) $terms as $term )
		$object_terms[$term->object_id][$term->taxonomy][$term->term_id] = $term;

	foreach ( $ids as $id ) {
		if ( ! isset($object_terms[$id]) )
				$object_terms[$id] = array();
	}

	foreach ( $object_terms as $id => $value )
		wp_cache_set($id, $value, 'object_terms');
}

/**
 * update_term_cache() - Updates Terms to Taxonomy in cache.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 2.3
 *
 * @param array $terms List of Term objects to change
 * @param string $taxonomy Optional. Update Term to this taxonomy in cache
 */
function update_term_cache($terms, $taxonomy = '') {
	foreach ( $terms as $term ) {
		$term_taxonomy = $taxonomy;
		if ( empty($term_taxonomy) )
			$term_taxonomy = $term->taxonomy;

		wp_cache_add($term->term_id, $term, $term_taxonomy);
	}
}

//
// Private
//

/**
 * _get_term_hierarchy() - Retrieves children of taxonomy
 *
 * {@internal Missing Long Description}}
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @access private
 * @since 2.3
 *
 * @param string $taxonomy {@internal Missing Description}}
 * @return array Empty if $taxonomy isn't hierarachical or returns children.
 */
function _get_term_hierarchy($taxonomy) {
	if ( !is_taxonomy_hierarchical($taxonomy) )
		return array();
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

/**
 * _get_term_children() - Get array of child terms
 * 
 * If $terms is an array of objects, then objects will returned from the function.
 * If $terms is an array of IDs, then an array of ids of children will be returned.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @access private
 * @since 2.3
 *
 * @param int $term_id Look for this Term ID in $terms
 * @param array $terms List of Term IDs
 * @param string $taxonomy Term Context
 * @return array Empty if $terms is empty else returns full list of child terms.
 */
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
			if ( is_wp_error( $term ) )
				return $term;
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

/**
 * _pad_term_counts() - Add count of children to parent count
 * 
 * Recalculates term counts by including items from child terms.
 * Assumes all relevant children are already in the $terms argument
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @access private
 * @since 2.3
 * @uses $wpdb
 *
 * @param array $terms List of Term IDs
 * @param string $taxonomy Term Context
 * @return null Will break from function if conditions are not met.
 */
function _pad_term_counts(&$terms, $taxonomy) {
	global $wpdb;

	// This function only works for post categories.
	if ( 'category' != $taxonomy )
		return;

	$term_hier = _get_term_hierarchy($taxonomy);

	if ( empty($term_hier) )
		return;

	$term_items = array();

	foreach ( $terms as $key => $term ) {
		$terms_by_id[$term->term_id] = & $terms[$key];
		$term_ids[$term->term_taxonomy_id] = $term->term_id;
	}

	// Get the object and term ids and stick them in a lookup table
	$results = $wpdb->get_results("SELECT object_id, term_taxonomy_id FROM $wpdb->term_relationships INNER JOIN $wpdb->posts ON object_id = ID WHERE term_taxonomy_id IN (".join(',', array_keys($term_ids)).") AND post_type = 'post' AND post_status = 'publish'");
	foreach ( $results as $row ) {
		$id = $term_ids[$row->term_taxonomy_id];
		++$term_items[$id][$row->object_id];
	}

	// Touch every ancestor's lookup row for each post in each term
	foreach ( $term_ids as $term_id ) {
		$child = $term_id;
		while ( $parent = $terms_by_id[$child]->parent ) {
			if ( !empty($term_items[$term_id]) )
				foreach ( $term_items[$term_id] as $item_id => $touches )
					++$term_items[$parent][$item_id];
			$child = $parent;
		}
	}

	// Transfer the touched cells 
	foreach ( (array) $term_items as $id => $items )
		if ( isset($terms_by_id[$id]) )
			$terms_by_id[$id]->count = count($items);
}

//
// Default callbacks
//

/**
 * _update_post_term_count() - Will update term count based on posts
 * 
 * Private function for the default callback for post_tag and category taxonomies.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @access private
 * @since 2.3
 * @uses $wpdb
 *
 * @param array $terms List of Term IDs
 */
function _update_post_term_count( $terms ) {
	global $wpdb;

	foreach ( $terms as $term ) {
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status = 'publish' AND post_type = 'post' AND term_taxonomy_id = %d", $term ) );
		$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
	}
}

?>
