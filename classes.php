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

define( 'ZP_E_ALL', 0 );
define( 'ZP_E_INFO', 1 );
define( 'ZP_E_WARNING', 2 );
define( 'ZP_E_ERROR', 3 );
define( 'ZP_E_FATAL', 4 );

// Create Error Handling Object
$zp_eh = new ZenphotoPressErrorHandler( ZP_E_ERROR );  // To debug use ZP_E_ALL

$zp_eh->addInfo( 'ZPP Version:', '1.8' );
$zp_eh->addInfo( 'PHP Version:', phpversion() );
$zp_eh->addInfo( 'Current working directory:', getcwd() );
$zp_eh->addInfo( 'POST:', $_POST );
$zp_eh->addInfo( 'GET:', $_GET );

if ( ! function_exists( 'get_option' ) ) {
	$wp_path = ZenphotoPressHelpers::getWPBasePath();
	require_once( $wp_path . '/wp-load.php' );
}

// Check the method used for ZenphotoProxy calls
if ( function_exists( 'curl_init' ) ) {
	$zp_eh->addInfo( 'ZenphotoProxy method:', 'cURL' );
} else if ( function_exists( 'file_get_contents' ) ) {
	$zp_eh->addInfo( 'ZenphotoProxy method:', 'file_get_contents' );
} else {
	$zp_eh->addInfo( 'ZenphotoProxy method:', 'fsockopen' );
}

$GLOBALS['zp_eh']        = $zp_eh;
$GLOBALS['zp_admin_dir'] = 'zp-core';

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
 * This is a (static) class meant to manage the user interface, like shortcodes
 * and widgets.
 */
class ZenphotoPressUI {

	/**
	 * Return HTML code for a badge containing images.
	 * The badge is an unordered list with base class "ZenphotoPress_badge".
	 *
	 * @param string  $sort    {random|latest|sort_order|title|id}
	 * @param integer $number  Number of images to show
	 * @param integer $albumid ID of the album to show (0 for all albums)
	 * @param string  $classes Additional classes for CSS
	 * @param string  $post_id ID of the post containing the badge (if any)
	 *
	 * @return string HTML code for a badge
	 */
	public static function getBadge(
		$sort,
		$number,
		$albumid = 0,
		$classes = '',
		$post_id = ''
	) {
		global $zp_admin_dir;

		$rel_value    = get_option( 'zenphotopress_rel_value', '' );
		$use_lightbox = strlen( $rel_value ) > 0;

		$rel_attribute = '';
		if ( $use_lightbox ) {
			$unique_id     = trim( $post_id . '-' . $albumid, '-' );
			$rel_attribute = 'rel="' . $rel_value . '[' . $unique_id . ']"';

		}

		$out = '';

		$class_string = trim( "ZenphotoPress_badge " . $classes );

		$zp_options  = ZenphotoProxy::getOptions();
		$mod_rewrite = $zp_options['mod_rewrite'];
		$thumb_size  = $zp_options['thumb_size'];
		$images      = ZenphotoProxy::getImages( $sort, $number, $albumid );

		$out .= '<dl class="' . $class_string . '">';
		foreach ( $images as $image ) {
			if ( $mod_rewrite ) {
				$imgpath   = get_option( 'zenphotopress_web_path' ) . "/"
				             . ZenphotoPressHelpers::pathurlencode( $image[ album_url ] )
				             . "/" . urlencode( $image[ url ] );
				$thumbpath = get_option( 'zenphotopress_web_path' ) . "/"
				             . ZenphotoPressHelpers::pathurlencode( $image[ album_url ] )
				             . "/image/thumb/" . urlencode( $image[ url ] );
			} else {
				$imgpath   = get_option( 'zenphotopress_web_path' )
				             . "/index.php?album="
				             . ZenphotoPressHelpers::pathurlencode( $image[ album_url ] )
				             . "&image=" . urlencode( $image[ url ] );
				$thumbpath = get_option( 'zenphotopress_web_path' ) . "/"
				             . $zp_admin_dir . "/i.php?a="
				             . ZenphotoPressHelpers::pathurlencode( $image[ album_url ] )
				             . "&i=" . urlencode( $image[ url ] ) . "&s=thumb";
			}
			// TEMPORARILY IGNORE mod_rewrite FOR IMAGE PATH TO BE COMPATIBLE WITH ZENPHOTO 1.4
			$thumbpath = get_option( 'zenphotopress_web_path' ) . "/"
			             . $zp_admin_dir . "/i.php?a="
			             . ZenphotoPressHelpers::pathurlencode( $image[ album_url ] )
			             . "&i=" . urlencode( $image[ url ] ) . "&s=thumb";
			if ( $image[ thumbX ] && $image[ thumbY ] && $image[ thumbW ]
			     && $image[ thumbH ]
			) {
				// Custom thumb cropping
				$thumbpath = get_option( 'zenphotopress_web_path' ) . "/"
				             . $zp_admin_dir . "/i.php?a="
				             . ZenphotoPressHelpers::pathurlencode( $image[ album_url ] )
				             . "&i=" . urlencode( $image[ url ] );

				$thumbpath .= "&w=" . $thumb_size;
				$thumbpath .= "&cx=" . $image[ thumbX ];
				$thumbpath .= "&cy=" . $image[ thumbY ];
				$thumbpath .= "&cw=" . $image[ thumbW ];
				$thumbpath .= "&ch=" . $image[ thumbH ];
			}
			if ( $use_lightbox ) {
				// If we are using lightbox, the link is to the image, not the Zenphoto page
				$imgpath = get_option( 'zenphotopress_web_path' )
				           . "/zp-core/i.php?a="
				           . ZenphotoPressHelpers::pathurlencode( $image[ album_url ] )
				           . "&i=" . urlencode( $image[ url ] );
			}
			if ( $image[ default_thumb ] ) {
				// The default thumbnail (e.g. for videos) behaves differently than others
				$thumbpath = get_option( 'zenphotopress_web_path' ) . "/"
				             . $zp_admin_dir . "/i.php?a=.&i="
				             . urlencode( $image[ default_thumb ] )
				             . "&s=thumb";
			}
			$bits = unserialize( $image[ name ] );
			if ( $bits ) {
				$image_name = join( " / ", array_values( $bits ) );
			} else {
				$image_name = $image[ name ];
			}
			$out .= '<dt><a href="' . $imgpath . '" alt="' . $image_name . '"'
			        . $rel_attribute
			        . '><img title="' . $image_name . '" alt="' . $image_name
			        . '" src="' . $thumbpath . '" /></a></dt>';
		}
		$out .= '<hr /></dl>';

		return $out;
	}

