<?php

class Task
{
    private ?int $id;
    private int $userId;
    private string $title;
    private ?string $description;
    private string $category;
    private string $priority;
    private string $status;
    private ?string $dueDate;
    private ?string $createdAt;
    private ?string $updatedAt;

    public const CATEGORIES = ['studia', 'praca', 'osobiste'];
    public const PRIORITIES = ['wysoki', 'sredni', 'niski'];
    public const STATUSES = ['todo', 'done'];

    public function __construct(
        int $userId,
        string $title,
        ?string $description = null,
        string $category = 'osobiste',
        string $priority = 'sredni',
        string $status = 'todo',
        ?string $dueDate = null,
        ?int $id = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->userId = $userId;
        $this->title = $title;
        $this->description = $description;
        $this->setCategory($category);
        $this->setPriority($priority);
        $this->setStatus($status);
        $this->dueDate = $dueDate;
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): ?string { return $this->description; }
    public function getCategory(): string { return $this->category; }
    public function getPriority(): string { return $this->priority; }
    public function getStatus(): string { return $this->status; }
    public function getDueDate(): ?string { return $this->dueDate; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }

    public function isDone(): bool { return $this->status === 'done'; }

    // Setters with validation
    public function setTitle(string $title): void { $this->title = $title; }
    public function setDescription(?string $description): void { $this->description = $description; }
    
    public function setCategory(string $category): void
    {
        if (!in_array($category, self::CATEGORIES)) {
            $category = 'osobiste';
        }
        $this->category = $category;
    }

    public function setPriority(string $priority): void
    {
        if (!in_array($priority, self::PRIORITIES)) {
            $priority = 'sredni';
        }
        $this->priority = $priority;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, self::STATUSES)) {
            $status = 'todo';
        }
        $this->status = $status;
    }

    public function setDueDate(?string $dueDate): void { $this->dueDate = $dueDate; }

    public function markAsDone(): void { $this->status = 'done'; }
    public function markAsTodo(): void { $this->status = 'todo'; }

    // Convert to array for JSON response
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status,
            'dueDate' => $this->dueDate,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt
        ];
    }

    // Create from database row
    public static function fromArray(array $row): self
    {
        return new self(
            (int)$row['user_id'],
            $row['title'],
            $row['description'] ?? null,
            $row['category'] ?? 'osobiste',
            $row['priority'] ?? 'sredni',
            $row['status'] ?? 'todo',
            $row['due_date'] ?? null,
            (int)$row['id'],
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
