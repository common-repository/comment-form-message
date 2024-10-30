<?php
/**
 * Text box option
 * 
 * Implement an HTML text box as a part of an admin page. The text box supports regular expression and or callback function validation methods.
 *
 * @package abd_libs
 * @author Abdussamad Abdurrazzaq
 *
 */
class abd_text_option extends abd_option {
	private $_regular_expression;
	
	public function __construct( $name, $title, $description, $section_name, $default_value = NULL, $regular_expression = NULL, $validation_callback = NULL ) {
		parent::__construct( $name, $title, 'text', $description, $section_name, $default_value, $validation_callback );
		$this->_regular_expression = $regular_expression;
				
	}
	
	public function display_option() {
		echo "	<input name='{$this->name}' class='{$this->name}_class' type='{$this->type}' value='{$this->value}' />
			<p class='description'>
				{$this->description}
			</p>
			";
	}
	
	public function validate( $input ) {
		$in_clean = trim( $input );
		if ( $this->_regular_expression != NULL ) {
			if ( preg_match( $this->_regular_expression, $in_clean ) ) {
				$this->value = $in_clean;
			} else {
			  	$this->validation_failed_message();
			}
			
		} elseif ( $this->validation_callback != NULL ) {
			if ( is_callable( $this->validation_callback ) ) {
				if( call_user_func( $this->validation_callback, $in_clean, $this->value ) ){
				  	$this->value = $in_clean;
				} else {
			  		$this->validation_failed_message();
				}
			} else {
			  	throw new Exception ( sprintf( 'Invalid callback function for %s . Function or class method does not exist.', $this->title ) );
			}
		} 
		return $this->value ;
	}
}