<?php
/*
Plugin Name: ZenphotoPress
Plugin URI: http://www.simbul.net/zenphotopress
Description: This plugin adds an interface for inserting Zenphoto thumbnails in Wordpress posts and widgets. Works with Wordpress 4.4 and ZenPhoto 1.4.
Author: Alessandro Morandi
Version: 1.8
Author URI: http://www.simbul.net
*/

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


// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) ) {
	define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}
if ( ! defined( 'WP_PLUGIN_URL' ) ) {
	define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins' );
}
if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

/**
 * This class is meant to avoid name collisions with existing WP functions
 */
class zenphotopress {

	/**
	 * Add the configuration page to the "Options" WP menu.
	 */
	function add_pages() {
		if ( current_user_can( 'manage_options' ) ) {
			add_options_page(
				__( 'ZenphotoPress Configuration', 'zenphotopress' ),
				'ZenphotoPress',
				'manage_options',
				'zenphotopress/zp_config.php'
			);
		}
	}

	/**
	 * Add CSS definitions (margin for thumbnails when Word Wrap is used)
	 */
	function add_style() {
		$output
			= '<style type="text/css">
			.ZenphotoPress_left {margin-right:1em;}
			.ZenphotoPress_right {margin-left:1em;}
			.ZenphotoPress_shortcode dt {float:left; margin-right:1em;}
			.ZenphotoPress_shortcode hr {clear:left; visibility:hidden;}
		</style>';
		echo $output;
	}

	/**
	 * Add the plugin for the tinyMCE editor
	 */
	function extended_editor_mce_plugins( $plugin_array ) {
		$plugin_array['zenphotopress'] = plugins_url(
			'tinymce/editor_plugin.js',
			__FILE__
		);

		return $plugin_array;
	}

	/**
	 * Add the button for the tinyMCE editor
	 */
	function extended_editor_mce_buttons( $buttons ) {
		array_push( $buttons, 'separator', 'zenphotopress' );

		return $buttons;
	}

	/**
	 * Add javascript to make ZenphotoPress available with the plain text editor
	 */
	function add_plainTextEditor_js() {

		$url = WP_PLUGIN_URL . '/zenphotopress/tinymce';
		if ( wp_script_is( 'quicktags' ) ) {
			$script
				= <<<EOF
			<script type="text/javascript">
				function zp_open() {
					var url = "{$url}/zp_popup.php?tinyMCE=0";
					var name = "zenphotopress_popup";
					var w = 480;
					var h = 480;
					var valLeft = (screen.width) ? (screen.width - w) / 2 : 0;
					var valTop = (screen.height) ? (screen.height - h) / 2 : 0;
					var features = "width=" + w + ",height=" + h + ",left=" + valLeft + ",top=" + valTop + ",resizable=1,scrollbars=1";
					var zenphotopressWindow = window.open(url, name, features);
					zenphotopressWindow.focus();
				}
				QTags.addButton('ed_ZenphotoPress', 'Insert ZenPhoto photos', zp_open, '', '', 'ZenphotoPress', 201);
			</script>
EOF;
			echo $script;
		}
	}

	function extend_tinymce() {
		// Check permissions
		if ( ! current_user_can( 'edit_posts' )
		     && ! current_user_can(
				'edit_pages'
			)
		) {
			return;
		}

		// Add only in Rich Editor mode
		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			add_filter(
				'mce_external_plugins',
				array( 'zenphotopress', 'extended_editor_mce_plugins' )
			);
			add_filter(
				'mce_buttons',
				array( 'zenphotopress', 'extended_editor_mce_buttons' )
			);
		}
	}

	function parse_shortcode( $atts ) {
		global $post;
		include_once( 'classes.php' );
		$id = 'post-' . $post->ID;
		$shortcode_atts
		    = shortcode_atts(
			array(
				'sort'   => 'random',
				'number' => 3,
				'album'  => 0,
			),
			$atts
		);
		extract( $shortcode_atts );

		return ZenphotoPressUI::getBadge(
			$shortcode_atts['sort'],
			$shortcode_atts['number'],
			$shortcode_atts['album'],
			'ZenphotoPress_shortcode',
			$id
		);
	}

	function add_shortcodes() {
		if ( function_exists( 'add_shortcode' ) ) {
			add_shortcode(
				'zenphotopress',
				array( 'zenphotopress', 'parse_shortcode' )
			);
		}
	}
}

