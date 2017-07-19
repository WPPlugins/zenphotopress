<?php
/**
 * Created by PhpStorm.
 * User: alena
 * Date: 03/01/16
 * Time: 12:13 PM
 */

/**
 * Build the absolute path for the ZenPhoto admin directory
 */
function zenphotopress_get_admin_path($web_path) {
	if (get_option('siteurl') != "" && ABSPATH != "") {
		// Calculate wordpress minimal URL and path
		$wordpress_url = get_option('siteurl');
		$wordpress_path = ABSPATH;
		$base_url = basename($wordpress_url);
		$base_path = basename($wordpress_path);
		while ($base_url == $base_path) {
			if (substr(dirname($wordpress_url), -1) != ":") {
				$wordpress_url = dirname($wordpress_url);
				$wordpress_path = dirname($wordpress_path);
				$base_url = basename($wordpress_url);
				$base_path = basename($wordpress_path);
			} else {
				// Stop when domain name is reached
				break;
			}
		}
		// Calculate Zenphoto minimal path
		$admin_minimal = zenphotopress_build_path($wordpress_path, str_replace($wordpress_url, "", $web_path));

		if (is_dir(zenphotopress_build_path($admin_minimal, "zp-data"))) {
			// Found Zenphoto 1.2.6 data directory
			return zenphotopress_build_path($admin_minimal, "zp-data");
		} else if (is_dir(zenphotopress_build_path($admin_minimal, "zp-core"))) {
			// Found Zenphoto 1.1 admin directory
			return zenphotopress_build_path($admin_minimal, "zp-core");
		} else if (is_dir(zenphotopress_build_path($admin_minimal, "zen"))) {
			// Found Zenphoto <1.1 admin directory
			return zenphotopress_build_path($admin_minimal, "/zen");
		} else {
			// Admin directory not found
			return null;
		}
	} else {
		// Admin path cannot be retrieved
		return null;
	}
}

/**
 * Build a path from two chunks
 */
function zenphotopress_build_path($part1, $part2) {
	$part1 = rtrim($part1, "/");
	$part2 = trim($part2, "/");
	return $part1 . "/" . $part2;
}

/**
 * Update old database variables to their new version
 * and import the old value
 */
function zenphotopress_update() {
	$options = array(
		'zp_admin_path' => '',
		'zp_web_path' => '',
		'zenpress_custom_what' => '',
		'zenpress_custom_link' => '',
		'zenpress_custom_close' => '',
		'zenpress_custom_show' => '',
		'zenpress_custom_orderby' => '',
		'zenpress_custom_wrap' => '',
		'zenpress_custom_size' => '',
		'zenpress_custom_width' => '',
		'zenpress_custom_height' => ''
	);
	// Get old values
	$empty = true;
	foreach($options as $key => $value) {
		$options[$key] = get_option($key);
		if ($options[$key] != '') {
			$empty = false;
		}
	}

	if ($empty) {
		return false;
	}

	// Create new values
	foreach($options as $key => $value) {
		$name = preg_replace('/^(zp|zenpress)/', 'zenphotopress', $key);
		update_option($name, $value);
	}

	// Delete old values
	foreach($options as $key => $value) {
		delete_option($key);
	}

	return true;
}


/**
 * Print a <select> HTML element.
 * @param $name	Name of the element
 * @param $options	Array of select options. Each option is an array of name and value
 * @param $selected	Value of the selected option (if any)
 */
function zp_printFormSelect($name,$options,$selected=NULL) {
	echo '<select name="'.$name.'" style="width:16em">';
	foreach ($options as $value) {
		$value[value]==$selected ? $sel=' selected="selected"' : $sel = '';
		echo '<option value="'.$value[value].'"'.$sel.'>'.$value[title].'</option>';
	}
	echo '</select>';
}