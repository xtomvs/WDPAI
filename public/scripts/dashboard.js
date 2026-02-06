/**
 * Dashboard JavaScript - Handles dynamic data loading and display
 */

document.addEventListener('DOMContentLoaded', () => {
    initDashboard();
});

// Store data for week view
let habitsData = [];
let tasksData = [];

async function initDashboard() {
    // Load all data in parallel
    await Promise.all([
        loadUserProfile(),
        loadTasks(),
        loadHabitsForDashboard(),
        loadStats()
    ]);
    
    // Initialize week view with habits data
    initWeekView();
}

/**
 * Load user profile data
 */
async function loadUserProfile() {
    try {
        const response = await fetch('/api/settings/profile');
        const result = await response.json();
        
        if (result.success && result.data) {
            const user = result.data;
            const fullName = `${user.firstname || ''} ${user.lastname || ''}`.trim() || 'Użytkownik';
            
            // Update greeting
            const greeting = document.getElementById('greeting');
            if (greeting) {
                greeting.textContent = `Cześć, ${user.firstname || 'tam'}! 👋`;
            }
            
            // Update sidebar profile
            const userName = document.getElementById('user-name');
            const userEmail = document.getElementById('user-email');
            if (userName) userName.textContent = fullName;
            if (userEmail) userEmail.textContent = user.email || '';
        }
    } catch (error) {
        console.error('Failed to load user profile:', error);
    }
}

/**
 * Load tasks and display upcoming ones
 */
async function loadTasks() {
    try {
        const response = await fetch('/api/tasks');
        const result = await response.json();
        
        if (result.success && result.data) {
            tasksData = result.data;
            renderTasks(result.data);
        }
    } catch (error) {
        console.error('Failed to load tasks:', error);
    }
}

/**
 * Render task cards
 */
function renderTasks(tasks) {
    const container = document.getElementById('tasks-container');
    if (!container) return;
    
    // Keep the "add new" button
    const addButton = container.querySelector('.card--add');
    container.innerHTML = '';
    
    // Filter and sort tasks - show only pending tasks, sorted by due date
    const pendingTasks = tasks
        .filter(task => task.status !== 'done')
        .sort((a, b) => {
            if (!a.dueDate) return 1;
            if (!b.dueDate) return -1;
            return new Date(a.dueDate) - new Date(b.dueDate);
        })
        .slice(0, 3); // Show max 3 tasks
    
    if (pendingTasks.length === 0) {
        container.innerHTML = `
            <div class="card card--empty">
                <span class="material-symbols-outlined">check_circle</span>
                <p>Brak zaplanowanych zadań</p>
            </div>
        `;
    } else {
        pendingTasks.forEach(task => {
            container.insertAdjacentHTML('beforeend', createTaskCard(task));
        });
    }
    
    // Add back the "add new" button
    if (addButton) {
        container.appendChild(addButton);
    } else {
        container.insertAdjacentHTML('beforeend', `
            <button class="card card--add" type="button" onclick="window.location.href='/tasks'">
                <span class="material-symbols-outlined">add_circle</span>
                <span>Dodaj nowe</span>
            </button>
        `);
    }
}

/**
 * Create a task card HTML
 */
function createTaskCard(task) {
    const categoryColors = {
        'studia': 'purple',
        'praca': 'orange',
        'osobiste': 'teal',
        'zdrowie': 'pink',
        'inne': 'teal'
    };
    
    const categoryLabels = {
        'studia': 'Studia',
        'praca': 'Praca',
        'osobiste': 'Osobiste',
        'zdrowie': 'Zdrowie',
        'inne': 'Inne'
    };
    
    const color = categoryColors[task.category] || 'teal';
    const label = categoryLabels[task.category] || 'Inne';
    const dueText = formatDueDate(task.dueDate);
    const metaClass = isOverdue(task.dueDate) ? 'meta--danger' : 'meta--muted';
    
    return `
        <article class="card" data-task-id="${task.id}">
            <div class="card__top">
                <span class="tag tag--${color}">${label}</span>
                <a href="/tasks" class="icon-ghost" aria-label="Edytuj">
                    <span class="material-symbols-outlined">edit</span>
                </a>
            </div>
            <h3 class="card__title">${escapeHtml(task.title)}</h3>
            <p class="card__desc">${escapeHtml(task.description || '')}</p>
            ${task.dueDate ? `
                <div class="meta ${metaClass}">
                    <span class="material-symbols-outlined">schedule</span>
                    <span>${dueText}</span>
                </div>
            ` : ''}
        </article>
    `;
}

