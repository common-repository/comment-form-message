<?php
/**
 * Check boxes with associated options including menu order (sorting)
 * 
 * @package abd_libs
 * @author Abdussamad Abdurrazzaq
 *
**/
class abd_sortable_option extends abd_option {
	private $all_values;
	private $page_name;
	private $column_names;
	public function __construct( $name, $title, $description, $page_name, $section_name, array $all_values, array $column_names, $default_values = NULL ) {
		$this->all_values = $all_values;
		$this->page_name = $page_name;
		$this->column_names = $column_names;
		if( empty( $this->all_values ) ) {
		  	throw new Exception( 'Sortable option "all values" fail validation' );
		}
		if( ! $this->validate_column_names() ) {
		  	throw new Exception( 'Sortable option "column names" fail validation' );
		}
	
		$default_values = $this->populate_default_order( $default_values );
		if( $this->check_default_values( $default_values ) ) {
		  	parent::__construct( $name, $title, 'sortable', $description, $section_name, $default_values, NULL );		  
		} else {
		  	throw new Exception( 'Invalid default values for sortable option' );
		}
 		add_action( 'abd_libs_options_page_admin_init', array( $this, 'init' ), 10, 2 );
	}

	private function validate_column_names() {
	  	if( ! isset( $this->column_names[ 'key' ] ) || ! isset( $this->column_names[ 'selected' ] ) || ! isset( $this->column_names[ 'order' ] ) ) {
		  	return false;
	  	}
		return true;
	}

	private function populate_default_order( $default_values ) {
		$one_arr = array_fill( 0, count( $this->all_values ), count( $this->all_values ) );
		$merged_arr = array_combine( array_keys( $this->all_values ), $one_arr );
	  	if( isset( $default_values[ 'order' ] ) ) {
		  	if( is_array( $default_values[ 'order' ] ) ) {
			  	
				$default_values[ 'order' ] = array_merge( $merged_arr, $default_values[ 'order' ] );
				return $default_values;
		  	}
	  	}
		$default_values[ 'order' ] = $merged_arr;
		return $default_values;
	}

	private function diff( $values ) {
	  	$all_keys = array_keys ( $this->all_values );  	
		$diff = array_diff ( $values, $all_keys ) ;
		if ( count( $diff ) > 0 ) {
		 	return false;
		} else {
			return true;
		}
	}

	private function check_key( $values ) {
		if( isset( $values[ 'key' ] ) ) {
			if( is_array( $values[ 'key' ] ) ) {
				return $this->diff( $values[ 'key' ] )  ;
			} else {
			  	return false;
			}
		} else {
		  	return true;
		}
	}

	private function check_selected( $values ) {
	  	if( isset( $values[ 'selected' ] ) ) {
			if( is_array( $values[ 'selected' ] ) ) {
		 	 	return $this->diff( $values[ 'selected' ] )  ;
			} else {
			  	return false;
			}
		} else {
		  	return true;
		}
	}

	private function check_order( $values ) {
	  	if( isset( $values[ 'order' ] ) ) {
			if( is_array( $values[ 'order' ] ) ) {
				if( ! $this->diff( array_keys( $values[ 'order' ] ) ) ) {
					return false;
				}
				if( count( array_diff( array_keys( $this->all_values ), array_keys( $values[ 'order' ] ) ) ) > 0 ) {
					return false;
				}
				foreach( $values[ 'order' ] as $key => $value ) {
					if ( is_numeric( $value ) && $value >= 1 && $value <= count( $this->all_values ) ) {
						if ( ! preg_match( '/^\d+$/', $value ) ) { //no decimal points or thousands separators. digits only.
							return false;
						} 
					} else {
		  				return false;
					}
				}
				return true;
		  	} else {
			  	return false;
		  	}
		} else {
		  	return false;
		}
	}

	private function check_default_values( $values ) {
		if( $this->check_key( $values ) && $this->check_selected( $values ) && $this->check_order( $values ) ) {
		  	return true;
		} else { 
			return false;
		}
	}

	private function key_is_selected( $key ) {
	  	if( isset( $this->value[ 'key' ] ) ) {
		  	if( is_array( $this->value[ 'key' ] ) ) {
			  	return in_array( $key, $this->value[ 'key' ] );
		  	}
	  	}
		return false;	
	}

	private function selected_is_selected( $selected ) {
	  	if( isset( $this->value[ 'selected' ] ) ) {
		  	if( is_array( $this->value[ 'selected' ] ) ) {
			  	return in_array( $selected, $this->value[ 'selected' ] );
		  	}
	  	}
		return false;	
	}

