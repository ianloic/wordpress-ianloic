<?php

function get_bloginfo_rss($show = '') {
	$info = strip_tags(get_bloginfo($show));
	return apply_filters('get_bloginfo_rss', convert_chars($info));
}


function bloginfo_rss($show = '') {
	echo apply_filters('bloginfo_rss', get_bloginfo_rss($show));
}

function get_wp_title_rss($sep = '&#187;') {
	$title = wp_title($sep, false);
	if ( is_wp_error( $title ) )
		return $title->get_error_message();
	$title = apply_filters('get_wp_title_rss', $title);
	return $title;
}

function wp_title_rss($sep = '&#187;') {
	echo apply_filters('wp_title_rss', get_wp_title_rss($sep));
}

function get_the_title_rss() {
	$title = get_the_title();
	$title = apply_filters('the_title_rss', $title);
	return $title;
}


function the_title_rss() {
	echo get_the_title_rss();
}


function the_content_rss($more_link_text='(more...)', $stripteaser=0, $more_file='', $cut = 0, $encode_html = 0) {
	$content = get_the_content($more_link_text, $stripteaser, $more_file);
	$content = apply_filters('the_content_rss', $content);
	if ( $cut && !$encode_html )
		$encode_html = 2;
	if ( 1== $encode_html ) {
		$content = wp_specialchars($content);
		$cut = 0;
	} elseif ( 0 == $encode_html ) {
		$content = make_url_footnote($content);
	} elseif ( 2 == $encode_html ) {
		$content = strip_tags($content);
	}
	if ( $cut ) {
		$blah = explode(' ', $content);
		if ( count($blah) > $cut ) {
			$k = $cut;
			$use_dotdotdot = 1;
		} else {
			$k = count($blah);
			$use_dotdotdot = 0;
		}
		for ( $i=0; $i<$k; $i++ )
			$excerpt .= $blah[$i].' ';
		$excerpt .= ($use_dotdotdot) ? '...' : '';
		$content = $excerpt;
	}
	$content = str_replace(']]>', ']]&gt;', $content);
	echo $content;
}


function the_excerpt_rss() {
	$output = get_the_excerpt();
	echo apply_filters('the_excerpt_rss', $output);
}

function the_permalink_rss() {
	echo apply_filters('the_permalink_rss', get_permalink());

}

function comment_link() {
	echo get_comment_link();
}


function get_comment_author_rss() {
	return apply_filters('comment_author_rss', get_comment_author() );
}


function comment_author_rss() {
	echo get_comment_author_rss();
}


function comment_text_rss() {
	$comment_text = get_comment_text();
	$comment_text = apply_filters('comment_text_rss', $comment_text);
	echo $comment_text;
}


function comments_rss_link($link_text = 'Comments RSS', $commentsrssfilename = 'nolongerused') {
	$url = get_post_comments_feed_link();
	echo "<a href='$url'>$link_text</a>";
}


function comments_rss($commentsrssfilename = 'nolongerused') {
	return get_post_comments_feed_link();
}


function get_author_rss_link($echo = false, $author_id, $author_nicename) {
	$auth_ID = (int) $author_id;
	$permalink_structure = get_option('permalink_structure');

	if ( '' == $permalink_structure ) {
		$link = get_option('home') . '?feed=rss2&amp;author=' . $author_id;
	} else {
		$link = get_author_posts_url($author_id, $author_nicename);
		$link = trailingslashit($link) . user_trailingslashit('feed', 'feed');
	}

	$link = apply_filters('author_feed_link', $link);

	if ( $echo )
		echo $link;
	return $link;
}

/** get_category_feed_link() - Get the feed link for a given category
 *
 * Returns a link to the feed for all post in a given category.  A specific feed can be requested
 * or left blank to get the default feed.
 *
 * @package WordPress
 * @subpackage Feed
 * @since 2.4
 *
 * @param int $cat_id ID of a category
 * @param string $feed Feed type
 * @return string Link to the feed for the category specified by $cat_id
*/
function get_category_feed_link($cat_id, $feed = 'rss2') {
	$cat_id = (int) $cat_id;
	
	$category = get_category($cat_id);
	
	if ( empty($category) || is_wp_error($category) )
		return false;
	
	$permalink_structure = get_option('permalink_structure');

	if ( '' == $permalink_structure ) {
		$link = get_option('home') . "?feed=$feed&amp;cat=" . $cat_id;
	} else {
		$link = get_category_link($cat_id);
		if( 'rss2' == $feed )
			$feed_link = 'feed';
		else
			$feed_link = "feed/$feed";
		
		$link = trailingslashit($link) . user_trailingslashit($feed_link, 'feed');
	}

	$link = apply_filters('category_feed_link', $link, $feed);
	
	return $link;
}


function get_category_rss_link($echo = false, $cat_ID, $category_nicename) {
	$link = get_category_feed_link($cat_ID, $feed = 'rss2');

	if ( $echo )
		echo $link;
	return $link;
}


