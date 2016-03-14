<?php

namespace  App\Model;

use App\Database\Connector;

abstract  class Model
{
	protected $model;
	protected $connection;
	protected $table;
	protected $fields;
	protected $primaryKey = 'id';
	protected $post_id = 'post_id';
	protected $fillable = [];
	protected $guarded = [];
	protected $specialchars = [];
	protected $dateFormat;
	protected $datetimeFormat;
	public $collection = [];

	public function __construct()
	{
		$this->class = get_class($this);
		$reflectionClass = new \ReflectionClass($this->class);
		$this->table = strtolower($reflectionClass->getShortName());
		$this->connection = Connector::createConnector();
		$this->connection->connect();
		$this->fields = $this->connection->fieldNameArrayByTable($this->table, false, false);
		$this->datetimeFormat = $this->connection->datetime;
		$this->dateFormat = $this->connection->date;
	}

	public function getAll()
	{
		$this->collection = $this->connection->select('*', $this->table);
	}

	public function getByPk($value)
	{
		$this->collection = $this->connection->selectOne('*', $this->table, $this->primaryKey." = $value");
	}

	public function add($item)
	{
		$this->checkItem($item);

		if ($this->connection->insert($item, $this->table)) {
			$item[$this->primaryKey] = $this->connection->insertId();
			$this->collection[] = $item;
		}
	}

	public function getDel($value)
	{
		$this->collection = $this->connection->delete($this->table, $this->primaryKey." = $value");
	}

	public function getDelAll($value)
	{
		$this->collection = $this->connection->delete($this->table, $this->post_id." = $value", false);
	}

	public function update($item)
	{
		$id = (int)$item[$this->primaryKey];
		$this->checkItem($item);

		if ($this->connection->update($item, $this->table, "id = $id")) {
			$item[$this->primaryKey] = $id;
			$this->collection = $item;
		}
	}

	private function checkItem(&$item)
	{
		foreach ($item as $key => &$value) {
			if (in_array($key, $this->guarded) || !in_array($key, $this->fillable)) unset($item[$key]);
			if (in_array($key, $this->specialchars)) $value = htmlspecialchars($value);
		}
	}
}