	private function sorted_values() {
	  	$order = array_merge( $this->default_value[ 'order' ], $this->value[ 'order' ] );
		asort( $order );
		return array_merge( $order, $this->all_values );
	}

	public function display_option() {
		$ul_id = $this->name . '_ul_id';
		echo '<div class="sortable_headings">
			<h4 class="sortable_heading">' . $this->column_names[ 'key' ] . '</h4>
			<h4 class="sortable_heading">' . $this->column_names[ 'selected' ] . '</h4>
			<h4 class="sortable_heading">' . $this->column_names[ 'order' ] . '</h4>
			</div>';
		echo "<ul id='$ul_id' class='sortable_ul_class' >";
		$count = 0;
		foreach ( $this->sorted_values() as $value => $title ) {
			$key_id = "{$this->name}_key_$count";
			$selected_id = "{$this->name}_selected_$count";
			$li_id = "{$this->name}_li_$count";
			$order_id = "{$this->name}_order_$li_id";
			$alternate = $count % 2 ? ' alternate ':'';
			echo "<li id='$li_id' class='sortable_li_class $alternate'>
				<div class='sortable_move_handle'>&nbsp;</div>
				<label for='$key_id' class='sortable_key_label'>
				<input name='{$this->name}[key][]' type='checkbox' value='$value' id='$key_id' class='sortable_key'";
			if ( $this->key_is_selected( $value ) ) {
				echo " checked='yes' ";		
			}
			echo " />$title</label>";

			echo "<label for='$selected_id' class='sortable_selected_label'>
				<input name='{$this->name}[selected][]' type='checkbox' value='$value' id='$selected_id' class='sortable_selected'";
			if ( $this->selected_is_selected( $value ) ) {
				echo " checked='yes' ";		
			}
			echo ' />' . __( 'Yes', 'ABDLIBS_LANG' ) . '</label>';

			echo "<input name='{$this->name}[order][$value]' type='text' value='" . ( isset( $this->value[ 'order' ][ $value ] ) ? $this->value[ 'order' ][ $value ] : $this->default_value[ 'order' ][ $value ] ) . "' id='$order_id' class='sortable_order'/>";
			echo '</li>';
			$count++;
		}
		echo '</ul>';
		echo "	<em>
				{$this->description}
			</em>
			";  

		echo "	<script type='text/javascript'>
				jQuery(document).ready(function() {
					jQuery('#$ul_id').sortable( {
					  	 update: function(event,ui) {
          						//create an array with the new order
          						var order = jQuery(this).sortable('toArray');
							for( var key in order ) {
          							var val = order[key];
								document.getElementById( '{$this->name}_order_' + val ).value = Number(key) + 1;
							}
						}
					} ) } );
			</script>";
	}

	public function validate( $input ) {
	  	if( isset( $input[ 'key' ] ) ) {
			if ( $this->check_key( $input ) ) {
		  		$this->value[ 'key' ] = $input[ 'key' ];
			} else {
				$this->admin_message( sprintf( __( 'Invalid value for option titled "%s".', 'ABDLIBS_LANG' ), $this->column_names[ 'key' ] ) );
			}
	  	} else {
		  	unset( $this->value[ 'key' ] );
	  	}
		if( isset( $input[ 'selected' ] ) ) {
			if ( $this->check_selected( $input ) ) {
		  		$this->value[ 'selected' ] = $input[ 'selected' ];
			} else {
				$this->admin_message( sprintf( __( 'Invalid value for option titled "%s".', 'ABDLIBS_LANG' ), $this->column_names[ 'selected' ] ) );
			}
	  	} else {
		  	unset( $this->value[ 'selected' ] );
	  	}

		if( $this->check_order( $input ) ) {
			$this->value[ 'order' ] = $input[ 'order' ];
		} else {
			$this->admin_message( sprintf( __( 'Invalid value for option titled "%1s". Enter a whole number between 1 and %2d.', 'ABDLIBS_LANG' ), $this->column_names[ 'order' ], count( $this->all_values ) ) );
		}
		return $this->value;
	}

	public function init( $page_name, $parent_page ) {
		if( $page_name == $this->page_name ) {
			$hook = get_plugin_page_hook( $page_name, $parent_page );
	  		add_action( "admin_print_styles-$hook", array( $this, 'add_css_js' ) );
		}
	}

	public function add_css_js() {
		wp_enqueue_script( 'jquery-ui-sortable' );		
		wp_enqueue_style( "{$this->name}-css", plugins_url( '/css/sortable.css', __FILE__ ) );
	}	
}