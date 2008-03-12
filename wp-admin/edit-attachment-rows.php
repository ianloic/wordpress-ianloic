<?php if ( ! defined('ABSPATH') ) die(); ?>
<table class="widefat">
	<thead>
	<tr>

<?php $posts_columns = wp_manage_media_columns(); ?>
<?php foreach($posts_columns as $column_display_name) { ?>
	<th scope="col"><?php echo $column_display_name; ?></th>
<?php } ?>

	</tr>
	</thead>
	<tbody id="the-list" class="list:post">
<?php
$i_post = 0;
if ( have_posts() ) {
$bgcolor = '';
add_filter('the_title','wp_specialchars');
while (have_posts()) : the_post(); $i_post++;
if ( 16 == $i_post )
	echo "\t</tbody>\n\t<tbody id='the-extra-list' class='list:post' style='display: none'>\n"; // Hack!
$class = ( $i_post > 15 || 'alternate' == $class) ? '' : 'alternate';
global $current_user;
$post_owner = ( $current_user->ID == $post->post_author ? 'self' : 'other' );
?>
	<tr id='post-<?php echo $id; ?>' class='<?php echo trim( $class . ' author-' . $post_owner . ' status-' . $post->post_status ); ?>' valign="top">

<?php

foreach($posts_columns as $column_name=>$column_display_name) {

	switch($column_name) {

	case 'cb':
		?>
		<th scope="row" style="text-align: center"><input type="checkbox" name="delete[]" value="<?php the_ID(); ?>" /></th>
		<?php
		break;

	case 'icon':
		?>
		<td class="media-icon"><?php echo get_the_attachment_link($post->ID, false, array(48,48)); ?></td>
		<?php
		// TODO
		break;

	case 'media':
		?>
		<td><strong><a href="media.php?action=edit&amp;attachment_id=<?php the_ID(); ?>"><?php the_title(); ?></a></strong><br />
		<?php echo strtoupper(preg_replace('/^.*?\.(\w+)$/', '$1', get_attached_file($post->ID))); ?>
		<?php do_action('manage_media_media_column', $post->ID); ?>
		</td>
		<?php
		break;

	case 'desc':
		?>
		<td><?php echo has_excerpt() ? $post->post_excerpt : ''; ?></td>
		<?php
		break;

	case 'date':
		if ( '0000-00-00 00:00:00' == $post->post_date && 'date' == $column_name ) {
			$t_time = $h_time = __('Unpublished');
		} else {
			$t_time = get_the_time(__('Y/m/d g:i:s A'));
			$m_time = $post->post_date;
			$time = get_post_time( 'G', true );
			if ( ( abs($t_diff = time() - $time) ) < 86400 ) {
				if ( $t_diff < 0 )
					$h_time = sprintf( __('%s from now'), human_time_diff( $time ) );
				else
					$h_time = sprintf( __('%s ago'), human_time_diff( $time ) );
			} else {
				$h_time = mysql2date(__('Y/m/d'), $m_time);
			}
		}
		?>
		<td><?php echo $h_time ?></td>
		<?php
		break;

	case 'parent':
		if ( $post_parent = get_post($post->post_parent) ) {
			$title = get_the_title($post->post_parent);
			if ( empty($title) )
				$title = __('(no title)');
		} else {
			$title = '';
		}
		?>
		<td><strong><a href="post.php?action=edit&amp;post=<?php echo $post->post_parent; ?>"><?php echo $title ?></a></strong></td>
		<?php
		break;

	case 'comments':
		?>
		<td style="text-align: center">
		<?php
		$left = get_pending_comments_num( $post->ID );
		$pending_phrase = sprintf( __('%s pending'), number_format( $left ) );
		if ( $left )
			echo '<strong>';
		comments_number("<a href='upload.php?attachment_id=$id' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . __('0') . '</span></a>', "<a href='upload.php?attachment_id=$id' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . __('1') . '</span></a>', "<a href='upload.php?attachment_id=$id' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . __('%') . '</span></a>');
		if ( $left )
			echo '</strong>';
		?>
		</td>
		<?php
		break;

	case 'location':
		?>
		<td><a href="<?php the_permalink(); ?>"><?php _e('Permalink'); ?></a></td>
		<?php
		break;

	default:
		?>
		<td><?php do_action('manage_media_custom_column', $column_name, $id); ?></td>
		<?php
		break;
	}
}
?>
	</tr>
<?php
endwhile;
} else {
?>
  <tr style='background-color: <?php echo $bgcolor; ?>'>
    <td colspan="8"><?php _e('No posts found.') ?></td>
  </tr>
<?php
} // end if ( have_posts() )
?>
	</tbody>
</table>
