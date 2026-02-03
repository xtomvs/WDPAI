<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/HabitsController.php';
require_once 'src/controllers/CalendarController.php';
require_once 'src/controllers/TasksController.php';
require_once 'src/controllers/SettingsController.php';
require_once 'src/controllers/TaskApiController.php';
require_once 'src/controllers/HabitApiController.php';
require_once 'src/controllers/CalendarApiController.php';
require_once 'src/controllers/SettingsApiController.php';

class Routing {

    // View routes
    public static $routes = [
        "login" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
        "register" => [
            "controller" => "SecurityController",
            "action" => "register"
        ],
        "logout" => [
            "controller" => "SecurityController",
            "action" => "logout"
        ],
        "dashboard" => [
            "controller" => "DashboardController",
            "action" => "index"
        ],
        "habits" => [
            "controller" => "HabitsController",
            "action" => "index"
        ],
        "calendar" => [
            "controller" => "CalendarController",
            "action" => "index"
        ],
        "tasks" => [
            "controller" => "TasksController",
            "action" => "index"
        ],
        "settings" => [
            "controller" => "SettingsController",
            "action" => "index"
        ],
        "search-cards" => [
            "controller" => "DashboardController",
            "action" => "search"
        ]
    ];

    // API routes with HTTP method support
    public static $apiRoutes = [
        // Tasks API
        'api/tasks' => [
            'GET' => ['controller' => 'TaskApiController', 'action' => 'getTasks'],
            'POST' => ['controller' => 'TaskApiController', 'action' => 'createTask']
        ],
        'api/tasks/stats' => [
            'GET' => ['controller' => 'TaskApiController', 'action' => 'getStats']
        ],
        
        // Habits API
        'api/habits' => [
            'GET' => ['controller' => 'HabitApiController', 'action' => 'getHabits'],
            'POST' => ['controller' => 'HabitApiController', 'action' => 'createHabit']
        ],
        'api/habits/stats' => [
            'GET' => ['controller' => 'HabitApiController', 'action' => 'getStats']
        ],
        
        // Events (Calendar) API
        'api/events' => [
            'GET' => ['controller' => 'CalendarApiController', 'action' => 'getEvents'],
            'POST' => ['controller' => 'CalendarApiController', 'action' => 'createEvent']
        ],
        'api/events/month' => [
            'GET' => ['controller' => 'CalendarApiController', 'action' => 'getEventsByMonth']
        ],
        'api/events/today' => [
            'GET' => ['controller' => 'CalendarApiController', 'action' => 'getTodayEvents']
        ],
        'api/events/upcoming' => [
            'GET' => ['controller' => 'CalendarApiController', 'action' => 'getUpcomingEvents']
        ],
        
        // Settings API
        'api/settings/profile' => [
            'GET' => ['controller' => 'SettingsApiController', 'action' => 'getProfile'],
            'PUT' => ['controller' => 'SettingsApiController', 'action' => 'updateProfile']
        ],
        'api/settings/preferences' => [
            'PUT' => ['controller' => 'SettingsApiController', 'action' => 'updatePreferences']
        ],
        'api/settings/password' => [
            'PUT' => ['controller' => 'SettingsApiController', 'action' => 'changePassword']
        ]
    ];

    // Dynamic API routes with ID parameter (regex patterns)
    public static $dynamicApiRoutes = [
        // Tasks with ID
        '#^api/tasks/(\d+)$#' => [
            'GET' => ['controller' => 'TaskApiController', 'action' => 'getTask'],
            'PUT' => ['controller' => 'TaskApiController', 'action' => 'updateTask'],
            'DELETE' => ['controller' => 'TaskApiController', 'action' => 'deleteTask']
        ],
        '#^api/tasks/(\d+)/status$#' => [
            'PATCH' => ['controller' => 'TaskApiController', 'action' => 'toggleStatus']
        ],
        
        // Habits with ID
        '#^api/habits/(\d+)$#' => [
            'GET' => ['controller' => 'HabitApiController', 'action' => 'getHabit'],
            'PUT' => ['controller' => 'HabitApiController', 'action' => 'updateHabit'],
            'DELETE' => ['controller' => 'HabitApiController', 'action' => 'deleteHabit']
        ],
        '#^api/habits/(\d+)/toggle$#' => [
            'POST' => ['controller' => 'HabitApiController', 'action' => 'toggleCompletion']
        ],
        
        // Events with ID
        '#^api/events/(\d+)$#' => [
            'GET' => ['controller' => 'CalendarApiController', 'action' => 'getEvent'],
            'PUT' => ['controller' => 'CalendarApiController', 'action' => 'updateEvent'],
            'DELETE' => ['controller' => 'CalendarApiController', 'action' => 'deleteEvent']
        ]
    ];

    public static function run(string $path) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Handle API routes first
        if (str_starts_with($path, 'api/')) {
            self::handleApiRoute($path, $method);
            return;
        }

        // Handle view routes
        if (array_key_exists($path, self::$routes)) {
            $controller = self::$routes[$path]["controller"];
            $action = self::$routes[$path]["action"];
            
            $controllerObj = new $controller;
            $controllerObj->$action();
        } else {
            http_response_code(404);
            include 'public/views/404.html';
        }
    }

    private static function handleApiRoute(string $path, string $method): void {
        // Check static API routes
        if (isset(self::$apiRoutes[$path])) {
            if (isset(self::$apiRoutes[$path][$method])) {
                $route = self::$apiRoutes[$path][$method];
                $controller = new $route['controller'];
                $controller->{$route['action']}();
                return;
            }
            self::methodNotAllowed();
            return;
        }

        // Check dynamic API routes (with ID parameter)
        foreach (self::$dynamicApiRoutes as $pattern => $methods) {
            if (preg_match($pattern, $path, $matches)) {
                if (isset($methods[$method])) {
                    // Pass ID via query string
                    $_GET['id'] = $matches[1];
                    $route = $methods[$method];
                    $controller = new $route['controller'];
                    $controller->{$route['action']}();
                    return;
                }
                self::methodNotAllowed();
                return;
            }
        }

        // API route not found
        self::apiNotFound();
    }

    private static function methodNotAllowed(): void {
        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Metoda niedozwolona']);
        exit;
    }

    private static function apiNotFound(): void {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Endpoint nie znaleziony']);
        exit;
    }
}
