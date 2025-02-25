<?php

use Core\DB\DBRetryPolicy;
use Core\Exception\RecentlyWorkDatabaseConnectionException;
use Core\Exception\SphinxConnectionRefusedException;
use Illuminate\Database\DetectsLostConnections;
use Symfony\Component\ErrorHandler\ErrorHandler;


class DataBasePDO {

	use DetectsLostConnections;

	private static $_instance;
	private $_rowCount;
	protected $_config;

	/**
	 * @var PDO
	 */
	protected $_pdoObj;
	protected $_lastError;
	private $connectionParams;

	private $database;

	public $countUsing = 0;

	public $logQueryTime = false;
	public $querysTimme = array();

	const codeServerError = 1000;
	const codePDOException = 1001;
	const codeRowNotFound = 1004;

	private $_tryReconnect = true;
	public $isSlave = false;


	private $usePDONotFromDB = false;


	private $throwExceptionNotExit = false;


	private $_params = [];

	const RETRY_PAUSE = 100000;


	const DEADLOCK_ERROR = 40001;

	const ISOLATION_LEVELS = [
		"READ COMMITTED",
		"READ UNCOMMITTED",
		"SERIALIZABLE",
		"REPEATABLE READ"
	];
	const SET_DELIMITER = ", ";
	const INSERT_OPTION_IGNORE = 'ignore';

	private $_defaultIsolationLevel;

	public static function getInstance($database = App::DB_MASTER) {
		if (!isset(self::$_instance[$database])) {
			self::$_instance[$database] = new DataBasePDO(["database" => $database]);
		}
		return self::$_instance[$database];
	}


	private function isTransactionStarted(): bool {
		return $this->_pdoObj->inTransaction();
	}

	public function __construct(array $options = array()) {
		if ($options["database"] == App::DB_SLAVE) {
			$this->isSlave = true;
			$this->usePDONotFromDB = true;
		} elseif (in_array($options["database"], App::SPHINX_DATABASES)) {
			$this->usePDONotFromDB = true;
			$this->throwExceptionNotExit = true;
		}

		$this->database = $options["database"];

		if (empty($options['custom'])) {
			$this->connect();
		}
	}

	public function lastInsertId() {
		return $this->_pdoObj->lastInsertId();
	}

	private static function serverError($msg, $sql, $params) {
		Log::daily('SQL: ' . $sql . " (params: " . http_build_query($params) . ")\nError: " . implode(":", $msg) . "\nFrom call: " . Log::getStackCall(), 'error');
		$setMsg = implode(":", $msg);
		self::processLastError($setMsg);
		return array(self::codeServerError, $setMsg);
	}

	private static function pdoException($msg) {
		$msg = is_array($msg) ? implode(":", $msg) : $msg;
		Log::daily("pdoException: $msg\nFrom call: " . Log::getStackCall(), 'error');
		self::processLastError($msg);
		return array(self::codePDOException, $msg);
	}

	public function affectedRows() {
		return $this->_rowCount;
	}

