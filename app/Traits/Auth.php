<?php

namespace App\Traits;


trait Auth
{
    function allow() {
        global $USER;

        return $USER->isAuth();
    }
}