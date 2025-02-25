<?php

class DBClass extends PDO {

	public function run(string $query, array $params = []) {
		$stmt = $this->prepare($query);
		$stmt->execute($params);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

}