<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';

// TODO Controllery 
//singleton
//regex do pobrania id

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
        ]
    ];

    public static function run(string $path) {
        switch($path) {
            case 'login':
            case 'register':
            case 'dashboard':
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
