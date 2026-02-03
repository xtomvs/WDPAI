/**
 * Tasks Module for Studify
 * Handles task management, filtering, and user interactions
 */

const USE_API = true;

class TaskManager {
  constructor() {
    this.tasks = [];
    this.currentView = 'list';
    this.filters = {
      categories: ['studia', 'praca', 'osobiste'],
      priorities: ['wysoki', 'sredni', 'niski'],
      status: ['active', 'completed']
    };
    this.activeFilterTags = [];
    this.editingTaskId = null;
    this.currentPage = 1;
    this.tasksPerPage = 10;

    this.init();
  }

  /**
   * Initialize the task manager
   */
  async init() {
    this.cacheElements();
    this.bindEvents();
    await this.loadTasks();
    this.render();
  }

  /**
   * Cache DOM elements
   */
  cacheElements() {
    this.elements = {
      tasksList: document.getElementById('tasksList'),
      tasksGrid: document.getElementById('tasksGrid'),
      totalTasks: document.getElementById('totalTasks'),
      tasksSummary: document.getElementById('tasksSummary'),
      addTaskBtn: document.getElementById('addTaskBtn'),
      taskModal: document.getElementById('taskModal'),
      taskForm: document.getElementById('taskForm'),
      modalTitle: document.getElementById('modalTitle'),
      closeModal: document.getElementById('closeModal'),
      cancelTask: document.getElementById('cancelTask'),
      filterBtn: document.getElementById('filterBtn'),
      filterModal: document.getElementById('filterModal'),
      closeFilterModal: document.getElementById('closeFilterModal'),
      applyFilters: document.getElementById('applyFilters'),
      resetFilters: document.getElementById('resetFilters'),
      activeFilters: document.getElementById('activeFilters'),
      viewToggle: document.querySelectorAll('.view-toggle__btn'),
      selectAll: document.getElementById('selectAll'),
      taskDropdown: document.getElementById('taskDropdown'),
      prevPage: document.getElementById('prevPage'),
      nextPage: document.getElementById('nextPage'),
      searchToggle: document.getElementById('searchToggle')
    };
  }