/**
 * Load habits and display today's habits
 */
async function loadHabitsForDashboard() {
    try {
        const response = await fetch('/api/habits');
        const result = await response.json();
        
        if (result.success && result.data) {
            habitsData = result.data;
            renderHabits(result.data);
        }
    } catch (error) {
        console.error('Failed to load habits:', error);
    }
}

/**
 * Render habits list
 */
function renderHabits(habits) {
    const container = document.getElementById('habits-container');
    if (!container) return;
    
    // Keep the edit button
    const editButton = container.querySelector('.habits__edit');
    container.innerHTML = '';
    
    // Filter habits for today based on frequency
    const todayHabits = habits.filter(habit => isHabitForToday(habit)).slice(0, 4);
    
    if (todayHabits.length === 0) {
        container.innerHTML = `
            <div class="habit habit--empty">
                <span class="material-symbols-outlined">self_improvement</span>
                <div class="habit__text">
                    <div class="habit__title habit__title--plain">Brak nawyków na dziś</div>
                    <div class="habit__sub">Dodaj nowe nawyki</div>
                </div>
            </div>
        `;
    } else {
        todayHabits.forEach(habit => {
            container.insertAdjacentHTML('beforeend', createHabitItem(habit));
        });
    }
    
    // Add back the edit button
    if (editButton) {
        container.appendChild(editButton);
    } else {
        container.insertAdjacentHTML('beforeend', `
            <a href="/habits" class="habits__edit">Edytuj listę nawyków</a>
        `);
    }
    
    // Add click handlers for toggling habits
    container.querySelectorAll('.habit[data-habit-id]').forEach(habitEl => {
        habitEl.addEventListener('click', () => toggleHabit(habitEl));
    });
}

/**
 * Create a habit item HTML
 */
function createHabitItem(habit) {
    const isDone = isHabitCompletedToday(habit);
    const doneClass = isDone ? 'habit--done' : '';
    const checkClass = isDone ? 'check--done' : '';
    
    const frequencyLabels = {
        'daily': 'Codziennie',
        'weekdays': 'Dni robocze',
        'weekends': 'Weekendy'
    };
    
    return `
        <div class="habit ${doneClass}" data-habit-id="${habit.id}">
            <div class="check ${checkClass}" aria-hidden="true">
                ${isDone ? '<span class="material-symbols-outlined">check</span>' : ''}
            </div>
            <div class="habit__text">
                <div class="habit__title ${isDone ? '' : 'habit__title--plain'}">${escapeHtml(habit.title)}</div>
                <div class="habit__sub">${isDone ? 'Zrealizowano' : (frequencyLabels[habit.frequency] || 'Do zrobienia')}</div>
            </div>
        </div>
    `;
}

/**
 * Check if habit is completed today based on week data
 */
function isHabitCompletedToday(habit) {
    const today = new Date().toISOString().split('T')[0];
    if (habit.week && Array.isArray(habit.week)) {
        const todayData = habit.week.find(day => day.date === today);
        return todayData ? todayData.done : false;
    }
    return false;
}

/**
 * Toggle habit completion
 */
async function toggleHabit(habitEl) {
    const habitId = habitEl.dataset.habitId;
    if (!habitId) return;
    
    try {
        const response = await fetch(`/api/habits/${habitId}/toggle`, {
            method: 'POST'
        });
        const result = await response.json();
        
        if (result.success) {
            // Reload habits to update UI
            await loadHabitsForDashboard();
            await loadStats();
            initWeekView();
        }
    } catch (error) {
        console.error('Failed to toggle habit:', error);
    }
}

/**
 * Load and display statistics
 */
