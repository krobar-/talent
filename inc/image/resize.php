<?php
/*
Class to resize images and save them in a cache, unlike some resizing scripts this is meant to be
called directly to use images from other servers and avoid security risks.  Avoid passing any user
input data to the functions in this class. * Requires PHP 5+ *
	
Loosely based on:
 image.php by Joe Lencioni [http://shiftingpixel.com]
 timthumb.php by Tim McDaniels and Darren Hoyt [http://code.google.com/p/timthumb/]
 imgsize.php by Michael John G. Lopez [http://www.sydel.net]

Usage:
 $resize = new Image_Resize;
 
 // set default width, height, crop, and quality
 $resize->width = '100';
 
 // set location of cache directory
 $resize->cache_dir = '[filepath]';
 
 // use defaults to resize original image
 $imagePath = $resize->resize('path/to/original.jpg');
 
 // or specify attributes for resizing (src, width, height, crop, quality)
 $imagePath = $resize->image('path/to/original.jpg', 100, 50, false, 90)
 
 
Note:
 Only the cached filename will be returned.  So it's up to you to know the
 path to the file.  Also, the cache_dir should be a local file path.  It
 defaults to ./cache - but this may not be useful.
 
Developed by Kurt Robar (dba krobar) - krobar@krobar.net
 
*/

