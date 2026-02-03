<?php

class User
{
    private ?int $id;
    private string $firstname;
    private string $lastname;
    private string $email;
    private string $password;
    private ?string $studentId;
    private ?string $university;
    private ?string $bio;
    private bool $darkMode;
    private bool $emailNotifications;
    private ?string $createdAt;
    private ?string $updatedAt;
    private bool $enabled;

    public function __construct(
        string $firstname,
        string $lastname,
        string $email,
        string $password,
        ?string $studentId = null,
        ?string $university = null,
        ?string $bio = null,
        bool $darkMode = false,
        bool $emailNotifications = true,
        bool $enabled = true,
        ?int $id = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->password = $password;
        $this->studentId = $studentId;
        $this->university = $university;
        $this->bio = $bio;
        $this->darkMode = $darkMode;
        $this->emailNotifications = $emailNotifications;
        $this->enabled = $enabled;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getFirstname(): string { return $this->firstname; }
    public function getLastname(): string { return $this->lastname; }
    public function getFullName(): string { return $this->firstname . ' ' . $this->lastname; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getStudentId(): ?string { return $this->studentId; }
    public function getUniversity(): ?string { return $this->university; }
    public function getBio(): ?string { return $this->bio; }
    public function isDarkMode(): bool { return $this->darkMode; }
    public function hasEmailNotifications(): bool { return $this->emailNotifications; }
    public function isEnabled(): bool { return $this->enabled; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }

    // Setters
    public function setFirstname(string $firstname): void { $this->firstname = $firstname; }
    public function setLastname(string $lastname): void { $this->lastname = $lastname; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setPassword(string $password): void { $this->password = $password; }
    public function setStudentId(?string $studentId): void { $this->studentId = $studentId; }
    public function setUniversity(?string $university): void { $this->university = $university; }
    public function setBio(?string $bio): void { $this->bio = $bio; }
    public function setDarkMode(bool $darkMode): void { $this->darkMode = $darkMode; }
    public function setEmailNotifications(bool $emailNotifications): void { $this->emailNotifications = $emailNotifications; }

    // Convert to array for JSON response
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'fullName' => $this->getFullName(),
            'email' => $this->email,
            'studentId' => $this->studentId,
            'university' => $this->university,
            'bio' => $this->bio,
            'darkMode' => $this->darkMode,
            'emailNotifications' => $this->emailNotifications,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt
        ];
    }

    // Create from database row
    public static function fromArray(array $row): self
    {
        return new self(
            $row['firstname'],
            $row['lastname'],
            $row['email'],
            $row['password'],
            $row['student_id'] ?? null,
            $row['university'] ?? null,
            $row['bio'] ?? null,
            (bool)($row['dark_mode'] ?? false),
            (bool)($row['email_notifications'] ?? true),
            (bool)($row['enabled'] ?? true),
            (int)$row['id'],
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
