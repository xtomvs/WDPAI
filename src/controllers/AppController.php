<?php


class AppController {

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function isGet(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'GET';
    }

    protected function isPost(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'POST';
    }

    protected function isPut(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'PUT';
    }

    protected function isPatch(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'PATCH';
    }

    protected function isDelete(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'DELETE';
    }

    protected function isApiRequest(): bool
    {
        $path = trim($_SERVER['REQUEST_URI'], '/');
        $path = parse_url($path, PHP_URL_PATH);
        return str_starts_with($path, 'api/');
    }

    protected function render(string $template = null, array $variables = [])
    {
        $templatePath = 'public/views/'. $template.'.html';
        $templatePath404 = 'public/views/404.html';
        $output = "";
                 
        if(file_exists($templatePath)){
            extract($variables);
            
            ob_start();
            include $templatePath;
            $output = ob_get_clean();
        } else {
            ob_start();
            include $templatePath404;
            $output = ob_get_clean();
        }
        echo $output;
    }

    protected function requireLogin(): void
    {
        if (empty($_SESSION['user_id'])) {
            if ($this->isApiRequest()) {
                // For API requests, return JSON error
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Wymagane logowanie'
                ], JSON_UNESCAPED_UNICODE);
                exit();
            } else {
                // For web requests, redirect to login
                $url = "http://$_SERVER[HTTP_HOST]";
                header("Location: {$url}/login");
                exit();
            }
        }
    }

    protected function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    protected function getCurrentUser(): array
    {
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'firstname' => $_SESSION['user_firstname'] ?? '',
            'lastname' => $_SESSION['user_lastname'] ?? '',
            'email' => $_SESSION['user_email'] ?? ''
        ];
    }
}