	/**
	 * Print HTML code for a badge containing images.
	 * The badge is an unordered list with base class "ZenphotoPress_badge".
	 *
	 * @param string  $sort    {random|latest|sort_order|title|id}
	 * @param integer $number  Number of images to show
	 * @param integer $albumid ID of the album to show (0 for all albums)
	 * @param string  $classes Additional classes for CSS
	 * @param integer $post_id
	 */
	public static function printBadge(
		$sort,
		$number,
		$albumid = 0,
		$classes = '',
		$post_id = ''
	) {
		echo ZenphotoPressUI::getBadge( $sort,
			$number,
			$albumid,
			$classes,
			$post_id );
	}
}

class ZenphotoProxy {

	/**
	 * Return a hash representing an album: {name => ..., url => '...'}
	 *
	 * @param $albumid ID of the album to show
	 */
	function getAlbum( $albumid ) {
		$out = ZenphotoProxy::call(
			'get_album',
			array(
				'albumid' => $albumid,
			)
		);

		return ( $out == null ) ? array()
			: $out; // Safe return: empty array on error
	}

	/**
	 * Return a nested array representing a list of albums.
	 * Each album is:
	 * {value => ..., name => '...', parentid => ..., children => [...]}
	 */
	public static function getAlbums() {
		$out = ZenphotoProxy::call(
			'get_nested_albums'
		);

		return ( $out == null ) ? array()
			: $out; // Safe return: empty array on error
	}

	/**
	 * Return an array representing a list of images.
	 * Each image is {id => ..., url => '...', name => '...', album_url => '...'}
	 *
	 * @param $sort    {random|latest} or a SQL "ORDER BY" clause
	 * @param $number  Number of images to show
	 * @param $albumid ID of the album to show (0 for all albums)
	 * @param $start   Offset for the SQL query ("LIMIT $start, $number")
	 */
	function getImages( $sort, $number, $albumid = 0, $start = 0 ) {
		$out = ZenphotoProxy::call(
			'get_images',
			array(
				'albumid' => $albumid,
				'sort'    => $sort,
				'start'   => $start,
				'limit'   => $number,
			)
		);

		return ( $out == null ) ? array()
			: $out; // Safe return: empty array on error
	}

	/**
	 * Return a hash representing an album: {name => ..., url => '...'}
	 *
	 * @param $albumid ID of the album to show
	 */
	function countImages( $albumid ) {
		$out = ZenphotoProxy::call(
			'count_images',
			array(
				'albumid' => $albumid,
			)
		);

		return ( $out == null ) ? 0 : $out; // Safe return: 0 on error
	}

	/**
	 * Return whether mod_rewrite is enabled.
	 *
	 * @param $albumid ID of the album to show
	 */
	function hasModRewrite() {
		$out = ZenphotoProxy::call(
			'has_mod_rewrite'
		);

		return ( $out == null ) ? false : $out; // Safe return: FALSE on error
	}

	/**
	 * Return a hash representing options: {name => value}
	 */
	function getOptions() {
		$out = ZenphotoProxy::call(
			'get_options'
		);

		return ( $out == null ) ? array()
			: $out; // Safe return: empty array on error
	}

