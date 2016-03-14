<?php


namespace App\Traits;

use Twig_Loader_Filesystem;
use Twig_Environment;


trait Response
{
    function error() {
        $loader = new Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'].'/mvc-framework/app/Views');
        $twig = new Twig_Environment($loader, [
            'cache' => $_SERVER['DOCUMENT_ROOT'].'/mvc-framework/app/tmp/cache',
            'auto_reload' => true,
            'debug' => true
        ]);

        header("HTTP/1.0 404 Not Found");
        echo $twig->render('error.html');
        die();
    }

    function notAllowed() {
        $loader = new Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'].'/mvc-framework/app/Views');
        $twig = new Twig_Environment($loader, [
            'cache' => $_SERVER['DOCUMENT_ROOT'].'/mvc-framework/app/tmp/cache',
            'auto_reload' => true,
            'debug' => true
        ]);

        header("HTTP/1.0 403 Forbidden");
        echo $twig->render('not_allowed.html');
        die();
    }
}