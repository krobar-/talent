<?php

if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { die(); }

if ( !class_exists( "TalentUpload" ) )
{

class TalentUpload
{
	public static $upload_folder = 'talent';
	public static $upload_path;
	public static $upload_url;
	private static $resize;

	public static function init()
	{
		Talent::registerDelegate( __CLASS__ );

		self::$resize = new Image_Resize;
		$upload_dir = wp_upload_dir();
		self::$upload_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . self::$upload_folder . DIRECTORY_SEPARATOR;
		self::$upload_url = $upload_dir['baseurl'] . '/' . self::$upload_folder . '/';
		self::$resize->cache_dir = self::$upload_path;
		self::$resize->prefix = "tcd_";

		self::activationAtInit();
	}

	public static function ajaxHandler()
	{
		if( 'headshot' == $_POST['tcd_call'] ) $file_field = "tcd_headshot";
		if( 'auxillary' == $_POST['tcd_call'] ) $file_field = "tcd_auxillary";

		$_success = FALSE;
		$_err_msg = '';

		if ( isset( $_FILES[$file_field] ) && !empty( $_FILES[$file_field] ) && $_FILES[$file_field]['error'] == UPLOAD_ERR_OK )
		{
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		 	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
		 	require_once(ABSPATH . "wp-admin" . '/includes/media.php');
		 	
		 	$upfile = $_FILES[$file_field];
			$upload_overrides = array( 'test_form' => FALSE );
			$tempfile = wp_handle_upload( $upfile, $upload_overrides );

			if ( $tempfile ) {
				$_success = TRUE;

				$img_full  = self::$resize->image( $tempfile['file'], Talent::getOption('img_full_width'), Talent::getOption('img_full_height'), Talent::getOption('img_full_crop'), 50 );
				$img_thumb = self::$resize->image( $tempfile['file'], Talent::getOption('img_thumb_width'), Talent::getOption('img_thumb_height'), Talent::getOption('img_thumb_crop'), 70 );
				if ( FALSE !== self::$resize->error ) {
					$_success = FALSE;
					$_err_msg = self::$resize->error;
				}
				unlink( $tempfile['file'] );
			}

			// save images to person (only works on edits since no id exists on creation)
			if( isset( $_POST['tcd_person_id'] ) && !empty( $_POST['tcd_person_id']  ) && $_success )
			{
				$person_id = filter_var( trim( $_POST['tcd_person_id'] ), FILTER_VALIDATE_INT );
				$_result = FALSE;

				if('tcd_headshot' == $file_field) 
				{
					$_person = TalentPerson::initWithID( $person_id );
					if( !empty( $_person->image_headshot ) ) TalentPerson::deleteProfileImage( $person_id );
					$_person->image_headshot = $img_full;
					$_person->image_headshot_thumb = $img_thumb;
					$_result = TalentPerson::addOrUpdate( $_person );
					
				}
				elseif ('tcd_auxillary' == $file_field)
				{
					$image = (object) array(
						'person_id'			=> $person_id,
						'image'				=> $img_full,
						'image_thumb'		=> $img_thumb,
						'image_title'		=> '',
						'image_description'	=> ''
					);
					$_result = TalentPerson::addOrUpdateImage( $image );
				}

				if( FALSE === $_result )
				{
					$_err_msg = 'Error saving record. ' . implode( ' ', TalentPerson::$error );
					$_success = FALSE;
				}
			}

			// additional profile images (does not require profile id, but it works better with it )

		}
		 
		 header( "Content-Type: application/json" );
		 echo( 
		 	json_encode( 
		 		array(
		 			'success' 		=> $_success,
		 			'upload_path'	=> self::$upload_path,
		 			'upload_url' 	=> self::$upload_url,
		 			'thumbnail' 	=> $img_thumb,
		 			'full_image' 	=> $img_full,
		 			'error' 		=> $_err_msg
		 		) 
		 	) 
		 );
	
	}

	public static function activationAtInit()
	{
		$_upload_dir = wp_upload_dir();
		$_upload_folder = $_upload_dir['basedir'] . DIRECTORY_SEPARATOR . self::$upload_folder;

		// make sure folder exists
		if( !file_exists( $_upload_folder ) )
		{
			// create with 777 permissions so that developer can overwrite
			// files created by web server user
			if( !mkdir( $_upload_folder, 0777 ) )
			{
				$err = "Unable to create upload directory: ". self::$upload_folder
					 . "<br/>\nYou can create this folder manually in Wordpress' upload folder.";
				$_noticeMessage = 
					( FALSE !== $_result ) ? "Created upload directory: &quot;". self::$upload_folder. "&quot;<br />" 
					: "Unable to create upload directory: ". self::$upload_folder
						. "<br/>\nYou can create this folder manually in Wordpress' upload folder.";
				$_noticeType = ( FALSE !== $_result ) ? 'notice' : 'error';
				TalentMain::addNotice( $_noticeMessage, $_noticeType );
				return( FALSE );
			}
		}
		return( TRUE );
	}

} // end class
} // end if/exists
?>