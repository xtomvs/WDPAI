<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';

// TODO Controllery 
//singleton do repo/bazy danych
//regex do pobrania id
//zamiast switch case IN_ARRAY($path, Routing::$roles)
//user session

class Routing {

    public static $routes = [
        "login" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
         "register" => [
            "controller" => "SecurityController",
            "action" => "register"
        ],
        "dashboard" => [
            "controller" => "DashboardController",
            "action" => "index"
        ],
        "search-cards" => [
            "controller" => "DashboardController",
            "action" => "search"
        ]
    ];

    public static function run(string $path) {
        switch($path) {
            case 'login':
            case 'register':
            case 'dashboard':
            case 'search-cards':
                $controller = Routing::$routes[$path]["controller"];
                $action = Routing::$routes[$path]["action"];
                $id = null;

                $controllerObj = new $controller;
                $controllerObj->$action($id);
                break;
            default:
                include 'public/views/404.html';
                break;
        }
    }
}
