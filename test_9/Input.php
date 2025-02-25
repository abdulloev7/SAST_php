<?php

class Input {
	const DEFAULT_CHARSET = 'windows-1251';

	public function get_value($arr, $name, $type, $default = false) {

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

	public function get($name, $type) {
		return $this->get_value($_GET, $name, $type);
	}

	public function getInt($name) {
		return $this->get($name, 'int');
	}

	public function getFloat($name) {
		return $this->get($name, 'float');
	}

	public function getStr($name) {
		return $this->get($name, 'str');
	}

	public function getBool($name) {

		$result = $this->get($name, "str");

		if (strcasecmp($result, "true") === 0) {
			return true;
		}
		if (strcasecmp($result, "false") === 0) {
			return false;
		}

		$result = $this->get($name, "int");
		return $result === 1;
	}

	public function post($name, $type) {
		return $this->encodeInput($this->get_value($_POST, $name, $type));
	}

	public function postInt($name) {
		return $this->get_value($_POST, $name, 'int');
	}

	public function postFloat($name) {
		return $this->get_value($_POST, $name, 'float');
	}

	public function postStr($name) {
		return $this->post($name, 'str');
	}

	public function postBool($name) {
		$result = $this->postStr($name);
		if (strcasecmp($result, 'true') === 0) {
			return true;
		}

		if (strcasecmp($result, 'false') === 0) {
			return false;
		}

		$result = $this->postInt($name);
		return $result === 1;
	}

	public function postArray($name, $type) {
		$return = array();
		$arr = $this->get_value($_POST, $name, 'array');
		if (!is_array($arr)) return false;
		foreach ($arr as $k => $v) {
			$return[$k] = $this->encodeInput($this->get_value($arr, $k, $type));
		}
		return $return;
	}

	public function postArrayOf($name, array $keys) {
		$return = array();
		$arr = $this->get_value($_POST, $name, 'array');
		if (!is_array($arr)) return false;
		foreach ($arr as $k => $v) {
			foreach ($keys as $key => $type) {
				if (!isset($v[$key])) {
					return false;
				} else {
					$return[$k][$key] = $this->encodeInput($this->get_value($v, $key, $type));
				}
			}
		}
		return $return;
	}

	public function encodeInput($value) {
		if (isset($_SERVER['CONTENT_TYPE']) and preg_match('!charset=(?<charset>.*)!i', $_SERVER['CONTENT_TYPE'], $info)) {
			if ($info['charset'] != $this->DEFAULT_CHARSET) {
				$value = mb_convert_encoding($value, $this->DEFAULT_CHARSET, $info['charset']);
			}
		} elseif (GGL::app()->request->isAjaxRequest and $this->DEFAULT_CHARSET != 'utf-8') {
			$value = mb_convert_encoding($value, $this->DEFAULT_CHARSET, 'utf-8');
		}
		return $value;
	}

	public function hasKey($array, $name, $allow_empty = true) {
		if (!isset($array[$name])) return false;
		if (!$allow_empty and empty($array[$name])) return false;
		return true;
	}

	public function hasGet($name, $allow_empty = true) {
		return $this->hasKey($_GET, $name, $allow_empty);
	}

	public function hasPost($name, $allow_empty = true) {
		return $this->hasKey($_POST, $name, $allow_empty);
	}

	public function getNotEmptyValue($arr, $name, $type, $default = false) {
		$return = $this->get_value($arr, $name, $type, $default);
		if (empty($return)) {
			return $default;
		}
		return $return;
	}

	public function detectUTF8($string) {
		return preg_match('%(?:[\xC2-\xDF][\x80-\xA7\xA9-\xB7\xB9-\xBF]|\xE0[\xA0-\xA7\xA9-\xB7\xB9-\xBF][\x80-\xA7\xA9-\xB7\xB9-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xA7\xA9-\xB7\xB9-\xBF]{2}|\xED[\x80-\x9F][\x80-\xA7\xA9-\xB7\xB9-\xBF]|\xF0[\x90-\xBF][\x80-\xA7\xA9-\xB7\xB9-\xBF]{2}|[\xF1-\xF3][\x80-\xA7\xA9-\xB7\xB9-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xA7\xA9-\xB7\xB9-\xBF]{2})+%xs', $string);
	}
}

function run() {
	$input = new Input();

	$sql = $input->getFloat("a", "int");
	mysqli_query($conn, $sql); 

	$sql1 = $input->getFloat("a", "int");
	mysqli_query($conn, $sql1); 

	$sql2 = $input->getInt("a", "int");
	mysqli_query($conn, $sql2); 

	$sql3 = $input->getStr("a", "int");
	mysqli_query($conn, $sql3); 

	$sql4 = $input->postInt("a");
	mysqli_query($conn, $sql4); 

	$sql5 = $input->postFloat("a");
	mysqli_query($conn, $sql5); 

	$sql6 = $input->postStr("a");
	mysqli_query($conn, $sql6); 
}

run();