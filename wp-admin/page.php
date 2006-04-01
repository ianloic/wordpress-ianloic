<?php
require_once('admin.php');

$wpvarstoreset = array('action');

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

if (isset($_POST['deletepost'])) {
$action = "delete";
}

switch($action) {
case 'post':

	$page_ID = write_post();

	// Redirect.
	if (!empty($_POST['mode'])) {
	switch($_POST['mode']) {
		case 'bookmarklet':
			$location = $_POST['referredby'];
			break;
		case 'sidebar':
			$location = 'sidebar.php?a=b';
			break;
		default:
			$location = 'page-new.php';
			break;
		}
	} else {
		$location = 'page-new.php?posted=true';
	}

	if ( isset($_POST['save']) )
		$location = "page.php?action=edit&post=$page_ID";

	header("Location: $location");
	exit();
	break;

case 'edit':
	$title = __('Edit');
	$parent_file = 'edit.php';
	$submenu_file = 'edit-pages.php';
	$editing = true;
	require_once('admin-header.php');

	$page_ID = $post_ID = $p = (int) $_GET['post'];

	if ( !current_user_can('edit_page', $page_ID) )
		die ( __('You are not allowed to edit this page.') );

	$post = get_post_to_edit($page_ID);

	include('edit-page-form.php');
	?>
	<div id='preview' class='wrap'>
	<h2 id="preview-post"><?php _e('Page Preview (updated when page is saved)'); ?> <small class="quickjump"><a href="#write-post"><?php _e('edit &uarr;'); ?></a></small></h2>
		<iframe src="<?php echo add_query_arg('preview', 'true', get_permalink($post->ID)); ?>" width="100%" height="600" ></iframe>
	</div>
	<?php
	break;

case 'editattachment':
	$page_id = $post_ID = (int) $_POST['post_ID'];

	// Don't let these be changed
	unset($_POST['guid']);
	$_POST['post_type'] = 'attachment';

	// Update the thumbnail filename
	$oldmeta = $newmeta = get_post_meta($page_id, '_wp_attachment_metadata', true);
	$newmeta['thumb'] = $_POST['thumb'];

	if ( '' !== $oldmeta )
		update_post_meta($page_id, '_wp_attachment_metadata', $newmeta, $oldmeta);
	else
		add_post_meta($page_id, '_wp_attachment_metadata', $newmeta);

case 'editpost':
	$page_ID = edit_post();

	if ($_POST['save']) {
		$location = $_SERVER['HTTP_REFERER'];
	} elseif ($_POST['updatemeta']) {
		$location = $_SERVER['HTTP_REFERER'] . '&message=2#postcustom';
	} elseif ($_POST['deletemeta']) {
		$location = $_SERVER['HTTP_REFERER'] . '&message=3#postcustom';
	} elseif (isset($_POST['referredby']) && $_POST['referredby'] != $_SERVER['HTTP_REFERER']) {
		$location = $_POST['referredby'];
		if ( $_POST['referredby'] == 'redo' )
			$location = get_permalink( $page_ID );
	} elseif ($action == 'editattachment') {
		$location = 'attachments.php';
	} else {
		$location = 'page-new.php';
	}
	header ('Location: ' . $location); // Send user on their way while we keep working

	exit();
	break;

case 'delete':
	check_admin_referer();

	$page_id = (isset($_GET['post']))  ? intval($_GET['post']) : intval($_POST['post_ID']);

	$page = & get_post($page_id);

	if ( !current_user_can('delete_page', $page_id) )
		die( __('You are not allowed to delete this page.') );

	if ( $page->post_type == 'attachment' ) {
		if ( ! wp_delete_attachment($page_id) )
			die( __('Error in deleting...') );
	} else {
		if ( !wp_delete_post($page_id) ) 
			die( __('Error in deleting...') );
	}

	$sendback = $_SERVER['HTTP_REFERER'];
	if (strstr($sendback, 'page.php')) $sendback = get_settings('siteurl') .'/wp-admin/page.php';
	elseif (strstr($sendback, 'attachments.php')) $sendback = get_settings('siteurl') .'/wp-admin/attachments.php';
	$sendback = preg_replace('|[^a-z0-9-~+_.?#=&;,/:]|i', '', $sendback);
	header ('Location: ' . $sendback);
	exit();
	break;

default:
	header('Location: edit-pages.php');
	exit();
	break;
} // end switch
include('admin-footer.php');
?>
