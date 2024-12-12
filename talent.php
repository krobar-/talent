<?php
/*
	Plugin Name: Talent Casting Database
	Plugin URI: http://krobar.net/
	Description: Casting Database.
	Version: 1.0.0
	Author: Kurt Robar
	Author URI: http://krobar.net/
*/

/*!
	@file talent.php
	A Casting Database plugin for WordPress.
	@version 1.0.0
	@author Kurt Robar
	@updated 2014-07-15
*/

if (!defined('DIRECTORY_SEPARATOR'))
{
	define('DIRECTORY_SEPARATOR', '/', true);	// Directory Separator
}

if (!class_exists("Talent")) {

/*!
	@class Talent
	@abstract
		The primary class for the Talent Casting Database WordPress plugin.
	@discussion
		Base class for Talent Casting Database. 
		A plugin for managing casting and talent.
	@var _options (array) [private static]
		Plugin Options. 
		Can only be changed with class getter and setter methods
		(ex. Talent::set_option('name', 'value'); ).
	@var _default_options (array) [private static]
		Plugin Option Defaults.
*/
class Talent
{
	const NAME 		= 'Talent Casting Database';
	const VERSION 	= '1.0.2';
	const OPTIONS 	= 'tcd_settings';
	const PREFIX 	= "tcd_";

	private	static $_delegate_classes 	= array();
	public	static $prefix 				= 'tcd_';

	private static $notices 			= array();
	private static $_options			= array();
	private static $_default_options 	= array(
		'img_thumb_width'		=> 240,
		'img_thumb_height'		=> 280,
		'img_thumb_crop'		=> TRUE,
		'img_full_width'		=> 600,
		'img_full_height'		=> 800,
		'img_full_crop'			=> FALSE
	);

	private	static $nonce;
	public	static $nonceTag			= 'tcd_nonce';
	public	static $url;
	public	static $path;

	public function init()
	{
		if( !is_admin() ) return;
		
		// Get Plugin Paths
		self::$path = self::getPathObject( array( 'lib' => 'inc' ) );
		
		// Get Plugin URLs
		self::$url = self::getUrlObject( array( 'lib' => 'inc' ) );

		// Setup Autoloading of Classes
		Roboloader::init();
		Roboloader::addIncludePath( self::$path->lib );
		Roboloader::addAutoloadExtension('.php');
		spl_autoload_register( array( 'Roboloader', 'load' ) );
		
		// Load/Create Plugin Options
		self::$_options = get_option( self::OPTIONS );
		if (empty(self::$_options))
		{
			self::$_options = self::$_default_options;
			self::$_options['version'] = self::VERSION;
			$deprecated = '';
			$autoload = 'yes';
			add_option( self::OPTIONS, self::$_options, $deprecated, $autoload );
		}

		if( !class_exists( 'WP_List_Table' ) )
		{
			// needed for WordPress tables
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
			
		}

		// Activation/Deactivation
		self::activationDeactivation();
		
		// Actions
		$_actions = array( 'plugins_loaded,1', 'init', 'after_setup_theme,99', 'parse_request', 'parse_query', 'pre_get_posts', 'generate_rewrite_rules', 'loop_start', 'loop_end', 'wp_head', 'wp_footer', 'send_headers' );
		self::addActionArray($_actions);

		if ( is_admin() )
		{
			// Admin Actions
			$admin_actions = array('admin_init','admin_menu', 'admin_head', 'add_meta_boxes,99', 'admin_footer', 'admin_notices', 'save_post');
			self::addActionArray($admin_actions);
		}

		TalentMain::init();
		self::updateCheck();
	}

	public static function activationDeactivation()
	{

		if( self::isPlugin() )
		{
			add_action( 'activate_'.str_replace('\\', '/', plugin_basename( __FILE__ ) ), array( __CLASS__, 'activation'));
			add_action( 'deactivate_'.str_replace('\\', '/', plugin_basename( __FILE__ ) ), array( __CLASS__, 'deactivation'));
		}
		else
		{
			$_cur_dir = explode( DIRECTORY_SEPARATOR, dirname( __FILE__ ) );
			$_tag = $_cur_dir[count($_cur_dir)-1];
			self::wp_register_theme_activation_hook($_tag, array( __CLASS__, 'activation'));
			self::wp_register_theme_deactivation_hook($_tag, array( __CLASS__, 'deactivation'));
		}
	}

	public static function updateOptions()
	{
		update_option( self::OPTIONS, self::$_options );
		return;
	}

	public static function getOption( $name )
	{	
		if ( array_key_exists($name, self::$_options ) )
		{
			return( self::$_options[$name] );
		}
		$trace = debug_backtrace();
		trigger_error(
		    'Undefined property via ' . __CLASS__ . '::getOption(): ' . $name .
		    ' in ' . $trace[0]['file'] .
		    ' on line ' . $trace[0]['line'],
		    E_USER_NOTICE );
		return( NULL );
	}

	public static function setOption( $name, $value )
	{
		self::$_options[$name] = $value;
	}
	
	public static function optionIsset( $name )
	{
		return isset( self::$_options[$name] );
	}
	
	public static function unsetOption( $name )
	{
		unset( self::$_options[$name] );
	}

	public static function registerDelegate( $delegate )
	{
		if( !in_array( $delegate, self::$_delegate_classes ) )
		{
			self::$_delegate_classes[] = $delegate;
		}
	}

	public static function callDelegates( $method, $params = NULL )
	{
		foreach ( self::$_delegate_classes as $_class )
		{
			if ( method_exists( $_class, $method ) )
			{
				// use call_user_func_array when the method requires multiple defined parameters,
				// use call_user_func when the method has single defined parameter, or method does not define parameters.
				if( is_array($params) && !is_object($params) )
				{
					return ( 'object' == gettype( $_class ) ) ? call_user_func_array(array($_class, $method), $params) : call_user_func_array("$_class::$method", $params);
				} elseif( $params !== NULL ) {
					return ( 'object' == gettype( $_class ) ) ? call_user_func(array($_class, $method), $params) : call_user_func("$_class::$method", $params);
				} else {
					return ( 'object' == gettype( $_class ) ) ? call_user_func(array($_class, $method)) : call_user_func("$_class::$method"); //$_class->$method() : $_class::$method();
				}
			}
		}
	}

	public static function activation()
	{
		self::setOption( 'one_time', 'activation' );
		self::updateOptions();
		self::callDelegates('activation' );
	}
	
	public static function deactivation()
	{
		self::setOption( 'one_time', 'deactivation' );
		self::updateOptions();
		self::callDelegates('deactivation' );
	}
	
	public static function updateCheck()
	{
		if ( version_compare( self::VERSION, self::getOption( 'version' ) ) > 0 )
		{
			self::setOption( 'one_time', 'update' );
			self::updateOptions();
			self::callDelegates( 'update' );
		}
	}
	
	private static function handleDelayedPluginActions()
	{	
		$_type = '';
		// Check if there is a special action required
		if( self::optionIsset( 'one_time' ) )
		{
			// which event, and handle it
			$_type = self::getOption( 'one_time' );
			self::unsetOption( 'one_time' );
			self::updateOptions();
		}
		
		switch ($_type)
		{
			case 'activation':
				self::callDelegates( 'activationAtInit' );
				break;
				
			case 'update':
				self::callDelegates( 'updateAtInit' );
				break;
				
			case 'deactivation':
				self::callDelegates( 'deactivationAtInit' );
				break;
			
			default:
				return;
				break;
		}
	}

	public static function ajaxHandler()
	{
		self::callDelegates( 'ajaxHandler' );
		
		exit();
	}

	public static function handleDelegateCalls()
	{
		$hook = current_filter();
		$function_name = self::underscoreToCamelCase( $hook );

		if( 'init' == $hook )
		{
			self::handleDelayedPluginActions();
			self::callDelegates( 'initialize' );
			add_action('wp_ajax_' . self::$path->filename, array( __CLASS__, 'ajaxHandler' ), 9999 );
			add_action('wp_ajax_nopriv_' . self::$path->filename, array( __CLASS__, 'ajaxHandler' ), 9999 );
		}
		else
		{
			$_numargs = func_num_args();
			$_args = func_get_arg(0);
			return ( $_numargs > 0 ) ? self::callDelegates( $function_name, $_args ) : self::callDelegates( $function_name );
		} 
	}

	public static function addActionArray( $actions )
	{
		foreach ($actions as $action) {
			$_params = explode(',', $action);
			$_hook = $_params[0];
			$_priority = (!empty($_params[1]) && is_numeric($_params[1]) && $_params[1] > 0) ? $_params[1] : 10;
			$_args = (!empty($_params[2]) && is_numeric($_params[2]) && $_params[2] > 0) ? $_params[2] : 1;
			if(!empty($_hook))
			{
				add_action($_hook, array( __CLASS__, 'handleDelegateCalls'), $_priority);
			}
		}
	}

	public static function addNotice( $message, $type = 'notice' )
	{
		if( empty( $message ) ) return( FALSE );
		if( 'notice' !== $type ) $type = 'error';
		self::$notices[] = (object) array( 'type' => $type, 'message' => $message );
	}

	public static function adminNotices()
	{
		foreach( self::$notices as $object )
		{
			if( empty( $object ) ) continue;
			$_class = ( 'notice' == $object->type ) ? 'updated' : 'error';
			echo( "<div class=\"" . $_class . "\">\n" );
			echo( "\t<p>" . $object->message . "</p>\n" );
			echo( "</div>\n" );
		}
	}

	public function getWordpressDirectory()
	{
		$dir = dirname( __FILE__ );
		do {
			if( file_exists ($dir . "/wp-config.php" ) )
			{
				return( $dir );
			}
		} while( $dir = realpath( "$dir/.." ) );
		
		return( NULL );
	}
	
	
	private function getPathObject()
	{

		if( self::isPlugin() )
		{
			$_result = (object) pathinfo( realpath( __FILE__ ) );
			$_result->plugin_basename = plugin_basename( __FILE__ );
			$_bdir = $_result->dirname;
		}
		else
		{
			$_bdir = get_stylesheet_directory();
			$_result = (object) pathinfo( $_bdir );
			$_result->dir = $_bdir;
			$_result->filename = __FILE__;
		}

		if( func_num_args() > 0 && is_array( func_get_arg( 0 ) ) )
		{
			$_arg_list = func_get_arg( 0 );
			foreach ( $_arg_list as $key => $value ) {
				$_result->$key = $_bdir . DIRECTORY_SEPARATOR . $value;
			}
		}
		
		return( $_result );
	}
	
	private function getUrlObject()
	{

		if( self::isPlugin() )
		{
			$_burl = WP_PLUGIN_URL . '/' . plugin_basename( __FILE__ );
	 		$_result = (object) parse_url( $_action );
	 		$_result->plugin = WP_PLUGIN_URL . '/' . str_replace( basename( __FILE__ ), "", plugin_basename( __FILE__ ) ); // PLUGIN_URL
	 		$_result->action = $_burl;  // ACTION_URL
		}
		else
		{
			$_burl = get_stylesheet_directory_uri();
	 		$_result = (object) parse_url( $_burl );
	 		$_result->theme = $_burl;
	 	}

	 	$_result->ajax = admin_url( 'admin-ajax.php' ); // AJAX_URL
		
	 	if( func_num_args() > 0 && is_array( func_get_arg( 0 ) ) )
		{
			$_arg_list = func_get_arg( 0 );
			foreach ( $_arg_list as $key => $value ) {
				$_result->$key = $_burl . '/'. $value;
			}
		}

		return( $_result );
	}

	public static function userIsAuthorized( $return_capability = FALSE )
	{
		$capability = 'edit_published_pages';
		return( ( $return_capability ) ? $capability : current_user_can( $capability ) );
	}

	public static function createNonce( $action = -1 )
	{
		return( wp_nonce_field( $action , self::$nonceTag, FALSE, FALSE ) );
	}
	
	public static function createURLNonce( $action = -1 )
	{
		$_nonceTag = self::$nonceTag;
		$_nonce = wp_create_nonce( $action );
		$_tag = "&{$_nonceTag}={$_nonce}";
		return( $_tag );
	}

	public static function verifyNonce( $action = -1 )
	{
		$_nonce = ( isset( $_POST[self::$nonceTag] ) ) ? $_POST[self::$nonceTag] : ( ( isset( $_GET[self::$nonceTag] ) ) ? $_GET[self::$nonceTag] : '' );
		
		switch( wp_verify_nonce( $_nonce, $action ) )
		{
			case 1:
				return( TRUE );
				break;
			case 2:
				if( is_admin() ) CollaborateAdmin::addNotice( 'Data is more than 12 hours old.  Please try again.', 'error' );
				return( FALSE );	
			case FALSE:
			default:
				if( is_admin() ) CollaborateAdmin::addNotice( 'Insecure data detected.', 'error' );
				return( FALSE );
		}
		
		return( FALSE );
	}

	public static function errorHandler($ErrLevel, $ErrMessage)
	{
		if ($ErrLevel == E_RECOVERABLE_ERROR)
		{
			return strpos($ErrMessage, 'must be an instance of string, string')
				|| strpos($ErrMessage, 'must be an instance of boolean, boolean')
				|| strpos($ErrMessage, 'must be an instance of integer, integer')
				|| strpos($ErrMessage, 'must be an instance of float, double')
				|| strpos($ErrMessage, 'must be an instance of resource, resource');
		}
	}

	public static function isPlugin()
	{
		$wp_plugin_dir = dirname( WP_PLUGIN_DIR );
		$current_dir = dirname( __FILE__ );
		$_plugin = stristr( $current_dir, $wp_plugin_dir );
		return( ( FALSE === $_plugin ) ? FALSE : TRUE );
	}

	public static function underscoreToCamelCase( $string, $first_char_caps = false)
	{
    	if( $first_char_caps == true )
    	{
    	    $string[0] = strtoupper($string[0]);
    	}
    	$func = create_function('$c', 'return strtoupper($c[1]);');
    	return preg_replace_callback('/_([a-z])/', $func, $string);
	}

	public static function camelCaseToUnderscore( $string )
	{
		return strtolower( preg_replace('/([a-z])([A-Z])/', '$1_$2', $string ) );
	}

	# Theme Activation and Deactivation Hooks

	/**
	*
	* @desc registers a theme activation hook
	* @param string $code : Code of the theme. This can be the base folder of your theme. Eg if your theme is in folder 'mytheme' then code will be 'mytheme'
	* @param callback $function : Function to call when theme gets activated.
	*/
	public static function wp_register_theme_activation_hook($code, $function) {
		$optionKey="theme_is_activated_" . $code;
		if(!get_option($optionKey))
		{
			call_user_func($function);
			update_option($optionKey , 1);
		}
	}

	/**
	* @desc registers deactivation hook
	* @param string $code : Code of the theme. This must match the value you provided in wp_register_theme_activation_hook function as $code
	* @param callback $function : Function to call when theme gets deactivated.
	*/
	public static function wp_register_theme_deactivation_hook($code, $function) {
		// store function in code specific global
		$GLOBALS["wp_register_theme_deactivation_hook_function" . $code]=$function;

		// create a runtime function which will delete the option set while activation of this theme and will call deactivation function provided in $function
		$fn=create_function('$theme', ' call_user_func($GLOBALS["wp_register_theme_deactivation_hook_function' . $code . '"]); delete_option("theme_is_activated_' . $code. '");');

		// add above created function to switch_theme action hook. This hook gets called when admin changes the theme.
		// Due to wordpress core implementation this hook can only be received by currently active theme (which is going to be deactivated as admin has chosen another one.
		// Your theme can perceive this hook as a deactivation hook.)
		add_action("switch_theme", $fn);
	}

} // end class Talent
} // end if/class_exists

