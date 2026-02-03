<?php

require_once 'Repository.php';
require_once __DIR__ . '/../models/User.php';

class UserRepository extends Repository
{
    /**
     * Get all users
     */
    public function getUsers(): array
    {
        $query = $this->database->connect()->prepare('SELECT * FROM users');
        $query->execute();

        $users = [];
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $users[] = User::fromArray($row);
        }
        return $users;
    }

    /**
     * Get user by email (returns array for login compatibility)
     */
    public function getUserByEmail(string $email): ?array
    {
        $query = $this->database->connect()->prepare(
            'SELECT * FROM users WHERE email = :email'
        );
        $query->bindParam(':email', $email);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get user entity by email
     */
    public function findByEmail(string $email): ?User
    {
        $row = $this->getUserByEmail($email);
        return $row ? User::fromArray($row) : null;
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $userId): ?User
    {
        $query = $this->database->connect()->prepare(
            'SELECT * FROM users WHERE id = :id'
        );
        $query->bindParam(':id', $userId, PDO::PARAM_INT);
        $query->execute();

        $row = $query->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromArray($row) : null;
    }

    /**
     * Create a new user
     */
    public function createUser(
        string $email,
        string $hashedPassword,
        string $firstName,
        string $lastName,
        string $bio = ''
    ): ?int {
        $query = $this->database->connect()->prepare('
            INSERT INTO users (firstname, lastname, email, password, bio)
            VALUES (:firstname, :lastname, :email, :password, :bio)
            RETURNING id
        ');
        $query->bindParam(':firstname', $firstName);
        $query->bindParam(':lastname', $lastName);
        $query->bindParam(':email', $email);
        $query->bindParam(':password', $hashedPassword);
        $query->bindParam(':bio', $bio);

        if ($query->execute()) {
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['id'] : null;
        }
        return null;
    }

    /**
     * Update user profile
     */
    public function updateProfile(User $user): bool
    {
        $query = $this->database->connect()->prepare('
            UPDATE users SET 
                firstname = :firstname,
                lastname = :lastname,
                email = :email,
                student_id = :student_id,
                university = :university,
                bio = :bio,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ');

        $id = $user->getId();
        $firstname = $user->getFirstname();
        $lastname = $user->getLastname();
        $email = $user->getEmail();
        $studentId = $user->getStudentId();
        $university = $user->getUniversity();
        $bio = $user->getBio();

        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->bindParam(':firstname', $firstname);
        $query->bindParam(':lastname', $lastname);
        $query->bindParam(':email', $email);
        $query->bindParam(':student_id', $studentId);
        $query->bindParam(':university', $university);
        $query->bindParam(':bio', $bio);

        return $query->execute();
    }

    /**
     * Update user settings (dark mode, notifications)
     */
    public function updateSettings(int $userId, bool $darkMode, bool $emailNotifications): bool
    {
        $query = $this->database->connect()->prepare('
            UPDATE users SET 
                dark_mode = :dark_mode,
                email_notifications = :email_notifications,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ');

        $query->bindParam(':id', $userId, PDO::PARAM_INT);
        $query->bindParam(':dark_mode', $darkMode, PDO::PARAM_BOOL);
        $query->bindParam(':email_notifications', $emailNotifications, PDO::PARAM_BOOL);

        return $query->execute();
    }

    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $query = $this->database->connect()->prepare('
            UPDATE users SET 
                password = :password,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ');

        $query->bindParam(':id', $userId, PDO::PARAM_INT);
        $query->bindParam(':password', $hashedPassword);

        return $query->execute();
    }

    /**
     * Check if email exists (excluding a specific user)
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        if ($excludeUserId) {
            $query = $this->database->connect()->prepare(
                'SELECT id FROM users WHERE email = :email AND id != :id'
            );
            $query->bindParam(':email', $email);
            $query->bindParam(':id', $excludeUserId, PDO::PARAM_INT);
        } else {
            $query = $this->database->connect()->prepare(
                'SELECT id FROM users WHERE email = :email'
            );
            $query->bindParam(':email', $email);
        }
        $query->execute();

        return $query->fetch() !== false;
    }
}