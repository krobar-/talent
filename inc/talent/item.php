<?php
if (!class_exists("TalentItem")) {


abstract class TalentItem
	extends TalentDb
{
	private static $itemCache; // in case the same item gets retrieved multiple times
	
	abstract static public function remove($id);

	public static function init( $table = NULL, $ID = NULL )
	{
		parent::setTableName( $table );
		$ID = ( NULL !== $ID ) ? $ID : FALSE;
		if( FALSE !== $ID && empty( self::$itemCache[ $table ][ $ID ] ) )
		{
			$newItem = parent::getRowByID( $ID );
			self::$itemCache[ $table ][ $ID ] = $newItem;
			return( $newItem );
		}
		elseif( FALSE !== $ID )
		{
			return( self::$itemCache[ $table ][ $ID ] );	
		}
		
		return( NULL );
	}

	public static function initWithTableAndID( $table, $ID )
	{
		return( self::init( $table, $ID ) );
	}
	
	public static function initWithAlternateField( $table, $field, array $value )
	{
		parent::setTableName( $table );
		$newItem = parent::getRow( $field, $value );
		self::$itemCache[ $table ][ $newItem->id ] = $newItem;
		return( $newItem );
	}

	public static function initAllWithAlternateField( $table, $field, array $value )
	{
		parent::setTableName( $table );
		$items = parent::getRows( $field, $value );
		return( $items );
	}
	
	public static function initWithAllRelational( $relational_table, $known_field, $known_id, $item_table, $item_field )
	{
		parent::setTableName( $relational_table );
		
		$_relations = parent::getRows( $known_field, array( '%d', $known_id ) );
		$_items = array();
		
		if( isset( $_relations ) && NULL !== $_relations )
		{
			foreach( $_relations as $_relation )
			{
				if( isset( self::$itemCache[ $item_table ][ $_relation->$item_field ] ) )
				{
					$_items[] = self::$itemCache[ $item_table ][ $_relation->$item_field ];
				}
				else
				{
					$_items[] = self::init( $item_table, $_relation->$item_field );
				}
			}
		}
		else
		{
			return( NULL );
		}
		return( $_items );
	}
	
	public static function initWithAll( $table, $max = 0, $offset = 1  )
	{
		parent::setTableName( $table );
		return( parent::getAllRecords( $max, $offset ) );
	}
	
	public static function addOrUpdateWithTableAndObject( $table = NULL, $object = NULL )
	{
		if( empty( $table ) || !is_object( $object ) ) return( FALSE );
		parent::setTableName( $table );
		if( isset( $object->id ) )
		{
			$object->db_where = array( 'id' => $object->id );
			$object->db_where_format = array('%s');
		}
		return( parent::addOrUpdateRow( $object ) );
	}
	
	public static function removeWithTableAndID( $table = NULL, $ID = NULL )
	{
		if( empty( $table ) || empty( $ID ) || !is_numeric( $ID ) ) return( FALSE );
		parent::setTableName( $table );
		return( parent::removeRow( $field = 'id', array( '%d', $ID ) ) );
	
	}
	
	public static function addRowToRelational()
	{
		$_object = new stdClass;
		$_numArgs = func_num_args();
		$_args = func_get_args();
		$_fields = array();
		
		if( $_numArgs < 5 ) return( FALSE ); // not enough args
		
		parent::setTableName( array_shift( $_args ) );
		
		if( ( count( $_args ) % 2 ) > 0 ) return( FALSE ); // odd number of args
		
		$_num = count( $_args )/2;
		for ($i = 0; $i < $_num; $i++)
		{
			$_ofield = array_shift( $_args );
			$_object->$_ofield = array_shift( $_args );
		}
		
		$_sql = "SELECT * FROM ___TABLENAME___ WHERE ";
		foreach( $_object as $_field => $_value)
		{
			$_fields[] = $_field . ' = ' . $_value;
		}
		$_sql .= implode( ' AND ', $_fields );
		$_existing = parent::sqlResult( $_sql );
		
		if( empty( $_existing ) )
		{
			return( parent::addOrUpdateRow( $_object ) );
		}
		return( FALSE );
	}
	
	public static function removeRowsFromRelational()
	{
		$_object = new stdClass;
		$_numArgs = func_num_args();
		$_args = func_get_args();
		$_fields = array();
		
		if( $_numArgs < 3 ) return( FALSE ); // not enough args
		
		parent::setTableName( array_shift( $_args ) );
		
		if( ( count( $_args ) % 2 ) > 0 ) return( FALSE ); // odd number of args
		
		$_num = count( $_args )/2;
		for ($i = 0; $i < $_num; $i++)
		{
			$_ofield = array_shift( $_args );
			$_object->$_ofield = array_shift( $_args );
		}
		
		$_sql = "SELECT * FROM ___TABLENAME___ WHERE ";
		foreach( $_object as $_field => $_value)
		{
			$_fields[] = $_field . ' = ' . $_value;
		}
		$_sql .= implode( ' AND ', $_fields );
		$_existing = parent::sqlResult( $_sql );
		
		if( !empty( $_existing ) )
		{
			$_result = array();
			foreach( $_existing as $_row )
			{
				$_result[] = parent::removeRow( $field = 'id', array( '%d', $_row->id ) );
			}
			if( !in_array( FALSE, $_result, TRUE ) ) return( TRUE );
		}
		
		return( FALSE );
	}
			
} // end class
} // end if/exists

?>