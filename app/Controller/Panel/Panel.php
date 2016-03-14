<?php

namespace App\Controller\Panel;


use App\Controller\Controller;
use App\Model as Model;
use App\Traits\Auth;

class Panel extends  Controller
{
    use Auth;
    private $user, $menu;

    public function __construct()
    {
        $this->user = new Model\Users();
        $this->menu = $this->buildMenu();
    }

    public function login($error = [])
    {
        if (!is_array($error)) {
            $error = array($error);
        }

        echo $this->view(
            "@panel/login.html",
            [
                'title' => "Логин",
                'errors' => $error
            ]
        );
    }

    public function index() {
        if($this->allow()) {
            echo $this->view(
                "@panel/index.html",
                [
                    'title' => "Система администрирования",
                    'menu' => $this->menu
                ]
            );
        } else {
            $this->login();
        }
    }

    public function register($error = [])
    {
        if (!is_array($error)) {
            $error = array($error);
        }

        echo $this->view(
            "@panel/register.html",
            [
                'title'  => "Регистрация",
                'errors' => $error
            ]
        );
    }

    public function createUser($data)
    {
        $error = [];
        if (!preg_match("/^[a-zA-Z0-9_]+$/", $data['username'])) {
            $error[] = "Логин может состоять только из букв латинского алфавита, арабских цифр и знака подчеркивания";
        }
        if (strlen($data['username']) < 3 || strlen($data['username']) > 20) {
            $error[] = "Логин должен быть от 3-х до 40-а символов";
        }
        if (strlen($data['password']) < 8) {
            $error[] = "Пароль должен быть длинее 8-ми символов";
        }

        if (empty($error)) {
            $user['username'] = $data['username'];
            $user['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            $result = $this->user->createUserDB($user);

            if ($result === true) {
                $this->login();
            }else {
                $this->register($result);
            }
        } else {
            $this->register($error);
        }
    }

    public function tryLogin($data) {

        global $isAuth;
        $error = $this->validateData($data['username'], $data['password']);

        if (empty($error)) {
            $hash = $this->user->getHash($data['username']);
            if (password_verify($data['password'], $hash)) {
                $_SESSION['tryLogin'] = $data['username'];
                $_SESSION['password'] = $hash;
                $_SESSION['auth'] = true;
                setcookie('tryLogin', $data['username'], time() + 3600 * 24 * 7, "/");
                setcookie('password', $hash, time() + 3600 * 24 * 7, "/");
                $isAuth = true;
                header('Location: /mvc-framework/panel/');
            }
        } else {
            $this->login($error);
        }
    }

    public function isAuth() {
        if (isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
            return true;
        } elseif  (isset($_SESSION['tryLogin']) && isset($_SESSION['password'])) {
            return $this->chekUser($_SESSION['tryLogin'], $_SESSION['password']);
        } elseif (isset($_COOKIE['tryLogin']) && isset($_COOKIE['password'])) {
            return $this->chekUser($_COOKIE['tryLogin'], $_COOKIE['password']);
        }
        return false;
    }

    private function chekUser($login, $passwordHash) {
        $hash = $this->user->getHash($login);
        if ($hash === $passwordHash) {
            $_SESSION['auth'] = true;
            $_SESSION['tryLogin'] = $login;
            $_SESSION['password'] = $passwordHash;
            setcookie('tryLogin',$login, time()+3600*24*7, "/");
            setcookie('password', $passwordHash, time()+3600*24*7, "/");
            return true;
        } else {
            unset($_SESSION['tryLogin']);
            unset($_SESSION['password']);
            setcookie('tryLogin', '', time() - 1, "/");
            setcookie('password', '', time() - 1, "/");
            return false;
        }
    }

    private function validateData($username, $password)
    {
        $error = [];
        if (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
            $error[] = "Логин может состоять только из букв латинского алфавита, арабских цифр и знака подчеркивания";
        }
        if (strlen($username) < 3 || strlen($username) > 20) {
            $error[] = "Логин должен быть от 3-х до 40-а символов";
        }
        if (strlen($password) < 8) {
            $error[] = "Пароль должен быть длинее 8-ми символов";
        }

        return $error;
    }

    public function logout() {
        unset($_SESSION['auth']);
        unset($_SESSION['tryLogin']);
        unset($_SESSION['password']);
        setcookie('tryLogin', '', time() - 1, "/");
        setcookie('password', '', time() - 1, "/");
        header('Location: /mvc-framework/');
    }

    public function buildMenu() {
        $menu = [
            [
                'title' => 'Статьи',
                'url' => '/mvc-framework/panel/articles/all'
            ],
            [
                'title' => 'Выход',
                'url' => 'panel/logout'
            ]

        ];

        return $menu;
    }
}