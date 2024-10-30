<?php
/*
 Plugin Name: Comment Form Message
 Plugin URI: http://abdussamad.com/archives/427-Custom-Comments-Notice-for-Wordpress.html
 Description: Add a custom message near the comments form on a sitewide and/or per post basis.
 Author: Abdussamad Abdurrazzaq
 Author URI: http://abdussamad.com
 License: GPLv3
 Version: 1.38
*/

/*	Copyright 2013  Abdussamad Abdurrazzaq

	This program is free software: you can redistribute it and/or modify
    	it under the terms of the GNU General Public License version 3.0 as 
    	published by the Free Software Foundation.

    	This program is distributed in the hope that it will be useful,
    	but WITHOUT ANY WARRANTY; without even the implied warranty of
    	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    	GNU General Public License for more details.

    	You should have received a copy of the GNU General Public License
    	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class comment_form_message {
	protected $meta_box; //custom meta box.
	protected $options_page; //admin options page
	protected $option; //admin options
	const VERSION = "1.38";

	function __construct() {
		load_plugin_textdomain( 'comment-form-message', false, basename( dirname( __FILE__ ) ) . '/languages/' );
		$this->save_version();
		//initialize option variable
		$this->option = array();
		
		//include required class files
		$this->include_class_files();
	
		//admin options and custom meta boxes
		$this->add_options_page();
		$this->add_meta_box();
	
		//If one or more content types were selected in admin options we display custom message
		if ( isset( $this->meta_box ) ) {
			if ( $this->option[ 'rad_position' ]->value == 'above' ) {
		  		add_action ('comment_form_top' , array( $this, 'display_before' ) );
			} elseif ( $this->option[ 'rad_position' ]->value == 'below' ) {
			  	add_action ('comment_form_defaults' , array( $this, 'display_after' ) );
			}
		}
		add_action( 'admin_init', array( $this, 'admin_css' ) );
		//Frontend css for this plugin.
		$this->add_css();
		register_activation_hook( __FILE__, array( $this, 'install' ) );
		add_action('admin_notices', array( $this, 'post_install_message' ) );
	}

	private function save_version() {
		update_option( 'wpcci_version', self::VERSION );
	}

	//Function loads files containing classes.
	private function include_class_files() {
		$includes_dir = plugin_dir_path( __FILE__ ) . 'includes/';
		if ( ! class_exists( 'abd_options_page' ) ) {
			if( file_exists( "$includes_dir/abd_libs.php" ) ) {
				include ( "$includes_dir/abd_libs.php" );
			}
		}
	}
	
	private function get_icons () {
		static $final_icons = false;
		$icons = array (	'object-unlocked.png',
  					'security-high.png',
  					'applications-internet.png',
  					'user-busy.png',
  					'dialog-information.png',
  					'object-locked.png',
  					'task-accepted.png',
  					'favorites.png',
  					'task-attention.png',
  					'applications-education-miscellaneous.png',
  					'security-medium.png',
  					'system-help.png',
  					'dialog-error.png',
  					'help-hint.png',
  					'draw-freehand.png',
			);
		if( ! $final_icons ) {
		  	$icons_preview_dir = plugin_dir_path( __FILE__) . 'icons/preview/';
			$icons_preview_url = plugins_url( 'icons/preview/', __FILE__ );
			$icons_dir = plugin_dir_path( __FILE__) . 'icons/';
			foreach( $icons as $icon ) {
				$final_icons [ 'icons/' . $icon ] = "<img src='{$icons_preview_url}$icon' />";
			}
			$final_icons[ 'none' ] = __( 'None', 'comment-form-message' )  ;
		}
		return $final_icons;
	}
	
	private function add_css() {
		if( ! class_exists( 'abd_dynamic_css' ) ) {
		  	return;
		}
				
		// depending on the location of the shadow we need to modify user input offsets. 
		// For instance to display shadow to the left we need to turn the offset negative. 
		$shadows = array(	'br'     => array( 1, 1 ),
					'bl'     => array( -1, 1 ),
					'tr'     => array( 1, -1 ),
					'tl'     => array( -1, -1 ),
					'center' => array( 0, 0 )
				);
		
		$code = " .wpcci_icon {
				float:left;
				margin:0em 0.5em 0.25em 0em;
			}
			.wpcci_clearing {
				clear:both;
			}
			.wpcci_instructions {
				margin: 0.5em 0em 1em 0em;
				padding: 0.75em 0.75em 0.1em 0.75em;
				background-color:{$this->option[ 'txt_background_color' ]->value}; 	
				color: {$this->option[ 'txt_text_color' ]->value};
				font-size: {$this->option[ 'txt_font_size' ]->value}pt;
			";

		//show shadows?
		if ( array_key_exists( $this->option[ 'sel_shadow' ]->value, $shadows ) ) {
			
			$x = $shadows[ $this->option[ 'sel_shadow' ]->value ][0] * $this->option[ 'txt_num_shadow_size_x' ]->value;
			$y = $shadows[ $this->option[ 'sel_shadow' ]->value ][1] * $this->option[ 'txt_num_shadow_size_y' ]->value;
						
			$code .= "-moz-box-shadow: {$x}px {$y}px {$this->option[ 'txt_num_shadow_cast' ]->value }px {$this->option[ 'txt_shadow_color' ]->value };";
			$code .= "-webkit-box-shadow: {$x}px {$y}px {$this->option[ 'txt_num_shadow_cast' ]->value }px {$this->option[ 'txt_shadow_color' ]->value };";
			$code .= "box-shadow: {$x}px {$y}px {$this->option[ 'txt_num_shadow_cast' ]->value }px {$this->option[ 'txt_shadow_color' ]->value };";
		} 

		//borders
		if ( $this->option[ 'rad_border_show' ]->value == 'y' ) {
		  	$code .= "border: {$this->option[ 'txt_border_size' ]->value}px solid {$this->option[ 'txt_border_color' ]->value}; ";
		} else {
		  	$code .= "border: none; ";
		}
		if ( $this->option[ 'rad_border_rounded' ]->value == 'y' ) {
		  	$code .= "border-radius: {$this->option[ 'txt_border_radius' ]->value}px;";
			$code .= "-moz-border-radius: {$this->option[ 'txt_border_radius' ]->value}px;";
		
		}
		$code .= '}';
		new abd_dynamic_css( 'wpcci_custom_css', $code, $this->options_page->name, array( $this, 'enqueue_css_if' ) );
	}

	//css is enqueued only on single pages/posts/attachments etc. of selected content types with comments open.
	public function enqueue_css_if() {
		global $post;
	  	if( is_singular() ) {
		  	return ( is_object( $post ) && $post->comment_status == 'open' && $this->permitted() );
	  	}
		return false;
	}
		
	//Admin options page for this plugin.
	private function add_options_page() {
		
		$default_content_types = array( 'post', 'page' );
		$all_content_types     = $this->get_content_types(); 

		if( ! class_exists( 'abd_options_page' ) ) {
		  	return;
		}
		//add page
		$this->options_page = new abd_options_page( 'wpcci_options_page', __( 'Comment Form Message Options' , 'comment-form-message'), __( 'Options for the Comment Form Message plugin.', 'comment-form-message' ), __( 'Comment Form Message', 'comment-form-message' ) );
		
		//add main section
		$main_section                         = $this->options_page->add_section( 'wpcci_options_section_main', __( 'Main' , 'comment-form-message'), __( 'Main options for this plugin' , 'comment-form-message') );
		$this->option[ 'chk_content_type' ]   = $main_section->add_check_option( 'wpcci_chk_content_type', __( 'Content types' , 'comment-form-message'), __( 'Select which content types should have comment form messages' , 'comment-form-message'), $all_content_types, $default_content_types );
		$this->option[ 'rad_position' ]       = $main_section->add_radio_option( 'wpcci_rad_position', __( 'Display position' , 'comment-form-message'), __( 'Where would you like to see the comment form message displayed?' , 'comment-form-message'), array( 'above' => 'Above comment form fields', 'below' => 'Below comment form fields' ), 'above' );
		$this->option[ 'txt_global_message' ] = $main_section->add_wysiwyg_option( 'wpccimessage', __( 'Sitewide message' , 'comment-form-message'), __( 'Sitewide message for all selected content types. This message will be shown near the comment form on all content types selected above.' , 'comment-form-message'), '', '/.*/' );
		$this->option[ 'rad_global_message' ] = $main_section->add_radio_option( 'wpcci_rad_global', __( 'Show sitewide message?' , 'comment-form-message'), __( 'Show the sitewide message configured above or not?' , 'comment-form-message'), array( 'y' => 'Yes', 'n' => 'No' ), 'n' );
		$this->option[ 'rad_icon' ]           = $main_section->add_inline_radio_option	( 'wpcci_rad_icon', __( 'Icon to display next to sitewide message' , 'comment-form-message'), __( 'Add an icon to the sitewide message box?' , 'comment-form-message'), $this->get_icons(), 'none' );  
		
		//text and background display options section
		$text_section                           = $this->options_page->add_section( 'wpcci_options_section_text_display', __( 'Text and Background' , 'comment-form-message'), __( 'Text and background display options for this plugin' , 'comment-form-message') );
		$this->option[ 'txt_font_size' ]        = $text_section->add_numeric_text_option( 'wpcci_txt_num_font_size', __( 'Text font size' , 'comment-form-message'), __( 'Enter the font size for text in the comment form message box. The number you enter here will be the font size in points.' , 'comment-form-message'), 6, 36, 11 );
		$this->option[ 'txt_text_color' ]       = $text_section->add_color_picker_option( 'wpcci_color_text', __( 'Text color' , 'comment-form-message'), 'The color of the text in the comment form message box', '#000000' );								
		$this->option[ 'txt_background_color' ] = $text_section->add_color_picker_option( 'wpcci_color_background', __( 'Background color' , 'comment-form-message'), 'Background color of the comment form message box', '#FFFFFF' );
		
		//border display options
		$border_section                       = $this->options_page->add_section( 'wpcci_options_section_border_display', __( 'Border' , 'comment-form-message'),__( 'Border display options for this plugin' , 'comment-form-message') );
		$this->option[ 'rad_border_show' ]    = $border_section->add_radio_option( 'wpcci_rad_border', __( 'Show solid color border?' , 'comment-form-message'), __( 'Add a solid color border to the message box?' , 'comment-form-message'), array( 'y'=>'Yes', 'n'=>'No' ), 'y' );
		$this->option[ 'txt_border_color' ]   = $border_section->add_color_picker_option( 'wpcci_color_border', __( 'Border color' , 'comment-form-message'), 'Pick a border color. Only applies if you elected to show a solid color border above. ', '#F0F0F0' );
		$this->option[ 'txt_border_size' ]    = $border_section->add_numeric_text_option( 'wpcci_txt_num_border_size', __( 'Border size' , 'comment-form-message'), __( 'Size of the border for the comment form message box' , 'comment-form-message'), 1, 30, 1 );
		$this->option[ 'rad_border_rounded' ] = $border_section->add_radio_option( 'wpcci_rad_round_border', __( 'Rounded border?' , 'comment-form-message'), __( 'Make the border rounded ?' , 'comment-form-message'), array( 'y' => 'Yes', 'n' => 'No' ), 'y' );
		$this->option[ 'txt_border_radius' ]  = $border_section->add_numeric_text_option( 'wpcci_txt_num_radius', __( 'Enter border radius', 'comment-form-message'), __( 'Enter radius for the border curvature. Only applies if you elected to show a rounded border above' , 'comment-form-message'), 0, 50, 15 );
			
		//shadow display options
		$shadow_section                          = $this->options_page->add_section( 'wpcci_options_section_shadow_display', __( 'Shadow' , 'comment-form-message'), __( 'Shadow display options for this plugin' , 'comment-form-message') );
		$this->option[ 'sel_shadow' ]            = $shadow_section->add_select_option( 'wpcci_sel_shadow', __( 'Shadow type' , 'comment-form-message'), __( 'Choose a type of shadow effect to apply to the message box' , 'comment-form-message'), array( 'none' => 'None', 'br' => 'Bottom Right', 'bl' => 'Bottom Left', 'tr' => 'Top Right', 'tl' => 'Top Left', 'center' => 'Center - Outer glow type' ), 'none' );
		$this->option[ 'txt_num_shadow_size_x' ] = $shadow_section->add_numeric_text_option( 'wpcci_txt_num_shadow_size_x', __( 'Shadow Horizontal Offset' , 'comment-form-message'), __( 'How long should the shadow be horizontally? Does not apply for center shadow type.' , 'comment-form-message'), 0, 50, 5 );
		$this->option[ 'txt_num_shadow_size_y' ] = $shadow_section->add_numeric_text_option( 'wpcci_txt_num_shadow_size_y', __( 'Shadow Vertical Offset' , 'comment-form-message'), __( 'How long should the shadow be vertically? Does not apply for center shadow type.' , 'comment-form-message'), 0, 50, 5 );
		$this->option[ 'txt_num_shadow_cast' ]   = $shadow_section->add_numeric_text_option( 'wpcci_txt_num_shadow_cast', __( 'Shadow Blur' , 'comment-form-message'), __( 'How much bluring or feathering should be applied to the shadow ?' , 'comment-form-message'), 0, 50, 7 );
		$this->option[ 'txt_shadow_color' ]      = $shadow_section->add_color_picker_option( 'wpcci_color_shadow', __( 'Shadow color' , 'comment-form-message'), __( 'Pick a color for the shadow effect' , 'comment-form-message'), '#b2b2b2' );

	}

	//returns content types that are public and support comments.
	private function get_content_types() {
	  	$all_post_types = get_post_types( array() , 'objects' );
		$ret = array();		
 		foreach( $all_post_types as $post_type => $obj ) {
			if( $obj->public && post_type_supports( $post_type, 'comments' ) ) {
 		  		$ret[ $post_type ] = $obj->labels->singular_name;
			}
  		}
		return $ret;
	}

	//Custom meta box and options for post/page edit screen.
	private function add_meta_box() {
		if( ! class_exists( 'abd_meta_box' ) ) {
		  	return;
		}

		$content_types = $this->option[ 'chk_content_type' ]->value ;
		
		//exit if no content types have been selected.
		if ( $content_types == NULL ) {
		  	return;
		}
		
		//add custom meta box
		$this->meta_box = new abd_meta_box( 'wpcci_meta_box', __( "Comment Form Message" , 'comment-form-message'), __( "Post or page specific comment form message. Enter custom message for comments to this blog post or page." , 'comment-form-message'), $content_types );
		
		//add options
		$this->meta_box->add_wysiwyg_option( 'wpccimetatxtinstructions', __( "Comment Form Message" , 'comment-form-message'), __( 'Enter custom comment form message for this post' , 'comment-form-message'), '', '/.*/' );
		$this->meta_box->add_check_option( 'wpcci_meta_chk_show', __( "Display specific message for this post" , 'comment-form-message'), __( 'Should the specific comment form message configured above be displayed or not?' , 'comment-form-message'), array( 'y' => __( 'Yes', 'comment-form-message' ) ) );
		$this->meta_box->add_inline_radio_option( 'wpcci_meta_rad_icon', __( 'Icon to display next to message' , 'comment-form-message'), __( 'Add an icon to the message?' , 'comment-form-message'), $this->get_icons(), 'none' );
		$this->meta_box->add_check_option( 'wpcci_meta_chk_show_global', __( "Display sitewide message for this post" , 'comment-form-message'), __( 'Should the sitewide message configured in the Comment Form Message settings page be displayed or not?' , 'comment-form-message'), array( 'y' => __( 'Yes', 'comment-form-message' ) ), array( 'y' ) );
	}

	//Has the user chosen to display the custom message for this content type?
	private function permitted () {
	  	return in_array( get_post_type(), $this->option[ 'chk_content_type' ]->value ); 
	}
	
	//returns the correct HTML taking into account the user's choices.
	private function get_message() {
		$global_show    = $this->option[ 'rad_global_message' ]->value ;
		$global_check   = $this->meta_box->get_option( 'wpcci_meta_chk_show_global' )->value;
		$specific_check = $this->meta_box->get_option( 'wpcci_meta_chk_show' )->value;
		$message = '';

		//global message
		if ( $global_show == 'y' ) {
			if ( is_array( $global_check ) && isset( $global_check[0] ) && $global_check[0] == 'y' ) {
				$message .=	"<div class='wpcci_instructions'>";
				if ( $this->option[ 'rad_icon' ]->value != 'none' ) { //display icon?
					//convert relative icon URL to absolute one.
					$global_icon_rel_URL = $this->option[ 'rad_icon']->value;
					$global_icon_abs_URL = plugins_url( $global_icon_rel_URL, __FILE__ );
					$global_icon_alt = basename( $global_icon_rel_URL );
					$message .= "	<div class='wpcci_icon'> 	
								<img src='$global_icon_abs_URL' alt='$global_icon_alt' />
							</div>";
				}
		  			$message .= "	{$this->option[ 'txt_global_message' ]->value}
								<div class='wpcci_clearing'>&nbsp;</div>
							</div>";
			}
		}
	  	
		//post specific message
		if ( is_array( $specific_check ) && isset( $specific_check[ 0 ] ) && 'y' == $specific_check[ 0 ] ) {
			$message .= 	"<div class='wpcci_instructions'>";
			if ( $this->meta_box->get_option( 'wpcci_meta_rad_icon' )->value != 'none' ) { //display icon?
					//convert relative icon URL to absolute one.
					$specific_icon_rel_URL = $this->meta_box->get_option( 'wpcci_meta_rad_icon' )->value;
					$specific_icon_abs_URL = plugins_url( $specific_icon_rel_URL, __FILE__ );
					$specific_icon_alt     = basename( $specific_icon_rel_URL );
					$message .= "	<div class='wpcci_icon'> 	
								<img src='$specific_icon_abs_URL' alt='$specific_icon_alt' />
							</div>";
				}
		  			$message .="		{$this->meta_box->get_option( 'wpccimetatxtinstructions' )->value}
								<div class='wpcci_clearing'>&nbsp;</div>
							</div>";
		}
		return $message;
	}	
	
	// Display after most of the fields in the comment form
	public function display_after( $defaults ) {
		if ( ! $this->permitted() ) {
		  	return $defaults;
		} 
		
		$message = $this->get_message() ;
		$defaults [ 'comment_notes_after' ] .= $message;
		return $defaults;
	}

	// Display above the comment form's fields
	public function display_before() {
		if ( $this->permitted() ) {
		  	echo $this->get_message();
		}
	}

	public function admin_css() {
	  	wp_enqueue_style( 'wpcci_admin_css', plugins_url( 'admin.css', __FILE__ ) ) ;

	}
	//save version 
	public function install() {
	  	update_option( 'wpcci_just_activated', 1 );
	}

	public function post_install_message() {
		if( get_option( 'wpcci_just_activated' ) ) {
			$message = sprintf( __( 'Thank you for installing the Comment Form Message WordPress plugin. Please proceed to the %1$splugin settings area%2$s to customize its options.', 'comment-form-message' ), '<a href="options-general.php?page=wpcci_options_page">', '</a>' );
	    		echo "	<div class='updated'>
       					<p>
						$message
					</p>
    				</div>";
			update_option( 'wpcci_just_activated', 0 );
		}
	}
} 

new comment_form_message();