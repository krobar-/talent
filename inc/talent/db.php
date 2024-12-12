<?php
if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { die(); }

if ( !class_exists( "TalentDb" ) )
{

abstract class TalentDb
{
	const PREFIX = "tcd_";
	protected static $_tableName;
	public static $error = array();

	public static function setTableName( $name )
	{
		self::prefixTableName( $name );
		self::$_tableName = $name;
	}

	protected static function prefixTableName( &$tableName = FALSE )
	{
		global $wpdb;
		if ( FALSE !== $tableName ) {
			$tableName = $wpdb->prefix . self::PREFIX . $tableName;
		}
		return( $tableName );
	}
	
	public static function getRowByID( $ID )
	{
		return( self::getRow( 'id', array( '%d', $ID) ) );
	}
	
	public static function getRowByPostID( $post_id )
	{
		return( self::getRow( 'post_id', array( '%d', $post_id) ) );
	}
	
	public static function createOrUpdateTable( $sql )
	{
		global $wpdb;
		$_table = self::$_tableName;
		
		if ( !empty($_table) )
		{
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			
			//$sql = "CREATE TABLE IF NOT EXISTS " . $_table .' '. $sql;
			$sql = 'CREATE TABLE ' . $_table .' '. $sql;
			dbDelta( $sql );
			
			return( TRUE );
		}
		return( FALSE );
	}
	
	public static function dropTable()
	{
		global $wpdb;
		$_table = self::$_tableName;
		if ( !empty( $_table ) )
		{
			$wpdb->query( "DROP TABLE $_table" );
		
		}
	}
	
	public static function sqlQuery( $sql )
	{
		global $wpdb;
		$_table = self::$_tableName;
		$_result = FALSE;
	
		if( empty( $sql ) ) return( FALSE );
		
		// Placeholders
		$sql = str_replace( '___TABLENAME___', $_table, $sql );
		
		$_result = $wpdb->query( $sql );
		
		return( $_result );
	}
	
	public static function sqlResult( $sql )
	{
		global $wpdb;
		$_table = self::$_tableName;
		$_result = FALSE;
	
		if( empty( $sql ) ) return( FALSE );
		
		// Placeholders
		$sql = str_replace( '___TABLENAME___', $_table, $sql );
	
		$_result = $wpdb->get_results( $sql );
		
		return( $_result );
	}
	
	public static function getAllRecords( $maxItems = 0, $offset = 1 )
	{
		global $wpdb;
		$_table = self::$_tableName;
		$_result = FALSE;
		
		$_sql = "SELECT * FROM $_table";
		
		$_totalNum = $wpdb->query( $_sql );
		
		//Page Number
		if( empty( $offset ) || !is_numeric( $offset ) || $offset <= 0 )
		{
			$offset = 1;
		}
		//How many pages do we have in total?
		$_totalParts = ( $maxItems > 0 ) ? ceil( $_totalNum / $maxItems ) : 1;
		
		//adjust the query to take pagination into account
		if( !empty( $offset ) && !empty( $maxItems ) && $maxItems > 0 )
		{
			$offset = ( $offset - 1 ) * $maxItems;
			$_sql .= ' LIMIT ' . (int) $offset . ',' . (int) $maxItems;
		}
		
		$_records = $wpdb->get_results( $_sql );
		
		if( !empty( $_records ) )
		{
			$_result = (object) array( 
				'records'		=> $_records,
				'num_records'	=> $_totalNum,
				'num_pages'		=> $_totalParts
			);
		} 
		
		
		return( $_result );
		
	}
	
	public static function addOrUpdateRow( stdClass $input = NULL )
	{
		global $wpdb;
		$_table = self::$_tableName;
		$_result = FALSE;

		$wpdb->show_errors();
		
		if(is_object( $input ) && !empty( $_table ) )
		{
			if( isset( $input->id ) )
			{
				// update
				$where	= $input->db_where;
				$format	= $input->db_where_format;
				unset( $input->db_where );
				unset( $input->db_where_format );
				if( is_array( $where ) && !empty( $format ) )
				{
					$_result = $wpdb->update( $_table, get_object_vars( $input ), $where, NULL, $format );
				}
			}
			else
			{
				// insert
				$_result = $wpdb->insert( $_table, get_object_vars( $input ) );
				if( FALSE !== $_result ) $_result = $wpdb->insert_id;
			}
			
		}
		return($_result);
	}
	
	public static function getRow( $field = NULL, array $value = NULL )
	{
		global $wpdb;
		$_table = self::$_tableName;
		$_result = FALSE;
		
		if( !empty( $_table ) && is_array( $value ) )
		{
			sanitize_key( &$field );
			
			$sql = $wpdb->prepare("
				SELECT * FROM $_table
				WHERE $field = {$value[0]}
				", 
				sanitize_text_field( $value[1] )
			);
			$_result = $wpdb->get_row( $sql, OBJECT ); // use ARRAY_A or OBJECT
		}
		return( $_result );
	}
	
	public static function getRows( $field = NULL, array $value = NULL )
	{
		global $wpdb;
		$_table = self::$_tableName;
		$_result = FALSE;
		
		if( !empty( $_table ) && is_array( $value ) )
		{
			sanitize_key( &$field );
			$sql = $wpdb->prepare("
				SELECT * FROM $_table
				WHERE $field = {$value[0]}
				", 
				sanitize_text_field( $value[1] )
			);		
			$_result = $wpdb->get_results( $sql, OBJECT ); // use ARRAY_A, OBJECT, or OBJECT_K
		}
		return( $_result );
	}
	
	public static function removeRow( $field = NULL, array $value = NULL )
	{
		global $wpdb;
		$_table = self::$_tableName;
		$_result = FALSE;
		
		if( !empty( $_table ) && is_array( $value ) )
		{
			sanitize_key( &$field );
			$sql = $wpdb->prepare("
				DELETE FROM $_table
				WHERE $field = {$value[0]}
				",
				sanitize_text_field( $value[1] ) 
			);
			$_result = $wpdb->query( $sql );
		}
		return( $_result );
	}
	
	public static function recordExists( $field, $value )
	{
		$_format = self::getFormatOfVariable( $value );
		$_result = self::getRows( $field, array( $_format, $value ) );
		return( count( $_result ) > 0 );
	}
	
	public static function generateTableSQL( $fields )
	{
		$_table_pre = "( id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT, post_id BIGINT(20) UNSIGNED NOT NULL, ";
		$_table_post = 	"PRIMARY KEY  (id), UNIQUE KEY post_id (post_id) ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
		return( $_table_pre . $fields . $_table_post );
	}
	
	public function getFormatOfVariable( $value )
	{
		$_format = NULL;
		if( is_numeric( $value ) )
		{
			if( (string)(int)$var == $var )
			{
				$_format = '%d'; // integer
			}
			else
			{
				$_format = '%f'; // float
			} 
		}
		elseif ( is_bool($var) || 'true' ==  strtolower( $var ) || 'false' == strtolower( $var ) )
		{
			$_format = '%d'; // boolean
		}
		elseif( is_string( $value ) )
		{
			$_format = '%s'; // string
		}
		return( $_format );
	}
	
	public static function formatDateTime( $dateTime, $format = 'Y-m-d H:i:s' )
	{
		return( date( "Y-m-d H:i:s", strtotime( $dateTime ) ) );
	}
	
	public static function validateDateTime($dateTime, $format = 'Y-m-d H:i:s')
	{
		$d = DateTime::createFromFormat($format, $dateTime);
		return $d && $d->format($format) == $dateTime;
	}

} // end class
} // end if/exists
?>