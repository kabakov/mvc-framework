<?php

namespace App\Database;

use PDO;
use PDOException;

class Connector
{
    private static $instance;

    private $host = 'localhost';
    private $dbname = 'u425736227_blog';
    private $user = 'root';
    private $password = '';
    private $charset = 'utf8';

    public $connection;
    protected $result;
    protected $error;

    public $datetime = 'Y-m-d H:i:s';
    public $date = 'Y-m-d';

    protected function __construct() { }

    private function __clone() { }

    private function __wakeup() { }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Получение экземпляра класса
     *
     * @return mixed
     */
    public static function createConnector()
    {
        if (static::$instance === null) static::$instance = new static();

        return static::$instance;
    }

    /**
     * Создание подключения к БД
     */
    public function connect()
    {
        try {
            $dsn = "mysql:host=$this->host;dbname=$this->dbname;charset=$this->charset";
            $this->connection = new PDO($dsn, $this->user, $this->password);
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * Делает выборку из БД
     *
     * @param        $fields
     * @param        $table
     * @param bool   $where
     * @param bool   $orderby
     * @param bool   $limit
     * @param string $join
     * @param string $groupby
     *
     * @return array|bool
     */
    public function select($fields, $table, $where = false, $orderby = false, $limit = false, $join = '', $groupby = '')
    {
        if (is_array($fields)) {
            $fields = "`".implode($fields, "`, `")."`";
        }

        $orderby = ($orderby) ? " ORDER BY ".$orderby : '';
        $where = ($where) ? " WHERE ".$where : '';
        $limit = ($limit) ? " LIMIT ".$limit : '';

        $result = $this->query("SELECT ".$fields." FROM ".$table." ".$join." ".$where." ".$groupby.$orderby.$limit);

        if ($this->numRows($result) > 0) {
            $rows = array();

            while ($r = $this->fetchObject($result)) {
                $rows[] = $r;
            }

            return $rows;
        } else {
            return false;
        }
    }

    /**
     * Получить одну запись из БД
     *
     * @param        $fields
     * @param        $table
     * @param bool   $where
     * @param bool   $orderby
     * @param string $join
     * @param string $groupby
     *
     * @return mixed
     */
    public function selectOne($fields, $table, $where = false, $orderby = false, $join = '', $groupby = '')
    {
        $result = $this->select($fields, $table, $where, $orderby, '1', $join, $groupby);
        return $result[0];
    }

    /**
     * Получить одно значение из БД
     *
     * @param        $field
     * @param        $table
     * @param bool   $where
     * @param bool   $orderby
     * @param string $join
     * @param string $groupby
     *
     * @return mixed
     */
    public function selectOneValue($field, $table, $where = false, $orderby = false, $join = '', $groupby = '')
    {
        $result = $this->selectOne($field, $table, $where, $orderby, $join, $groupby);

        return $result->$field;
    }

    /**
     * Добавить запись в БД
     *
     * @param array $values - массив данных
     * @param       $table  - имя таблицы
     *
     * @return bool
     */
    public function insert(array $values, $table)
    {
        if (count($values) < 0) {
            return false;
        }

        foreach ($values as $field => $val) {
            $values[$field] = $this->escapeString($val);
        }

        if ($this->query("INSERT INTO ".$table." (`".implode(array_keys($values), "`, `")."`) VALUES ('".implode($values, "', '")."')")) {
            return true;
        } else {
            return $this->_lastError();
        }
    }

    /**
     * Обновляет данные в БД
     *
     * @param array $values
     * @param       $table
     * @param bool  $where
     * @param bool  $limit
     *
     * @return bool|string
     */
    public function update(array $values, $table, $where = false, $limit = false)
    {
        if (count($values) < 0) {
            return false;
        }

        $fields = array();
        foreach ($values as $field => $val) {
            $fields[] = "`".$field."` = '".$this->escapeString($val)."'";
        }

        $where = ($where) ? " WHERE ".$where : '';
        $limit = ($limit) ? " LIMIT ".$limit : '';

        if ($this->query("UPDATE ".$table." SET ".implode($fields, ", ").$where.$limit)) {
            return true;
        } else {
            return $this->_lastError();
        }
    }

    /**
     *
     * Удаляет данные из БД
     *
     * @param      $table
     * @param bool $where
     * @param int  $limit
     *
     * @return bool|string
     */
    public function delete($table, $where = false, $limit = 1)
    {
        $where = ($where) ? "WHERE {$where}" : "";
        $limit = ($limit) ? "LIMIT {$limit}" : "";

        if ($this->query("DELETE FROM `{$table}` {$where} {$limit}")) {
            return true;
        } else {
            return $this->_lastError();
        }
    }

    /**
     * Получить данные из БД как объект
     *
     * @param bool $result
     *
     * @return mixed
     */
    public function fetchObject($result = false)
    {
        $this->_ensureResult($result);

        return $result->fetch(PDO::FETCH_OBJ);
    }


    /**
     * Получить данные из БД как массив
     *
     * @param bool $result
     *
     * @return mixed
     */
    public function fetchAssoc($result = false)
    {
        $this->_ensureResult($result);

        return $result->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Возвращает имена колонок из таблицы БД
     *
     * @param        $table
     * @param bool   $incTableName
     * @param string $backtick
     *
     * @return array
     */
    public function fieldNameArrayByTable($table, $incTableName = false, $backtick = '`')
    {
        $names = array();
        $query = "SELECT * FROM `{$table}` LIMIT 1";

        $result = $this->query($query);
        $field = $this->numFields($result);

        if ($backtick === false) {
            $backtick = '';
        }

        $table = ($incTableName) ? $backtick.$table.$backtick.'.' : '';

        for ($i = 0; $i < $field; $i++) {
            $names[] = $table.$backtick.$this->fieldName($result, $i).$backtick;
        }

        return $names;
    }

    /**
     * @param $result
     *
     * @return int
     */
    public function numFields($result)
    {
        $this->_ensureResult($result);
        if (is_array($result)) {
            return count($result);
        }

        $data = $result->fetch(PDO::FETCH_NUM);

        return count($data);
    }

    /**
     * Количество строк полученых в результате запроса
     *
     * @param $result
     *
     * @return int
     */
    public function numRows($result)
    {
        $this->_ensureResult($result);
        if (is_array($result)) {
            return count($result);
        }

        $query = $result->queryString;
        $cloned = $this->query($query);
        $data = $cloned->fetchAll();

        return count($data);
    }

    /**
     * Экранирует строку
     *
     * @param $string
     *
     * @return bool|string
     */
    public function escapeString($string)
    {
        try {
            $string = $this->connection->quote($string);

            return substr($string, 1, -1);
        } catch (PDOException $e) {
            $this->_handleError($e);
        }

        return false;
    }

    /**
     * Отправляет SQL-запрос
     *
     * @param $query - строка SQL-запроса
     *
     * @return mixed - результат выполнения запроса
     */
    public function query($query)
    {
        $result = false;
        try {
            $result = $this->connection->query($query);
            $this->result = $result;
        } catch (PDOException $e) {
            $this->_handleError($e);
        }
        return $result;
    }

    /**
     * Удаляет соединение с БД
     *
     * @return bool
     */
    public function close()
    {
        if (isset($this->connection)) {
            $this->connection = null;
            unset($this->connection);

            return true;
        }

        return false;
    }

    /**
     * Получить одну запись из БД
     *
     * @param bool $result
     *
     * @return mixed
     */
    public function fetchOne($result = false)
    {
        $this->_ensureResult($result);
        list($ret) = $this->fetchObject($result);

        return $ret;
    }

    /**
     * Получить имя поля
     *
     * @param bool $result
     * @param int  $field_offset
     *
     * @return string
     */
    public function fieldName($result = false, $field_offset = 0)
    {
        $this->_ensureResult($result);
        $data = $result->getColumnMeta($field_offset);

        return $this->_mapPdoType($data['name']);
    }

    /**
     * Получить массив с именами полей
     *
     * @param bool $result
     *
     * @return array
     */
    public function fieldNameArray($result = false)
    {
        $names = array();

        $field = $this->numFields($result);

        for ($i = 0; $i < $field; $i++) {
            $names[] = $this->fieldName($result, $i);
        }

        return $names;
    }

    /**
     * Получить имя таблицы
     *
     * @param bool $result
     * @param int  $field_offset
     *
     * @return mixed
     */
    public function fieldTable($result = false, $field_offset = 0)
    {
        $this->_ensureResult($result);
        $data = $result->getColumnMeta($field_offset);

        return $data['table'];
    }

    /**
     * Получить тип данных в которых будет хранится в PHP
     *
     * @param bool $result
     * @param int  $field_offset
     *
     * @return string
     */
    public function fieldType($result = false, $field_offset = 0)
    {
        $this->_ensureResult($result);
        $data = $result->getColumnMeta($field_offset);

        return $this->_mapPdoType($data['native_type']);
    }

    /**
     * Получить id последней записи
     *
     * @return int
     */
    public function insertId()
    {
        return (int)$this->connection->lastInsertId();
    }

    /**
     * Получить количество затронутых запросом строк в БД
     *
     * @return mixed
     */
    public function affectedRows()
    {
        $result = $this->_ensureResult(false);

        return $result->rowCount();
    }

    /**
     * Получить аттрибуты клиента из соединения с БД
     *
     * @return mixed
     */
    public function getClientInfo()
    {
        return $this->connection->getAttribute(PDO::ATTR_CLIENT_VERSION);
    }

    /**
     * Получить аттрибуты сервера из соединения с БД
     *
     * @return mixed
     */
    public function getServerInfo()
    {
        return $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Получить статус сервера
     *
     * @param string $which
     *
     * @return array
     */
    public function getStatus($which = "%")
    {
        $result = $this->query("SHOW STATUS LIKE '{$which}'");
        $status = array();
        while ($row = $this->fetchObject($result)) {
            $status[$row->Variable_name] = $row->Value;
        }

        return $status;
    }

    /**
     * Получить количество записей в таблице
     *
     * @param $table
     *
     * @return mixed
     */
    public function getTableRows($table)
    {
        $result = $this->query("SELECT COUNT(*) FROM {$table}");
        $row = $this->fetchOne($result);

        return $row;
    }

    /**
     * Проверка содинения с БД
     *
     * @return bool
     */
    public function ping()
    {
        try {
            // делаем проверочный
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            try {
                // если проверочный запрос к БД провалился, поднимаем подключение снова
                $this->connect();
            } catch (PDOException $e) {
                $this->_loadError($e, false);

                return false;
            }
        }

        return true;
    }

    /**
     * Обнулить результат по ссылке и в свойстве объекта
     *
     * @param $result
     *
     * @return bool
     */
    public function freeResult(&$result)
    {
        if (is_array($result)) {
            $result = false;
            $this->result = null;

            return true;
        }

        if (get_class($result) != 'PDOStatement') {
            return false;
        }

        return $result->closeCursor();
    }

    /**
     * Получить значения по умолчанию из БД
     *
     * @param      $table
     * @param bool $dbName
     *
     * @return array
     */
    public function getDefaultValues($table, $dbName = false)
    {
        $returnValue = array();

        if (empty($table)) {
            return $returnValue;
        }

        if (empty($dbName)) {
            $dbName = $this->dbName;
        }

        // Получаем поля
        $result = $this->query("DESCRIBE `$dbName`.`{$table}`");
        $nbRows = $this->numRows($result);
        if (count($nbRows) == 0) {
            return array();
        }

        // Получаем значения по умолчанию для каждого из полей
        for ($i = 0; $i < $nbRows; $i++) {
            $val = $this->fetchObject($result);
            if ($val['Default'] AND $val['Default'] == 'CURRENT_TIMESTAMP') {
                $returnValue[$val['Field']] = date($this->datetime);
            }
            if ($val['Extra'] AND $val['Extra'] == 'auto_increment') {
                $returnValue[$val['Field']] = 0;
            }
            if ($val['Default'] AND $val['Default'] != 'CURRENT_TIMESTAMP') {
                $returnValue[$val['Field']] = $val['Default'];
            } else {
                if ($val['Null'] == 'YES') {
                    $returnValue[$val['Field']] = null;
                } else {
                    $type = $val['Type'];
                    if (strpos($type, '(') !== false) {
                        $type = substr($type, 0, strpos($type, '('));
                    }
                    if (in_array(
                        $type,
                        array(
                            'varchar',
                            'text',
                            'char',
                            'tinytext',
                            'mediumtext',
                            'longtext',
                            'set',
                            'binary',
                            'varbinary',
                            'tinyblob',
                            'blob',
                            'mediumblob',
                            'longblob'
                        )
                    )) {
                        $returnValue[$val['Field']] = '';
                    } elseif ($type == 'datetime') {
                        $returnValue[$val['Field']] = '0000-00-00 00:00:00';
                    } elseif ($type == 'date') {
                        $returnValue[$val['Field']] = '0000-00-00';
                    } elseif ($type == 'time') {
                        $returnValue[$val['Field']] = '00:00:00';
                    } elseif ($type == 'year') {
                        $returnValue[$val['Field']] = '0000';
                    } elseif ($type == 'timestamp') {
                        $returnValue[$val['Field']] = date($this->datetime);
                    } elseif ($type == 'enum') {
                        $returnValue[$val['Field']] = 1;
                    } else {
                        $returnValue[$val['Field']] = 0;
                    }
                }
            }
        }

        return $returnValue;
    }

    /**
     * Получить описание таблицы
     *
     * @param $table
     *
     * @return array
     */
    public function describeTable($table)
    {
        $result = $this->query("DESCRIBE `{$this->dbname}`.`{$table}`");
        $data = array();

        while ($row = $this->fetchObject($result)) {
            $data[$row['Field']] = $row;
        }

        return $data;
    }

    /**
     * Получить информацию о колонках
     *
     * @param $table
     *
     * @return array
     */
    public function getFullColumnsInfo($table)
    {

        $result = $this->query("SHOW FULL COLUMNS FROM `{$this->dbname}`.`{$table}`");
        $data = array();

        while ($row = $this->fetchObject($result)) {
            $data[$row['Field']] = $row;
        }

        return $data;
    }

    /**
     * Проверить существует ли таблица
     *
     * @param $table
     *
     * @return bool
     */
    public function tableExist($table)
    {
        $result = $this->query("SELECT `table_name` FROM `information_schema`.`tables` WHERE `table_schema` = '{$this->dbname}' AND `table_name` = '{$table}'");
        $numRows = $this->numRows($result);

        return $numRows > 0;
    }

    /**
     * Получить всю информацию о колонке
     *
     * @param      $data
     * @param bool $simple
     * @param int  $maxLength
     *
     * @return object|string
     */
    protected function _getAllColumnData($data, $simple = false, $maxLength = 0)
    {
        $type = $this->_mapPdoType($data['native_type']);

        $query = $this->query("DESCRIBE `{$data['table']}` `{$data['name']}`");
        $typeInner = $this->fetchObject($query);

        if ($simple === true) {
            $string = in_array('not_null', $data['flags']) ? 'not_null' : 'null';
            $string .= in_array('primary_key', $data['flags']) ? ' primary_key' : '';
            $string .= in_array('unique_key', $data['flags']) ? ' unique_key' : '';
            $string .= in_array('multiple_key', $data['flags']) ? ' multiple_key' : '';

            $unSigned = strpos($typeInner['Type'], 'unsigned');
            if ($unSigned !== false) {
                $string .= ' unsigned';
            } else {
                $string .= strpos($typeInner['Type'], 'signed') !== false ? ' signed' : '';
            }

            $string .= strpos($typeInner['Type'], 'zerofill') !== false ? ' zerofill' : '';
            $string .= isset($typeInner['Extra']) ? ' '.$typeInner['Extra'] : '';

            return $string;
        }

        $return = array(
            'name'         => $data['name'],
            'table'        => $data['table'],
            'def'          => $typeInner['Default'],
            'max_length'   => $maxLength,
            'not_null'     => in_array('not_null', $data['flags']) ? 1 : 0,
            'primary_key'  => in_array('primary_key', $data['flags']) ? 1 : 0,
            'multiple_key' => in_array('multiple_key', $data['flags']) ? 1 : 0,
            'unique_key'   => in_array('unique_key', $data['flags']) ? 1 : 0,
            'numeric'      => ($type == 'int') ? 1 : 0,
            'blob'         => ($type == 'blob') ? 1 : 0,
            'type'         => $type,
            'unsigned'     => strpos($typeInner['Type'], 'unsigned') !== false ? 1 : 0,
            'zerofill'     => strpos($typeInner['Type'], 'zerofill') !== false ? 1 : 0,
        );

        return (object)$return;
    }

    /**
     * Привязка типов данных из MySQL к типам данным PHP
     *
     * @param $type
     *
     * @return string
     */
    protected function _mapPdoType($type)
    {
        $type = strtolower($type);
        switch ($type) {
            case 'tiny':
            case 'short':
            case 'long':
            case 'longlong';
            case 'int24':
                return 'int';
            case 'null':
                return null;
            case 'varchar':
            case 'var_string':
            case 'string':
                return 'string';
            case 'blob':
            case 'tiny_blob':
            case 'long_blob':
                return 'blob';
            default:
                return $type;
        }
    }


    /**
     * Генерация массива с параметрами PDO
     *
     * @param $flags
     *
     * @return array
     */
    protected function _ensurePdoFlags($flags)
    {
        if ($flags == false || empty($flags)) {
            return array();
        }

        // принудительно приводим флаги к массиву
        if (!is_array($flags)) {
            $flags = array($flags);
        }

        $pdoParams = array();
        foreach ($flags as $flag) {
            switch ($flag) {
                case 2:
                    $params = array(PDO::MYSQL_ATTR_FOUND_ROWS => true);
                    break;
                case 32:
                    $params = array(PDO::MYSQL_ATTR_COMPRESS => true);
                    break;
                case 128:
                    $params = array(PDO::MYSQL_ATTR_LOCAL_INFILE => true);
                    break;
                case 256:
                    $params = array(PDO::MYSQL_ATTR_IGNORE_SPACE => true);
                    break;
                case 12:
                    $params = array(PDO::ATTR_PERSISTENT => true);
                    break;
            }

            $pdoParams[] = $params;
        }

        return $pdoParams;
    }

    /**
     * Обработка exception
     *
     * @param $e - Exception
     */
    protected function _handleError($e)
    {

        if ($e === false || is_null($e)) {
            $this->error = array('error' => "", 'errno' => 0);

            return;
        }

        $this->error = array('error' => $e->getMessage(), 'errno' => $e->getCode());
    }

    /**
     * Проверка того что $result результат выполнения SQL-запроса
     *
     * @param $result
     */
    protected function _ensureResult(&$result)
    {
        if ($result == false) {
            $result = $this->result;
        } else {
            if (gettype($result) !== 'resource' && is_string($result)) {
                $result = $this->query($result);
            }
        }
    }

    /**
     * Возвращает текст последней ошибки
     *
     * @return string
     */
    protected function _lastError()
    {
        $error = '';

        if ($this->connection) {
            $error = $this->connection->errorInfo()[2];
        }

        return $error;
    }

    /**
     * Возвращает номер последней ошибки
     *
     * @return string
     */
    protected function _lastErrNo()
    {
        $error = '';

        if ($this->connection) {
            $error = $this->connection->errorCode()[0];
        }

        return $error;
    }
}