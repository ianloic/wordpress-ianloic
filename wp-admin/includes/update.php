<?php

// The admin side of our 1.0 update system

function core_update_footer( $msg = '' ) {
	if ( !current_user_can('manage_options') )
		return sprintf( '| '.__( 'Version %s' ), $GLOBALS['wp_version'] );

	$cur = get_option( 'update_core' );

	switch ( $cur->response ) {
	case 'development' :
		return sprintf( '| '.__( 'You are using a development version (%s). Cool! Please <a href="%s">stay updated</a>.' ), $GLOBALS['wp_version'], 'http://wordpress.org/download/svn/' );
	break;

	case 'upgrade' :
		return sprintf( '| <strong>'.__( 'Your WordPress %s is out of date. <a href="%s">Please update</a>.' ).'</strong>', $GLOBALS['wp_version'], $cur->url );
	break;

	case 'latest' :
	default :
		return sprintf( '| '.__( 'Version %s' ), $GLOBALS['wp_version'] );
	break;
	}
}
add_filter( 'update_footer', 'core_update_footer' );

function update_nag() {
	$cur = get_option( 'update_core' );

	if ( ! isset( $cur->response ) || $cur->response != 'upgrade' )
		return false;

	if ( current_user_can('manage_options') )
		$msg = sprintf( __('A new version of WordPress is available! <a href="%s">Please update now</a>.'), $cur->url );
	else
		$msg = __('A new version of WordPress is available! Please notify the site administrator.');

	echo "<div id='update-nag'>$msg</div>";
}
add_action( 'admin_notices', 'update_nag', 3 );

function wp_update_plugins() {
	global $wp_version;

	if ( !function_exists('fsockopen') )
		return false;

	$plugins = get_plugins();
	$active  = get_option( 'active_plugins' );
	$current = get_option( 'update_plugins' );

	$new_option = '';
	$new_option->last_checked = time();

	$plugin_changed = false;
	foreach ( $plugins as $file => $p ) {
		$new_option->checked[ $file ] = $p['Version'];

		if ( !isset( $current->checked[ $file ] ) ) {
			$plugin_changed = true;
			continue;
		}

		if ( $current->checked[ $file ] != $p['Version'] )
			$plugin_changed = true;
	}

	if (
		isset( $current->last_checked ) &&
		43200 > ( time() - $current->last_checked ) &&
		!$plugin_changed
	)
		return false;

	$to_send->plugins = $plugins;
	$to_send->active = $active;
	$send = serialize( $to_send );

	$request = 'plugins=' . urlencode( $send );
	$http_request  = "POST /plugins/update-check/1.0/ HTTP/1.0\r\n";
	$http_request .= "Host: api.wordpress.org\r\n";
	$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
	$http_request .= "Content-Length: " . strlen($request) . "\r\n";
	$http_request .= 'User-Agent: WordPress/' . $wp_version . '; ' . get_bloginfo('url') . "\r\n";
	$http_request .= "\r\n";
	$http_request .= $request;

	$response = '';
	if( false != ( $fs = @fsockopen( 'api.wordpress.org', 80, $errno, $errstr, 3) ) && is_resource($fs) ) {
		fwrite($fs, $http_request);

		while ( !feof($fs) )
			$response .= fgets($fs, 1160); // One TCP-IP packet
		fclose($fs);
		$response = explode("\r\n\r\n", $response, 2);
	}

	$response = unserialize( $response[1] );

	if ( $response )
		$new_option->response = $response;

	update_option( 'update_plugins', $new_option );
}
add_action( 'load-plugins.php', 'wp_update_plugins' );

function wp_plugin_update_row( $file ) {
	global $plugin_data;
	$current = get_option( 'update_plugins' );
	if ( !isset( $current->response[ $file ] ) )
		return false;

	$r = $current->response[ $file ];

	echo "<tr><td colspan='5' class='plugin-update'>";
	printf( __('There is a new version of %1$s available. <a href="%2$s">Download version %3$s here</a> or <a href="%4$s">upgrade automatically</a>.'), $plugin_data['Name'], $r->url, $r->new_version, "update.php?action=upgrade-plugin&amp;plugin=$file" );
	echo "</td></tr>";
}
add_action( 'after_plugin_row', 'wp_plugin_update_row' );

function wp_update_plugin($plugin, $feedback = '') {
	global $wp_filesystem;

	if ( !empty($feedback) )
		add_filter('update_feedback', $feedback);

	// Is an update available?
	$current = get_option( 'update_plugins' );
	if ( !isset( $current->response[ $plugin ] ) )
		return new WP_Error('up_to_date', __('The plugin is at the latest version.'));

	// Is a filesystem accessor setup?
	if ( ! $wp_filesystem || !is_object($wp_filesystem) )
		WP_Filesystem();

	if ( ! is_object($wp_filesystem) )
		return new WP_Error('fs_unavailable', __('Could not access filesystem.'));

	if ( $wp_filesystem->errors->get_error_code() ) 
		return new WP_Error('fs_error', __('Filesystem error'), $wp_filesystem->errors);

	// Get the URL to the zip file
	$r = $current->response[ $plugin ];

	if ( empty($r->package) )
		return new WP_Error('no_package', __('Upgrade package not available.'));

	// Download the package
	$package = $r->package;
	apply_filters('update_feedback', __("Downloading update from $package"));
	$file = download_url($package);

	if ( !$file )
		return new WP_Error('download_failed', __('Download failed.'));

	$name = basename($plugin, '.php');
	$working_dir = ABSPATH . 'wp-content/upgrade/' . $name;

	// Clean up working directory
	$wp_filesystem->delete($working_dir, true);

	apply_filters('update_feedback', __("Unpacking the update"));
	// Unzip package to working directory
	$result = unzip_file($file, $working_dir);
	if ( is_wp_error($result) ) {
		unlink($file);
		$wp_filesystem->delete($working_dir, true);
		return $result;
	}

	// Once installed, delete the package
	unlink($file);
	
	// Remove the existing plugin.
	apply_filters('update_feedback', __("Removing the old version of the plugin"));
	$wp_filesystem->delete(ABSPATH . PLUGINDIR . "/$plugin");
	$plugin_dir = dirname(ABSPATH . PLUGINDIR . "/$plugin");

	// If plugin is in its own directory, recursively delete the directory.
	if ( '.' != $plugin_dir )
		$wp_filesystem->delete($plugin_dir, true);

	apply_filters('update_feedback', __("Installing the latest version"));
	// Copy new version of plugin into place.
	copy_dir($working_dir, ABSPATH . PLUGINDIR);

	// Remove working directory
	$wp_filesystem->delete($working_dir, true);

	// Force refresh of plugin update information
	delete_option('update_plugins');
}

?>
