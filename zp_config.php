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

require_once( 'include/zp_config.inc.php' );


if ( isset( $_POST['zenphotopress_update'] ) ) {
	// ****** Update variables to 1.3 version
	if ( zenphotopress_update() ) {
		echo '<div id="message" class="updated fade"><p><strong>';
		_e( 'ZenphotoPress path and popup preferences have been successfully imported',
			'zenphotopress' );
		echo '</strong></p></div>';
	} else {
		echo '<div id="message" class="error"><p><strong>';
		_e( 'There was an error in the update process', 'zenphotopress' );
		echo '</strong></p></div>';
	}


}

$zenphotopress_show_admin_path = false;
$zenphotopress_error_msg       = '';

if ( isset( $_POST['info_update'] ) ) {
	// ****** Update operation
	if ( ! $_POST['zenphotopress_admin_path'] ) {
		// Admin path was not provided -> calculate it
		$zenphotopress_admin_path
			= zenphotopress_get_admin_path( $_POST['zenphotopress_web_path'] );
		if ( $zenphotopress_admin_path == null ) {
			$zenphotopress_error_msg
				= __( 'Zenphoto data path could not be retrieved. Please insert it manually below. Try to come up with something similar to "'
				      . ABSPATH
				      . '" which is your current <i>Wordpress</i> path.',
				'zenphotopress' );
		}
	} else {
		// Admin path was provided -> use it
		if ( is_dir( $_POST['zenphotopress_admin_path'] ) ) {
			$zenphotopress_admin_path = $_POST['zenphotopress_admin_path'];
		} else {
			$zenphotopress_admin_path = null;
			$zenphotopress_error_msg
			                          = __( 'Wrong Zenphoto data path. It should point to the filesystem folder containing zp-config.php. Try to come up with something similar to "'
			                                . ABSPATH
			                                . '" which is your current <i>Wordpress</i> path.',
				'zenphotopress' );
		}
	}
	if ( $zenphotopress_admin_path == null ) {
		// Admin path is wrong -> error
		if ( ! $zenphotopress_error_msg ) {
			$zenphotopress_error_msg = __( 'Error', 'zenphotopress' );
		}
		echo '<div id="message" class="error"><p><strong>'
		     . $zenphotopress_error_msg . '</strong></p></div>';
		$zenphotopress_show_admin_path = true;
	} else {
		// Set path values
		update_option( 'zenphotopress_admin_path', $zenphotopress_admin_path );
		update_option( 'zenphotopress_web_path',
			$_POST['zenphotopress_web_path'] );

		// Set Lightbox rel value
		update_option( 'zenphotopress_rel_value',
			$_POST['zenphotopress_rel_value'] );

		// Set custom popup values
		update_option( 'zenphotopress_custom_what',
			$_POST['zenphotopress_custom_what'] );
		update_option( 'zenphotopress_custom_link',
			$_POST['zenphotopress_custom_link'] );
		update_option( 'zenphotopress_custom_close',
			$_POST['zenphotopress_custom_close'] );
		update_option( 'zenphotopress_custom_show',
			$_POST['zenphotopress_custom_show'] );
		update_option( 'zenphotopress_custom_orderby',
			$_POST['zenphotopress_custom_orderby'] );
		update_option( 'zenphotopress_custom_wrap',
			$_POST['zenphotopress_custom_wrap'] );
		update_option( 'zenphotopress_custom_size',
			$_POST['zenphotopress_custom_size'] );
		update_option( 'zenphotopress_custom_width',
			$_POST['zenphotopress_custom_width'] );
		update_option( 'zenphotopress_custom_height',
			$_POST['zenphotopress_custom_height'] );

		echo '<div id="message" class="updated fade"><p><strong>';
		_e( 'ZenphotoPress options updated successfully.', 'zenphotopress' );
		echo '</strong></p></div>';
		$zenphotopress_show_admin_path = true;
	}
} else {
	// ****** Normal visualization
	if ( get_option( "zenphotopress_admin_path" ) ) {
		// Ad admin path is set
		$zenphotopress_admin_path
			= zenphotopress_get_admin_path( get_option( "zenphotopress_web_path" ) );
		if ( $zenphotopress_admin_path
		     != get_option( "zenphotopress_admin_path" )
		) {
			// Admin path is not standard -> show it
			$zenphotopress_show_admin_path = true;
		}
	}
}

include( "include/zp_config.inc.html" );

?>