	protected function connect() {
		try {
			$this->reConnect();
		} catch (PDOException $e) {
			Log::daily($e->getMessage() . "\nFrom call: " . Log::getStackCall(), 'error');
			Log::write(print_R(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2), true), 'PDOException');
			if (!DBRetryPolicy::isWorkOnWeb($this->database)) {
				if ($this->throwExceptionNotExit) {
					throw new SphinxConnectionRefusedException("Failed to connect to database");
				} else {
					throw new Core\Exception\DatabaseTransactionException("Failed to connect to database");
				}
			}
		}
	}


	private function pingBySelect() {
		static $lastPing;
		if (isset($lastPing) && microtime(true) - $lastPing > 1) {
			$pingStatement = $this->_pdoObj->query("SELECT 1");
			$fetchedData = $pingStatement->fetchAll();
		}
		$lastPing = microtime(true);
	}


	public function checkReconnect(): void {
		try {
			$this->registerErrorHandler();
			$this->pingBySelect();
		} catch (Exception $exception) {
			if ($this->causedByLostConnection($exception)) {
				if (!$this->restoreConnection()) {
					throw $exception;
				}
			} else {
				throw $exception;
			}
		} finally {
			$this->restoreErrorHandler();
		}
	}


	public function setConnectionParams($dbHost = '', $dbUser = '', $dbPassword = '', $dbName = '', $dbSocket = '') {
		$this->connectionParams = [
			'dbHost' => $dbHost,
			'dbUser' => $dbUser,
			'dbPassword' => $dbPassword,
			'dbName' => $dbName,
			'dbSocket' => $dbSocket,
		];
	}

	private function getConnectionParams() {
		return $this->connectionParams;
	}

	private function getDatabase() {
		return $this->database;
	}

	public function reConnect() {
		$database = $this->getDatabase();

		if (!in_array($database, App::VALID_DATABASES)) {
			throw new PDOException("Invalid database: ".$database);
		}

		if (empty($this->connectionParams)) {
			$hosts = App::config("db.$database.host");
			if (is_array($hosts)) {
				shuffle($hosts);
				$host = array_shift($hosts);
			} else {
				$host = $hosts;
			}

			$this->setConnectionParams(
				$host,
				App::config("db.".$database.".user"),
				App::config("db.".$database.".password"),
				App::config("db.".$database.".name"),
				App::config("db.".$database.".socket")
			);
		}

		if ($this->usePDONotFromDB) {
			$params = $this->getConnectionParams();
			$options = [
				PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
				PDO::ATTR_TIMEOUT => 5,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_STRINGIFY_FETCHES => true,
			];
			$this->_pdoObj = new PDO("mysql:host={$params['dbHost']};dbname={$params['dbName']};charset=utf8", $params['dbUser'], $params['dbPassword'], $options);
			return true;
		}

		$this->_pdoObj = \Core\DB\DB::getPdo($database);
		return true;
	}

	public function lastError() {
		return $this->_lastError[1];
	}

	public function lastFullError() {
		return $this->_lastError;
	}

	public function quote($string) {
		return $this->_pdoObj->quote((string)$string);
	}

	public static function processLastError($errorMsg) {
		list(, $code,) = explode(':', $errorMsg);
	}


	public function pdoNullValue($value) {
		if (is_null($value)) {
			return ["value" => $value, "PDOType" => PDO::PARAM_NULL];
		}
		return $value;
	}


	protected function bindValues($sql, array $params = [], $fetchMode = null) {
		if ($this->_tryReconnect && !$this->isTransactionStarted()) {
			$this->checkReconnect();
		}

		$this->countUsing++;
		$stmt = $this->_pdoObj->prepare($sql);

		if (!empty($fetchMode)) {
			$stmt->setFetchMode($fetchMode);
		}
		$this->clearParams();
		foreach ($params as $key => $value) {
			if (is_array($value) && isset($value['PDOType'])) {
				$param = $value['PDOType'];
				switch ($param) {
					case PDO::PARAM_STR:
						$setValue = strval($value['value']);
						break;

					case PDO::PARAM_INT:
						$setValue = intval($value['value']);
						break;

					case PDO::PARAM_NULL:
						$setValue = null;
						break;

					default:
						$setValue = $value['value'];
						break;
				}
			} else {
				$param = PDO::PARAM_STR;
				if (is_numeric($value) && intval($value) == $value) {
					$param = PDO::PARAM_INT;
					$setValue = intval($value);
				} else {
					$setValue = strval($value);
				}
			}
			$this->setParam($key, $setValue);
			$stmt->bindValue($key, $setValue, $param);
		}
		return $stmt;
	}


	private function retryExecute(PDOStatement &$stmt, $logFile) {
		if ($this->isTransactionStarted()) {
			return false;
		}

		$maxAttempt = $this->getMaxTryCount();
		$res = false;
		for ($i = 1; $i <= $maxAttempt; $i++) {
			usleep(self::RETRY_PAUSE);
			try {
				$res = $stmt->execute();
			} catch (Exception $exception) {
				Log::write("Cannot resolve in $i try because error:". $exception->getMessage() . "\n", $logFile);
			}
			if ($res == true) {
				Log::write("Resolved at " . $i . " try", $logFile);
				break;
			} elseif ($i == $maxAttempt) {
				Log::write("Cannot resolve in $maxAttempt tries", $logFile);
			}
		}
		return $res;
	}

	private function getMaxTryCount(): int {
		return DBRetryPolicy::getMaxTryCount($this->database);
	}

	private function restoreConnection(): bool {
		$maxAttempts = $this->getMaxReconnectAttempts();
		for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
			try {
				\Core\DB\DB::reconnect($this->database);
				$this->connect();
				return true;
			} catch (Exception $exception) {
				if ($this->throwExceptionNotExit) {
					throw $exception;
				}
				usleep(self::RETRY_PAUSE);
			}
		}
		return false;
	}

	private function getMaxReconnectAttempts(): int {
		return DBRetryPolicy::getMaxConnectionTryCount($this->database);
	}

	private function executePDOStatment(PDOStatement $stmt) {
		global $enableSqlLogInPage;
		$isSqllogEnable = App::config("sqllog.enable");
		if ($this->logQueryTime || $isSqllogEnable) {
			$startTime = microtime(true);
		}

		$res = null;
		try {
			$this->registerErrorHandler();

			$res = $stmt->execute();

			$this->restoreErrorHandler();
		} catch (Exception $exception) {
			if ($this->isTransactionStarted()) {
				throw $exception; 
			}

			try {
				if ($stmt->errorCode() == self::DEADLOCK_ERROR) {
					$this->logInnodbStatus();
					Log::write(($_SERVER['REQUEST_URI'] ?? "") . "\n" . $stmt->queryString, "deadlock_error");
					$res = $this->retryExecute($stmt, "deadlock_error");
				}
			} finally {
				$this->restoreErrorHandler();
			}
		}
		
		if ($this->logQueryTime || $isSqllogEnable) {
			$workTime = round(microtime(true) - $startTime, 4);
		}
		if ($this->logQueryTime) {
			$this->querysTimme[] = array('sql' => $stmt->queryString, 'work_time' => $workTime);
		}
		if ($isSqllogEnable && $enableSqlLogInPage) {
			Log::write(($_SERVER['REQUEST_URI'] ?? "") . "\n[" . round($workTime * 1000) . "] " . $stmt->queryString, "sql");
		}

		if ($isSqllogEnable && App::showDebugPanel()) {
			$this->sqlLog($stmt->queryString, $this->getParams(), ceil($workTime * 1000), $res != true);
		}

		return $res;
	}

	public function sqlLog($query, $params, $dbTime, $err) {
		global $logSessionTime, $logSqlTime, $logSqlQuerys;
		if (php_sapi_name() === "cli" || strpos($_SERVER["REQUEST_URI"], "get_dev_log") !== false) {
			return false;
		}
		if (empty($logSqlQuerys)) {
			$logSessionTime = microtime(true);
			$logSqlQuerys = [];
			$logSqlTime = 0;

		
		}

		$stack = debug_backtrace();
		while ($item = array_shift($stack)) {
			if ($item["function"] === "sqlLog") {
				break;
			}
		}
		$stack = array_map(function($item) {
			unset($item["args"], $item["object"], $item["type"]);
			return $item;
		}, $stack);

		$query = str_replace(["\r", "\n", "\t", "  "], " ", $query);
		$logSqlQuerys[] = [
			$query,
			$params,
			$dbTime,
			$err,
			$stack,
		];
		$logSqlTime += $dbTime;
		return true;
	}

	public function execute($sql, $params = array()) {
		if ($this->isSlave) {
			Log::daily("Use execute in SLAVE\n" . Log::getStackCall(), 'error');
			exit('DB Slave is not supported yet.');
		}
		if (DBRetryPolicy::isWorkOnWeb($this->database) && DBRetryPolicy::isRecentlyWorkDatabaseLostConnection()) {
			$this->_lastError = (new RecentlyWorkDatabaseConnectionException())->getMessage();
			return false;
		}
		try {
			$stmt = $this->bindValues($sql, $params);
			$res = $this->executePDOStatment($stmt);
			$this->_rowCount = $stmt->rowCount();
			if ($res == true) {
				return $this->affectedRows();
			} else {
				$this->_lastError = self::serverError($stmt->errorInfo(), $sql, $params);
				return false;
			}
		} catch (Exception $e) {
			if ($this->isTransactionStarted()) {
				throw $e;
			}
			$this->_lastError = self::pdoException($sql . "\n" . $e->getMessage());
			return false;
		}
	}

	public function fetchAll($sql, array $params = array(), $fetch_mode = PDO::FETCH_ASSOC) {
		if (DBRetryPolicy::isWorkOnWeb($this->database) && DBRetryPolicy::isRecentlyWorkDatabaseLostConnection()) {
			$this->_lastError = (new RecentlyWorkDatabaseConnectionException())->getMessage();
			return false;
		}
		try {
			$stmt = $this->bindValues($sql, $params, $fetch_mode);
			$res = $this->executePDOStatment($stmt);
			$this->_rowCount = $stmt->rowCount();
			if ($res == true) {
				$data = $stmt->fetchAll();
				if ($data === false) {
					return array();
				} else {
					return $data;
				}
			} else {
				$this->_lastError = self::serverError($stmt->errorInfo(), $sql, $params);
				return false;
			}
		} catch (Exception $e) {
			if ($this->isTransactionStarted()) {
				throw $e;
			}
			$this->_lastError = self::pdoException($e->getMessage());
			return false;
		}
	}

	public function fetch($sql, $params = array(), $fetch_mode = PDO::FETCH_ASSOC) {
		if (DBRetryPolicy::isWorkOnWeb($this->database) && DBRetryPolicy::isRecentlyWorkDatabaseLostConnection()) {
			$this->_lastError = (new RecentlyWorkDatabaseConnectionException())->getMessage();
			return false;
		}
		try {
			$stmt = $this->bindValues($sql, $params, $fetch_mode);

			$res = $this->executePDOStatment($stmt);
			$this->_rowCount = $stmt->rowCount();
			if ($res == true) {
				$row = $stmt->fetch();
				if ($row === false) {
					return array();
				} else {
					return $row;
				}
			} else {
				$this->_lastError = self::serverError($stmt->errorInfo(), $sql, $params);
				return false;
			}
		} catch (Exception $e) {
			if ($this->isTransactionStarted()) {
				throw $e;
			}
			$this->_lastError = self::pdoException($e->getMessage());
			return false;
		}
	}

	/**
	 * @throws Exception
	 */
	public function fetchScalar($sql, $params = array()) {
		if (DBRetryPolicy::isWorkOnWeb($this->database) && DBRetryPolicy::isRecentlyWorkDatabaseLostConnection()) {
			$this->_lastError = (new RecentlyWorkDatabaseConnectionException())->getMessage();
			return false;
		}
		try {
			$stmt = $this->bindValues($sql, $params);
			$res = $this->executePDOStatment($stmt);
			$this->_rowCount = $stmt->rowCount();
			if ($res == true) {
				$row = $stmt->fetchColumn();
				if ($row === false) {
					return '';
				} else {
					return $row;
				}
			} else {
				$this->_lastError = self::serverError($stmt->errorInfo(), $sql, $params);
				return false;
			}
		} catch (Exception $e) {
			if ($this->isTransactionStarted()) {
				throw $e;
			}
			$this->_lastError = self::pdoException($e->getMessage());
			return false;
		}
	}

	public function fetchAllByColumn($sql, $column = 0, $params = array()) {
		if (DBRetryPolicy::isWorkOnWeb($this->database) && DBRetryPolicy::isRecentlyWorkDatabaseLostConnection()) {
			$this->_lastError = (new RecentlyWorkDatabaseConnectionException())->getMessage();
			return false;
		}
		try {
			$stmt = $this->bindValues($sql, $params);
			$res = $this->executePDOStatment($stmt);
			$this->_rowCount = $stmt->rowCount();
			if ($res == true) {
				$data = $stmt->fetchAll(PDO::FETCH_COLUMN, $column);
				if ($data === false) {
					return array();
				} else {
					return $data;
				}
			} else {
				$this->_lastError = self::serverError($stmt->errorInfo(), $sql, $params);
				return false;
			}
		} catch (Exception $e) {
			if ($this->isTransactionStarted()) {
				throw $e;
			}
			$this->_lastError = self::pdoException($e->getMessage());
			return false;
		}
	}

	public function fetchAllNameByColumn($sql, $column = 0, $params = array(), $asObjects = false) {
		if (DBRetryPolicy::isWorkOnWeb($this->database) && DBRetryPolicy::isRecentlyWorkDatabaseLostConnection()) {
			$this->_lastError = (new RecentlyWorkDatabaseConnectionException())->getMessage();
			return false;
		}
		try {
			$stmt = $this->bindValues($sql, $params, PDO::FETCH_ASSOC);
			$res = $this->executePDOStatment($stmt);
			$this->_rowCount = $stmt->rowCount();
			if ($res == true) {
				$data = $stmt->fetchAll();
				if ($data === false) {
					return array();
				} else {
					$retArray = array();
					foreach ($data as $i => $array) {
						$indx = -1;
						$temp = array();
						$key = -1;
						foreach ($array as $j => $value) {
							$indx++;
							if ($indx == $column || (!is_numeric($column) && $j == $column)) {
								$key = $value;
							}
							$temp[$j] = $value;
						}
						if ($asObjects) {
							$retArray[$key] = (object)$temp;
						} else {
							$retArray[$key] = $temp;
						}
						if ($key == -1) {
							$this->_lastError = "Указан неправильный номер столбца";
							return false;
						}
						unset($data[$i]);
					}
					return $retArray;
				}
			} else {
				$this->_lastError = self::serverError($stmt->errorInfo(), $sql, $params);
				return false;
			}
		} catch (Exception $e) {
			if ($this->isTransactionStarted()) {
				throw $e;
			}
			$this->_lastError = self::pdoException($e->getMessage());
			return false;
		}
	}

	public function foundRows() {
		$this->_tryReconnect = false;
		$return = $this->fetchScalar("SELECT FOUND_ROWS()");
		$this->_tryReconnect = true;
		return $return;
	}


	public function exist($tableName, $condition, $params = array()) {
		$sql = "SELECT 1 FROM $tableName WHERE $condition LIMIT 1";
		$res = $this->fetchScalar($sql, $params);
		if ($res === false) {
			return $this->lastError();
		}
		return ($res == 1);
	}


	public function existByFields(string $tableName, array $fields) {
		$this->makeSet($fields, $sql_set, $sql_params);
		if (empty($sql_set)) {
			$this->_lastError = 'Fields empty';
			return false;
		}

		$conditions = explode(self::SET_DELIMITER, $sql_set);
		$condition = implode(" AND ", $conditions);

		return $this->exist($tableName, $condition, $sql_params);
	}


	protected static function makeSet($fields, &$sql_set, &$sql_params) {
		$sql_fields = array();
		$sql_params = array();
		$i = 0;
		foreach ($fields as $n => $v) {
			if (!Helper::isFieldDb($n)) {
				continue;
			}
			$sql_fields[] = "`$n`" . '=:v' . $i;
			$sql_params['v' . $i] = $v;
			$i++;
		}
		$sql_set = implode(self::SET_DELIMITER, $sql_fields);
	}


	public function insert($table, $fields, $o = array()) {
		if ($this->isSlave) {
			Log::daily("Use insert in SLAVE\n" . Log::getStackCall(), 'error');
			exit('DB Slave is not supported yet.');
		}
		try {
			$this->countUsing++;
			self::makeSet($fields, $sql_set, $sql_params);
			if (empty($sql_set)) {
				$this->_lastError = 'Fields empty';
				return false;
			}
			$ignore = '';
			if (isset($o[self::INSERT_OPTION_IGNORE]) and $o[self::INSERT_OPTION_IGNORE]) {
				$ignore = ' ignore';
			}
			$sql = 'insert' . $ignore . ' into ' . $table . ' set ' . $sql_set;
			if ($this->execute($sql, $sql_params) !== false) {
				$id = $this->lastInsertId();
				if (empty($id)) {
					return true;
				}
				return $id;
			}
			return false;
		} catch (Exception $e) {
			if ($this->isTransactionStarted()) {
				throw $e;
			}
			$this->_lastError = self::pdoException($e->getMessage());
			return false;
		}
	}


	public function replace(string $table, array $fields) {
		if ($this->isSlave) {
			Log::daily("Use replace in SLAVE\n" . Log::getStackCall(), 'error');
			return false;
		}
		try {
			if (empty($fields)) {
				$this->_lastError = 'Fields empty';
				return false;
			}

			$sqlParams = [];
			$placeholders = [];
			$i = 0;
			foreach ($fields as $fieldName => $fieldValue) {
				if (!Helper::isFieldDb($fieldName)) {
					$this->_lastError = "$fieldName is not field name";
					return false;
				}
				if (!is_array($fieldValue) || isset($fieldValue["PDOType"])) {
					$placeholders[] = $key = ":f" . $i++;
					$sqlParams[$key] = $fieldValue;
				} else {
					$subPlaceholders = [];
					foreach ($fieldValue as $subValue) {
						$subPlaceholders[] = $key = ":f" . $i++;
						$sqlParams[$key] = $subValue;
					}
					$placeholders[] = "(" . implode(",", $subPlaceholders) . ")";
				}
			}
			$fieldNamesString = implode(",", array_keys($fields));
			$placeHoldersString = implode(",", $placeholders);

			$sql = "REPLACE INTO {$table} ({$fieldNamesString}) VALUES ({$placeHoldersString})";
			if ($this->execute($sql, $sqlParams) !== false) {
				$id = $this->lastInsertId();
				if (empty($id)) {
					return true;
				}
				return $id;
			}
			return false;
		} catch (Exception $e) {
			if ($this->isTransactionStarted()) {
				throw $e;
			}
			$this->_lastError = self::pdoException($e->getMessage());
			return false;
		}
	}


	public function update($table, $fields, $condition, $params = array()) {
		if ($this->isSlave) {
			Log::daily("Use update in SLAVE\n" . Log::getStackCall(), 'error');
			exit('DB Slave is not supported yet.');
		}
		try {
			$sql_params = $params;
			self::makeSet($fields, $sql_set, $sql_params);
			if (empty($sql_set)) {
				$this->_lastError = 'Fields empty';
				return false;
			}
			$sql = 'update ' . $table . '  set ' . $sql_set . ' where ' . $condition;
			return $this->execute($sql, array_merge($sql_params, $params));
		} catch (Exception $e) {
			if ($this->isTransactionStarted()) {
				throw $e;
			}
			$this->_lastError = self::pdoException($e->getMessage());
			return false;
		}
	}


	public function getStmt($sql, $params = array(), $fetch_mode = PDO::FETCH_ASSOC) {
		try {
			$stmt = $this->bindValues($sql, $params, $fetch_mode);
			$res = $this->executePDOStatment($stmt);
			if ($res == true) {
				return $stmt;
			} else {
				$this->_lastError = self::serverError($stmt->errorInfo(), $sql, $params);
				return false;
			}
		} catch (Exception $e) {
			if ($this->isTransactionStarted()) {
				throw $e;
			}
			$this->_lastError = self::pdoException($e->getMessage());
			return false;
		}
	}


	public function getNextAutoIncrement($tableName) {
		# forbidden to use (by galera)
		throw new Exception('PDO method forbidden');
	}


	public function fetchAllAssocPair($sql, $keyColumn = 0, $valueColumn = 1, array $params = array()) {
		if (DBRetryPolicy::isWorkOnWeb($this->database) && DBRetryPolicy::isRecentlyWorkDatabaseLostConnection()) {
			$this->_lastError = (new RecentlyWorkDatabaseConnectionException())->getMessage();
			return false;
		}
		try {
			$stmt = $this->bindValues($sql, $params, PDO::FETCH_BOTH);
			$res = $this->executePDOStatment($stmt);
			$this->_rowCount = $stmt->rowCount();
			if ($res == true) {
				$data = $stmt->fetchAll();
				if ($data === false) {
					return array();
				} else {
					$retArray = array();
					foreach ($data as $i => $row) {
						if (!array_key_exists($keyColumn, $row)) {
							$this->_lastError = "Указан неправильный индексный столбц";
							return false;
						}
						if (!array_key_exists($valueColumn, $row)) {
							$this->_lastError = "Указан неправильный столбц значений";
							return false;
						}
						$retArray[$row[$keyColumn]] = $row[$valueColumn];
					}
					return $retArray;
				}
			} else {
				$this->_lastError = self::serverError($stmt->errorInfo(), $sql, $params);
				return false;
			}
		} catch (Exception $ex) {
			if ($this->isTransactionStarted()) {
				throw $ex;
			}
			$this->_lastError = self::pdoException($ex->getMessage());
			return false;
		}
	}


	public function insertRows($table, array $values, $o = array()) {
		if ($this->isSlave) {
			Log::daily("Use insertRows in SLAVE\n" . Log::getStackCall(), 'error');
			exit('DB Slave is not supported yet.');
		}
		try {
			if (empty($values)) {
				$this->_lastError = "Fields empty";
				return false;
			}

			$fields = array_keys(reset($values));
			$insertParams = [];
			$setValues = [];
			$counter = 0;
			foreach ($values as $rowNum => $setRow) {
				$placeholders = [];
				foreach ($setRow as $field => $value) {
					if (!is_array($value) || isset($value["PDOType"])) {
						$placeholders[] = $key = ":f" . ($counter++);
						$insertParams[$key] = $value;
					} else {
						$subPlaceholders = [];
						foreach ($value as $subValue) {
							$subPlaceholders[] = $key = ":f" . ($counter++);
							$insertParams[$key] = $subValue;
						}
						$placeholders[] = "(" . implode(",", $subPlaceholders) . ")";
					}
				}
				$setValues[] = "(" . implode(",", $placeholders) . ")";
			}

			$ignore = '';
			if (isset($o['ignore']) and $o['ignore']) {
				$ignore = ' ignore';
			}

			$duplicate = '';
			if (isset($o['duplicate']) and is_array($o['duplicate'])) {
				$updateFields = $o['duplicate'];
				if (empty($updateFields)) {
					$this->_lastError = 'Updated Fields empty';
					return false;
				}

				$updated = [];
				foreach ($updateFields as $field) {
					if (!in_array($field, $fields)) {
						$this->_lastError = 'Поле не найдено';
						return false;
					}

					$updated[] = $field . " = VALUES(" . $field . ")";
				}
				$duplicate = ' on duplicate key update ' . implode(", ", $updated);
			}

			$statement = "insert";
			if (isset($o["replace"])) {
				$statement = "replace";
			}
			$sql = $statement . $ignore . " into " . $table . "(" . implode(",", $fields) . ") values\n" . implode(",\n", $setValues) . $duplicate;
			return $this->execute($sql, $insertParams);
		} catch (Exception $e) {
			if ($this->isTransactionStarted()) {
				throw $e;
			}
			$this->_lastError = self::pdoException($e->getMessage());
			return false;
		}
	}


	public function insertByIteration($table, $values, $options = [], $iterationCount = 1000) {
		$chunks = array_chunk($values, $iterationCount, true);

		foreach ($chunks as $chunk) {
			$this->insertRows($table, $chunk, $options);
		}
	}


	public function arrayToStrParams(array &$values, array &$params, $valueType = PDO::PARAM_STR, $prefix = "p", $separator = ",", bool $isValueTypeNeed = true) {

		$valueType = intval($valueType);

		$result = "";
		$isFirst = true;

		foreach ($values as $key => $value) {

			$paramKey = $prefix . $key;

			while (array_key_exists($paramKey, $params)) {
				$paramKey = $prefix . $paramKey;
			}

			if ($isFirst) {
				$result = ":$paramKey";
				$isFirst = false;
			} else {
				$result .= "$separator:$paramKey";
			}

			if ($isValueTypeNeed) {
				$params[$paramKey] = array('PDOType' => $valueType, 'value' => $value);
			} else {
				$params[$paramKey] = $value;
			}
		}

		return $result;
	}


	public function getList($sql, $params = []) {
		$list = [];

		$rows = $this->fetchAllNameByColumn($sql, 0, $params);

		if (!empty($rows)) {
			foreach ($rows as $key => $row) {
				$list[$key] = (object)$row;
			}
		}

		return $list;
	}


	protected function logInnodbStatus() {
		$data = $this->fetchAll("show engine innodb status");
		Log::daily(print_r($data, true), 'deadlock_reason');
	}

	public function fieldExist(string $table, string $field): bool {
		$sql = "SHOW COLUMNS FROM $table WHERE Field = '$field'";
		$result = $this->fetchAll($sql);
		return !empty($result);
	}

	private function currentIsolationLevel(): string {
		$setting = $this->fetch("SHOW VARIABLES LIKE 'tx_isolation'");
		if (!empty($setting["Value"])) {
			//MYSQL возвращает READ-UNCOMMITTED
			return str_replace('-', ' ', $setting["Value"]);
		}
		return "";
	}


	private function saveDefaultIsolationLevel() {
		if (empty($this->_defaultIsolationLevel)) {
			$this->_defaultIsolationLevel = $this->currentIsolationLevel();
		}
	}


	public function setIsolationLevelSerializable() {
		$this->setIsolationLevel("SERIALIZABLE");
	}


	public function setIsolationLevelUncommited() {
		$this->setIsolationLevel("READ UNCOMMITTED");
	}


	private function setIsolationLevel(string $level) {
		$this->saveDefaultIsolationLevel();
		if (!empty($this->_defaultIsolationLevel) && in_array($level, self::ISOLATION_LEVELS)) {
			$this->execute("SET SESSION TRANSACTION ISOLATION LEVEL $level");
		}
	}


	public function setDefaultIsolationLevel() {
		if (!empty($this->_defaultIsolationLevel)) {
			$this->setIsolationLevel($this->_defaultIsolationLevel);
		}
	}

	public function buildCondition(array $condition, string $alias = '', $sign = "="): string {
		$string = "";
		foreach ($condition as $key => $value) {
			$string .= (empty($string)) ? "" : " AND ";
			$string .= (empty($alias) ? "" : $alias . '.') . $key . " " . $sign . ":" . $value;
		}

		return $string;
	}


	public function buildInCondition(string $column, array $array): array {
		$condition = "";
		$params = [];

		if (!empty($array)) {
			$in = "";
			foreach ($array as $i => $item) {
				$inKey = ":{$column}_value_" . $i;
				$in .= "$inKey,";
				$params[$inKey] = $item;
			}
			$in = rtrim($in, ",");
			$condition = "({$in})";
		}

		return [
			"condition" => $condition,
			"params" => $params
		];
	}


	public function buildInQuery(string $column, array $array): string {
		return $this->buildInCondition($column, $array)["condition"];
	}


	public function buildInParams(string $column, array $array): array {
		return $this->buildInCondition($column, $array)["params"];
	}

	public function clearParams() {
		$this->_params = [];
	}


	public function getParams() {
		return $this->_params;
	}

	public function getParam(string $key) {
		return $this->_params[$key];
	}


	public function setParam(string $key, $value) {
		$this->_params[$key] = $value;
	}


	public function removeByIteration(string $sql, int $iterationCount) {
		$sql = "{$sql} LIMIT {$iterationCount}";
		do {
			$removed = App::pdo($this->getDatabase())->execute($sql);
		} while ($removed > 0);
	}


	public static function getBindValues(array $params) {
		foreach ($params as $name => $value) {
			if (is_array($value) && isset($value['PDOType'])) {
				$params[$name] = $value['value'];
			}
		}
		return $params;
	}

	public function massUpdate(string $table, array $data, string $keyField, array $updateFields) {
		if (empty($data)) {
			Log::dailyErrorException(new RuntimeException("empty data"));
			return false;
		}

		if (!Helper::isFieldDb($keyField)) {
			Log::dailyErrorException(new RuntimeException("keyField \"$keyField\" is not DB field"));
			return false;
		}
		if (empty($updateFields)) {
			Log::dailyErrorException(new RuntimeException("empty update Fields"));
			return false;
		}
		$updateFields = array_values($updateFields);

		$keys = array_column($data, $keyField, $keyField);
		if (empty($keys)) {
			Log::dailyErrorException(new RuntimeException("empty keys"));
			return false;
		}

		$setStatements = [];
		$params = [];

		foreach ($updateFields as $fieldIndex => $updateField) {
			if (!Helper::isFieldDb($updateField)) {
				Log::dailyErrorException(new RuntimeException("update field \"$updateField\" is not DB field"));
				return false;
			}
			$updateValues = array_column($data, $updateField, $keyField);
			if (empty($updateValues)) {
				Log::dailyErrorException(new RuntimeException("empty updateValues"));
				return false;
			}
			$setStatements[$updateField] = "\n$updateField = CASE ";
			foreach ($updateValues as $key => $value) {
				$keyPlaceholder = "k{$key}";
				$valuePlaceholder = "v{$fieldIndex}_{$key}";
				$setStatements[$updateField] .= "\nWHEN {$keyField} = :$keyPlaceholder THEN :$valuePlaceholder";
				$params[$keyPlaceholder] = $key;
				$params[$valuePlaceholder] = $value;
			}
			$setStatements[$updateField] .= "\nEND ";
		}

		$keysPlacheHolders = $this->arrayToStrParams($keys, $params);
		$setsString = implode(",", $setStatements);
		$sql = "UPDATE $table SET $setsString \nWHERE $keyField IN ($keysPlacheHolders)";

		return $this->execute($sql, $params);
	}


	private function registerErrorHandler(): ErrorHandler {
		return \Core\DB\ExceptionErrorHandler::setHandler();
	}

	private function restoreErrorHandler(): void {
		\Core\DB\ExceptionErrorHandler::unsetHandler();
	}
}
