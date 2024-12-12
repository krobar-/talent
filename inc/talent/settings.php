<?php


if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { die(); }

if ( !class_exists( "TalentSettings" ) )
{

class TalentSettings
{
	protected static $action;
	protected static $next_action;
	protected static $person;

	public static function processAction()
	{
		if( !Talent::userIsAuthorized() || $_GET['page'] !== 'talent-settings' ) return;
	}

	public static function adminInterface()
	{
		if( !Talent::userIsAuthorized() || $_GET['page'] !== 'talent-settings' ) return;
	}

}} // end if/class
?>