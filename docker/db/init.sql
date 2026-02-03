-- =============================================
-- STUDIFY - Database Schema
-- =============================================

-- Users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    student_id VARCHAR(50),
    university VARCHAR(200),
    bio TEXT,
    dark_mode BOOLEAN DEFAULT FALSE,
    email_notifications BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    enabled BOOLEAN DEFAULT TRUE
);

-- Tasks table
CREATE TABLE tasks (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL DEFAULT 'osobiste',
    priority VARCHAR(20) NOT NULL DEFAULT 'sredni',
    status VARCHAR(20) NOT NULL DEFAULT 'todo',
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Habits table
CREATE TABLE habits (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'zdrowie',
    frequency VARCHAR(50) NOT NULL DEFAULT 'daily',
    accent_color VARCHAR(20) DEFAULT 'blue',
    icon VARCHAR(50) DEFAULT 'check',
    points_per_day INTEGER DEFAULT 10,
    streak_days INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Habit completions (tracking daily completions)
CREATE TABLE habit_completions (
    id SERIAL PRIMARY KEY,
    habit_id INTEGER NOT NULL REFERENCES habits(id) ON DELETE CASCADE,
    completion_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(habit_id, completion_date)
);

-- Calendar events table
CREATE TABLE calendar_events (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL DEFAULT 'prywatne',
    event_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    all_day BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User categories (for filtering)
CREATE TABLE user_categories (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) DEFAULT 'blue',
    type VARCHAR(20) NOT NULL, -- 'task', 'habit', 'event'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, name, type)
);

-- Indexes for performance
CREATE INDEX idx_tasks_user_id ON tasks(user_id);
CREATE INDEX idx_tasks_due_date ON tasks(due_date);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_habits_user_id ON habits(user_id);
CREATE INDEX idx_habit_completions_habit_id ON habit_completions(habit_id);
CREATE INDEX idx_habit_completions_date ON habit_completions(completion_date);
CREATE INDEX idx_calendar_events_user_id ON calendar_events(user_id);
CREATE INDEX idx_calendar_events_date ON calendar_events(event_date);

-- =============================================
-- Sample Data
-- =============================================

-- Insert sample user
INSERT INTO users (firstname, lastname, email, password, student_id, university, bio, enabled)
VALUES (
    'Jan',
    'Kowalski',
    'j.kowalski@email.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '123456',
    'Uniwersytet Jagielloński',
    'Student informatyki, pasjonat programowania.',
    TRUE
);

-- Insert sample tasks
INSERT INTO tasks (user_id, title, description, category, priority, status, due_date) VALUES
(1, 'Przygotować prezentację z marketingu', 'Projekt grupowy, rozdział 4', 'studia', 'wysoki', 'todo', '2026-02-02'),
(1, 'Oddać projekt z programowania', 'Aplikacja webowa w PHP', 'studia', 'wysoki', 'done', '2026-01-29'),
(1, 'Zaplanować spotkanie zespołu', 'Omówić harmonogram projektu', 'praca', 'sredni', 'todo', '2026-02-05'),
(1, 'Zrobić zakupy spożywcze', 'Mleko, chleb, owoce', 'osobiste', 'niski', 'todo', '2026-02-01');

-- Insert sample habits
INSERT INTO habits (user_id, title, category, frequency, accent_color, icon, points_per_day, streak_days) VALUES
(1, 'Codzienna nauka (2h)', 'studia', 'daily', 'blue', 'study', 10, 12),
(1, 'Aktywność fizyczna', 'zdrowie', '3x', 'green', 'fitness', 20, 5),
(1, 'Wieczorna medytacja', 'zdrowie', 'daily', 'purple', 'meditate', 10, 31);

-- Insert sample habit completions (last week)
INSERT INTO habit_completions (habit_id, completion_date) VALUES
(1, CURRENT_DATE - INTERVAL '6 days'),
(1, CURRENT_DATE - INTERVAL '5 days'),
(1, CURRENT_DATE - INTERVAL '4 days'),
(1, CURRENT_DATE - INTERVAL '3 days'),
(2, CURRENT_DATE - INTERVAL '6 days'),
(2, CURRENT_DATE - INTERVAL '4 days'),
(3, CURRENT_DATE - INTERVAL '6 days'),
(3, CURRENT_DATE - INTERVAL '5 days'),
(3, CURRENT_DATE - INTERVAL '4 days'),
(3, CURRENT_DATE - INTERVAL '3 days'),
(3, CURRENT_DATE - INTERVAL '2 days'),
(3, CURRENT_DATE - INTERVAL '1 day'),
(3, CURRENT_DATE);

-- Insert sample calendar events
INSERT INTO calendar_events (user_id, title, description, category, event_date, start_time, end_time, all_day) VALUES
(1, 'Matematyka Dyskretna', 'Wykład', 'uczelnia', '2026-02-01', '08:00', '09:30', FALSE),
(1, 'Projekt UI/UX - Research', 'Przygotowanie makiet', 'projekt', '2026-02-04', '14:00', '16:00', FALSE),
(1, 'Oddanie Projektu', 'Deadline projektu PHP', 'uczelnia', '2026-02-09', NULL, NULL, TRUE),
(1, 'Język Angielski', 'Lektorat', 'uczelnia', '2026-02-11', '09:00', '10:30', FALSE),
(1, 'Spotkanie Koła Nauk.', 'Koło naukowe programistów', 'uczelnia', '2026-02-18', '17:30', '19:00', FALSE);

-- Insert default categories for user
INSERT INTO user_categories (user_id, name, color, type) VALUES
(1, 'studia', 'purple', 'task'),
(1, 'praca', 'teal', 'task'),
(1, 'osobiste', 'orange', 'task'),
(1, 'uczelnia', 'blue', 'event'),
(1, 'prywatne', 'purple', 'event'),
(1, 'projekt', 'green', 'event'),
(1, 'sport', 'orange', 'event'),
(1, 'studia', 'blue', 'habit'),
(1, 'zdrowie', 'green', 'habit');