class ZenphotoPress_Widget extends WP_Widget {
	function ZenphotoPress_Widget() {
		parent::__construct( false, $name = 'ZenphotoPress Badge' );
	}

	function widget( $args, $instance ) {
		include_once( 'classes.php' );
		//extract( $args );
		// echo $id; This is a unique id for the widget
		$title   = empty( $instance['title'] ) ? 'Zenphoto Badge'
			: $instance['title'];
		$sort    = empty( $instance['sort'] ) ? 'random' : $instance['sort'];
		$number  = empty( $instance['number'] ) ? 5 : $instance['number'];
		$albumid = empty( $instance['albumid'] ) ? 0 : $instance['albumid'];

		echo $args['before_widget'];
		echo $args['before_title'] . $title . $args['after_title'];
		ZenphotoPressUI::printBadge(
			$sort,
			$number,
			$albumid,
			'ZenphotoPress_widget',
			$args['id']
		);
		echo $args['after_widget'];
	}

	function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	function form( $instance ) {
		include_once( 'classes.php' );

		$title   = esc_attr( $instance['title'] );
		$sort    = esc_attr( $instance['sort'] );
		$number  = esc_attr( $instance['number'] );
		$albumid = esc_attr( $instance['albumid'] );

		$albums = ZenphotoProxy::getAlbums();

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php _e( 'Title:' ); ?>
				<input class="widefat"
				       id="<?php echo $this->get_field_id( 'title' ); ?>"
				       name="<?php echo $this->get_field_name( 'title' ); ?>"
				       type="text" value="<?php echo $title; ?>"/>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'sort' ); ?>">
				<?php _e( 'Sorting:' ); ?>
				<select id="<?php echo $this->get_field_id( 'sort' ); ?>"
				        name="<?php echo $this->get_field_name( 'sort' ); ?>">
					<option value="random" <?php echo $sort == 'random'
						? "selected='selected'" : '' ?>>Random
					</option>
					<option value="latest" <?php echo $sort == 'latest'
						? "selected='selected'" : '' ?>>Latest
					</option>
				</select>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>">
				<?php _e( 'Number of images to show:' ); ?>
				<input class="widefat"
				       id="<?php echo $this->get_field_id( 'number' ); ?>"
				       name="<?php echo $this->get_field_name( 'number' ); ?>"
				       type="text" value="<?php echo $number; ?>"/>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'albumid' ); ?>">
				<?php _e( 'Album:' ); ?>
				<select id="<?php echo $this->get_field_id( 'albumid' ); ?>"
				        name="<?php echo $this->get_field_name( 'albumid' ); ?>">
					<option value="0" <?php echo $albumid == 0
						? "selected='selected'" : '' ?>>All albums
					</option>
					<?php ZenphotoPressAdminUI::printNestedOptions(
						$albums,
						0,
						$albumid
					); ?>
				</select>
			</label>
		</p>
		<?php
	}
}

// Add actions
add_action( 'admin_menu', array( 'zenphotopress', 'add_pages' ) );
add_action( 'wp_head', array( 'zenphotopress', 'add_style' ) );
add_action( 'init', array( 'zenphotopress', 'extend_tinymce' ) );
add_action( 'init', array( 'zenphotopress', 'add_shortcodes' ) );

// Add filters
add_filter(
	'admin_print_footer_scripts',
	array( 'zenphotopress', 'add_plainTextEditor_js' )
);

// Add widget
add_action(
	'widgets_init',
	create_function( '', 'return register_widget("ZenphotoPress_Widget");' )
);

?>