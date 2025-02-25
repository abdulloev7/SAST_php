<?php

class DataBase
{
	private $tmpTablesOnDisk;

	public function getTablesOnDisk($update = false) {
		$sql = 'SHOW GLOBAL STATUS like "Created_tmp_disk_tables"';
		$result = App::pdo()->fetchAll($sql);
        $cnt = $result[0]['Value'];
		if ($update) {
			$this->tmpTablesOnDisk = $cnt;
		}
		return $cnt;
	}

	public function logSqlTablesOnDisk($sql) {
		$newCnt = $this->getTablesOnDisk();
		if ($newCnt > $this->tmpTablesOnDisk) {
			Log::daily($sql, 'query_use_disk');
			$this->tmpTablesOnDisk = $newCnt;

		}
	}

	public function connect()
	{
        App::pdo()->execute("set charset utf8");
	}

	private function sqlLog($sql, $dbTime){
		$maxAllowInTable=10000;
        $result = App::pdo()->fetchAll('SELECT COUNT(*) as cnt FROM sql_log');
		if ($result === false) {
			return false;
		}
		$cnt = $result['cnt'];
		if ($cnt > $maxAllowInTable) {
            $result = App::pdo()->execute('SELECT date_create FROM sql_log ORDER BY date_create DESC LIMIT ' . $maxAllowInTable . ', 1');
			$deleteBefore = $result['date_create'];
			if(!empty($deleteBefore)){
                App::pdo()->execute('DELETE FROM sql_log WHERE date_create < "' . mres($deleteBefore) . '"');
			}
		}

        App::pdo()->execute("INSERT INTO sql_log (hash, query, time, request) VALUES ('" . mres(md5($sql)) . "', '" . mres($sql) . "', '" . mres($dbTime) . "', '" . mres($_SERVER['REQUEST_URI']) . "')");
	}

	public function Execute($sql)
	{
        $sql = trim($sql);
        if(stripos($sql, "SELECT") === 0) {
            $result = App::pdo()->fetchAll($sql);
        } else {
            $result = App::pdo()->execute($sql);
        }

		{
			$entityId = 0;
			if(stripos($sql, "INSERT") === 0)
				$entityId = App::pdo()->lastInsertId();
		}

		{
			if($entityId)
				return $entityId;
		}

		return new DataBaseData($result);
	}

	function getList($sql, $time = null)
	{
		$res = $this->execute($sql);
		$rows = $res->getrows();

		$list = array();
		foreach($rows as $row)
		{
			$entity = new stdclass();

			foreach($row as $key => $value)
				$entity->$key = $value;
                        $list[array_shift($row)] = $entity;
		}
		return $list;
	}

	function getEntity($sql, $time = null)
	{

		if($time && App::config("redis.enable"))
		{
			$entity = RedisManager::getInstance()->get($sql);
			if($entity)
				return $entity;
		}

		$entity = null;

		$res = $this->getList($sql);
		if($res && count($res) == 1)
			$entity = array_shift($res);

		if($time && App::config("redis.enable"))
		{
			RedisManager::getInstance()->set($sql, $entity, $time);
		}

		return $entity;
	}

	function getColumn($sql, $time = null)
	{

		$column = array();

		$res = $this->getList($sql);
		foreach($res as $key => $row) {
			$arr = get_mangled_object_vars($row);
			$column[$key] = end($arr);
		}
		return $column;
	}

	function getCell($sql, $time = null)
	{

		if($time && App::config("redis.enable"))
		{
			$cell = RedisManager::getInstance()->get($sql);
			if($cell)
				return $cell;
		}

		$cell = 0;

		$column = $this->getColumn($sql);
		if($column)
			$cell = current($column);

		if($time && App::config("redis.enable"))
		{
			RedisManager::getInstance()->set($sql, $cell, $time);
		}

		return $cell;
	}

	function getRowsCount()
	{
		return App::pdo()->foundRows();
	}

	public function getAffectedCount()
	{
		return App::pdo()->affectedRows();
	}

	function getIds($list, $name)
	{
		$ids = [];

		foreach($list as $item)
		{
			if(is_array($item))
			{
				if($item[$name])
					$ids[] = (int)$item[$name];
			}
			else
			{
				if($item->$name)
					$ids[] = (int)$item->$name;
			}
		}

		$ids = array_unique($ids);

		return implode(",", $ids);
	}
}

class DataBaseData
{
	protected $data;
	public $fields;

	function __construct($data)
    {
        $this->data = $data;
        $this->fields = is_array($data) && isset($data[0]) ? $data[0] : array();
    }

	public function getrows()
	{
		$res = $this->data ? $this->data : array();

        return $res;
	}

	public function getRow($rowIdx = 0)
    {
        $result = null;
        $data = $this->data && count($this->data) ? array_values($this->data) : null;

        if (!is_null($data) && array_key_exists($rowIdx, $data)) {
            $result = $data[$rowIdx];
        }

        return $result;
    }

	public function recordcount()
	{
		return ($this->data && is_array($this->data)) ? count($this->data) : 0;
	}

	public function rowcount()
	{
		return $this->recordcount();
	}

	public function getarray()
	{
		return $this->getrows();
	}
}