	/**
	 * Call a remote page, read the result and return it as a PHP variable.
	 *
	 * @param string $function Name for the remote function
	 * @param array  $params   Hash of parameters for the remote function
	 */
	function call( $function, $params = array() ) {
		global $zp_eh;

		$url                 = WP_PLUGIN_URL
		                       . '/zenphotopress/zenphoto_bridge.php';
		$params['function']  = $function;
		$params['data_path'] = get_option( 'zenphotopress_admin_path' );

		$query = array();
		while ( list( $key, $val ) = each( $params ) ) {
			$query[] = "$key=$val";
		}
		$url .= '?' . join( '&', $query );

		$zp_eh->addInfo( 'ZenphotoProxy call:', $url );

		$out = unserialize( ZenphotoProxy::get_request( $url ) );

		if ( sizeof( $out ) == 1 && $out['fault'] ) {
			extract( (Array) $out[ fault ] ); // This is a fix for what seems like a PHP bug
			$zp_eh->addError( $faultString );

			return null;
		} else {
			return $out;
		}
	}

	/**
	 * Perform a GET request. Use file_get_contents when available, or fallback
	 * on sockets.
	 *
	 * @param $url URL for the request
	 */
	function get_request( $url ) {
		global $zp_eh;

		if ( function_exists( 'curl_init' ) ) {
			$ch      = curl_init();
			$timeout = 10;
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
			$file_contents = curl_exec( $ch );
			curl_close( $ch );

			return $file_contents;
		} else if ( function_exists( 'file_get_contents' ) ) {
			return file_get_contents( $url, 'r' );
		} else {
			$url_pieces = parse_url( $url );

			// Always use port 80 if not otherwise specified
			$port = 80;
			if ( is_int( $url_pieces[ port ] ) ) {
				$port = $url_pieces[ port ];
			}

			$header = "GET $url HTTP/1.0\n";
			$header .= "User-Agent: ZenphotoPress\n";
			$header .= "Host: $url_pieces[host]:$port\n";
			$header .= "Content-Type: text/plain\n";
			$header .= "Content-Length: 0\n";
			$header .= "\n";

			$fp = fsockopen( $url_array[ host ], $port, $errno, $errstr, 15 );

			if ( ! $fp ) {
				$zp_eh->addError( $faultString );
			} else {
				fputs( $fp, $header, strlen( $header ) );
				while ( ! feof( $fp ) ) {
					$recieved .= fgets( $fp, 4096 );
				}
				fclose( $fp );
				$response = explode( "\r\n\r\n", $recieved, 2 );

				return $response[1];
			}
		}

		return null;
	}
}

/**
 * This is a (static) class meant to manage all the GUI construction
 */
class ZenphotoPressAdminUI {

