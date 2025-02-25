<?php

class Params {
	
	
	const TYPE_INT = 1;
	
	const TYPE_NULLINT = 2;
	
	const TYPE_UINT = 3;
	
	const TYPE_NULLUINT = 4;
	
	const TYPE_BOOL = 5;
	
	const TYPE_NULLBOOL = 6;
	
	const TYPE_FLOAT = 7;
	
	const TYPE_NULLFLOAT = 8;
	
	const TYPE_UFLOAT = 9;
	
	const TYPE_NULLUFLOAT = 10;
	
	const TYPE_STR = 11;
	
	const TYPE_NULLSTR = 12;
	
	const TYPE_DB_FIELD = 13;
	
	const TYPE_DATE = 14;
	
	const TYPE_DATETIME = 15;

	private static function isValidType($type) {
		
		$types = array(self::TYPE_INT, self::TYPE_NULLINT, self::TYPE_UINT, self::TYPE_NULLUINT, self::TYPE_BOOL, self::TYPE_NULLBOOL,
			self::TYPE_FLOAT, self::TYPE_NULLFLOAT, self::TYPE_UFLOAT, self::TYPE_NULLUFLOAT, self::TYPE_STR, self::TYPE_NULLSTR, self::TYPE_DB_FIELD,
			self::TYPE_DATE, self::TYPE_DATETIME);
		return array_search($type, $types, true) !== false;
	}
	

	public static function toInt($value) {
		
		if (is_int($value)) {
			
			return $value;
		}
		
		if (is_float($value)) {
			
			return intval(floor($value));
		}
		
		if (is_string($value) && preg_match('/^\-?\d+$/', trim($value)) === 1) {
			
			return intval(trim($value));
		}
		
		SystemLog::log('exception', Debuging::getStackCall());
		SystemLog::log('exception_ext', debug_backtrace());
		throw new Exception('Invalid parameters in ' . Debuging::getFromCall(2));
	}
	

	public static function toNullInt($value) {
		
		if (is_null($value)) {
			
			return null;
		}
		
		return self::toInt($value);
	}


	public static function toUint($value) {
		
		$result = self::toInt($value);
		if ($result >= 0) {
			
			return $result;
		}
		
		SystemLog::log('exception', Debuging::getStackCall());
		SystemLog::log('exception_ext', debug_backtrace());
		throw new Exception('Invalid parameters in ' . Debuging::getFromCall(2));
	}

	public static function toNullUint($value) {
		
		if (is_null($value)) {
			
			return null;
		}
		
		return self::toUint($value);
	}
	

	public static function toBool($value) {
		
		if (is_bool($value)) {
			
			return $value;
		}
		
		if (is_int($value) && ($value === 1 || $value === 0)) {
			
			return $value === 1;
		}
		
		if (is_string($value)) {
			
			if ($value == '1' || $value == 'true') {
				
				return true;
			}
			
			if ($value == '0' || $value == 'false') {
				
				return false;
			}
		}
		
		SystemLog::log('exception', Debuging::getStackCall());
		SystemLog::log('exception_ext', debug_backtrace());
		throw new Exception('Invalid parameters in ' . Debuging::getFromCall(2));
	}
	
	public static function toNullBool($value) {
		
		if (is_null($value)) {
			
			return null;
		}
		
		return self::toBool($value);
	}

	public static function toFloat($value) {
		
		if (is_float($value)) {
			
			return $value;
		}
		
		if (is_int($value)) {
			
			return floatval($value);
		}
		
		if (is_string($value) && preg_match('/^\-?\d+(\.\d+)?$/', trim($value)) === 1) {
			
			return floatval(trim($value));
		}
		
		SystemLog::log('exception', Debuging::getStackCall());
		SystemLog::log('exception_ext', debug_backtrace());
		throw new Exception('Invalid parameters in ' . Debuging::getFromCall(2));
	}
	

	public static function toNullFloat($value) {
		
		if (is_null($value)) {
			
			return null;
		}
		
		return self::toFloat($value);
	}
	

	public static function toUfloat($value) {
		
		$result = self::toFloat($value);
		if ($result >= 0) {
			
			return $result;
		}
		
		SystemLog::log('exception', Debuging::getStackCall());
		SystemLog::log('exception_ext', debug_backtrace());
		throw new Exception('Invalid parameters in ' . Debuging::getFromCall(2));
	}
	
