<?php

namespace  App\Model;

class Articles extends Model
{
	protected $fillable = ['title', 'body'];
	protected $guarded = ['id'];
	protected $specialchars = ['title', 'body'];
}