	/**
	 * Print the image selection form, with all the options
	 *
	 * @param integer $albumid ID of the album the images belong to
	 */
	public static function print_image_select( $albumid ) {
		global $zp_web_path, $zp_admin_path, $zp_admin_dir, $conf, $zp_eh, $_POST;

		$rel_value = get_option( 'zenphotopress_rel_value', '' );

		if ( $albumid ) {
			$_POST['imgperpage'] && $_POST['submit_order']
				? $imgperpage = $_POST['imgperpage']
				: $imgperpage
				= ZenphotoPressAdminUI::get_option( 'zenphotopress_custom_show',
				12 );    // Default value
			$_POST['orderby'] && $_POST['submit_order']
				? $order = $_POST['orderby']
				: $order
				= ZenphotoPressAdminUI::get_option( 'zenphotopress_custom_orderby',
				'sort_order' );            // Default value
			$_POST['page'] ? $curpage = $_POST['page']
				: $curpage = 1;                        // Default value

			$limit_start = ( $curpage - 1 ) * $imgperpage;

			$_POST['what']
				? $wh = $_POST['what']
				: $wh
				= ZenphotoPressAdminUI::get_option( 'zenphotopress_custom_what',
				'thumb' );
			$_POST['link']
				? $lk = $_POST['link']
				: $lk
				= ZenphotoPressAdminUI::get_option( 'zenphotopress_custom_link',
				'image' );
			$_POST['close']
				? $cl = $_POST['close']
				: $cl
				= ZenphotoPressAdminUI::get_option( 'zenphotopress_custom_close',
				'true' );
			$_POST['wrap']
				? $wrap = $_POST['wrap']
				: $wrap
				= ZenphotoPressAdminUI::get_option( 'zenphotopress_custom_wrap',
				'none' );
			$_POST['size']
				? $size = $_POST['size']
				: $size
				= ZenphotoPressAdminUI::get_option( 'zenphotopress_custom_size',
				'default' );

			$album = ZenphotoProxy::getAlbum( $albumid );

			$images = ZenphotoProxy::getImages( $order,
				$imgperpage,
				$albumid,
				$limit_start );

			$imagesnum = ZenphotoProxy::countImages( $albumid );

			$zp_options  = ZenphotoProxy::getOptions();
			$mod_rewrite = $zp_options['mod_rewrite'];
			$thumb_size  = $zp_options['thumb_size'];
			$zp_eh->addInfo( 'mod_rewrite:', $mod_rewrite );

			$zp_web_path   = get_option( 'zenphotopress_web_path' );
			$zp_admin_path = get_option( 'zenphotopress_admin_path' );
			?>
			<?php ZenphotoPressAdminUI::print_albums( $albumid, 'image' ); ?>
			<form id="options" name="options"
			      action="?tinyMCE=<?php echo $_GET[ tinyMCE ]; ?>&amp;section=<?php echo $_GET['section'] ?>"
			      method="POST">
				<fieldset>
					<legend><a href="#"><span id="toggle_what"
					                          onclick="zenphotopressPopup.toggleMenu(this);return false;"><?php echo $_POST['toggle_what_status']
					                                                                                                 == 'open'
									? '[-]'
									: '[+]'; ?></span></a> <?php _e( 'What do you want to include?',
							'zenphotopress' ) ?></legend>
					<div id="fields_what"
					     class="<?php echo $_POST['toggle_what_status']
					                       == 'open' ? 'zpOpen'
						     : 'zpClosed'; ?>">
						<?php
						$options = array(
							array(
								'value'   => 'thumb',
								'title'   => __( 'Image Thumbnail',
									'zenphotopress' ),
								'onclick' => 'zenphotopressPopup.changeHandler()',
							),
							array(
								'value'   => 'title',
								'title'   => __( 'Image Title',
									'zenphotopress' ),
								'onclick' => 'zenphotopressPopup.changeHandler()',
							),
							array(
								'value'   => 'album',
								'title'   => __( 'Album Name',
									'zenphotopress' ),
								'onclick' => 'zenphotopressPopup.changeHandler()',
							),
							array(
								'value'          => 'custom',
								'title'          => __( 'Custom Text:',
									'zenphotopress' ),
								'textfield_name' => 'what_custom_text',
								'onclick'        => 'zenphotopressPopup.changeHandler()',
							),
						);
						ZenphotoPressAdminUI::printFormRadio( 'what',
							$options,
							$wh,
							$_POST['what_custom_text'] );
						?>
					</div>
				</fieldset>
				<fieldset>
					<legend><a href="#"><span id="toggle_link"
					                          onclick="zenphotopressPopup.toggleMenu(this);return false;"><?php echo $_POST['toggle_link_status']
					                                                                                                 == 'open'
									? '[-]'
									: '[+]'; ?></span></a> <?php _e( 'Do you want to link it?',
							'zenphotopress' ) ?></legend>
					<div id="fields_link"
					     class="<?php echo $_POST['toggle_link_status']
					                       == 'open' ? 'zpOpen'
						     : 'zpClosed'; ?>">
						<?php
						$options = array(
							array(
								'value'   => 'image',
								'title'   => __( 'Link to Image',
									'zenphotopress' ),
								'onclick' => 'zenphotopressPopup.changeHandler()',
							),
							array(
								'value'   => 'album',
								'title'   => __( 'Link to Album',
									'zenphotopress' ),
								'onclick' => 'zenphotopressPopup.changeHandler()',
							),
							array(
								'value'   => 'none',
								'title'   => __( 'No Link',
									'zenphotopress' ),
								'onclick' => 'zenphotopressPopup.changeHandler()',
							),
							array(
								'value'          => 'custom',
								'title'          => __( 'Custom URL:',
									'zenphotopress' ),
								'textfield_name' => 'link_custom_url',
								'onclick'        => 'zenphotopressPopup.changeHandler()',
							),
						);
						ZenphotoPressAdminUI::printFormRadio( 'link',
							$options,
							$lk,
							$_POST['link_custom_url'] );
						?>
					</div>
				</fieldset>
				<fieldset>
					<legend><a href="#"><span id="toggle_close"
					                          onclick="zenphotopressPopup.toggleMenu(this);return false;"><?php echo $_POST['toggle_close_status']
					                                                                                                 == 'open'
									? '[-]'
									: '[+]'; ?></span></a> <?php _e( 'Do you want to close this window?',
							'zenphotopress' ) ?></legend>
					<div id="fields_close"
					     class="<?php echo $_POST['toggle_close_status']
					                       == 'open' ? 'zpOpen'
						     : 'zpClosed'; ?>">
						<?php
						$options = array(
							array(
								'value' => 'true',
								'title' => __( 'Close after inserting',
									'zenphotopress' ),
							),
							array(
								'value' => 'false',
								'title' => __( 'Keep open', 'zenphotopress' ),
							),
						);
						ZenphotoPressAdminUI::printFormRadio( 'close',
							$options,
							$cl );
						?>
					</div>
				</fieldset>
				<fieldset>
					<legend><a href="#"><span id="toggle_order"
					                          onclick="zenphotopressPopup.toggleMenu(this);return false;"><?php echo $_POST['toggle_order_status']
					                                                                                                 == 'open'
									? '[-]'
									: '[+]'; ?></span></a> <?php _e( 'Popup options',
							'zenphotopress' ) ?></legend>
					<div id="fields_order"
					     class="<?php echo $_POST['toggle_order_status']
					                       == 'open' ? 'zpOpen'
						     : 'zpClosed'; ?>">
						<?php _e( 'Show', 'zenphotopress' ) ?>
						<?php
						$opts = array(
							array( 'name' => 12, 'value' => 12 ),
							array( 'name' => 24, 'value' => 24 ),
							array( 'name' => 48, 'value' => 48 ),
							array( 'name' => 96, 'value' => 96 ),
						);
						ZenphotoPressAdminUI::printFormSelect( 'imgperpage',
							$opts,
							$imgperpage );
						?>
						<?php _e( 'images in a page, ordered by',
							'zenphotopress' ) ?>
						<?php
						$opts = array(
							array(
								'name'  => __( 'Sort Order',
									'zenphotopress' ),
								'value' => 'sort_order',
							),
							array(
								'name'  => __( 'Title', 'zenphotopress' ),
								'value' => 'title',
							),
							array(
								'name'  => __( 'ID', 'zenphotopress' ),
								'value' => 'id',
							),
						);
						ZenphotoPressAdminUI::printFormSelect( 'orderby',
							$opts,
							$order );
						?>
						<input type="submit" name="submit_order"
						       value="<?php _e( 'Update', 'zenphotopress' ) ?>">
					</div>
				</fieldset>
				<fieldset>
					<legend><a href="#"><span id="toggle_wrap"
					                          onclick="zenphotopressPopup.toggleMenu(this);return false;"><?php echo $_POST['toggle_wrap_status']
					                                                                                                 == 'open'
									? '[-]'
									: '[+]'; ?></span></a> <?php _e( 'Text Wrap',
							'zenphotopress' ) ?></legend>
					<div id="fields_wrap"
					     class="<?php echo $_POST['toggle_wrap_status']
					                       == 'open' ? 'zpOpen'
						     : 'zpClosed'; ?>">
						<?php
						$options = array(
							array(
								'value' => 'none',
								'title' => __( '<img src="img/wrapNone.gif"> No wrap',
									'zenphotopress' ),
							),
							array(
								'value' => 'left',
								'title' => __( '<img src="img/wrapLeft.gif"> Right',
									'zenphotopress' ),
							),
							array(
								'value' => 'right',
								'title' => __( '<img src="img/wrapRight.gif"> Left',
									'zenphotopress' ),
							),
						);
						ZenphotoPressAdminUI::printFormRadio( 'wrap',
							$options,
							$wrap );
						?>
					</div>
				</fieldset>
				<fieldset>
					<legend><a href="#"><span id="toggle_size"
					                          onclick="zenphotopressPopup.toggleMenu(this);return false;"><?php echo $_POST['toggle_size_status']
					                                                                                                 == 'open'
									? '[-]'
									: '[+]'; ?></span></a> <?php _e( 'Image Size',
							'zenphotopress' ) ?></legend>
					<div id="fields_size"
					     class="<?php echo $_POST['toggle_size_status']
					                       == 'open' ? 'zpOpen'
						     : 'zpClosed'; ?>">
						<?php
						$txtfield = array(
							array(
								'name'  => 'custom_width',
								'title' => __( 'Width (px)',
									'zenphotopress' ),
								'value' => ZenphotoPressAdminUI::get_option( 'zenphotopress_custom_width',
									$_POST['custom_width'] ),
							),
							array(
								'name'  => 'custom_height',
								'title' => __( 'Height (px)',
									'zenphotopress' ),
								'value' => ZenphotoPressAdminUI::get_option( 'zenphotopress_custom_height',
									$_POST['custom_height'] ),
							),
						);
						$options  = array(
							array(
								'value' => 'default',
								'title' => __( 'Default size (thumbnail)',
									'zenphotopress' ),
							),
							array(
								'value' => 'full',
								'title' => __( 'Full size', 'zenphotopress' ),
							),
							array(
								'value'          => 'custom',
								'title'          => __( 'Custom size:',
									'zenphotopress' ),
								'textfield_name' => $txtfield,
							),
						);
						ZenphotoPressAdminUI::printFormRadio( 'size',
							$options,
							$size );
						?>
					</div>
				</fieldset>
				<input type="hidden" name="album"
				       value="<?php echo $albumid; ?>">
				<input type="hidden" name="album_name"
				       value="<?php echo $album[ name ]; ?>">
				<input type="hidden" name="album_url"
				       value="<?php echo $album[ url ]; ?>">
				<input type="hidden" name="zenphotopress_web_path"
				       value="<?php echo $zp_web_path; ?>">
				<input type="hidden" name="zenphotopress_admin_dir"
				       value="<?php echo $zp_admin_dir; ?>">
				<input type="hidden" name="mod_rewrite"
				       value="<?php echo $mod_rewrite; ?>">
				<input type="hidden" name="page" value="">
				<input type="hidden" id="toggle_what_status"
				       name="toggle_what_status"
				       value="<?php echo $_POST['toggle_what_status'] == 'open'
					       ? 'open' : 'closed'; ?>">
				<input type="hidden" id="toggle_link_status"
				       name="toggle_link_status"
				       value="<?php echo $_POST['toggle_link_status'] == 'open'
					       ? 'open' : 'closed'; ?>">
				<input type="hidden" id="toggle_close_status"
				       name="toggle_close_status"
				       value="<?php echo $_POST['toggle_close_status'] == 'open'
					       ? 'open' : 'closed'; ?>">
				<input type="hidden" id="toggle_order_status"
				       name="toggle_order_status"
				       value="<?php echo $_POST['toggle_order_status'] == 'open'
					       ? 'open' : 'closed'; ?>">
				<input type="hidden" id="toggle_wrap_status"
				       name="toggle_wrap_status"
				       value="<?php echo $_POST['toggle_wrap_status'] == 'open'
					       ? 'open' : 'closed'; ?>">
				<input type="hidden" id="toggle_size_status"
				       name="toggle_size_status"
				       value="<?php echo $_POST['toggle_size_status'] == 'open'
					       ? 'open' : 'closed'; ?>">
			</form>
			<fieldset>
				<legend><?php _e( 'Select Image', 'zenphotopress' ) ?></legend>
				<div id="fields_image" class="normal">
					<div class="normal">
						<?php ZenphotoPressAdminUI::printPageIndex( $imagesnum,
							$imgperpage,
							$curpage ); ?>
						<?php
						foreach ( $images as $image ) {
							$thumbpath = $zp_web_path . "/" . $zp_admin_dir
							             . "/i.php?a="
							             . urlencode( $album[ url ] ) . "&i="
							             . urlencode( $image[ url ] );
							if ( $image[ thumbX ] && $image[ thumbY ]
							     && $image[ thumbW ]
							     && $image[ thumbH ]
							) {
								// Custom thumb cropping
								$crop_params .= "&w=" . $thumb_size;
								$crop_params .= "&cx=" . $image[ thumbX ];
								$crop_params .= "&cy=" . $image[ thumbY ];
								$crop_params .= "&cw=" . $image[ thumbW ];
								$crop_params .= "&ch=" . $image[ thumbH ];

								$thumbpath .= $crop_params;
							} else {
								// Standard thumb cropping
								$crop_params = "";

								$thumbpath .= "&s=thumb";
							}
							$bits = unserialize( $image["name"] );
							if ( $bits ) {
								$image_name = join( " / ",
									array_values( $bits ) );
							} else {
								$image_name = $image["name"];
							}
							echo '<div class="thumb"><img title="' . $image_name
							     . '" alt="' . $image_name . '" src="'
							     . $thumbpath
							     . '" onClick="zenphotopressPopup.insertImage(\''
							     . $image[ id ] . '\',\'' . $image[ url ]
							     . '\',\'' . str_replace( "'",
									"\'",
									$image_name ) . '\', \'' . $rel_value
							     . '\', \'' . $crop_params
							     . '\');return false;" /></div>';
						}
						?>
					</div>
					<div class="alt">
						<?php _e( 'No image selection needed.',
							'zenphotopress' ) ?><br/>
						<input type="button"
						       value="<?php _e( 'INSERT', 'zenphotopress' ) ?>"
						       onClick="zenphotopressPopup.insertImage(null,null,null);return false;"/>
					</div>
				</div>

			</fieldset>
			<?php
		}
	}

