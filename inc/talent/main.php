<?php

if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { die(); }

if ( !class_exists( "TalentMain" ) )
{

class TalentMain
{
	protected static $action;
	protected static $next_action;
	protected static $person;
	private static $notices = array();


	public function init()
	{
		Talent::registerDelegate( __CLASS__ );
		self::$action = ( 'cancel' == strtolower( $_POST['submit'] ) ) ? 'cancel' : trim( $_GET['action'] );
		if( function_exists('wp_enqueue_script') && function_exists('wp_register_script') )
		{
			if( FALSE !== stripos( $_GET['page'], 'talent') )
			{
				if( file_exists( Talent::$path->dirname . '/js/talent.js' ) )
				{
					wp_register_script( 'talent', Talent::$url->plugin . 'js/talent.js', array( 'jquery' ) );
					wp_enqueue_script( 'talent' );
					wp_localize_script( 'talent', 'TCD', array( 'ajaxUrl' => Talent::$url->ajax ) );
				}
			}
		}
		TalentUpload::init();
	}

	public static function initialize()
	{
		// all data handling should be done here
		switch ( trim( $_GET['page'] ) )
		{
			case 'talent':
				// people
				self::processAction();
				break;
			case 'talent-settings':
				// settings
				TalentSettings::processAction();
				break;
			default:
				return;
		}
	}

	public static function activationAtInit()
	{
		TalentDbTables::init();
	}
	
	public static function updateAtInit()
	{
		TalentDbTables::init();
	}

	public static function adminMenu()
	{
		self::addMenus();
	}
	
	public static function adminHead()
	{
		TalentDisplay::adminCSS();
	}
	
	
	public static function addMenus()
	{
		add_menu_page(
			'Talent | Casting Database',
			'Casting Database',
			Talent::userIsAuthorized( TRUE ),
			'talent',
			array( 'TalentMain', 'adminInterface' ),
			'',//CAS::$url->plugin . 'lib/images/menu.png',
			14 // after Media menu item
		);
		
		add_submenu_page(
			'talent',
			'Talent | People',
			'People',
			Talent::userIsAuthorized( TRUE ),
			'talent',
			array( 'TalentMain', 'adminInterface' )
		);
		
		add_submenu_page(
			'talent',
			'Talent | Settings',
			'Settings',
			Talent::userIsAuthorized( TRUE ),
			'talent-settings',
			array( 'TalentSettings', 'adminInterface' )
		);
	}
	
	protected static function objectByAction( &$object = NULL )
	{
		$_objPassed = ( NULL === $object ) ? FALSE : TRUE;
		$object = TalentPerson::skeleton( 'person' );
		
		switch ( self::$action )
		{
			case 'create':
			case 'update':
			case 'edit':
				self::$next_action = "update";
				if( 'create' !== self::$action )
				{
					$_id = ( isset( $_POST['tcd_person_id'] ) ) ? $_POST['tcd_person_id'] : ( ( isset( $_GET['person-id'] ) ) ? $_GET['person-id'] : '' );
					$_id = filter_var( trim( $_id ), FILTER_SANITIZE_NUMBER_INT );
					if( !empty( $_id ) ) $object = TalentPerson::initWithID( $_id );
				}
				break;
			case 'new-person':
				self::$next_action = "create";
				break;
			case 'delete':
				self::delete();
			case 'cancel':
			default:
				return( FALSE );
		}
		return( $_objPassed ? $object : TRUE );
	}
			
	protected static function delete()
	{	
		if( !Talent::verifyNonce( 'delete person' ) || !Talent::userIsAuthorized() ) return( FALSE );
		
		$_id = ( isset( $_POST['tcd_person_id'] ) ) ? $_POST['tcd_person_id'] : ( ( isset( $_GET['person-id'] ) ) ? $_GET['person-id'] : '' );
		$_id = filter_var( trim( $_id ), FILTER_SANITIZE_NUMBER_INT );
		
		
		$_result = TalentPerson::remove( $_id );
		list( $_message, $_type ) = ( FALSE !== $_result ) ? array('Record removed.', 'notice' ) : array( 'Failed to remove record.', 'error' );
		Talent::addNotice( $_message, $_type );
		return;
	}

	public static function adminInterface()
	{
		if( !Talent::userIsAuthorized() || $_GET['page'] !== 'talent' ) return;

		switch ( self::$action )
		{
			case 'new-person':
			case 'edit':
			case 'update':
			case 'create':
				TalentDisplay::editor(self::$person, self::$action, self::$next_action);
				break;
			case 'delete':
			case 'cancel':
			default:
				TalentDisplay::table();
		}
		
	}

	public static function processAction()
	{
		if( !Talent::userIsAuthorized() || $_GET['page'] !== 'talent' ) return;
		if( !self::objectByAction( $_person ) ) return;
		
		$_person_before = clone $_person;
		
		if( 'create' == self::$action || 'update' == self::$action )
		{
			
			$_update = FALSE;
			$_update_failed = FALSE;
			$_failures = '';
			foreach( $_person as $field => $value )
			{
				$_skip = array( 'id' );
				$_email = array( 'email_address' );
				$_mysql_esc = array(
					'name_first', 'name_last', 'name_middle', 'guardian_first', 'guardian_last', 
					'guardian_relation', 'agency_name', 'agency_number', 'phone_primary', 
					'phone_alternate', 'address_street_1', 'address_street_2', 'address_city', 
					'address_state', 'address_zipcode', 'address_country', 'body_height', 'body_weight', 
					'size_shoe', 'size_boot', 'size_shirt', 'size_shirt_neck', 'size_shirt_sleeve', 
					'size_dress', 'size_jacket', 'size_pant', 'size_pant_length', 'size_pant_waist', 
					'size_hat', 'skills', 'skill_language', 'skill_accent', 'skill_sports', 'skill_hobby', 
					'union_sag_id', 'special_features', 'experience', 'notes' 
				);
				$_url = array( 'image_headshot', 'image_headshot_thumb');
				$_bool = array( 'gender', 'union_sag', 'union_aftra', 'union_aea', 'use_birthdate' );
				$_int = array( 'ethnicity', 'color_eye', 'color_hair', 'color_hair_gray', 'age' );
				$_date = array( 'birthdate' );
				$_no_whitespace = array( 'name_first', 'name_last', 'name_middle', 'guardian_first', 'guardian_last' );

				if( in_array( $field, $_skip ) ) continue;
				$raw_value = trim( $_POST['tcd_' . $field] );
				$raw_value = stripslashes( $raw_value );
				if( !in_array( $field, $_url) ) $raw_value = htmlentities( $raw_value, ENT_QUOTES );
				$raw_value = strip_tags( $raw_value );

				$form_value = $value;

				$form_value = ( in_array( $field, $_email) ) ? 
					filter_var( $raw_value, FILTER_SANITIZE_EMAIL ) : 
					$form_value;

				$form_value = ( in_array( $field, $_mysql_esc) ) ? 
					mysql_real_escape_string( $raw_value ) : 
					$form_value;

				$form_value = ( in_array( $field, $_url) ) ? 
					filter_var( $raw_value, FILTER_SANITIZE_URL ) : 
					$form_value;

				$form_value = ( in_array( $field, $_bool) ) ? 
					filter_var( $raw_value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : 
					$form_value;

				$form_value = ( in_array( $field, $_int) ) ? 
					filter_var( $raw_value, FILTER_VALIDATE_INT ) : 
					$form_value;

				$form_value = ( in_array( $field, $_date) && !empty($raw_value) ) ? 
					TalentDb::formatDateTime($raw_value, 'Y-m-d') : 
					$form_value;

				$form_value = ( in_array( $field, $_no_whitespace) ) ? 
					str_replace(' ', '', $form_value ) : 
					$form_value;

				if( in_array( $field, $_date) && empty($raw_value) )
				{
					$form_value = NULL;
				}

				if( 'use_birthdate' === $field )
				{
					if( empty($_person->birthdate) && empty( $_POST['tcd_birthdate'] ) ) $form_value = 0;
				}
				
				if( $form_value !== $value )
				{
					$_person->$field = $form_value;
					$_update = TRUE;
				}
			}

			if( $_update )
			{
				$_result = TalentPerson::addOrUpdate( $_person );
				if( FALSE === $_result ) 
				{
					$_update_failed = TRUE;
					$_update_failure[] = 'profile';
					$_person = $_person_before;
					self::$next_action = self::$action;
				}
				else
				{
					$_noticeMessage = 'Profile ' . self::$action . 'd.<br />';
					$_noticeType = 'notice';
					$_person->id = ( 'create' == self::$action ) ? $_result : $_person->id;
				}
			}

			if( !empty( $_POST['tcd_image'] ) && !$_update_failed )
			{			
				foreach( $_POST['tcd_image'] as $index => $form_image )
				{
					$_image_updated = FALSE;

					if( isset($form_image['id']) )
					{
						$_image = TalentPerson::getImage( filter_var( $form_image['id'], FILTER_VALIDATE_INT ) );
					} else {
						$_image = TalentPerson::skeleton('image');
					}

					$_image_url = filter_var( $form_image['full'], FILTER_SANITIZE_URL );
					$_thumb_url = filter_var( $form_image['thumb'], FILTER_SANITIZE_URL );
					$_title = mysql_real_escape_string( $form_image['title'] );
					$_description = mysql_real_escape_string( $form_image['description'] );

					$_items = array( 'image' => '_image_url', 'image_thumb' => '_thumb_url', 'image_title' => '_title', 'image_description' => '_description');

					foreach ($_items as $_field => $_var )
					{
						if ( $$_var !== $_image->$_field )
						{
							$_image->$_field = $$_var;
							$_image_updated = TRUE;
						}
					}
	
					if( $_image_updated )
					{
						$_image->person_id = $_person->id;
						$_result = TalentPerson::addOrUpdateImage( $_image );
						if( FALSE === $_result ) 
						{
							$_update_failed = TRUE;
							$_update_failure[] = 'image ['. $_image_url .']';
						}
						$_image->id = ( !isset($_image->id) ) ? $_result : $_image->id;
					}
				}
			}

			if( $_update_failed )
			{
				$_err = implode( ' ', TalentPerson::$error );
				$_failures = implode(' and ', $_update_failure) . '.';
				$_noticeMessage = 'Failed to ' . self::$action . ' profile. ' . $_err . '<br />There were problems with ' . $_failures;
				$_noticeType = 'error';
			}

			self::addNotice( $_noticeMessage, $_noticeType );
		}

		self::$person = $_person;
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

}} // end if/class

?>