if (!class_exists( "Image_Resize", FALSE ) ) { class Image_Resize {

	private $data 				= array();
	private static $cache_dir	= './cache';
	private static $use_curl	= TRUE;
	private static $prefix		= 'ir_';
	public static  $staticVars 	= array( 'cache_dir', 'use_curl', 'prefix' );
	
	# constructors #
	function __construct()
	{
		
		$this->data['crop']			= TRUE;
		$this->data['width']		= 75;
		$this->data['height']		= 75;
		$this->data['quality']		= 80;
		$this->data['error']		= FALSE;
		
		# check to see if GD exists
		if( !function_exists( 'imagecreatetruecolor' ) )
		{
			$this->data['error'] = "imagecreatetruecolor (GD Library) does not exist.";
			return( FALSE );
		}
		return( TRUE );
	}

	# standard methods #
	public function __set( $name, $value )
	{
		if( in_array( $name, self::$staticVars ) )
		{
			self::$$name = $value;
		} else {
			$this->data[$name] = $value;
		}
		
	}
	
	public function __get( $name )
	{
		if ( array_key_exists( $name, $this->data ) || in_array( $name, self::$staticVars ) )
		{
			return ( in_array( $name, self::$staticVars ) ) ? self::$$name : $this->data[$name];
		}
		$trace = debug_backtrace();
	    trigger_error(
	        'Undefined property via __get(): ' . $name .
	        ' in ' . $trace[0]['file'] .
	        ' on line ' . $trace[0]['line'],
	        E_USER_NOTICE);
	    return null;
	}
	
	public function __isset( $name )
	{
		return isset( $this->data[$name] );
	}

	public function __unset( $name )
	{
		unset( $this->data[$name] );
	}
	
	public function __toString()
	{
		$send = '';
		return( $send );
	}
	
	
	# public class methods #
	public function image( $src = FALSE, $width = NULL, $height = NULL, $crop = NULL, $quality = NULL )
	{
		
		if( !$src ) {
			$this->data['error'] = "No source specified.";
			return( FALSE );
		}
		
		# Override defaults?
		$width		= ( isset( $width ) )	? $width	: $this->data['width'];
		$height 	= ( isset( $height ) )	? $height	: $this->data['height'];
		$crop 		= ( isset( $crop ) ) 	? $crop		: $this->data['crop'];
		$quality 	= ( isset( $quality ) )	? $quality	: $this->data['quality'];
		if( $quality > 100 || $quality < 0 ) $quality = 100;
		
		// get extension
		$fragments = split( "\.", $src );
		$ext = strtolower( $fragments[ count( $fragments ) - 1 ] );
		
		// make sure source is valid file type
		if( !$this->valid_extension( $ext ) )
		{
			$this->data['error'] = "Invalid file type: .$ext; Must be .gif, .jpg, .jpeg, or .png.";
			return( FALSE );
		}
		
		// make file name
		$cache_file = self::$prefix . md5( "{$src}{$width}{$height}{$crop}{$quality}" ) . '.' . $ext; /* MODIFY? */
		
		// check to see if this image is in the cache already
		if( $this->check_cache( $cache_file ) )
		{
			return( $cache_file );
		}
		
		$image = $this->open_image( $ext, $src );
		if( FALSE === $image )
		{
			$this->data['error'] = "Unable to open image: $src.";
			// change cache filename to reflect an error image
			$cache_file = self::$prefix . md5( "{$src}{$width}{$height}{$crop}{$quality}" ) . '_err.' . $ext; /* SHORTEN? */
			if( $this->check_cache( $cache_file ) )
			{
				return( $cache_file );
			}
			$image = $this->error_image( $src );
		}
		
		// Get original width and height
		$this->data['o_width'] = $o_width = imagesx($image);
		$this->data['o_height'] = $o_height = imagesy($image);
		
		// don't allow new width or height to be greater than the original
		if( $width > $o_width ) {
			$width = $o_width;
		}
		if( $height > $o_height ) {
			$height = $o_height;
		}
		
		// generate new w/h if one or both is false or 0
		if( $width && !$height ) {
			$height = $o_height * ( $width / $o_width );
		} elseif($height && !$width) {
			$width = $o_width * ( $height / $o_height );
		} elseif(!$width && !$height) {
			$width = $o_width;
			$height = $o_height;
		}
		
		if( $crop ) {
			// create a new true color image
			$canvas = ImageCreateTrueColor( $width, $height );

			$src_x = $src_y = 0;
			$src_w = $o_width;
			$src_h = $o_height;
			
			$cmp_x = $o_width  / $width;
			$cmp_y = $o_height / $height;
			
			// calculate x or y coordinate and width or height of source
			if ( $cmp_x > $cmp_y ) {
				$src_w = round( ( $o_width / $cmp_x * $cmp_y ) );
				$src_x = round( ( $o_width - ( $o_width / $cmp_x * $cmp_y ) ) / 2 );
			} elseif ( $cmp_y > $cmp_x ) {
				$src_h = round( ( $o_height / $cmp_y * $cmp_x ) );
				$src_y = round( ( $o_height - ( $o_height / $cmp_y * $cmp_x ) ) / 2 );
			}
			
			ImageCopyResampled( $canvas, $image, 0, 0, $src_x, $src_y, $width, $height, $src_w, $src_h );

		} else {
			// copy and resize part of an image with resampling - keep proportional by treating width and height as maximum of each
			$ratio = min( $width / $o_width, $height / $o_height );
			$d_width = $ratio * $o_width;
			$d_height = $ratio * $o_height;

			// create a new true color image
			$canvas = ImageCreateTrueColor( $d_width, $d_height );

			ImageCopyResampled( $canvas, $image, 0, 0, 0, 0, $d_width, $d_height, $o_width, $o_height );
		}
		
		// output image to browser based on mime type
		$written = $this->write_image( $ext, $canvas, $quality, $cache_file );
		
		// remove image from memory
		@ImageDestroy( $canvas );
		
		if( !$written )
		{
			if( empty( $this->data['error'] ) ) $this->data['error'] = "Unable to write to cache directory: ". self::$cache_dir;
			return( FALSE );
		}
		
		return( $cache_file );
	}
	
	# private class methods #
	
	private function check_cache( $cache_file )
	{
		// make sure cache exists
		if( !file_exists( self::$cache_dir ) )
		{
			// create with 777 permissions so that developer can overwrite
			// files created by web server user
			if( !mkdir( self::$cache_dir, 0777 ) )
			{
				$this->data['error'] = "Unable to create cache directory: ". self::$cache_dir;
				return( FALSE );
			}
		}
		
		if( file_exists( self::$cache_dir . '/' . $cache_file ) ) {
			return( TRUE );
		}
		
		return( FALSE );
	}
	
	private function valid_extension( $ext )
	{
		if( preg_match( "/jpg|jpeg|png|gif/i", $ext ) ) return( TRUE );
		return( FALSE );
	}
	
	private function open_image( $ext, $src )
	{
		// if its local and we find the file it will be a directory path
		$this->convert_if_local( $src );
		
		# if src contains http:// and we use curl, we will get it from the remote site #
		if( FALSE !== stripos( $src, 'http://' ) && self::$use_curl )
		{
			$string = $this->get_curl_data( $src );
			if( $string )
			{
				$image = imagecreatefromstring( $string );
			} else {
				$image = FALSE;
			}
		# otherwise, we will assume it's either a local file or we don't need curl
		} else {
			switch ( $ext ) {
				case 'gif':
					$image = imagecreatefromgif( $src );
					break;
				case 'jpg':
				case 'jpeg':
					@ini_set( 'gd.jpeg_ignore_warning', 1 );
					$image = imagecreatefromjpeg( $src );
					break;
				case 'png':
					$image = imagecreatefrompng( $src );
					break;
				default:
					$image = FALSE;
			}
		}
		return( $image );
	}
	
	# if you can't use a remote source you can try curl #
	private function get_curl_data( $url )
	{
		
		@$ch = ( function_exists('curl_init') ) ? curl_init() : FALSE;
    	
		if( $ch )
		{
    		curl_setopt( $ch, CURLOPT_URL, $url );
    		curl_setopt( $ch, CURLOPT_HEADER, 0 );

    		ob_start();

    		curl_exec( $ch );
    		curl_close( $ch );
    		$string = ob_get_contents();
	
	    	ob_end_clean();
		} else {
			$this->data['error'] = "Unable to open stream, is libcurl available?";
			return( FALSE );
		}
		
    	return( $string );    
	}
	
	private function error_image( $n = 'unknown', $w = 800, $h = 600 )
	{
		
        /* Create a blank image */
        $im  = ImageCreateTrueColor( $w, $h );
        $bgc = ImageColorAllocate( $im, 255, 255, 255 );
        $tc  = ImageColorAllocate( $im, 0, 0, 0 );
		
        ImageFilledRectangle( $im, 0, 0, $w, $h, $bgc );
		
        /* Output an error message */
        ImageString( $im, 1, 30, 300, 'Error loading ' . $n, $tc );
    
		return ( $im );
	}
	
	private function write_image( $ext, $image_resized, $quality, $cache_file )
	{
		// check to see if we can write to the cache directory
		$is_writable = 0;
		$dir_writable = is_writable( self::$cache_dir );
		
		$cache_file_name = self::$cache_dir . '/' . $cache_file;        	
		
		if( touch( $cache_file_name ) ) {
			// give 666 permissions so that the developer 
			// can overwrite web server user
			chmod( $cache_file_name, 0666 );
			$is_writable = 1;
		}
		
		if( $is_writable ) {
			if( $ext == 'gif' ) {
				ImageGIF( $image_resized, $cache_file_name );
			} elseif( $ext == 'jpeg' || $ext == 'jpg' ) {
				ImageJPEG( $image_resized, $cache_file_name, $quality );
			} elseif( $ext == 'png' ) {
				$quality = floor( $quality * 0.09 );		
				ImagePNG( $image_resized, $cache_file_name, $quality );
			}
			return( TRUE );
		}
		
		return( FALSE );
	}
	
	private function convert_if_local( &$url )
	{
		$urlParts = (object) parse_url( $url );
		$dir = dirname( __FILE__ ); // assumes this file is in the base server path, might be a better way.
		if( $urlParts->host == $_SERVER['HTTP_HOST'] )
		{
			do {
				if( file_exists ($dir . $urlParts->path ) )
				{
					$url = $dir . $urlParts->path;
					break;
				}
			} while( $dir = realpath( "$dir/.." ) );
		}
	}
}}

?>