	/**
	 * Return the value of an option from the database. Fallback on the default
	 * value if the result from the database is empty
	 *
	 * @param $name    Name of the option
	 * @param $default Default value
	 */
	function get_option( $name, $default ) {
		global $zp_eh;
		$out = get_option( $name );
		$zp_eh->addInfo( $name . ':', $out );
		if ( $out ) {
			return $out;
		} else {
			return $default;
		}
	}

	/**
	 * Print the album selection menu
	 *
	 * @param $selected    ID of the selected album (if any)
	 * @param $section     Section to load after the selection (image|gallery)
	 */
	public static function print_albums( $selected, $section = "" ) {
		global $zp_eh;

		$opts   = ZenphotoProxy::getAlbums();
		$action = "?tinyMCE=" . $_GET[ tinyMCE ];
		if ( $section ) {
			$action .= "&amp;section=" . $section;
		}
		?>
		<form action="<?php echo $action; ?>" method="POST">
			<p>
				<label for="album">Album:
					<?php ZenphotoPressAdminUI::printFormSelect( 'album',
						$opts,
						$selected ); ?>
				</label>
				<input type="submit"
				       value="<?php _e( 'Select', 'zenphotopress' ) ?>"/>
			</p>
		</form>
		<?php
	}

	/**
	 * Print the gallery insertion menu
	 */
	public static function print_gallery() {
		$opts = ZenphotoProxy::getAlbums();
		array_unshift( $opts, array( 'value' => 0, 'name' => 'All albums' ) );

		$sort_opts = array(
			array( "name" => "Random", "value" => "random" ),
			array( "name" => "Latest", "value" => "latest" ),
		);
		?>
		<form id="options" name="options">
			<p>
				<label for="album">Album:
					<?php ZenphotoPressAdminUI::printFormSelect( 'album',
						$opts ); ?>
				</label>
			</p>
			<p>
				<label for="sort">Sorting:
					<?php ZenphotoPressAdminUI::printFormSelect( 'sort',
						$sort_opts,
						'random' ); ?>
				</label>
			</p>
			<p>
				<label for="number">Number of images to show:
					<input type="text" name="number" value="3"></input>
				</label>
			</p>
			<input type="button"
			       value="<?php _e( 'INSERT', 'zenphotopress' ) ?>"
			       onClick="zenphotopressPopup.insertGallery();return false;"/>
		</form>
		<?php
	}

