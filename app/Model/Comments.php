<?php

namespace  App\Model;

	class Comments extends Model 
	{
		protected $fillable = ['post_id', 'body'];
		protected $guarded = ['id'];
		protected $specialchars = ['post_id', 'body'];

		public function selectComments($id)
		{
			$this->collection = $this->connection->select('*', $this->table, "post_id = $id");
		}
	}