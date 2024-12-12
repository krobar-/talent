<?php
if (!class_exists("TalentPerson")) {


class TalentPerson
	extends TalentItem
{
	public static $eye_colors = array(
		1 	=> 'Amber',
		2	=> 'Black',
		3 	=> 'Blue',
		4 	=> 'Brown',
		5 	=> 'Gray',
		6 	=> 'Green',
		7 	=> 'Hazel',
		8 	=> 'Pink/Red'
	);

	public static $hair_colors = array(
		1 	=> 'Auburn',
		2 	=> 'Black',
		3 	=> 'Blonde',
		4 	=> 'Brown',
		5	=> 'Chestnut',
		6 	=> 'Gray',
		7 	=> 'Red',
		8 	=> 'White'
	);

	public static $races = array(
		1 	=> 'White',
		2 	=> 'African American',
		3 	=> 'Hispanic',
		4 	=> 'Asian',
		5 	=> 'Asian Pacific',
		6 	=> 'Alaskan Native',
		7 	=> 'American Indian',
		8 	=> 'Native American'
	);
	
	public static function initWithID( $person_id )
	{
		if( is_array( $person_id ) )
		{
			$_personArray = array();
			foreach( $person_id as $_id )
			{
				$_personArray[] = parent::init( 'person', $_id );
			}
			return( $_personArray );
		}
		
		return( parent::init( 'person', $person_id ) );
	}

	public static function initAllLinkedImages( $person_id )
	{
		return( parent::initAllWithAlternateField( 'images', 'person_id', array( '%d', $person_id ) ) );
	}
	
	public static function initAllByPage( $maxItems = 0, $offset = 1 )
	{
		return( parent::initWithAll( 'person', $maxItems, $offset ) );
	}
	
	public static function initAll()
	{
		return( parent::initWithAll( 'person' ) );
	}

	public static function getImage( $image_id )
	{
		return( parent::initWithTableAndID( 'images', $image_id ) );
	}
	
	public static function skeleton( $type )
	{
		if( 'person' == $type )
		{
			$obj = (object) array(
				'name_first' 			=> '',
				'name_last' 			=> '',
				'name_middle' 			=> '',
				'birthdate' 			=> NULL,
				'age' 					=> NULL,
				'use_birthdate' 		=> 0,
				'gender' 				=> NULL,
				'guardian_last'			=> NULL,
				'guardian_first'		=> NULL,
				'guardian_relation'		=> NULL,
				'agency_name' 			=> NULL,
				'agency_number' 		=> NULL,
				'phone_primary' 		=> NULL,
				'phone_alternate' 		=> NULL,
				'email_address' 		=> NULL,
				'address_street_1' 		=> NULL,
				'address_street_2' 		=> NULL,
				'address_city' 			=> NULL,
				'address_state' 		=> NULL,
				'address_zipcode' 		=> NULL,
				'address_country' 		=> NULL,
				'ethnicity' 			=> NULL,
				'color_eye' 			=> NULL,
				'color_hair' 			=> NULL,
				'color_hair_gray'		=> NULL,
				'body_height' 			=> NULL,
				'body_weight' 			=> NULL,
				'size_shoe' 			=> NULL,
				'size_boot' 			=> NULL,
				'size_shirt' 			=> NULL,
				'size_shirt_neck' 		=> NULL,
				'size_shirt_sleeve'		=> NULL,
				'size_dress'			=> NULL,
				'size_jacket'			=> NULL,
				'size_pant'				=> NULL,
				'size_pant_length'		=> NULL,
				'size_pant_waist'		=> NULL,
				'skills'				=> '',
				'skill_language'		=> '',
				'skill_accent'			=> '',
				'skill_sports'			=> '',
				'skill_hobby'			=> '',
				'union_sag'				=> 0,
				'union_sag_id'			=> NULL,
				'union_aftra'			=> 0,
				'union_aea'				=> 0,
				'special_features'		=> '',
				'experience'			=> '',
				'notes'					=> '',
				'image_headshot'		=> '',
				'image_headshot_thumb'	=> ''
			);
		}
		if( 'image' == $type )
		{
			$obj = (object) array(
				'image'				=> '',
				'image_thumb'		=> '',
				'image_title'		=> '',
				'image_description'	=> ''
			);
		}
		return( $obj );
	}
	
	public static function addOrUpdate( $person )
	{
		if( !is_object( $person ) )
		{
			self::$error[] = 'Profile data is not an object.';
			return( FALSE );
		}
		
		return( self::addOrUpdateWithTableAndObject( 'person', $person ) );
	}

	public static function addOrUpdateImage( $image )
	{
		if( !is_object( $image ) )
		{
			self::$error[] = 'Image data is not an object.';
			return( FALSE );
		}

		return( self::addOrUpdateWithTableAndObject( 'images', $image ) );
	}
	
	public static function remove( $person_id )
	{
		if( !current_user_can( 'edit_published_pages' ) || empty( $person_id ) || !is_numeric( $person_id ) ) return( FALSE );
		// delete profile image
		self::deleteProfileImage( $person_id );
		// delete image files
		$images = self::initAllLinkedImages( $person_id );
		foreach ($images as $image) {
			self::removeLinkedImage( $image );
		}
		// remove all associated relational images
		parent::removeRowsFromRelational( 'images', 'person_id', $person_id );
		return( parent::removeWithTableAndID( 'person', $person_id ) );
	}

	public static function removeLinkedImage( $input )
	{
		if( !current_user_can( 'edit_published_pages' ) || empty( $input ) ) return( FALSE );

		if( is_object( $input ) )
		{
			$image = $input;
		}
		else
		{
			if( !is_numeric( $input ) ) return( FALSE );
			$image = parent::initWithTableAndID( 'images', $input );
			if( FALSE === $image ) return( FALSE );
		}

		// unlink requires system path, not url.
		if( !empty( $image->image ) && file_exists( TalentUpload::$upload_path . $image->image ) ) unlink( TalentUpload::$upload_path . $image->image );
		if( !empty( $image->image_thumb ) && file_exists( TalentUpload::$upload_path . $image->image_thumb ) ) unlink( TalentUpload::$upload_path . $image->image_thumb );
		return( parent::removeWithTableAndID( 'images', $image->id ) );
	}

	public static function deleteProfileImage( $person_id )
	{
		if( !current_user_can( 'edit_published_pages' ) || empty( $person_id ) || !is_numeric( $person_id ) ) return( FALSE );
		// delete profile image
		$person = self::initWithID( $person_id );
		
		if( !empty( $person->image_headshot ) && file_exists( TalentUpload::$upload_path . $person->image_headshot ) ) unlink( TalentUpload::$upload_path . $person->image_headshot );
		if( !empty( $person->image_headshot_thumb ) && file_exists( TalentUpload::$upload_path . $person->image_headshot_thumb ) ) unlink( TalentUpload::$upload_path . $person->image_headshot_thumb );
		
	}

	public static function parseAge( $birthdate, $part = NULL )
	{
		$age = self::getAge( $birthdate );
		$age_obj = (object) array(
			'birthdate'				=> $birthdate,
			'age'					=> $age,
			'description' 			=> NULL,
			'permission_required'	=> ( $age < 18 ) ? TRUE : FALSE
		);

		//$age_obj->permission_required = ($age < 18) ?: FALSE;

		switch (TRUE) {
			case ($age < 2):
				// baby 1-2
				$age_obj->description = 'baby';
				break;
			case ($age > 2 && $age < 5):
				// toddler 3-4
				$age_obj->description = 'toddler';
				break;
			case ($age > 4 && $age < 10):
				// child 5-9
				$age_obj->description = 'child';
				break;
			case ($age > 9 && $age < 13):
				// pre-teen 10-12
				$age_obj->description = 'pre-teen';
				break;
			case ($age > 12 && $age < 20):
				// teen 13-19
				$age_obj->description = 'teen';
				break;
			case ($age > 19 && $age < 26):
				// young adult 20-25
				$age_obj->description = 'young adult';
				break;
			case ($age > 25 && $age < 60):
				// adult 26-59
				$age_obj->description = 'adult';
				break;
			case ($age > 59):
				// senior 60+
				$age_obj->description = 'senior';
				break;
			default:
				// should never get here
				$age_obj->description = 'immortal';
				break;
		}

		return( ( $part !== NULL && isset( $age_obj->$part ) ) ? $age_obj->$part : $age_obj );
	}

	public static function getAge( $birthdate )
	{
		$then = date( 'Ymd', strtotime( $birthdate ));
		$diff = date( 'Ymd' ) - $then;
		return( substr( $diff, 0, -4 ) );
	}

} // end class
} // end if/exists

?>