function get_the_category_rss($type = 'rss') {
	$categories = get_the_category();
	$tags = get_the_tags();
	$home = get_bloginfo_rss('home');
	$the_list = '';
	$cat_names = array();

	$filter = 'rss';
	if ( 'atom' == $type )
		$filter = 'raw';

	if ( !empty($categories) ) foreach ( (array) $categories as $category ) {
		$cat_names[] = sanitize_term_field('name', $category->name, $category->term_id, 'category', $filter);
	}

	if ( !empty($tags) ) foreach ( (array) $tags as $tag ) {
		$cat_names[] = sanitize_term_field('name', $tag->name, $tag->term_id, 'post_tag', $filter);
	}

	$cat_names = array_unique($cat_names);

	foreach ( $cat_names as $cat_name ) {
		if ( 'rdf' == $type )
			$the_list .= "\n\t\t<dc:subject><![CDATA[$cat_name]]></dc:subject>\n";
		elseif ( 'atom' == $type )
			$the_list .= sprintf( '<category scheme="%1$s" term="%2$s" />', attribute_escape( apply_filters( 'get_bloginfo_rss', get_bloginfo( 'url' ) ) ), attribute_escape( $cat_name ) );
		else
			$the_list .= "\n\t\t<category><![CDATA[$cat_name]]></category>\n";
	}

	return apply_filters('the_category_rss', $the_list, $type);
}


function the_category_rss($type = 'rss') {
	echo get_the_category_rss($type);
}

function get_tag_feed_link($tag_id, $feed = 'rss2') {
	$tag_id = (int) $tag_id;

	$tag = get_tag($tag_id);

	if ( empty($tag) || is_wp_error($tag) )
		return false;

	$permalink_structure = get_option('permalink_structure');

	if ( '' == $permalink_structure ) {
		$link = get_option('home') . "?feed=$feed&amp;tag=" . $tag->slug;
	} else {
		$link = get_tag_link($tag->term_id);
		if ( 'rss2' == $feed )
			$feed_link = 'feed';
		else
			$feed_link = "feed/$feed";
		$link = $link . user_trailingslashit($feed_link, 'feed');
	}

	$link = apply_filters('tag_feed_link', $link, $feed);

	return $link;
}

function html_type_rss() {
	$type = get_bloginfo('html_type');
	if (strpos($type, 'xhtml') !== false)
		$type = 'xhtml';
	else
		$type = 'html';
	echo $type;
}


function rss_enclosure() {
	global $id, $post;
	if ( !empty($post->post_password) && ($_COOKIE['wp-postpass_'.COOKIEHASH] != $post->post_password) )
		return;

	foreach (get_post_custom() as $key => $val) {
		if ($key == 'enclosure') {
			foreach ((array)$val as $enc) {
				$enclosure = split("\n", $enc);
				echo apply_filters('rss_enclosure', '<enclosure url="' . trim(htmlspecialchars($enclosure[0])) . '" length="' . trim($enclosure[1]) . '" type="' . trim($enclosure[2]) . '" />' . "\n");
			}
		}
	}
}

function atom_enclosure() {
	global $id, $post;
	if ( !empty($post->post_password) && ($_COOKIE['wp-postpass_'.COOKIEHASH] != $post->post_password) )
		return;

	foreach (get_post_custom() as $key => $val) {
		if ($key == 'enclosure') {
			foreach ((array)$val as $enc) {
				$enclosure = split("\n", $enc);
				echo apply_filters('atom_enclosure', '<link href="' . trim(htmlspecialchars($enclosure[0])) . '" rel="enclosure" length="' . trim($enclosure[1]) . '" type="' . trim($enclosure[2]) . '" />' . "\n");
			}
		}
	}
}

/**
 * prep_atom_text_construct() - determine if given string of data is
 * type text, html, or xhtml, per RFC 4287 section 3.1.
 *
 * In the case of WordPress, text is defined as containing no markup,
 * xhtml is defined as "well formed", and html as tag soup (i.e., the rest).
 *
 * Container div tags are added to xhtml values, per section 3.1.1.3.
 *
 * @package WordPress
 * @subpackage Feed
 * @since 2.4 
 *
 * @param string $data input string
 * @return array $result array(type, value)
 * @link http://www.atomenabled.org/developers/syndication/atom-format-spec.php#rfc.section.3.1
 */
function prep_atom_text_construct($data) {
	if (strpos($data, '<') === false && strpos($data, '&') === false) {
		return array('text', $data);
	}

	$parser = xml_parser_create();
	xml_parse($parser, '<div>' . $data . '</div>', true);
	$code = xml_get_error_code($parser);
	xml_parser_free($parser);

	if (!$code) {
		       if (strpos($data, '<') === false) {
			       return array('text', $data);
                       } else {
			       $data = "<div xmlns='http://www.w3.org/1999/xhtml'>$data</div>";
			       return array('xhtml', $data);
                       }
	}

	if (strpos($data, ']]>') == false) {
		return array('html', "<![CDATA[$data]]>");
	} else {
		return array('html', htmlspecialchars($data));
	}
}

?>