	/**
	 * Print a breadcrumb detailing the current section
	 *
	 * @param string $section Section currently loaded (image|gallery)
	 */
	public static function print_breadcrumb( $section ) {
		echo '<h1><a href="?tinyMCE=' . $_GET[ tinyMCE ]
		     . '">ZenphotoPress</a>';
		if ( $section ) {
			echo " &raquo; " . $section;
		}
		echo "</h1>";
	}

	/**
	 * Print a <select> HTML element.
	 *
	 * @param $name        Name of the element
	 * @param $options     Array of select options. Each option is an array of name and value
	 * @param $selected    Value of the selected option (if any)
	 */
	public static function printFormSelect(
		$name,
		$options,
		$selected = null
	) {
		echo '<select name="' . $name . '">';
		ZenphotoPressAdminUI::printNestedOptions( $options, 0, $selected );
		echo '</select>';
	}

	/**
	 * Print a list o <option> HTML elements, recursing in case of nesting.
	 *
	 * @param array   $options  Array of select options. Each option is an array of name and value (and possibly children).
	 * @param integer $depth    Depth of the recursion.
	 * @param string  $selected Value of the selected option (if any)
	 */
	public static function printNestedOptions(
		$options,
		$depth = 0,
		$selected = null
	) {
		$prefix = str_repeat( "&nbsp;", $depth );

		foreach ( $options as $value ) {

			$value["value"] == $selected ? $sel = ' selected="selected"'
				: $sel = '';
			echo '<option myval value="' . $value["value"] . '"' . $sel . '>';
			$bits = unserialize( $value[ name ] );
			if ( $bits ) {
				$option = join( " / ", array_values( $bits ) );
			} else {
				$option = $value[ name ];
			}
			echo $prefix . $option;
			echo '</option>';
			if ( $value["children"] ) {
				ZenphotoPressAdminUI::printNestedOptions( $value["children"],
					$depth + 1,
					$selected );
			}
		}
	}