if( !class_exists( "Roboloader" ) ) { class Roboloader
{

	// WordPress is finicky 
	// ... separate it out from the usual spl_autoload include paths ...
	private static $include_paths = array();
	

	public static function init()
	{
		if (!defined('DIRECTORY_SEPARATOR'))
		{
			define('DIRECTORY_SEPARATOR', '/', true);	// Directory Separator
		}
	}

	// autoload specified class
	public static function load( $className )
	{
		$_exts = explode( ',', spl_autoload_extensions() );
		
		// Translate camel case and underscores to a directory separator,
		// this allows us to use Class_Subclass or classSubclass to load from [...]/class/subclass.php
		$_classPath = strtolower( preg_replace('/([a-z])([A-Z])/', '$1_$2', $className ) ); // camel case to underscore
		$_classPath = str_replace( '_', DIRECTORY_SEPARATOR, $_classPath ); // underscore to directory separator

		foreach( self::$include_paths as $_path )
		{
			$_path = rtrim( $_path, DIRECTORY_SEPARATOR );
			foreach( $_exts as $_ext )
			{
				$filepath = $_path . DIRECTORY_SEPARATOR . $_classPath . $_ext;
				if( file_exists( $filepath ) )
				{
					include_once( $filepath );
					return( TRUE );
				}
			}
		}
		return( FALSE );
	}
 
	// try to load recursively the specified file
	private static function recursiveLoad( $file, $path )
	{
		$_exts = explode( ',', spl_autoload_extensions() );

		if( FALSE !== ( $handle = opendir( $path ) ) )
		{
			// search recursively for the specified file
			while ( FALSE !== ( $dir = readdir( $handle ) ) )
			{
				if( FALSE === strpos( $dir, '.' ) )
				{
					$path .= DIRECTORY_SEPARATOR . $dir;
					foreach( $_exts as $_ext )
					{
						$filepath = $path . DIRECTORY_SEPARATOR . $file . $_ext;
						if( file_exists( $filepath ) )
						{
							include_once( $filepath );
							//closedir( $handle );
							return( TRUE );
						}
					}
					self::recursiveAutoload( $file, $path );
				}
			}
			closedir( $handle );
		}
		return( FALSE );
	}
	
	/*!
		@method addIncludePath
		@abstract
			Add an include path for auto loading classes.
		@param path
			(string|array) Path(s) to add.
		@return
			(void)
	*/
	public static function addIncludePath( $path )
	{
	    foreach ( func_get_args() as $path )
	    {
	        if ( !file_exists( $path ) || ( file_exists( $path ) && 'dir' !== filetype( $path ) ) )
	        {
	            trigger_error( "Include path '{$path}' does not exist, or is not a directory.", E_USER_WARNING );
	            continue;
	        }
	   
			if ( FALSE === array_search( $path, self::$include_paths ) )
	        {
	            array_push( self::$include_paths, $path );
	        }
	        
	        return( self::$include_paths );
	    }
	}
	
	/*!
		@method removeIncludePath
		@abstract
			Remove an include path for auto loading classes.
		@param path
			(string|array) Path(s) to remove.
		@return
			(void)
	*/
	public static function removeIncludePath( $path )
	{
	    foreach ( func_get_args() as $path )
	    {
	        if ( FALSE !== ( $k = array_search( $path, self::$include_paths ) ) )
	        {
	        	if ( count( self::$include_paths ) < 2 )
	        	{
	        	    trigger_error( "Include path '{$path}' can not be removed; it is the last path available.", E_USER_NOTICE );
	        	    return;
	        	}

	            unset( self::$include_paths[$k] );
	        }
	        else
	        {
	            continue;
	        }
	    }
	}
	
	/*!
		@method addAutoloadExtension
		@abstract
			Add file extension(s) to autoload extensions.
		@discussion
			The extensions .php and .inc are already included by default.
			However, adding any extensions (including .php or .inc) will force a reorder from the
			default of .inc, .php to .php, .inc which may increase loading efficiency.
		@param extension
			(string|array) Extension(s) to add.
		@return
			(void)
	*/
	public static function addAutoloadExtension( $extension )
	{
		$_first_exts = array( '.php', '.inc' );
		$_exts = explode( ',', spl_autoload_extensions() );
		
		// remove exts so we can specify their order later.
		foreach ($_first_exts as $_r_ext)
		{
			if ( FALSE !== ( $k = array_search( $_r_ext, $_exts ) ) )
			{
			    unset( $_exts[$k] );
			}
		}
		
		// parse parameters and add if not already in the extension list.
		foreach ( func_get_args() as $extension )
		{
			$_add = explode( ',', $extension );
			
			foreach ( $_add as $_ext )
			{
				if ( FALSE === array_search( $_ext, $_exts ) && !in_array( $_ext, $_first_exts ) )
				{
					array_push( $_exts, $_ext );
				}
			}
		}
		
		spl_autoload_extensions( implode( ',', $_first_exts ) .','. implode( ',', $_exts ) );
	}
	
}} // end if/class

Talent::init();
?>