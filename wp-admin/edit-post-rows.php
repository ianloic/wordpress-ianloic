<table class="widefat">
	<thead>
	<tr>

<?php foreach($posts_columns as $column_display_name) { ?>
	<th scope="col"><?php echo $column_display_name; ?></th>
<?php } ?>

	</tr>
	</thead>
	<tbody id="the-list">
<?php
if ( have_posts() ) {
$bgcolor = '';
while (have_posts()) : the_post();
add_filter('the_title','wp_specialchars');
$class = ('alternate' == $class) ? '' : 'alternate';
?>
	<tr id='post-<?php echo $id; ?>' class='<?php echo $class; ?>'>

<?php

foreach($posts_columns as $column_name=>$column_display_name) {

	switch($column_name) {

	case 'id':
		?>
		<th scope="row" style="text-align: center"><?php echo $id ?></th>
		<?php
		break;
	case 'modified':
		?>
		<td><?php if ( '0000-00-00 00:00:00' ==$post->post_modified ) _e('Never'); else the_modified_time(__('Y-m-d \<\b\r \/\> g:i:s a')); ?></td>
		<?php
		break;
	case 'date':
		?>
		<td><?php if ( '0000-00-00 00:00:00' ==$post->post_modified ) _e('Unpublished'); else the_time(__('Y-m-d \<\b\r \/\> g:i:s a')); ?></td>
		<?php
		break;
	case 'title':
		?>
		<td><?php the_title() ?>
		<?php if ('private' == $post->post_status) _e(' - <strong>Private</strong>'); ?></td>
		<?php
		break;

	case 'categories':
		?>
		<td><?php the_category(','); ?></td>
		<?php
		break;

	case 'comments':
		?>
		<td style="text-align: center">
			<?php comments_number("<a href='edit.php?p=$id&amp;c=1'>" . __('0') . '</a>', "<a href='edit.php?p=$id&amp;c=1'>" . __('1') . '</a>', "<a href='edit.php?p=$id&amp;c=1'>" . __('%') . '</a>') ?>
			</td>
		<?php
		break;

	case 'author':
		?>
		<td><?php the_author() ?></td>
		<?php
		break;

	case 'control_view':
		?>
		<td><a href="<?php the_permalink(); ?>" rel="permalink" class="edit"><?php _e('View'); ?></a></td>
		<?php
		break;

	case 'control_edit':
		?>
		<td><?php if ( current_user_can('edit_post',$post->ID) ) { echo "<a href='post.php?action=edit&amp;post=$id' class='edit'>" . __('Edit') . "</a>"; } ?></td>
		<?php
		break;

	case 'control_delete':
		?>
		<td><?php if ( current_user_can('delete_post',$post->ID) ) { echo "<a href='" . wp_nonce_url("post.php?action=delete&amp;post=$id", 'delete-post_' . $post->ID) . "' class='delete' onclick=\"return deleteSomething( 'post', " . $id . ", '" . js_escape(sprintf(__("You are about to delete this post '%s'.\n'OK' to delete, 'Cancel' to stop."), get_the_title())) . "' );\">" . __('Delete') . "</a>"; } ?></td>
		<?php
		break;

	default:
		?>
		<td><?php do_action('manage_posts_custom_column', $column_name, $id); ?></td>
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
