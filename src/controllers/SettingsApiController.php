<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class SettingsApiController extends AppController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        parent::__construct();
        $this->userRepository = new UserRepository();
    }

    /**
     * Send JSON response
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Get JSON input from request body
     */
    private function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    /**
     * GET /api/settings/profile - Get current user profile
     */
    public function getProfile(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];

        $user = $this->userRepository->getUserById($userId);

        if (!$user) {
            $this->jsonResponse(['success' => false, 'message' => 'Użytkownik nie znaleziony'], 404);
        }

        $this->jsonResponse([
            'success' => true,
            'data' => $user->toArray()
        ]);
    }

    /**
     * PUT /api/settings/profile - Update user profile
     */
    public function updateProfile(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $data = $this->getJsonInput();

        $user = $this->userRepository->getUserById($userId);

        if (!$user) {
            $this->jsonResponse(['success' => false, 'message' => 'Użytkownik nie znaleziony'], 404);
        }

        // Update user properties
        if (isset($data['firstname']) && !empty(trim($data['firstname']))) {
            $user->setFirstname(trim($data['firstname']));
        }
        if (isset($data['lastname']) && !empty(trim($data['lastname']))) {
            $user->setLastname(trim($data['lastname']));
        }
        if (isset($data['email']) && !empty(trim($data['email']))) {
            $newEmail = trim($data['email']);
            // Check if email is already taken by another user
            if ($newEmail !== $user->getEmail() && $this->userRepository->emailExists($newEmail, $userId)) {
                $this->jsonResponse(['success' => false, 'message' => 'Ten email jest już zajęty'], 400);
            }
            $user->setEmail($newEmail);
        }
        if (isset($data['studentId'])) {
            $user->setStudentId($data['studentId'] ?: null);
        }
        if (isset($data['university'])) {
            $user->setUniversity($data['university'] ?: null);
        }
        if (isset($data['bio'])) {
            $user->setBio($data['bio'] ?: null);
        }

        if ($this->userRepository->updateProfile($user)) {
            // Update session data
            $_SESSION['user_firstname'] = $user->getFirstname();
            $_SESSION['user_lastname'] = $user->getLastname();
            $_SESSION['user_email'] = $user->getEmail();

            $this->jsonResponse([
                'success' => true,
                'message' => 'Profil zaktualizowany',
                'data' => $user->toArray()
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Błąd aktualizacji profilu'], 500);
        }
    }

    /**
     * PUT /api/settings/preferences - Update user preferences (dark mode, notifications)
     */
    public function updatePreferences(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $data = $this->getJsonInput();

        $darkMode = isset($data['darkMode']) ? (bool)$data['darkMode'] : false;
        $emailNotifications = isset($data['emailNotifications']) ? (bool)$data['emailNotifications'] : true;

        if ($this->userRepository->updateSettings($userId, $darkMode, $emailNotifications)) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Preferencje zaktualizowane',
                'data' => [
                    'darkMode' => $darkMode,
                    'emailNotifications' => $emailNotifications
                ]
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Błąd aktualizacji preferencji'], 500);
        }
    }

    /**
     * PUT /api/settings/password - Change user password
     */
    public function changePassword(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $data = $this->getJsonInput();

        // Validation
        if (empty($data['currentPassword'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Obecne hasło jest wymagane'], 400);
        }
        if (empty($data['newPassword'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Nowe hasło jest wymagane'], 400);
        }
        if (strlen($data['newPassword']) < 6) {
            $this->jsonResponse(['success' => false, 'message' => 'Hasło musi mieć co najmniej 6 znaków'], 400);
        }
        if (isset($data['confirmPassword']) && $data['newPassword'] !== $data['confirmPassword']) {
            $this->jsonResponse(['success' => false, 'message' => 'Hasła nie są identyczne'], 400);
        }

        $user = $this->userRepository->getUserById($userId);

        if (!$user) {
            $this->jsonResponse(['success' => false, 'message' => 'Użytkownik nie znaleziony'], 404);
        }

        // Verify current password
        if (!password_verify($data['currentPassword'], $user->getPassword())) {
            $this->jsonResponse(['success' => false, 'message' => 'Nieprawidłowe obecne hasło'], 400);
        }

        $hashedPassword = password_hash($data['newPassword'], PASSWORD_BCRYPT);

        if ($this->userRepository->updatePassword($userId, $hashedPassword)) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Hasło zostało zmienione'
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Błąd zmiany hasła'], 500);
        }
    }
}
