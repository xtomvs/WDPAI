<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';

class SecurityController extends AppController {

    private $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    public function login() {

        if (!$this->isPost()) {
            return $this->render('login');
        }

        $email = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';

        if (empty($email) || empty($password)) {
            return $this->render('login', ['messages' => 'Fill all fields']);
        }

        $userRow = $this->userRepository->getUserByEmail($email);

        if (!$userRow) {
            return $this->render('login', ['messages' => 'User not found']);
        }

        if (!password_verify($password, $userRow['password'])) {
            return $this->render('login', ['messages' => 'Wrong password']);
        }

        //TODO create user session, cookie, token


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
            return $this->render('register', ['messages' => 'Fill all fields']);
        }

        if ($password !== $password2) {
            return $this->render('register', ['messages' => 'Passwords do not match']);
        }

        //TODO chceck if user with this email already exists

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $this->userRepository->createUser(
            $email, $hashedPassword, $firstName, $lastName
        );

        return $this->render('login', ['messages' => 'User registered successfully, please login!']);
    }
}