async function loadStats() {
    try {
        const [tasksResponse, habitsResponse] = await Promise.all([
            fetch('/api/tasks/stats'),
            fetch('/api/habits/stats')
        ]);
        
        const tasksStats = await tasksResponse.json();
        const habitsStats = await habitsResponse.json();
        
        updateProgressDisplay(tasksStats, habitsStats);
    } catch (error) {
        console.error('Failed to load stats:', error);
    }
}

/**
 * Update progress ring and stats display
 */
function updateProgressDisplay(tasksStats, habitsStats) {
    const tasksDone = tasksStats.success ? tasksStats.data.done : 0;
    const tasksTotal = tasksStats.success ? tasksStats.data.total : 0;
    const habitsDone = habitsStats.success ? habitsStats.data.todayCompleted : 0;
    const habitsTotal = habitsStats.success ? habitsStats.data.totalHabits : 0;
    
    const totalDone = tasksDone + habitsDone;
    const totalAll = tasksTotal + habitsTotal;
    const percentage = totalAll > 0 ? Math.round((totalDone / totalAll) * 100) : 0;
    
    // Update progress ring
    const progressValue = document.getElementById('progress-value');
    const progressCircle = document.getElementById('progress-circle');
    
    if (progressValue) {
        progressValue.textContent = `${percentage}%`;
    }
    
    if (progressCircle) {
        // Circle circumference = 2 * PI * r = 2 * 3.14159 * 40 = 251.2
        const circumference = 251.2;
        const offset = circumference - (circumference * percentage / 100);
        progressCircle.style.strokeDasharray = circumference;
        progressCircle.style.strokeDashoffset = offset;
    }
    
    // Update progress text
    const progressTitle = document.getElementById('progress-title');
    const progressDescription = document.getElementById('progress-description');
    
    if (progressTitle) {
        if (percentage >= 80) {
            progressTitle.textContent = 'Świetna robota!';
        } else if (percentage >= 50) {
            progressTitle.textContent = 'Dobra robota!';
        } else if (percentage > 0) {
            progressTitle.textContent = 'Tak trzymaj!';
        } else {
            progressTitle.textContent = 'Czas zacząć!';
        }
    }
    
    if (progressDescription) {
        const remaining = totalAll - totalDone;
        if (remaining === 0 && totalAll > 0) {
            progressDescription.textContent = 'Ukończyłeś wszystkie zadania na dziś! 🎉';
        } else if (remaining > 0) {
            progressDescription.textContent = `Zostało Ci jeszcze ${remaining} ${getPluralForm(remaining, 'zadanie', 'zadania', 'zadań')} do wykonania.`;
        } else {
            progressDescription.textContent = 'Dodaj zadania i nawyki, aby śledzić swoje postępy.';
        }
    }
    
    // Update stats numbers
    const tasksDoneEl = document.getElementById('tasks-done');
    const habitsDoneEl = document.getElementById('habits-done');
    
    if (tasksDoneEl) tasksDoneEl.textContent = `${tasksDone}/${tasksTotal}`;
    if (habitsDoneEl) habitsDoneEl.textContent = `${habitsDone}/${habitsTotal}`;
}

/**
 * Initialize week view with current week
 */
function initWeekView() {
    const weekContainer = document.getElementById('week-view');
    const monthLabel = document.getElementById('current-month');
    if (!weekContainer) return;
    
    const today = new Date();
    const dayOfWeek = today.getDay(); // 0 = Sunday
    const mondayOffset = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
    
    const monday = new Date(today);
    monday.setDate(today.getDate() + mondayOffset);
    
    // Update month label
    const months = [
        'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
        'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'
    ];
    if (monthLabel) {
        monthLabel.textContent = `${months[today.getMonth()]} ${today.getFullYear()}`;
    }
    
    const dayNames = ['PON', 'WT', 'ŚR', 'CZW', 'PT', 'SOB', 'NDZ'];
    
    // Calculate progress for each day based on habit completions
    const weekProgress = calculateWeekProgress();
    
    // Clear existing content
    weekContainer.innerHTML = '';
    
    for (let i = 0; i < 7; i++) {
        const date = new Date(monday);
        date.setDate(monday.getDate() + i);
        
        const isToday = date.toDateString() === today.toDateString();
        const isFuture = date > today;
        
        let dayClass = 'day';
        if (isToday) dayClass += ' day--active';
        else if (isFuture) dayClass += ' day--muted';
        
        const barClass = isToday ? 'bar bar--on-dark' : 'bar';
        const dateStr = date.toISOString().split('T')[0];
        const progressWidth = isFuture ? '0%' : `${weekProgress[dateStr] || 0}%`;
        
        weekContainer.innerHTML += `
            <div class="${dayClass}">
                <div class="day__dow">${dayNames[i]}</div>
                <div class="day__date">${date.getDate()}</div>
                <div class="${barClass}"><span style="width: ${progressWidth}"></span></div>
            </div>
        `;
    }
}

