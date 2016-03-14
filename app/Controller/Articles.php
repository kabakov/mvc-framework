<?php

namespace App\Controller;

use App\Model as Model;
use App\Traits\Auth;
use App\Traits\Response;

class Articles extends Controller
{
	use Auth;
	use Response;

	private $articles, $comments;

	function __construct() {
		$this->articles = new Model\Articles();
		$this->comments = new Model\Comments();
	}

	public function showAll() {

		$this->articles->getAll();

		echo $this->view(
			"articles_list.html",
			[
				'title'    => "Список статей",
				'articles' => $this->articles->collection
			]
		);
	}

	public function index()
	{
		$this->showAll();
	}

	public function show($parameters)
	{
		global $isAuth;

		$this->articles->getByPk($parameters['id']);
		$this->comments->selectComments($parameters['id']);
		echo $this->view("article.html", [
			'article' => $this->articles->collection,
			'comments' => $this->comments->collection
		]);
	}

}