  /**
   * Bind event listeners
   */
  bindEvents() {
    // Add task modal
    this.elements.addTaskBtn.addEventListener('click', () => this.openAddModal());
    this.elements.closeModal.addEventListener('click', () => this.closeModal());
    this.elements.cancelTask.addEventListener('click', () => this.closeModal());
    this.elements.taskModal.querySelector('.modal__backdrop').addEventListener('click', () => this.closeModal());

    // Task form
    this.elements.taskForm.addEventListener('submit', (e) => this.handleTaskSubmit(e));

    // Filter modal
    this.elements.filterBtn.addEventListener('click', () => this.openFilterModal());
    this.elements.closeFilterModal.addEventListener('click', () => this.closeFilterModal());
    this.elements.filterModal.querySelector('.modal__backdrop').addEventListener('click', () => this.closeFilterModal());
    this.elements.applyFilters.addEventListener('click', () => this.applyFilters());
    this.elements.resetFilters.addEventListener('click', () => this.resetFilters());

    // View toggle
    this.elements.viewToggle.forEach(btn => {
      btn.addEventListener('click', (e) => this.changeView(e.currentTarget.dataset.view));
    });

    // Select all checkbox
    this.elements.selectAll.addEventListener('change', (e) => this.toggleSelectAll(e.target.checked));

    // Pagination
    this.elements.prevPage.addEventListener('click', () => this.changePage(-1));
    this.elements.nextPage.addEventListener('click', () => this.changePage(1));

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.task-row__menu') && !e.target.closest('.dropdown')) {
        this.closeDropdown();
      }
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.closeModal();
        this.closeFilterModal();
        this.closeDropdown();
      }
    });
  }

  /**
   * Render tasks
   */
  render() {
    this.renderTasks();
    this.updateSummary();
    this.renderActiveFilters();
    this.updatePagination();
  }

  /**
   * Render tasks list/grid
   */
  renderTasks() {
    const filteredTasks = this.getFilteredTasks();
    const paginatedTasks = this.getPaginatedTasks(filteredTasks);

    if (this.currentView === 'list') {
      this.elements.tasksList.innerHTML = '';
      this.elements.tasksGrid.style.display = 'none';
      this.elements.tasksList.parentElement.parentElement.style.display = 'block';

      paginatedTasks.forEach(task => {
        this.elements.tasksList.appendChild(this.createTaskRow(task));
      });
    } else {
      this.elements.tasksGrid.innerHTML = '';
      this.elements.tasksList.parentElement.parentElement.style.display = 'none';
      this.elements.tasksGrid.style.display = 'grid';

      paginatedTasks.forEach(task => {
        this.elements.tasksGrid.appendChild(this.createTaskCard(task));
      });
    }

    this.elements.totalTasks.textContent = filteredTasks.length;
  }

  /**
   * Create task row for list view
   */
  createTaskRow(task) {
    const row = document.createElement('div');
    row.className = `task-row ${task.completed ? 'task-row--completed' : ''}`;
    row.dataset.taskId = task.id;

    const priorityIcons = {
      wysoki: 'priority_high',
      sredni: 'horizontal_rule',
      niski: 'arrow_downward'
    };

    const priorityLabels = {
      wysoki: 'Wysoki',
      sredni: 'Średni',
      niski: 'Niski'
    };

    row.innerHTML = `
      <div class="task-row__checkbox">
        <input type="checkbox" class="checkbox" ${task.completed ? 'checked' : ''} data-task-id="${task.id}" />
      </div>
      <div class="task-row__name">
        <span class="task-row__title">${this.escapeHtml(task.name)}</span>
        ${task.description ? `<span class="task-row__description">${this.escapeHtml(task.description)}</span>` : ''}
      </div>
      <div class="task-row__category">
        <span class="category-badge category-badge--${task.category}">${this.capitalizeFirst(task.category)}</span>
      </div>
      <div class="task-row__date">
        <span class="material-symbols-outlined">event</span>
        ${this.formatDate(task.date)}
      </div>
      <div class="task-row__priority">
        ${task.completed ? `
          <span class="priority-badge priority-badge--completed">Ukończono</span>
        ` : `
          <span class="priority-badge priority-badge--${task.priority}">
            <span class="material-symbols-outlined">${priorityIcons[task.priority]}</span>
            ${priorityLabels[task.priority]}
          </span>
        `}
      </div>
      <div class="task-row__actions">
        <button class="task-row__menu" data-task-id="${task.id}">
          <span class="material-symbols-outlined">more_vert</span>
        </button>
      </div>
    `;

    // Bind checkbox event
    const checkbox = row.querySelector('.checkbox');
    checkbox.addEventListener('change', () => this.toggleTaskComplete(task.id));

    // Bind menu button event
    const menuBtn = row.querySelector('.task-row__menu');
    menuBtn.addEventListener('click', (e) => this.openDropdown(e, task.id));

    return row;
  }

  /**
   * Create task card for grid view
   */
  createTaskCard(task) {
    const card = document.createElement('div');
    card.className = `task-card ${task.completed ? 'task-card--completed' : ''}`;
    card.dataset.taskId = task.id;

    card.innerHTML = `
      <div class="task-card__header">
        <h3 class="task-card__title">${this.escapeHtml(task.name)}</h3>
        <input type="checkbox" class="checkbox" ${task.completed ? 'checked' : ''} data-task-id="${task.id}" />
      </div>
      ${task.description ? `<p class="task-card__description">${this.escapeHtml(task.description)}</p>` : ''}
      <div class="task-card__footer">
        <div class="task-card__meta">
          <span class="category-badge category-badge--${task.category}">${this.capitalizeFirst(task.category)}</span>
          <span class="task-card__date">
            <span class="material-symbols-outlined">event</span>
            ${this.formatDate(task.date)}
          </span>
        </div>
        <span class="priority-badge priority-badge--${task.completed ? 'completed' : task.priority}">
          ${task.completed ? 'Ukończono' : this.capitalizeFirst(task.priority)}
        </span>
      </div>
    `;

    // Bind checkbox event
    const checkbox = card.querySelector('.checkbox');
    checkbox.addEventListener('change', () => this.toggleTaskComplete(task.id));

    return card;
  }

  /**
   * Get filtered tasks
   */
  getFilteredTasks() {
    return this.tasks.filter(task => {
      const categoryMatch = this.filters.categories.includes(task.category);
      const priorityMatch = this.filters.priorities.includes(task.priority);
      const statusMatch = (this.filters.status.includes('active') && !task.completed) ||
                          (this.filters.status.includes('completed') && task.completed);

      return categoryMatch && priorityMatch && statusMatch;
    });
  }

  /**
   * Get paginated tasks
   */
  getPaginatedTasks(tasks) {
    const start = (this.currentPage - 1) * this.tasksPerPage;
    const end = start + this.tasksPerPage;
    return tasks.slice(start, end);
  }

  /**
   * Update summary text
   */
  updateSummary() {
    const activeTasks = this.tasks.filter(t => !t.completed);
    const todayTasks = activeTasks.filter(t => this.isToday(t.date));

    if (todayTasks.length > 0) {
      this.elements.tasksSummary.textContent = `Masz dzisiaj ${todayTasks.length} ${this.getTaskWord(todayTasks.length)} do ukończenia.`;
    } else if (activeTasks.length > 0) {
      this.elements.tasksSummary.textContent = `Masz ${activeTasks.length} ${this.getTaskWord(activeTasks.length)} do ukończenia.`;
    } else {
      this.elements.tasksSummary.textContent = 'Wszystkie zadania ukończone! 🎉';
    }
  }

  /**
   * Get correct word form for task count
   */
  getTaskWord(count) {
    if (count === 1) return 'zadanie';
    if (count >= 2 && count <= 4) return 'zadania';
    return 'zadań';
  }

  /**
   * Check if date is today
   */
  isToday(dateStr) {
    const today = new Date();
    const date = new Date(dateStr);
    return date.toDateString() === today.toDateString();
  }

  /**
   * Render active filter tags
   */
  renderActiveFilters() {
    this.elements.activeFilters.innerHTML = '';

    // Add category filters
    this.filters.categories.forEach(cat => {
      if (cat !== 'studia' || cat !== 'praca' || cat !== 'osobiste') {
        // Show tag for active filters
      }
    });

    // Show first active category and priority as example tags
    if (this.activeFilterTags.length > 0) {
      this.activeFilterTags.forEach(filter => {
        const tag = document.createElement('button');
        tag.className = `filter-tag filter-tag--${filter.value}`;
        tag.innerHTML = `
          <span>${filter.label}</span>
          <span class="material-symbols-outlined">close</span>
        `;
        tag.addEventListener('click', () => this.removeFilterTag(filter));
        this.elements.activeFilters.appendChild(tag);
      });
    }
  }

  /**
   * Remove filter tag
   */
  removeFilterTag(filter) {
    const index = this.activeFilterTags.findIndex(f => f.value === filter.value);
    if (index > -1) {
      this.activeFilterTags.splice(index, 1);
    }
    this.render();
  }

  /**
   * Update pagination
   */
  updatePagination() {
    const filteredTasks = this.getFilteredTasks();
    const totalPages = Math.ceil(filteredTasks.length / this.tasksPerPage);

    this.elements.prevPage.disabled = this.currentPage <= 1;
    this.elements.nextPage.disabled = this.currentPage >= totalPages;
  }

  /**
   * Change page
   */
  changePage(direction) {
    this.currentPage += direction;
    this.render();
  }

  /**
   * Change view (list/grid)
   */
  changeView(view) {
    this.currentView = view;
    this.elements.viewToggle.forEach(btn => {
      btn.classList.toggle('view-toggle__btn--active', btn.dataset.view === view);
    });
    this.render();
  }

  /**
   * Toggle task complete status
   */
  async toggleTaskComplete(taskId) {
    const task = this.tasks.find(t => t.id === taskId);
    if (task) {
      if (USE_API) {
        try {
          const result = await this.toggleTaskStatusApi(taskId);
          if (result.success) {
            task.completed = result.data.status === 'done';
          }
        } catch (err) {
          console.error('Error toggling task:', err);
          alert('Nie udało się zmienić statusu zadania');
          return;
        }
      } else {
        task.completed = !task.completed;
        this.saveTasks();
      }
      this.render();
    }
  }

  /**
   * Toggle select all
   */
  toggleSelectAll(checked) {
    const checkboxes = document.querySelectorAll('.task-row .checkbox');
    checkboxes.forEach(cb => {
      cb.checked = checked;
    });
  }

  /**
   * Open add task modal
   */
  openAddModal() {
    this.editingTaskId = null;
    this.elements.modalTitle.textContent = 'Dodaj nowe zadanie';
    this.elements.taskForm.reset();

    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('taskDate').value = today;

    this.elements.taskModal.classList.add('modal--active');
    document.getElementById('taskName').focus();
  }

  /**
   * Open edit task modal
   */
  openEditModal(taskId) {
    const task = this.tasks.find(t => t.id === taskId);
    if (!task) return;

    this.editingTaskId = taskId;
    this.elements.modalTitle.textContent = 'Edytuj zadanie';

    document.getElementById('taskName').value = task.name;
    document.getElementById('taskDescription').value = task.description || '';
    document.getElementById('taskCategory').value = task.category;
    document.getElementById('taskPriority').value = task.priority;
    document.getElementById('taskDate').value = task.date;

    this.elements.taskModal.classList.add('modal--active');
    this.closeDropdown();
  }

  /**
   * Close task modal
   */
  closeModal() {
    this.elements.taskModal.classList.remove('modal--active');
    this.editingTaskId = null;
  }

  /**
   * Handle task form submission
   */
  async handleTaskSubmit(e) {
    e.preventDefault();

    const taskData = {
      name: document.getElementById('taskName').value,
      description: document.getElementById('taskDescription').value,
      category: document.getElementById('taskCategory').value,
      priority: document.getElementById('taskPriority').value,
      date: document.getElementById('taskDate').value,
      completed: false
    };

    try {
      if (USE_API) {
        if (this.editingTaskId) {
          // Update existing task
          const index = this.tasks.findIndex(t => t.id === this.editingTaskId);
          if (index > -1) {
            taskData.completed = this.tasks[index].completed;
            await this.updateTaskApi(this.editingTaskId, taskData);
            taskData.id = this.editingTaskId;
            this.tasks[index] = taskData;
          }
        } else {
          // Add new task
          const result = await this.createTaskApi(taskData);
          if (result.success && result.data) {
            taskData.id = result.data.id;
            this.tasks.unshift(taskData);
          }
        }
      } else {
        if (this.editingTaskId) {
          const index = this.tasks.findIndex(t => t.id === this.editingTaskId);
          if (index > -1) {
            taskData.id = this.editingTaskId;
            taskData.completed = this.tasks[index].completed;
            this.tasks[index] = taskData;
          }
        } else {
          taskData.id = Date.now();
          this.tasks.unshift(taskData);
        }
        this.saveTasks();
      }

      this.closeModal();
      this.render();
    } catch (err) {
      console.error('Error saving task:', err);
      alert('Nie udało się zapisać zadania');
    }
  }

  /**
   * Open filter modal
   */
  openFilterModal() {
    this.elements.filterModal.classList.add('modal--active');
  }

  /**
   * Close filter modal
   */
  closeFilterModal() {
    this.elements.filterModal.classList.remove('modal--active');
  }

  /**
   * Apply filters
   */
  applyFilters() {
    const categoryCheckboxes = this.elements.filterModal.querySelectorAll('.filter-section:nth-child(1) input');
    const priorityCheckboxes = this.elements.filterModal.querySelectorAll('.filter-section:nth-child(2) input');
    const statusCheckboxes = this.elements.filterModal.querySelectorAll('.filter-section:nth-child(3) input');

    this.filters.categories = Array.from(categoryCheckboxes)
      .filter(cb => cb.checked)
      .map(cb => cb.value);

    this.filters.priorities = Array.from(priorityCheckboxes)
      .filter(cb => cb.checked)
      .map(cb => cb.value);

    this.filters.status = Array.from(statusCheckboxes)
      .filter(cb => cb.checked)
      .map(cb => cb.value);

    this.currentPage = 1;
    this.closeFilterModal();
    this.render();
  }

  /**
   * Reset filters
   */
  resetFilters() {
    const checkboxes = this.elements.filterModal.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = true);

    this.filters = {
      categories: ['studia', 'praca', 'osobiste'],
      priorities: ['wysoki', 'sredni', 'niski'],
      status: ['active', 'completed']
    };
  }

  /**
   * Open dropdown menu
   */
  openDropdown(e, taskId) {
    e.stopPropagation();
    const dropdown = this.elements.taskDropdown;
    const rect = e.currentTarget.getBoundingClientRect();

    dropdown.style.top = `${rect.bottom + 8}px`;
    dropdown.style.left = `${rect.left - 100}px`;
    dropdown.classList.add('dropdown--active');
    dropdown.dataset.taskId = taskId;

    // Bind dropdown actions
    dropdown.querySelectorAll('.dropdown__item').forEach(item => {
      item.onclick = () => this.handleDropdownAction(item.dataset.action, taskId);
    });
  }

  /**
   * Close dropdown
   */
  closeDropdown() {
    this.elements.taskDropdown.classList.remove('dropdown--active');
  }

  /**
   * Handle dropdown action
   */
  handleDropdownAction(action, taskId) {
    switch (action) {
      case 'edit':
        this.openEditModal(taskId);
        break;
      case 'duplicate':
        this.duplicateTask(taskId);
        break;
      case 'delete':
        this.deleteTask(taskId);
        break;
    }
    this.closeDropdown();
  }

  /**
   * Duplicate task
   */
  async duplicateTask(taskId) {
    const task = this.tasks.find(t => t.id === taskId);
    if (task) {
      const newTaskData = {
        name: `${task.name} (kopia)`,
        description: task.description,
        category: task.category,
        priority: task.priority,
        date: task.date,
        completed: false
      };
      
      try {
        if (USE_API) {
          const result = await this.createTaskApi(newTaskData);
          if (result.success && result.data) {
            newTaskData.id = result.data.id;
            this.tasks.unshift(newTaskData);
          }
        } else {
          newTaskData.id = Date.now();
          this.tasks.unshift(newTaskData);
          this.saveTasks();
        }
        this.render();
      } catch (err) {
        console.error('Error duplicating task:', err);
        alert('Nie udało się zduplikować zadania');
      }
    }
  }

  /**
   * Delete task
   */
  async deleteTask(taskId) {
    if (confirm('Czy na pewno chcesz usunąć to zadanie?')) {
      try {
        if (USE_API) {
          await this.deleteTaskApi(taskId);
        }
        this.tasks = this.tasks.filter(t => t.id !== taskId);
        if (!USE_API) {
          this.saveTasks();
        }
        this.render();
      } catch (err) {
        console.error('Error deleting task:', err);
        alert('Nie udało się usunąć zadania');
      }
    }
  }

  /**
   * Format date to Polish format
   */
  formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pl-PL', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  }

  /**
   * Capitalize first letter
   */
  capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  /**
   * Escape HTML to prevent XSS
   */
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  /**
   * Load tasks from API
   */
  async loadTasks() {
    if (USE_API) {
      try {
        const res = await fetch('/api/tasks', {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin'
        });
        if (!res.ok) throw new Error('Failed to load tasks');
        const json = await res.json();
        if (json.success && json.data) {
          this.tasks = json.data.map(t => ({
            id: t.id,
            name: t.title,
            description: t.description || '',
            category: t.category,
            priority: t.priority,
            date: t.dueDate,
            completed: t.status === 'done'
          }));
        }
      } catch (err) {
        console.error('Error loading tasks:', err);
        this.tasks = this.getDefaultTasks();
      }
    } else {
      const stored = localStorage.getItem('studify_tasks');
      if (stored) {
        this.tasks = JSON.parse(stored);
      } else {
        this.tasks = this.getDefaultTasks();
      }
    }
    return this.tasks;
  }

  /**
   * Get default sample tasks
   */
  getDefaultTasks() {
    const today = new Date();
    const formatDate = (daysOffset) => {
      const date = new Date(today);
      date.setDate(date.getDate() + daysOffset);
      return date.toISOString().split('T')[0];
    };

    return [
      {
        id: 1,
        name: 'Przygotować prezentację z marketingu',
        description: 'Projekt grupowy, rozdział 4',
        category: 'studia',
        priority: 'wysoki',
        date: formatDate(2),
        completed: false
      },
      {
        id: 2,
        name: 'Oddać projekt z programowania',
        description: 'Aplikacja webowa w PHP',
        category: 'studia',
        priority: 'wysoki',
        date: formatDate(-2),
        completed: true
      },
      {
        id: 3,
        name: 'Zaplanować spotkanie zespołu',
        description: '',
        category: 'praca',
        priority: 'sredni',
        date: formatDate(5),
        completed: false
      },
      {
        id: 4,
        name: 'Zrobić zakupy spożywcze',
        description: 'Mleko, chleb, owoce',
        category: 'osobiste',
        priority: 'niski',
        date: formatDate(1),
        completed: false
      },
      {
        id: 5,
        name: 'Przeczytać materiały do egzaminu',
        description: 'Rozdziały 5-8',
        category: 'studia',
        priority: 'wysoki',
        date: formatDate(7),
        completed: false
      },
      {
        id: 6,
        name: 'Odpowiedzieć na maile',
        description: 'Sprawy firmowe',
        category: 'praca',
        priority: 'sredni',
        date: formatDate(0),
        completed: false
      }
    ];
  }

  /**
   * Save tasks to API or localStorage
   */
  async saveTasks() {
    if (!USE_API) {
      localStorage.setItem('studify_tasks', JSON.stringify(this.tasks));
    }
    // When using API, tasks are saved via individual API calls
  }

  /**
   * Create task via API
   */
  async createTaskApi(taskData) {
    const res = await fetch('/api/tasks', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        title: taskData.name,
        description: taskData.description,
        category: taskData.category,
        priority: taskData.priority,
        dueDate: taskData.date
      })
    });
    if (!res.ok) throw new Error('Failed to create task');
    return res.json();
  }

  /**
   * Update task via API
   */
  async updateTaskApi(taskId, taskData) {
    const res = await fetch(`/api/tasks/${taskId}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        title: taskData.name,
        description: taskData.description,
        category: taskData.category,
        priority: taskData.priority,
        dueDate: taskData.date,
        status: taskData.completed ? 'done' : 'todo'
      })
    });
    if (!res.ok) throw new Error('Failed to update task');
    return res.json();
  }

  /**
   * Delete task via API
   */
  async deleteTaskApi(taskId) {
    const res = await fetch(`/api/tasks/${taskId}`, {
      method: 'DELETE',
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    });
    if (!res.ok) throw new Error('Failed to delete task');
    return res.json();
  }

  /**
   * Toggle task status via API
   */
  async toggleTaskStatusApi(taskId) {
    const res = await fetch(`/api/tasks/${taskId}/status`, {
      method: 'PATCH',
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    });
    if (!res.ok) throw new Error('Failed to toggle task status');
    return res.json();
  }
}

// Initialize task manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  window.taskManager = new TaskManager();
});
