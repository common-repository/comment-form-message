<?php
/**
 * Admin options page sections.
 * 
 * Sections in an admin options. A section belongs to a single page. A section has multiple options
 *
 * @package abd_libs
 * @author Abdussamad Abdurrazzaq
 *
**/
class abd_section extends abd_ground_floor {
	protected $page_name;
	protected $options;  //array of option objects
	
	function __construct( $name, $title, $description, $page ) {
		parent::__construct( $name, $title, $description );
		$this->page_name  = $page;
		$this->options = array();
		add_action( 'admin_init', array( $this, 'register_section' ) );
	}
	
	public function register_section() {
		add_settings_section( $this->name, $this->title, array( $this, 'display_section_description' ), $this->name );
	}
	
	public function display_section_description() {
		echo "<p id='{$this->name}_id'>{$this->description}</p>";
	}
	
	public function get_option( $option_name ) {
		if ( array_key_exists( $option_name, $this->options ) ) {
			return $this->options [$option_name];
		} else {
			return false;
		}
	}

	public function get_title() {
	  	return $this->title;
	}

	public function add_sortable_option( $name, $title, $description, $all_values, $column_names, $default_value = NULL ) {
	  	return $this->options[ $name ] = new abd_sortable_option( $name, $title, $description, $this->page_name, $this->name, $all_values, $column_names, $default_value ) ;
	}

	public function add_date_picker_option( $name, $title, $description, $default_value ) {
	  	return $this->options[ $name ] = new abd_date_picker_option( $name, $title, $description, $this->page_name, $this->name, $default_value ) ;
	}
	
	public function add_color_picker_option( $name, $title, $description, $default_value ) {
	  	$this->options [ $name ] = new abd_color_picker_option( $name, $title, $description, $this->page_name, $this->name, $default_value ) ;
		return $this->options [ $name ] ;
	}

	public function add_yesno_radio_option ( $name, $title, $description, $default_value = 'y', $validation_callback = NULL ) {
	  	$this->options[ $name ] = new abd_radio_option( $name, $title, $description, $this->name, array( 'y' => __( 'Yes', 'ABDLIBS_LANG' ), 'n' => __( 'No', 'ABDLIBS_LANG' ) ), $default_value, $validation_callback );
	}

	public function add_slider_option( $name, $title, $description, $min, $max, $default_value, $whole_number = true, $step = 1 ) {
	  	$this->options [ $name ] = new abd_slider_option( $name, $title, $description, $this->page_name, $this->name, $min, $max, $default_value, $whole_number, $step ) ;
		return $this->options [ $name ] ;
	}

	public function add_numeric_text_option( $name, $title, $description, $min, $max, $default_value, $whole_number = true ) {
	  	$this->options [ $name ] = new abd_numeric_text_option( $name, $title, $description, $this->name, $min, $max, $default_value, $whole_number ) ;
		return $this->options [ $name ] ;
	}

	public function add_upload_option( $name, $title, $description, $validation_callback = NULL ) {
	  	return $this->options[ $name ] = new abd_upload_option( $name, $title, $description, $this->page_name, $this->name, $validation_callback );
	}

	public function add_option( $type, $args ) {
		if ( count( $args ) < 3 || count( $args ) > 6 ) {
			throw new Exception ( 'Invalid arguments' );
		}
		@list( $name, $title, $description, $default_value, $regular_expression, $validation_callback ) = $args;
		$object_name = "abd_{$type}_option";
		if ( ! class_exists( $object_name ) ) {
			throw new Exception( 'Class does not exist' );
		} else {
	  		$this->options [ $name ] = new $object_name( $name, $title, $description, $this->name, $default_value, $regular_expression, $validation_callback ) ;
			return $this->options [ $name ];
		}
	}
	
	public function __call( $name, $args ) {
	  	if ( method_exists( $this, $name ) ) {
		  	return call_user_func_array( array( $this, $name ), $args );
	  	} else {
			$map = array(
					'add_option' => array( 'radio', 'check', 'select', 'multiple_select',
								'text', 'textarea', 'wysiwyg', 'image_select', 
								'inline_radio', 'inline_check' )
					);
			$function_name = "add_%s_option";
			foreach ( $map as $real_function_name => $fake_func_array ) {
				foreach( $fake_func_array as $fake_func ) {
					$fake_function_name = sprintf( $function_name, $fake_func ) ;
					if( $name == $fake_function_name ) {
				  		return call_user_func( array( $this, $real_function_name ), $fake_func, $args );
					}
				}
			}
			
	  	}
		throw new Exception( 'Invalid method' );
	}
}