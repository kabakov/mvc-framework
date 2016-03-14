<?php
require "vendor/autoload.php";

use App\Route;
use App\Controller\Panel\Panel;

global $USER, $isAuth;

$USER = new Panel();
$isAuth = $USER->isAuth();

$route = isset($_GET['_route']) ? preg_replace('/index.php\?_route=(.*)/', '$1', $_GET['_route']) : '';
$router = new Route($route);
$router->dispatch();

?>