	public static function toNullUfloat($value) {
		
		if (is_null($value)) {
			
			return null;
		}
		
		return self::toUfloat($value);
	}
	

	public static function toStr($value) {
		
		if (is_string($value)) {
			
			return $value;
		}
		
		if (is_int($value) || is_float($value)) {
			
			return strval($value);
		}
		
		if (is_bool($value)) {
			
			return $value ? '1' : '0';
		}
		
		SystemLog::log('exception', Debuging::getStackCall());
		SystemLog::log('exception_ext', debug_backtrace());
		throw new Exception('Invalid parameters in ' . Debuging::getFromCall(2));
	}
	

	public static function toNullStr($value) {
		
		if (is_null($value)) {
			
			return null;
		}
		
		return self::toStr($value);
	}
	

	public static function toType($value, $type) {
		
		switch ($type) {

			case self::TYPE_INT:
				return self::toInt($value);
			case self::TYPE_NULLINT:
				return self::toNullInt($value);
			case self::TYPE_UINT:
				return self::toUint($value);
			case self::TYPE_NULLUINT:
				return self::toNullUint($value);
			case self::TYPE_BOOL:
				return self::toBool($value);
			case self::TYPE_NULLBOOL:
				return self::toNullBool($value);
			case self::TYPE_FLOAT:
				return self::toFloat($value);
			case self::TYPE_NULLFLOAT:
				return self::toNullFloat($value);
			case self::TYPE_UFLOAT:
				return self::toUfloat($value);
			case self::TYPE_NULLUFLOAT:
				return self::toNullUfloat($value);
			case self::TYPE_STR:
				return self::toStr($value);
			case self::TYPE_NULLSTR:
				return self::toNullStr($value);
			case self::TYPE_DB_FIELD:
				return self::toFieldDB($value);
			case self::TYPE_DATE:
				return self::toDate($value);
			case self::TYPE_DATETIME:
				return self::toDatetime($value);
			default:
				SystemLog::log('exception', Debuging::getStackCall());
				SystemLog::log('exception_ext', debug_backtrace());
				throw new Exception('Type out of range');
		}
	}
	

	public static function toArray(array $param, $valueType = self::TYPE_STR, $keyType = self::TYPE_INT) {

		if (!self::isValidType($keyType) || !self::isValidType($valueType)) {
			
			throw new Exception('Invalid method params');
		}
		
		$result = array();

		foreach ($param as $key => $value) {

			$resultKey = self::toType($key, $keyType);
			$resultValue = self::toType($value, $valueType);

			$result[$resultKey] = $resultValue;
		}

		return $result;
	}
	

	public static function checkInEnum($value, array $enum, $valueType = self::TYPE_UINT) {
		
		$value = self::toType($value, $valueType);
		
		foreach ($enum as $item) {
			$item = self::toType($item, $valueType);
			if ($value === $item) {
				return;
			}
		}
		
		SystemLog::log('exception', Debuging::getStackCall());
		SystemLog::log('exception_ext', debug_backtrace());
		throw new Exception('Invalid parameters in ' . Debuging::getFromCall(2));
	}


	public static function toUintDefault($value, $default) {
		if ($value === false) {
			$value = $default;
		}
		return self::toUint($value);
	}
	

	public static function toFieldDB($field){
		$field = self::toStr($field);
		
		if($field != "" && preg_replace("/[^a-z0-9_\.*]/i", "", $field) == $field){
			return $field;
		}
		
		SystemLog::log('exception', Debuging::getStackCall());
		SystemLog::log('exception_ext', debug_backtrace());
		throw new Exception('Invalid parameters in ' . Debuging::getFromCall(2));
	}

	public static function toFieldsDB(array $fields){
		$fields = self::toArray($fields);
		
		$result = array();
		foreach($fields as $field){
			$result[] = self::toFieldDB($field);
		}
		return $result;
	}
	

	public static function toDate($date, $format = "Y-m-d"){
		$date = self::toStr($date);
		
		if($date != "" && preg_replace("/[^0-9 :\-]/i", "", $date) == $date){
			if(!empty($date) && date($format, strtotime($date)) == $date){
				return $date;
			}
		}
		
		SystemLog::log('exception', Debuging::getStackCall());
		SystemLog::log('exception_ext', debug_backtrace());
		throw new Exception('Invalid parameters in ' . Debuging::getFromCall(2));
	}
	

	public static function toDatetime($date){
		return self::toDate($date, "Y-m-d H:i:s");
	}

}

?>