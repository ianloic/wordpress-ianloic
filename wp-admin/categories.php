<?php
require_once('../wp-includes/wp-l10n.php');

$title = __('Categories');

function add_magic_quotes($array) {
    foreach ($array as $k => $v) {
        if (is_array($v)) {
            $array[$k] = add_magic_quotes($v);
        } else {
            $array[$k] = addslashes($v);
        }
    }
    return $array;
}

if (!get_magic_quotes_gpc()) {
    $_GET    = add_magic_quotes($_GET);
    $_POST   = add_magic_quotes($_POST);
    $_COOKIE = add_magic_quotes($_COOKIE);
}

$wpvarstoreset = array('action','standalone','cat');
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

switch($action) {

case 'addcat':

    $standalone = 1;
    require_once('admin-header.php');
    
    if ($user_level < 3)
        die (__('Cheatin&#8217; uh?'));
    
    $cat_name= addslashes(stripslashes(stripslashes($_POST['cat_name'])));
    $cat_ID = $wpdb->get_var("SELECT cat_ID FROM $wpdb->categories ORDER BY cat_ID DESC LIMIT 1") + 1;
    $category_nicename = sanitize_title($cat_name, $cat_ID);
    $category_description = addslashes(stripslashes(stripslashes($_POST['category_description'])));
    $cat = intval($_POST['cat']);

    $wpdb->query("INSERT INTO $wpdb->categories (cat_ID, cat_name, category_nicename, category_description, category_parent) VALUES ('0', '$cat_name', '$category_nicename', '$category_description', '$cat')");
    
    header('Location: categories.php?message=1#addcat');

break;

case 'Delete':

    $standalone = 1;
    require_once('admin-header.php');

    check_admin_referer();

    $cat_ID = intval($_GET["cat_ID"]);
    $cat_name = get_catname($cat_ID);
    $cat_name = addslashes($cat_name);
    $category = $wpdb->get_row("SELECT * FROM $wpdb->categories WHERE cat_ID = '$cat_ID'");
    $cat_parent = $category->category_parent;

    if (1 == $cat_ID)
        die(sprintf(__("Can't delete the <strong>%s</strong> category: this is the default one"), $cat_name));

    if ($user_level < 3)
        die (__('Cheatin&#8217; uh?'));

    $wpdb->query("DELETE FROM $wpdb->categories WHERE cat_ID = '$cat_ID'");
    $wpdb->query("UPDATE $wpdb->categories SET category_parent = '$cat_parent' WHERE category_parent = '$cat_ID'");
    $wpdb->query("UPDATE $wpdb->post2cat SET category_id='1' WHERE category_id='$cat_ID'");

    header('Location: categories.php?message=2');

break;

case 'edit':

    require_once ('admin-header.php');
    $cat_ID = intval($_GET['cat_ID']);
    $category = $wpdb->get_row("SELECT * FROM $wpdb->categories WHERE cat_ID = '$cat_ID'");
    $cat_name = stripslashes($category->cat_name);
    ?>

<div class="wrap">
    <h2><?php _e('Edit Category') ?></h2>
    <form name="editcat" action="categories.php" method="post">
        <input type="hidden" name="action" value="editedcat" />
        <input type="hidden" name="cat_ID" value="<?php echo $_GET['cat_ID'] ?>" />
        <p><?php _e('Category name:') ?><br />
        <input type="text" name="cat_name" value="<?php echo htmlspecialchars($cat_name); ?>" /></p>
        <p><?php _e('Category parent:') ?><br />
        <select name='cat' class='postform'>
        <option value='0'<?php if (!$category->category_parent) echo " selected='selected'"; ?>><?php _e('None') ?></option>
        <?php wp_dropdown_cats($category->cat_ID, $category->category_parent); ?>
        </select>
        </p>

        <p><?php _e('Description:') ?><br />
										     <textarea name="category_description" rows="5" cols="50" style="width: 97%;"><?php echo htmlspecialchars($category->category_description, ENT_NOQUOTES); ?></textarea></p>
        <p class="submit"><input type="submit" name="submit" value="<?php _e('Edit category &raquo;') ?>" /></p>
    </form>
</div>

    <?php

break;

case 'editedcat':

    $standalone = 1;
    require_once('admin-header.php');

    if ($user_level < 3)
        die (__('Cheatin&#8217; uh?'));
    
    $cat_name = $wpdb->escape(stripslashes($_POST['cat_name']));
    $cat_ID = (int) $_POST['cat_ID'];
    $category_nicename = sanitize_title($cat_name, $cat_ID);
    $category_description = $wpdb->escape(stripslashes($_POST['category_description']));

    $wpdb->query("UPDATE $wpdb->categories SET cat_name = '$cat_name', category_nicename = '$category_nicename', category_description = '$category_description', category_parent = '$cat' WHERE cat_ID = '$cat_ID'");
    
    header('Location: categories.php?message=3');

break;

default:

    $standalone = 0;
    require_once ('admin-header.php');
    if ($user_level < 3) {
        die(sprintf(__("You have no right to edit the categories for this blog.<br />Ask for a promotion to your <a href='mailto:%s'>blog admin</a>. :)"), get_settings('admin_email')));
    }
$messages[1] = __('Category added.');
$messages[2] = __('Category deleted.');
$messages[3] = __('Category updated.');
?>
<?php if (isset($_GET['message'])) : ?>
<div class="updated"><p><?php echo $messages[$_GET['message']]; ?></p></div>
<?php endif; ?>

<div class="wrap">
     <h2><?php printf(__('Current Categories (<a href="%s">add new</a>)'), '#addcat') ?> </h2>
<table width="100%" cellpadding="3" cellspacing="3">
	<tr>
		<th scope="col"><?php _e('ID') ?></th>
        <th scope="col"><?php _e('Name') ?></th>
        <th scope="col"><?php _e('Description') ?></th>
        <th scope="col"><?php _e('# Posts') ?></th>
        <th colspan="2"><?php _e('Action') ?></th>
	</tr>
<?php
cat_rows();
?>
</table>

</div>

<div class="wrap">
    <p><?php printf(__('<strong>Note:</strong><br />
Deleting a category does not delete posts from that category, it will just
set them back to the default category <strong>%s</strong>.'), get_catname(1)) ?>
  </p>
</div>

<div class="wrap">
    <h2><?php _e('Add New Category') ?></h2>
    <form name="addcat" id="addcat" action="categories.php" method="post">
        
        <p><?php _e('Name:') ?><br />
        <input type="text" name="cat_name" value="" /></p>
        <p><?php _e('Category parent:') ?><br />
        <select name='cat' class='postform'>
        <option value='0'><?php _e('None') ?></option>
        <?php wp_dropdown_cats(0); ?>
        </select></p>
        <p><?php _e('Description: (optional)') ?> <br />
        <textarea name="category_description" rows="5" cols="50" style="width: 97%;"></textarea></p>
        <p class="submit"><input type="hidden" name="action" value="addcat" /><input type="submit" name="submit" value="<?php _e('Add Category &raquo;') ?>" /></p>
    </form>
</div>

    <?php
break;
}

include('admin-footer.php');
?>