<?php
if (!class_exists("TalentDbTables")) {


class TalentDbTables
	extends TalentDb
{

	protected static 	$definitions = array(
		'person' 				=> "(
				id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
				name_first TINYTEXT DEFAULT '' NOT NULL,
				name_last TINYTEXT DEFAULT '' NOT NULL,
				name_middle TINYTEXT DEFAULT '' NOT NULL,
				birthdate DATETIME NULL DEFAULT NULL,
				age SMALLINT(5) UNSIGNED DEFAULT NULL,
				use_birthdate BOOL DEFAULT 0 NOT NULL,
				gender BOOL NULL DEFAULT NULL,
				guardian_last TINYTEXT NULL DEFAULT NULL,
				guardian_first TINYTEXT NULL DEFAULT NULL,
				guardian_relation TINYTEXT NULL DEFAULT NULL,
				agency_name TINYTEXT NULL DEFAULT NULL,
				agency_number TINYTEXT NULL DEFAULT NULL,
				phone_primary TINYTEXT NULL DEFAULT NULL,
				phone_alternate TINYTEXT NULL DEFAULT NULL,
				email_address TINYTEXT NULL DEFAULT NULL,
				address_street_1 TINYTEXT NULL DEFAULT NULL,
				address_street_2 TINYTEXT NULL DEFAULT NULL,
				address_city TINYTEXT NULL DEFAULT NULL,
				address_state TINYTEXT NULL DEFAULT NULL,
				address_zipcode TINYTEXT NULL DEFAULT NULL,
				address_country TINYTEXT NULL DEFAULT NULL,
				ethnicity SMALLINT(5) UNSIGNED DEFAULT NULL,
				color_eye SMALLINT(5) UNSIGNED DEFAULT NULL,
				color_hair SMALLINT(5) UNSIGNED DEFAULT NULL,
				color_hair_gray SMALLINT(5) UNSIGNED DEFAULT NULL,
				body_height TINYTEXT NULL DEFAULT NULL,
				body_weight TINYTEXT NULL DEFAULT NULL,
				size_shoe TINYTEXT NULL DEFAULT NULL,
				size_boot TINYTEXT NULL DEFAULT NULL,
				size_shirt TINYTEXT NULL DEFAULT NULL,
				size_shirt_neck TINYTEXT NULL DEFAULT NULL,
				size_shirt_sleeve TINYTEXT NULL DEFAULT NULL,
				size_dress TINYTEXT NULL DEFAULT NULL,
				size_jacket TINYTEXT NULL DEFAULT NULL,
				size_pant TINYTEXT NULL DEFAULT NULL,
				size_pant_length TINYTEXT NULL DEFAULT NULL,
				size_pant_waist TINYTEXT NULL DEFAULT NULL,
				size_hat TINYTEXT NULL DEFAULT NULL,
				skills TEXT(480) DEFAULT '' NOT NULL,
				skill_language TINYTEXT DEFAULT '' NOT NULL,
				skill_accent TINYTEXT DEFAULT '' NOT NULL,
				skill_sports TINYTEXT DEFAULT '' NOT NULL,
				skill_hobby TINYTEXT DEFAULT '' NOT NULL,
				union_sag BOOL DEFAULT 0 NOT NULL,
				union_sag_id TINYTEXT NULL DEFAULT NULL,
				union_aftra BOOL DEFAULT 0 NOT NULL,
				union_aea BOOL DEFAULT 0 NOT NULL,
				special_features TEXT(480) DEFAULT '' NOT NULL,
				experience TEXT(480) DEFAULT '' NOT NULL,
				notes TEXT(480) DEFAULT '' NOT NULL,
				image_headshot TINYTEXT DEFAULT '' NOT NULL,
				image_headshot_thumb TINYTEXT DEFAULT '' NOT NULL,
				PRIMARY KEY  ( id )
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8;",
				
		'images'			=> "(
				id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
				person_id MEDIUMINT(8) UNSIGNED NOT NULL,
				image TINYTEXT DEFAULT '' NOT NULL,
				image_thumb TINYTEXT DEFAULT '' NOT NULL,
				image_title TINYTEXT DEFAULT '' NOT NULL,
				image_description TINYTEXT DEFAULT '' NOT NULL,
				PRIMARY KEY  ( id )
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8;"
	);
	
	public static function init()
	{
		foreach( self::$definitions as $table => $sql )
		{
			parent::setTableName( $table );
			parent::createOrUpdateTable( $sql );
		}
	}
	
} // end class
} // end if/exists

?>