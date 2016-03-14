<?php

namespace App\Controller;

use Twig_Loader_Filesystem;
use Twig_Environment;

abstract class Controller
{
	public function request($action, $parametrs= [])
	{
		$this->$action($parametrs);
	}

	protected function view($view, $parameters = [])
	{
		$loader = new Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'].'/mvc-framework/app/Views');
		$loader->addPath($_SERVER['DOCUMENT_ROOT'].'/mvc-framework/app/Views/Panel', 'panel');
		$twig = new Twig_Environment($loader, [
			'cache' => $_SERVER['DOCUMENT_ROOT'].'/mvc-framework/app/tmp/cache',
			'auto_reload' => true,
			'debug' => true
		]);
		
		return $twig->render($view, $parameters); //передаем имя шаблона, в controller для отрисовки html и параметры 
	}

	public function isGet()
	{
		return $_SERVER['REQUEST_METHOD'] == "GET";
	}

	public function isPost()
	{
		return $_SERVER['REQUEST_METHOD'] == "POST";
	}

	public function __call($method, $params)
	{
		die("Unknown method $method");
	}
}