	/**
	 * Print a group of <radio> HTML element.
	 *
	 * @param $name         Name of the element
	 * @param $options      Array of options. Each option is an array of title, value and onclick
	 * @param $selected     Value of the selected option (if any)
	 * @param $textvalue    Value of the textfield (if any)
	 */
	function printFormRadio(
		$name,
		$options,
		$selected = null,
		$textvalue = null
	) {
		foreach ( $options as $value ) {
			"$value[value]" == "$selected" ? $ch = ' checked="checked" '
				: $ch = '';
			echo '<label><input type="radio" class=" id="' . $name . '_'
			     . $value[ value ] . '" name="' . $name . '" value="'
			     . $value[ value ] . '" onclick="' . $value[ onclick ] . '"'
			     . $ch . '/>' . $value[ title ] . '</label>';
			if ( is_string( $value[ textfield_name ] ) ) {
				echo ' <input type="text" id="' . $value[ textfield_name ]
				     . '" name="' . $value[ textfield_name ] . '" '
				     . ( $textvalue ? 'value="' . $textvalue . '" ' : '' )
				     . '/>';
			} else if ( is_array( $value[ textfield_name ] ) ) {
				foreach ( $value['textfield_name'] as $txtfield ) {
					echo ' ' . $txtfield['title']
					     . ' <input type="text" class="shortInput" id="'
					     . $txtfield['name'] . '" name="' . $txtfield['name']
					     . '" ' . ( $txtfield['value'] ? 'value="'
					                                     . $txtfield['value']
					                                     . '" ' : '' ) . '/>';
				}
			}
			echo '<br />';
		}
	}

	/**
	 * Print an index for page navigation, if the items span multiple pages.
	 *
	 * @param $items_num        Number of items
	 * @param $items_perpage    Max number of items on each page
	 * @param $page_selected    Number of the selected page (if any)
	 */
	function printPageIndex( $items_num, $items_perpage, $page_selected = 1 ) {
		if ( $items_num > $items_perpage ) {
			if ( ! $page_selected ) {
				$page_selected = 1;
			}
			echo '<div class="ZPpageIndex">Page: ';
			for ( $i = 1; $i <= ceil( $items_num / $items_perpage ); $i ++ ) {
				if ( $i == $page_selected ) {
					echo '<b>' . $i . '</b>';
				} else {
					echo '<a href="#" onclick="zenphotopressPopup.gotoPage('
					     . $i . ');">' . $i . '</a>';
				}

				if ( $i < ceil( $items_num / $items_perpage ) ) {
					echo ' - ';
				}
			}
			echo '</div>';
		}
	}

	/**
	 * Callback function. It should be called automatically
	 * before flushing the output buffer.
	 * It prints error messages, if present.
	 *
	 * @param string $buffer Content of the buffer
	 *
	 * @return string Content of the buffer with additional error messages
	 */
	public static function ob_callback( $buffer ) {
		global $zp_eh;

		if ( sizeof( $zp_eh->messages )
		     && $zp_eh->messages_level >= $zp_eh->level
		) {
			$buffer .= $zp_eh->getMessages();
		}

		return $buffer;
	}
}

/**
 * This is a class to manage error/warning/info messages.
 * It is meant to be used with PHP output buffering.
 */
class ZenphotoPressErrorHandler {

