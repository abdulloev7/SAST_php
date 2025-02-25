<?php

readonly class User {
	public string $username;
	public string $uid;
}

final class testMysql {

	final public const X = "foo";

	final public function run() {
		mysqli_query($conn, $_GET["query"]);
	}

}

$mysql = new testMysql();
$mysql->run();