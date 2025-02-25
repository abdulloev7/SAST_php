<?php

class Input{
	const DEFAULT_CHARSET='windows-1251';
	

	public static function get_value($arr, $name, $type, $default = false) {
		if (!isset($arr[$name]))
			return $default;
		switch ($type) {
			case 'int':
				$return = intval($arr[$name]);
				break;
			case 'float':
				$return = floatval($arr[$name]);
				break;
			case 'str':
				$return = trim($arr[$name]);
				break;
			case 'array':
				if (is_array($arr[$name])) {
					$return = $arr[$name];
				}
				break;
			default:
				$return = $default;
				break;
		}
		return $return;
	}

	public static function get($name, $type){
		return self::get_value($_GET, $name, $type);
	}
	

	public static function getInt($name) {
		return self::get($name, 'int');
	}

	public static function getFloat($name) {
		return self::get($name, 'float');
	}

	public static function getStr($name) {
		return self::get($name, 'str');
	}


	public static function getBool($name) {
		
		$result = self::get($name, "str");
		
		if (strcasecmp($result, "true") === 0) {
			return true;
		}
		if (strcasecmp($result, "false") === 0) {
			return false;
		}
		
		$result = self::get($name, "int");
		return $result === 1;
	}
	
	public static function post($name, $type){
		return self::encodeInput(self::get_value($_POST, $name, $type));
	}
	
	public static function postInt($name)
	{
		return self::get_value($_POST, $name, 'int');
	}
	
	public static function postFloat($name)
	{
		return self::get_value($_POST, $name, 'float');
	}
	
	public static function postStr($name)
	{
		return self::post($name, 'str');
	}
	
	public static function postBool($name)
	{
		$result = self::postStr($name);
		if (strcasecmp($result, 'true') === 0)
		{
			return true;
		}
		
		if (strcasecmp($result, 'false') === 0)
		{
			return false;
		}
		
		$result = self::postInt($name);
		return $result === 1;
	}
	
	public static function postArray($name, $type){
		$return=array();
		$arr=self::get_value($_POST, $name, 'array');
		if(!is_array($arr)) return false;
		foreach($arr as $k=>$v){
			$return[$k]=self::encodeInput(self::get_value($arr, $k, $type));
		}
		return $return;
	}


	public static function postArrayOf($name, array $keys) {
		$return=array();
		$arr=self::get_value($_POST, $name, 'array');
		if(!is_array($arr)) return false;
		foreach($arr as $k=>$v){
			foreach ($keys as $key => $type) {
				if (!isset($v[$key])) {
					return false;
				} else {
					$return[$k][$key] = self::encodeInput(self::get_value($v, $key, $type));
				}
			}
		}
		return $return;
	}
	
	public static function encodeInput($value){
		if(isset($_SERVER['CONTENT_TYPE']) and preg_match('!charset=(?<charset>.*)!i', $_SERVER['CONTENT_TYPE'], $info)){
			if($info['charset']!=self::DEFAULT_CHARSET){
				$value=mb_convert_encoding($value, self::DEFAULT_CHARSET, $info['charset']);
			}
		}elseif(GGL::app()->request->isAjaxRequest and self::DEFAULT_CHARSET!='utf-8'){
			$value=mb_convert_encoding($value, self::DEFAULT_CHARSET, 'utf-8');
		}
		return $value;
	}
	
	public static function hasKey($array, $name, $allow_empty=true){
		if(!isset($array[$name])) return false;
		if(!$allow_empty and empty($array[$name])) return false;
		return true;
	}
	
	public static function hasGet($name, $allow_empty=true){
		return self::hasKey($_GET, $name, $allow_empty);
	}
	
	public static function hasPost($name, $allow_empty=true){
		return self::hasKey($_POST, $name, $allow_empty);
	}
	

	public static function getNotEmptyValue($arr, $name, $type, $default = false) {
		$return = self::get_value($arr, $name, $type, $default);
		if (empty($return)) {
			return $default;
		}
		return $return;
	}

	public static function detectUTF8($string){
		return preg_match('%(?:[\xC2-\xDF][\x80-\xA7\xA9-\xB7\xB9-\xBF]|\xE0[\xA0-\xA7\xA9-\xB7\xB9-\xBF][\x80-\xA7\xA9-\xB7\xB9-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xA7\xA9-\xB7\xB9-\xBF]{2}|\xED[\x80-\x9F][\x80-\xA7\xA9-\xB7\xB9-\xBF]|\xF0[\x90-\xBF][\x80-\xA7\xA9-\xB7\xB9-\xBF]{2}|[\xF1-\xF3][\x80-\xA7\xA9-\xB7\xB9-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xA7\xA9-\xB7\xB9-\xBF]{2})+%xs', $string);
	}
}

?>