<?php

class testClass {

	public function method1($id) {

	}

	public static function method2($id) {

	}

	public static function method3($id, $name = "lol") {

	}

}

class newClass {

	public function method1($id) {

	}

	public static function method2($id) {

	}

	public static function method3($id, $name = "kek") {

	}

}

function func1($name) {

}

function func2($name) {

}

function func3($name) {

}

func1("1");
func1(123);
func1($_GET);

testClass::method2("string");
testClass::method2(12312);

func4();