	var $messages;
	var $messages_level;    // Highest level in the messages stack
	var $level;

	/**
	 * Class constructor.
	 *
	 * @param $debug    True if debug messages are to be shown, false otherwise
	 */
	function ZenphotoPressErrorHandler( $debug = ZP_E_ERROR ) {
		$this->messages_level = ZP_E_ALL;    // Set to zero

		$this->level = $debug;
	}

	/**
	 * Add a message to the error stack
	 *
	 * @param $level    Level of the message
	 * @param $msg      Text of the message
	 * @param $add      Additional informations (can be a string or an array)
	 */
	function addMessage( $level, $msg, $add ) {
		if ( $add ) {
			if ( is_array( $add ) ) {
				ob_start();
				print_r( $add );
				$additional = ob_get_contents();
				ob_end_clean();
			} else {
				$additional = $add;
			}
			$message = '<i>' . $msg . '</i> ' . $this->sanitize( $additional );
		} else {
			$message = $msg;
		}

		$this->messages[] = array( "level" => $level, "msg" => $message );
		if ( $this->messages_level < $level ) {
			$this->messages_level = $level;
		}
	}

	/**
	 * Add a fatal error message to the stack. Wrapper for the addMessage function
	 *
	 * @param $msg    Text of the message
	 * @param $add    Additional informations, if any (can be a string or an array)
	 */
	function addFatal( $msg, $add = null ) {
		$this->addMessage( ZP_E_FATAL, $msg, $add );
		die();
	}

	/**
	 * Add an error message to the stack. Wrapper for the addMessage function
	 *
	 * @param string       $msg Text of the message
	 * @param string|array $add Additional informations, if any (can be a string or an array)
	 */
	function addError( $msg, $add = null ) {
		$this->addMessage( ZP_E_ERROR, $msg, $add );
	}

	/**
	 * Add a warning message to the stack. Wrapper for the addMessage function
	 *
	 * @param $msg    Text of the message
	 * @param $add    Additional informations, if any (can be a string or an array)
	 */
	function addWarning( $msg, $add = null ) {
		$this->addMessage( ZP_E_WARNING, $msg, $add );
	}

	/**
	 * Add an information message to the stack. Wrapper for the addMessage function
	 *
	 * @param string $msg    Text of the message
	 * @param        $add    Additional informations, if any (can be a string or an array)
	 */
	function addInfo( $msg, $add = null ) {
		$this->addMessage( ZP_E_INFO, $msg, $add );
	}

	/**
	 * Return a string containing all the messages in HTML format. Select which
	 * messages to show according to the current level.
	 *
	 * @return A string containing HTML code
	 */
	function getMessages() {
		$out
			= '<div id="zp_errormessage" style="border:1px solid #666;background:#EEE;padding:0.5em;margin-top:0.5em;">';
		$out .= '<em style="font-size:1.5em;">ZenphotoPress Messages</em>';
		foreach ( $this->messages as $value ) {
			if ( $value[ level ] >= $this->level ) {
				$out .= '<div class="errormessage" style="border:1px dotted #999;background:#FFF;padding:0;margin-top:0.5em;">';
				if ( $value[ level ] == ZP_E_INFO ) {
					$out .= '<p style="background:#9e9;padding:0.2em;margin:0;">INFO</p>';
				}
				if ( $value[ level ] == ZP_E_WARNING ) {
					$out .= '<p style="background:#ee6;padding:0.2em;margin:0;">WARNING</p>';
				}
				if ( $value[ level ] == ZP_E_ERROR ) {
					$out .= '<p style="background:#e88;padding:0.2em;margin:0;">ERROR</p>';
				}
				if ( $value[ level ] == ZP_E_FATAL ) {
					$out .= '<p style="background:#333;color:#DDD;padding:0.2em;margin:0;">FATAL ERROR</p>';
				}
				$out .= '<p style="margin:0.5em;">' . $value[ msg ] . '</p>';
				$out .= '</div>';
			}
		}
		$out .= '</div>';

		return $out;
	}

	/**
	 * Sanitize input text
	 *
	 * @param $text
	 *
	 * @return Sanitized text
	 */
	function sanitize( $text ) {
		$out = str_replace( '<', '&lt;', $text );
		$out = str_replace( '>', '&gt;', $out );

		return $out;
	}
}

/**
 * This is a (static) class containing some helper methods
 */
class ZenphotoPressHelpers {

	/**
	 * Recurse on directory to find the Wordpress base path
	 * (i.e. the directory containing wp-config.php)
	 *
	 * @return string The base path or null on failure
	 */
	public static function getWPBasePath() {
		$rel_path = '';
		for ( $count = 1; $count <= 10; $count ++ ) {
			$rel_path = $rel_path . '../';
			if ( file_exists( $rel_path . 'wp-load.php' ) ) {
				return $rel_path;
			}
		}

		return null;
	}

	/**
	 * Return the $path parameter encoded with rawurlencode() except for the
	 * slashes ("/").
	 * Function by Trisweb http://www.trisweb.com
	 */
	function pathurlencode( $path ) {
		return implode( "/",
			array_map( "rawurlencode", explode( "/", $path ) ) );
	}
}

?>
