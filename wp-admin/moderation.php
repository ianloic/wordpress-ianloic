<?php
require_once('admin.php');

$title = __('Moderate comments');
$parent_file = 'edit.php';

$wpvarstoreset = array('action','item_ignored','item_deleted','item_approved');
for ($i=0; $i<count($wpvarstoreset); $i += 1) {
	$wpvar = $wpvarstoreset[$i];
	if (!isset($$wpvar)) {
		if (empty($_POST["$wpvar"])) {
			if (empty($_GET["$wpvar"])) {
				$$wpvar = '';
			} else {
				$$wpvar = $_GET["$wpvar"];
			}
		} else {
			$$wpvar = $_POST["$wpvar"];
		}
	}
}

$comment = array();
if (isset($_POST["comment"])) {
	foreach ($_POST["comment"] as $k => $v) {
		$comment[intval($k)] = $v;
	}
}

switch($action) {

case 'update':

	if ($user_level < 3) {
		die(__('<p>Your level is not high enough to moderate comments.</p>'));
	}

	$item_ignored = 0;
	$item_deleted = 0;
	$item_approved = 0;
	
	foreach($comment as $key => $value) {
	if ($feelinglucky && 'later' == $value)
		$value = 'delete';
	    switch($value) {
			case 'later':
				// do nothing with that comment
				// wp_set_comment_status($key, "hold");
				++$item_ignored;
				break;
			
			case 'delete':
				wp_set_comment_status($key, 'delete');
				++$item_deleted;
				break;
			
			case 'approve':
				wp_set_comment_status($key, 'approve');
				if (get_settings('comments_notify') == true) {
					wp_notify_postauthor($key);
				}
				++$item_approved;
				break;
	    }
	}

	$file = basename(__FILE__);
	header("Location: $file?ignored=$item_ignored&deleted=$item_deleted&approved=$item_approved");
	exit();

break;

default:

	require_once('admin-header.php');

if (isset($deleted) || isset($approved) || isset($ignored)) {
	echo "<div class='updated'>\n<p>";
	if ($approved) {
		if ('1' == $approved) {
		 echo __("1 comment approved <br />") . "\n";
		} else {
		 echo sprintf(__("%s comments approved <br />"), $approved) . "\n";
		}
	}
	if ($deleted) {
		if ('1' == $deleted) {
		echo __("1 comment deleted <br />") . "\n";
		} else {
		echo sprintf(__("%s comments deleted <br />"), $deleted) . "\n";
		}
	}
	if ($ignored) {
		if ('1' == $ignored) {
		echo __("1 comment unchanged <br />") . "\n";
		} else {
		echo sprintf(__("%s comments unchanged <br />"), $ignored) . "\n";
		}
	}
	echo "</p></div>\n";
}

?>
	
<div class="wrap">

<?php
if ($user_level > 3)
	$comments = $wpdb->get_results("SELECT * FROM $wpdb->comments WHERE comment_approved = '0'");
else
	$comments = '';

if ($comments) {
    // list all comments that are waiting for approval
    $file = basename(__FILE__);
?>
    <h2><?php _e('Moderation Queue') ?></h2>
    <form name="approval" action="moderation.php" method="post">
    <input type="hidden" name="action" value="update" />
    <ol id="comments" class="commentlist">
<?php
$i = 0;
    foreach($comments as $comment) {
	++$i;
	$comment_date = mysql2date(get_settings("date_format") . " @ " . get_settings("time_format"), $comment->comment_date);
	$post_title = $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE ID='$comment->comment_post_ID'");
	if ($i % 2) $class = 'class="alternate"';
	else $class = '';
	echo "\n\t<li id='comment-$comment->comment_ID' $class>"; 
	?>
			<p><strong><?php _e('Name:') ?></strong> <?php comment_author() ?> <?php if ($comment->comment_author_email) { ?>| <strong><?php _e('E-mail:') ?></strong> <?php comment_author_email_link() ?> <?php } if ($comment->comment_author_email) { ?> | <strong><?php _e('URI:') ?></strong> <?php comment_author_url_link() ?> <?php } ?>| <strong><?php _e('IP:') ?></strong> <a href="http://ws.arin.net/cgi-bin/whois.pl?queryinput=<?php comment_author_IP() ?>"><?php comment_author_IP() ?></a></p>
<?php comment_text() ?>
<p><?php
echo '<a href="post.php?action=editcomment&amp;comment='.$comment->comment_ID.'">' . __('Edit') . '</a> | ';?>
<a href="<?php echo get_permalink($comment->comment_post_ID); ?>"><?php _e('View Post') ?></a> | 
<?php 
echo " <a href=\"post.php?action=deletecomment&amp;p=".$comment->comment_post_ID."&amp;comment=".$comment->comment_ID."\" onclick=\"return confirm('" . sprintf(__("You are about to delete this comment by \'%s\'\\n  \'Cancel\' to stop, \'OK\' to delete."), $comment->comment_author) . "')\">" . __('Delete just this comment') . "</a> | "; ?>  <?php _e('Bulk action:') ?>
	<input type="radio" name="comment[<?php echo $comment->comment_ID; ?>]" id="comment[<?php echo $comment->comment_ID; ?>]-approve" value="approve" /> <label for="comment[<?php echo $comment->comment_ID; ?>]-approve"><?php _e('Approve') ?></label>
	<input type="radio" name="comment[<?php echo $comment->comment_ID; ?>]" id="comment[<?php echo $comment->comment_ID; ?>]-delete" value="delete" /> <label for="comment[<?php echo $comment->comment_ID; ?>]-delete"><?php _e('Delete') ?></label>
	<input type="radio" name="comment[<?php echo $comment->comment_ID; ?>]" id="comment[<?php echo $comment->comment_ID; ?>]-nothing" value="later" checked="checked" /> <label for="comment[<?php echo $comment->comment_ID; ?>]-nothing"><?php _e('Defer until later') ?></label>

	</li>
<?php
    }
?>
    </ol>
	<p>
		<input name="feelinglucky" type="checkbox" id="feelinglucky" value="true" /> <label for="feelinglucky"><?php _e('Delete every comment marked "defer." <strong>Warning: This can&#8217;t be undone.</strong>'); ?></label>
	</p>
    <p class="submit"><input type="submit" name="submit" value="<?php _e('Moderate Comments &raquo;') ?>" /></p>
    </form>
<?php
} else {
    // nothing to approve
    echo __("<p>Currently there are no comments for you to moderate.</p>") . "\n";
}
?>

</div>

<?php

break;
}


include('admin-footer.php') ?>