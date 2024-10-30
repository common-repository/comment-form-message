<?php
/**
 * abd_libs auto loader
 * 
 * - Registers auto loader for abd_libs classes
 * - Loads language files. 
 *
 * @package abd_libs
 * @author Abdussamad Abdurrazzaq
 * 
*/
class abd_libs {
	const VERSION = '1.1.2';
  	function __construct() {
	  	load_plugin_textdomain( 'ABDLIBS_LANG', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	  	spl_autoload_register( array( $this, 'autoload' ) );
		$this->save_version();
  	}

	private function save_version() {
	  	update_option( 'abd_libs_version', self::VERSION );
	}

	private function autoload( $obj_name ) {
		$dir =  plugin_dir_path( __FILE__ ) ;
		//only deal with classes with the correct prefix. Disallow problematic characters like periods.
		if ( preg_match( '/^abd_[_a-z0-9]+$/', $obj_name ) ) {
			$class_name = "class.$obj_name.inc.php";
			$abstract_class_name = "abstract.$class_name";
			if ( file_exists( $dir . $class_name ) ) {
				include( $dir . $class_name );
			} elseif ( file_exists( $dir . $abstract_class_name ) ) {
				include( $dir . $abstract_class_name );
			} 
		}	
	}
}

new abd_libs();