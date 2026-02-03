<?php

class Habit
{
    private ?int $id;
    private int $userId;
    private string $title;
    private string $category;
    private string $frequency;
    private string $accentColor;
    private string $icon;
    private int $pointsPerDay;
    private int $streakDays;
    private ?string $createdAt;
    private ?string $updatedAt;
    private array $weekCompletions = [];

    public const CATEGORIES = ['studia', 'zdrowie', 'praca', 'osobiste'];
    public const FREQUENCIES = ['daily', '3x', 'custom'];
    public const COLORS = ['blue', 'green', 'purple', 'orange'];
    public const ICONS = ['study', 'fitness', 'meditate', 'check'];

    public function __construct(
        int $userId,
        string $title,
        string $category = 'zdrowie',
        string $frequency = 'daily',
        string $accentColor = 'blue',
        string $icon = 'check',
        int $pointsPerDay = 10,
        int $streakDays = 0,
        ?int $id = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->userId = $userId;
        $this->title = $title;
        $this->setCategory($category);
        $this->setFrequency($frequency);
        $this->setAccentColor($accentColor);
        $this->setIcon($icon);
        $this->pointsPerDay = $pointsPerDay;
        $this->streakDays = $streakDays;
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getTitle(): string { return $this->title; }
    public function getCategory(): string { return $this->category; }
    public function getFrequency(): string { return $this->frequency; }
    public function getAccentColor(): string { return $this->accentColor; }
    public function getIcon(): string { return $this->icon; }
    public function getPointsPerDay(): int { return $this->pointsPerDay; }
    public function getStreakDays(): int { return $this->streakDays; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function getWeekCompletions(): array { return $this->weekCompletions; }

    public function getFrequencyLabel(): string
    {
        return match($this->frequency) {
            'daily' => 'Codziennie',
            '3x' => '3× w tygodniu',
            'custom' => 'Własny',
            default => $this->frequency
        };
    }

    // Setters with validation
    public function setTitle(string $title): void { $this->title = $title; }
    
    public function setCategory(string $category): void
    {
        if (!in_array($category, self::CATEGORIES)) {
            $category = 'zdrowie';
        }
        $this->category = $category;
    }

    public function setFrequency(string $frequency): void
    {
        if (!in_array($frequency, self::FREQUENCIES)) {
            $frequency = 'daily';
        }
        $this->frequency = $frequency;
    }

    public function setAccentColor(string $color): void
    {
        if (!in_array($color, self::COLORS)) {
            $color = 'blue';
        }
        $this->accentColor = $color;
    }

    public function setIcon(string $icon): void
    {
        if (!in_array($icon, self::ICONS)) {
            $icon = 'check';
        }
        $this->icon = $icon;
    }

    public function setPointsPerDay(int $points): void { $this->pointsPerDay = max(0, $points); }
    public function setStreakDays(int $days): void { $this->streakDays = max(0, $days); }
    public function setWeekCompletions(array $completions): void { $this->weekCompletions = $completions; }

    public function incrementStreak(): void { $this->streakDays++; }
    public function resetStreak(): void { $this->streakDays = 0; }

    // Convert to array for JSON response
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'title' => $this->title,
            'category' => $this->category,
            'frequency' => $this->frequency,
            'frequencyLabel' => $this->getFrequencyLabel(),
            'accentColor' => $this->accentColor,
            'icon' => $this->icon,
            'pointsPerDay' => $this->pointsPerDay,
            'streakDays' => $this->streakDays,
            'week' => $this->weekCompletions,
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
            $row['category'] ?? 'zdrowie',
            $row['frequency'] ?? 'daily',
            $row['accent_color'] ?? 'blue',
            $row['icon'] ?? 'check',
            (int)($row['points_per_day'] ?? 10),
            (int)($row['streak_days'] ?? 0),
            (int)$row['id'],
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
