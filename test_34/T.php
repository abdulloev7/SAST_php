<?php

class TestClass {

	protected $standardfield ;

	public function __construct($structure) {
		if (is_string($structure['sf'])) {
			$this->standardfield = $structure['sf'];
		}
	}

	public function run(){
		mysqli_query($conn, $this->standardfield); 
	}

}