/**
 * Calculate progress for each day of the week (same logic as "Twoje postępy")
 * Combines completed tasks and habits
 */
function calculateWeekProgress() {
    const progress = {};
    const totalHabits = habitsData.length;
    const totalTasks = tasksData.length;
    const totalItems = totalHabits + totalTasks;
    
    if (totalItems === 0) {
        return progress;
    }
    
    // Get current week dates
    const today = new Date();
    const dayOfWeek = today.getDay();
    const mondayOffset = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
    const monday = new Date(today);
    monday.setDate(today.getDate() + mondayOffset);
    
    // Initialize progress for each day of the week
    for (let i = 0; i < 7; i++) {
        const date = new Date(monday);
        date.setDate(monday.getDate() + i);
        const dateStr = date.toISOString().split('T')[0];
        progress[dateStr] = { completedHabits: 0, completedTasks: 0 };
    }
    
    // Count completed habits per day from week data
    habitsData.forEach(habit => {
        if (habit.week && Array.isArray(habit.week)) {
            habit.week.forEach(day => {
                if (progress[day.date] && day.done) {
                    progress[day.date].completedHabits++;
                }
            });
        }
    });
    
    // Count completed tasks (tasks with status 'done' by dueDate)
    tasksData.forEach(task => {
        if (task.status === 'done' && task.dueDate) {
            const taskDate = task.dueDate.split(' ')[0]; // Get date part only
            if (progress[taskDate]) {
                progress[taskDate].completedTasks++;
            }
        }
    });
    
    // Convert to percentages (same logic as Twoje postępy)
    const percentages = {};
    Object.keys(progress).forEach(date => {
        const { completedHabits, completedTasks } = progress[date];
        const totalDone = completedHabits + completedTasks;
        percentages[date] = totalItems > 0 ? Math.round((totalDone / totalItems) * 100) : 0;
    });
    
    return percentages;
}

/**
 * Check if habit should be shown today based on frequency
 */
function isHabitForToday(habit) {
    if (habit.frequency === 'daily') return true;
    
    const today = new Date().getDay(); // 0 = Sunday
    
    if (habit.frequency === 'weekdays') {
        return today >= 1 && today <= 5;
    }
    
    if (habit.frequency === 'weekends') {
        return today === 0 || today === 6;
    }
    
    return true; // Default: show all habits
}

/**
 * Format due date for display
 */
function formatDueDate(dueDateStr) {
    if (!dueDateStr) return '';
    
    const dueDate = new Date(dueDateStr);
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    // Reset time for date comparison
    today.setHours(0, 0, 0, 0);
    tomorrow.setHours(0, 0, 0, 0);
    const dueDateOnly = new Date(dueDate);
    dueDateOnly.setHours(0, 0, 0, 0);
    
    const time = dueDate.toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });
    
    if (dueDateOnly.getTime() === today.getTime()) {
        return `Dziś, ${time}`;
    } else if (dueDateOnly.getTime() === tomorrow.getTime()) {
        return `Jutro, ${time}`;
    } else {
        const options = { weekday: 'long', day: 'numeric', month: 'short' };
        return dueDate.toLocaleDateString('pl-PL', options);
    }
}

/**
 * Check if due date is overdue
 */
function isOverdue(dueDateStr) {
    if (!dueDateStr) return false;
    return new Date(dueDateStr) < new Date();
}

/**
 * Get Polish plural form
 */
function getPluralForm(count, singular, few, many) {
    if (count === 1) return singular;
    if (count % 10 >= 2 && count % 10 <= 4 && (count % 100 < 10 || count % 100 >= 20)) {
        return few;
    }
    return many;
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
