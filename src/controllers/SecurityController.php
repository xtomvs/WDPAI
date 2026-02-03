<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';

class SecurityController extends AppController {

    private $userRepository;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
    }

    public function login() {

        if (!$this->isPost()) {
            return $this->render('login');
        }

        $email = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';

        if (empty($email) || empty($password)) {
            return $this->render('login', ['messages' => '<span class="message-error">Wypełnij wszystkie pola!</span>']);
        }

        $userRow = $this->userRepository->getUserByEmail($email);

        if (!$userRow) {
            return $this->render('login', ['messages' => '<span class="message-error">Użytkownik nie znaleziony!</span>']);
        }

        if (!password_verify($password, $userRow['password'])) {
            return $this->render('login', ['messages' => '<span class="message-error">Błędne hasło!</span>']);
        }


        session_regenerate_id(true);
        $_SESSION['user_id'] = $userRow['id'] ?? null;
        $_SESSION['user_email'] = $userRow['email'] ?? $email;
        $_SESSION['user_firstname'] = $userRow['firstname'] ?? null;
        $_SESSION['is_logged_in'] = true;


        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dashboard");
    }

    public function register() {

        if (!$this->isPost()) {
            return $this->render('register');
        }

        $email = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';
        $password2 = $_POST["password2"] ?? '';
        $firstName = $_POST["firstName"] ?? '';
        $lastName = $_POST["lastName"] ?? '';

        if (empty($email) || empty($password) || empty($password2) || empty($firstName) || empty($lastName)) {
            return $this->render('register', ['messages' => 'Wypełnij wszystkie pola!']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('register', ['messages' => '<span class="message-error">Niepoprawny adres e-mail!</span>']);
        }

        if (strlen($password) < 6) {
            return $this->render('register', ['messages' => '<span class="message-error">Hasło musi mieć minimum 6 znaków!</span>']);
        }

        $namePattern = '/^[A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż]+([ -][A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż]+)*$/u';
        if (mb_strlen(trim($firstName)) < 2 || !preg_match($namePattern, $firstName)) {
            return $this->render('register', ['messages' => '<span class="message-error">Niepoprawne imię!</span>']);
        }

        if (mb_strlen(trim($lastName)) < 2 || !preg_match($namePattern, $lastName)) {
            return $this->render('register', ['messages' => '<span class="message-error">Niepoprawne nazwisko!</span>']);
        }

        if ($password !== $password2) {
            return $this->render('register', ['messages' => '<span class="message-error">Hasła nie są identyczne!</span>']);
        }

        $existingUser = $this->userRepository->getUserByEmail($email);
        if ($existingUser) {
            return $this->render('register', ['messages' => '<span class="message-error">Użytkownik z tym emailem już istnieje!</span>']);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $this->userRepository->createUser(
            $email, $hashedPassword, $firstName, $lastName
        );

        return $this->render('login', ['messages' => '<span class="message-success">Użytkownik zarejestrowany pomyślnie, zaloguj się!</span>']);
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
    }
}