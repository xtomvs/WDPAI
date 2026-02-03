<?php

class CalendarEvent
{
    private ?int $id;
    private int $userId;
    private string $title;
    private ?string $description;
    private string $category;
    private string $eventDate;
    private ?string $startTime;
    private ?string $endTime;
    private bool $allDay;
    private ?string $createdAt;
    private ?string $updatedAt;

    public const CATEGORIES = ['uczelnia', 'prywatne', 'projekt', 'sport'];
    public const CATEGORY_COLORS = [
        'uczelnia' => 'blue',
        'prywatne' => 'purple',
        'projekt' => 'green',
        'sport' => 'orange'
    ];

    public function __construct(
        int $userId,
        string $title,
        string $eventDate,
        ?string $description = null,
        string $category = 'prywatne',
        ?string $startTime = null,
        ?string $endTime = null,
        bool $allDay = false,
        ?int $id = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->userId = $userId;
        $this->title = $title;
        $this->eventDate = $eventDate;
        $this->description = $description;
        $this->setCategory($category);
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->allDay = $allDay;
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
    public function getEventDate(): string { return $this->eventDate; }
    public function getStartTime(): ?string { return $this->startTime; }
    public function getEndTime(): ?string { return $this->endTime; }
    public function isAllDay(): bool { return $this->allDay; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }

    public function getCategoryColor(): string
    {
        return self::CATEGORY_COLORS[$this->category] ?? 'blue';
    }

    public function getTimeRange(): string
    {
        if ($this->allDay) {
            return 'Cały dzień';
        }
        if ($this->startTime && $this->endTime) {
            return substr($this->startTime, 0, 5) . ' - ' . substr($this->endTime, 0, 5);
        }
        if ($this->startTime) {
            return substr($this->startTime, 0, 5);
        }
        return '';
    }

    // Setters with validation
    public function setTitle(string $title): void { $this->title = $title; }
    public function setDescription(?string $description): void { $this->description = $description; }
    public function setEventDate(string $date): void { $this->eventDate = $date; }
    public function setStartTime(?string $time): void { $this->startTime = $time; }
    public function setEndTime(?string $time): void { $this->endTime = $time; }
    public function setAllDay(bool $allDay): void { $this->allDay = $allDay; }

    public function setCategory(string $category): void
    {
        if (!in_array($category, self::CATEGORIES)) {
            $category = 'prywatne';
        }
        $this->category = $category;
    }

    // Convert to array for JSON response
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'categoryColor' => $this->getCategoryColor(),
            'eventDate' => $this->eventDate,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'timeRange' => $this->getTimeRange(),
            'allDay' => $this->allDay,
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
            $row['event_date'],
            $row['description'] ?? null,
            $row['category'] ?? 'prywatne',
            $row['start_time'] ?? null,
            $row['end_time'] ?? null,
            (bool)($row['all_day'] ?? false),
            (int)$row['id'],
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
