<?php
/**
 * Copyright 2006/2009  Alessandro Morandi  (email : webmaster@simbul.net)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once( '../classes.php' );

// Start output buffering
ob_start( array( 'ZenphotoPressAdminUI', 'ob_callback' ) );

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php _e( 'ZenphotoPress Dialog', 'zenphotopress' ) ?></title>
	<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>
	<?php if ( $_GET['tinyMCE'] ) { ?>
		<script type="text/javascript"
		        src='<?= get_bloginfo( 'wpurl' ) ?>/wp-includes/js/tinymce/tiny_mce_popup.js'></script>
	<?php } else { ?>
	<link rel='stylesheet' href='css/zenphotopress.css' type='text/css'/>
	<?php } ?>
	<link rel='stylesheet' href='css/zenphotopress_additional.css'
	      type='text/css'/>
	<script type="text/javascript" src='js/functions.js'></script>
</head>

<body>
<?php
if ( ! get_option( 'zenphotopress_web_path' ) ) {
	$zp_eh->addError( "Could not find Zenphoto gallery. Remember to configure ZenphotoPress!" );
}
$section = $_GET['section'];

ZenphotoPressAdminUI::print_breadcrumb( $section );

if ( ! $section ) {
	// Starting page: no section selected
	echo "<h2>Insert Image</h2>";
	ZenphotoPressAdminUI::print_albums( $_POST['album'], 'image' );

	echo "<h2>Insert Gallery</h2>";
	ZenphotoPressAdminUI::print_gallery();
} else if ( $section == 'image' ) {
	// Image insertion section
	ZenphotoPressAdminUI::print_image_select( $_POST['album'] );
} else if ( $section == 'gallery' ) {
	// Nothing to do: everything's in the starting page
}

// Stop buffering output
ob_end_flush();
?>
</body>
</html>
