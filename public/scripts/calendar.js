/**
 * Calendar Module for Studify
 * Handles calendar rendering, events management, and user interactions
 */

const USE_API = true;

class Calendar {
  constructor() {
    this.currentDate = new Date();
    this.currentView = 'month';
    this.events = [];
    this.activeFilters = ['uczelnia', 'prywatne', 'projekt', 'sport'];
    this.selectedEvent = null;

    this.init();
  }

  /**
   * Initialize the calendar
   */
  async init() {
    this.cacheElements();
    this.bindEvents();
    await this.loadEvents();
    this.render();
  }

  /**
   * Cache DOM elements
   */
  cacheElements() {
    this.elements = {
      currentMonth: document.getElementById('currentMonth'),
      prevMonth: document.getElementById('prevMonth'),
      nextMonth: document.getElementById('nextMonth'),
      calendarGrid: document.getElementById('calendarGrid'),
      viewButtons: document.querySelectorAll('.view-btn'),
      filterTags: document.getElementById('filterTags'),
      addEventBtn: document.getElementById('addEventBtn'),
      eventModal: document.getElementById('eventModal'),
      eventForm: document.getElementById('eventForm'),
      closeModal: document.getElementById('closeModal'),
      cancelEvent: document.getElementById('cancelEvent'),
      eventDetailsModal: document.getElementById('eventDetailsModal'),
      closeDetailsModal: document.getElementById('closeDetailsModal'),
      deleteEvent: document.getElementById('deleteEvent'),
      editEvent: document.getElementById('editEvent'),
      searchInput: document.getElementById('searchInput'),
    };
  }

  /**
   * Bind event listeners
   */
  bindEvents() {
    // Navigation
    this.elements.prevMonth.addEventListener('click', () => this.navigateMonth(-1));
    this.elements.nextMonth.addEventListener('click', () => this.navigateMonth(1));

    // View toggle
    this.elements.viewButtons.forEach(btn => {
      btn.addEventListener('click', (e) => this.changeView(e.target.dataset.view));
    });

    // Filter tags
    this.elements.filterTags.addEventListener('click', (e) => {
      const tag = e.target.closest('.filter-tag');
      if (tag) {
        this.toggleFilter(tag);
      }
    });

    // Add event modal
    this.elements.addEventBtn.addEventListener('click', () => this.openAddEventModal());
    this.elements.closeModal.addEventListener('click', () => this.closeEventModal());
    this.elements.cancelEvent.addEventListener('click', () => this.closeEventModal());
    this.elements.eventModal.querySelector('.modal__backdrop').addEventListener('click', () => this.closeEventModal());

    // Event form
    this.elements.eventForm.addEventListener('submit', (e) => this.handleEventSubmit(e));

    // Event details modal
    this.elements.closeDetailsModal.addEventListener('click', () => this.closeDetailsModal());
    this.elements.eventDetailsModal.querySelector('.modal__backdrop').addEventListener('click', () => this.closeDetailsModal());
    this.elements.deleteEvent.addEventListener('click', () => this.deleteSelectedEvent());
    this.elements.editEvent.addEventListener('click', () => this.editSelectedEvent());

    // Search
    this.elements.searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.closeEventModal();
        this.closeDetailsModal();
      }
    });
  }

  /**
   * Navigate to previous/next month
   */
  navigateMonth(direction) {
    this.currentDate.setMonth(this.currentDate.getMonth() + direction);
    this.render();
  }

  /**
   * Change calendar view (month/week)
   */
  changeView(view) {
    this.currentView = view;
    this.elements.viewButtons.forEach(btn => {
      btn.classList.toggle('view-btn--active', btn.dataset.view === view);
    });
    this.render();
  }

  /**
   * Toggle filter
   */
  toggleFilter(tag) {
    const category = tag.dataset.category;
    const index = this.activeFilters.indexOf(category);

    if (index > -1) {
      this.activeFilters.splice(index, 1);
      tag.classList.add('filter-tag--inactive');
    } else {
      this.activeFilters.push(category);
      tag.classList.remove('filter-tag--inactive');
    }

    this.render();
  }

  /**
   * Render the calendar
   */
  render() {
    this.updateHeader();
    this.renderGrid();
  }

  /**
   * Update header with current month/year
   */
  updateHeader() {
    const months = [
      'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
      'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'
    ];
    const month = months[this.currentDate.getMonth()];
    const year = this.currentDate.getFullYear();
    this.elements.currentMonth.textContent = `${month} ${year}`;
  }

  /**
   * Render calendar grid
   */
  renderGrid() {
    const grid = this.elements.calendarGrid;
    grid.innerHTML = '';

    if (this.currentView === 'month') {
      this.renderMonthView(grid);
    } else {
      this.renderWeekView(grid);
    }
  }

  /**
   * Render month view
   */
  renderMonthView(grid) {
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    // Adjust for Monday start (0 = Sunday, 1 = Monday, etc.)
    let startDay = firstDay.getDay() - 1;
    if (startDay < 0) startDay = 6;

    const daysInMonth = lastDay.getDate();
    const today = new Date();

    // Previous month days
    const prevMonth = new Date(year, month, 0);
    const prevMonthDays = prevMonth.getDate();

    for (let i = startDay - 1; i >= 0; i--) {
      const day = prevMonthDays - i;
      const date = new Date(year, month - 1, day);
      grid.appendChild(this.createCell(date, true));
    }

    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(year, month, day);
      const isToday = this.isSameDay(date, today);
      grid.appendChild(this.createCell(date, false, isToday));
    }

    // Next month days
    const totalCells = startDay + daysInMonth;
    const remainingCells = totalCells <= 35 ? 35 - totalCells : 42 - totalCells;

    for (let day = 1; day <= remainingCells; day++) {
      const date = new Date(year, month + 1, day);
      grid.appendChild(this.createCell(date, true));
    }
  }

  /**
   * Render week view
   */
  renderWeekView(grid) {
    const today = new Date();
    const currentDay = today.getDay();
    const monday = new Date(today);
    monday.setDate(today.getDate() - (currentDay === 0 ? 6 : currentDay - 1));

    for (let i = 0; i < 7; i++) {
      const date = new Date(monday);
      date.setDate(monday.getDate() + i);
      const isToday = this.isSameDay(date, today);
      const cell = this.createCell(date, false, isToday);
      cell.style.minHeight = '400px';
      grid.appendChild(cell);
    }
  }

  /**
   * Create a calendar cell
   */
  createCell(date, isOtherMonth = false, isToday = false) {
    const cell = document.createElement('div');
    cell.className = 'calendar__cell';

    if (isOtherMonth) {
      cell.classList.add('calendar__cell--other-month');
    }

    if (isToday) {
      cell.classList.add('calendar__cell--today');
    }

    if (date.getDay() === 0) {
      cell.classList.add('calendar__cell--sunday');
    }

    // Cell header
    const header = document.createElement('div');
    header.className = 'calendar__cell-header';

    const dayNumber = document.createElement('span');
    dayNumber.className = 'calendar__day-number';
    if (isToday) {
      dayNumber.classList.add('calendar__day-number--today');
    }
    dayNumber.textContent = date.getDate();
    header.appendChild(dayNumber);

    if (isToday) {
      const todayLabel = document.createElement('span');
      todayLabel.className = 'calendar__today-label';
      todayLabel.textContent = 'Dziś';
      header.appendChild(todayLabel);
    }

    cell.appendChild(header);

    // Events container
    const eventsContainer = document.createElement('div');
    eventsContainer.className = 'calendar__events';

    const dayEvents = this.getEventsForDate(date);
    dayEvents.forEach(event => {
      if (this.activeFilters.includes(event.category)) {
        eventsContainer.appendChild(this.createEventCard(event));
      }
    });

    cell.appendChild(eventsContainer);

    // Click to add event
    cell.addEventListener('click', (e) => {
      if (!e.target.closest('.event-card')) {
        this.openAddEventModal(date);
      }
    });

    return cell;
  }

  /**
   * Create an event card
   */
  createEventCard(event) {
    const card = document.createElement('div');
    card.className = `event-card event-card--${event.category}`;

    const title = document.createElement('div');
    title.className = 'event-card__title';
    title.textContent = event.title;

    const time = document.createElement('div');
    time.className = 'event-card__time';
    time.textContent = event.time || 'Cały dzień';

    card.appendChild(title);
    card.appendChild(time);

    card.addEventListener('click', (e) => {
      e.stopPropagation();
      this.openEventDetails(event);
    });

    return card;
  }

  /**
   * Get events for a specific date
   */
  getEventsForDate(date) {
    const dateStr = this.formatDateKey(date);
    return this.events.filter(event => event.date === dateStr);
  }

  /**
   * Format date as key (YYYY-MM-DD)
   */
  formatDateKey(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  /**
   * Check if two dates are the same day
   */
  isSameDay(date1, date2) {
    return date1.getDate() === date2.getDate() &&
           date1.getMonth() === date2.getMonth() &&
           date1.getFullYear() === date2.getFullYear();
  }

  /**
   * Open add event modal
   */
  openAddEventModal(date = null) {
    this.elements.eventModal.classList.add('modal--active');
    this.elements.eventForm.reset();

    if (date) {
      document.getElementById('eventDate').value = this.formatDateKey(date);
    }

    document.getElementById('eventTitle').focus();
  }

  /**
   * Close event modal
   */
  closeEventModal() {
    this.elements.eventModal.classList.remove('modal--active');
  }

  /**
   * Handle event form submission
   */
  async handleEventSubmit(e) {
    e.preventDefault();

    const title = document.getElementById('eventTitle').value;
    const date = document.getElementById('eventDate').value;
    const time = document.getElementById('eventTime').value;
    const category = document.getElementById('eventCategory').value;
    const description = document.getElementById('eventDescription').value;

    // Parse time if provided
    let startTime = null;
    let endTime = null;
    if (time && time.includes('-')) {
      const parts = time.split('-').map(t => t.trim());
      startTime = parts[0] ? parts[0] + ':00' : null;
      endTime = parts[1] ? parts[1] + ':00' : null;
    } else if (time) {
      startTime = time + ':00';
    }

    const eventData = {
      title,
      date,
      time,
      category,
      description,
      startTime,
      endTime,
      allDay: !time
    };

    try {
      if (USE_API) {
        const result = await this.createEventApi(eventData);
        if (result.success && result.data) {
          eventData.id = result.data.id;
          this.events.push(eventData);
        }
      } else {
        eventData.id = Date.now();
        this.events.push(eventData);
        this.saveEvents();
      }

      this.closeEventModal();
      this.render();
    } catch (err) {
      console.error('Error creating event:', err);
      alert('Nie udało się utworzyć wydarzenia');
    }
  }

  /**
   * Open event details modal
   */
  openEventDetails(event) {
    this.selectedEvent = event;
    const modal = this.elements.eventDetailsModal;

    document.getElementById('detailsTitle').textContent = event.title;
    document.getElementById('detailsTime').textContent = event.time || 'Cały dzień';

    const date = new Date(event.date);
    const options = { day: 'numeric', month: 'long', year: 'numeric' };
    document.getElementById('detailsDate').textContent = date.toLocaleDateString('pl-PL', options);

    const categoryNames = {
      uczelnia: 'Uczelnia',
      prywatne: 'Prywatne',
      projekt: 'Projekt',
      sport: 'Sport'
    };
    document.getElementById('detailsCategory').textContent = categoryNames[event.category] || event.category;

    document.getElementById('detailsDescription').textContent = event.description || '';

    modal.classList.add('modal--active');
  }

  /**
   * Close event details modal
   */
  closeDetailsModal() {
    this.elements.eventDetailsModal.classList.remove('modal--active');
    this.selectedEvent = null;
  }

  /**
   * Delete selected event
   */
  async deleteSelectedEvent() {
    if (this.selectedEvent) {
      try {
        if (USE_API) {
          await this.deleteEventApi(this.selectedEvent.id);
        }
        this.events = this.events.filter(e => e.id !== this.selectedEvent.id);
        if (!USE_API) {
          this.saveEvents();
        }
        this.closeDetailsModal();
        this.render();
      } catch (err) {
        console.error('Error deleting event:', err);
        alert('Nie udało się usunąć wydarzenia');
      }
    }
  }

  /**
   * Edit selected event
   */
  editSelectedEvent() {
    if (this.selectedEvent) {
      const event = this.selectedEvent;
      this.closeDetailsModal();

      // Open add modal with event data
      this.openAddEventModal();
      document.getElementById('eventTitle').value = event.title;
      document.getElementById('eventDate').value = event.date;
      document.getElementById('eventTime').value = event.time || '';
      document.getElementById('eventCategory').value = event.category;
      document.getElementById('eventDescription').value = event.description || '';

      // Remove old event
      this.events = this.events.filter(e => e.id !== event.id);
    }
  }

  /**
   * Handle search
   */
  handleSearch(query) {
    // Highlight matching events or filter display
    const lowerQuery = query.toLowerCase();
    const eventCards = document.querySelectorAll('.event-card');

    eventCards.forEach(card => {
      const title = card.querySelector('.event-card__title').textContent.toLowerCase();
      if (query && !title.includes(lowerQuery)) {
        card.style.opacity = '0.3';
      } else {
        card.style.opacity = '1';
      }
    });
  }

  /**
   * Load events from API or localStorage
   */
  async loadEvents() {
    if (USE_API) {
      try {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth() + 1;
        const res = await fetch(`/api/events?year=${year}&month=${month}`, {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin'
        });
        if (!res.ok) throw new Error('Failed to load events');
        const json = await res.json();
        if (json.success && json.data) {
          this.events = json.data.map(e => ({
            id: e.id,
            title: e.title,
            date: e.eventDate,
            time: e.timeRange || '',
            category: e.category,
            description: e.description || '',
            allDay: e.allDay
          }));
        }
      } catch (err) {
        console.error('Error loading events:', err);
        this.events = this.getDefaultEvents();
      }
    } else {
      const stored = localStorage.getItem('studify_calendar_events');
      if (stored) {
        this.events = JSON.parse(stored);
      } else {
        this.events = this.getDefaultEvents();
      }
    }
    return this.events;
  }

  /**
   * Get default sample events
   */
  getDefaultEvents() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');

    return [
      {
        id: 1,
        title: 'Matematyka Dyskretna',
        date: `${year}-${month}-01`,
        time: '08:00 - 09:30',
        category: 'uczelnia',
        description: 'Wykład z matematyki dyskretnej, sala 201'
      },
      {
        id: 2,
        title: 'Projekt UI/UX - Research',
        date: `${year}-${month}-04`,
        time: '14:00 - 16:00',
        category: 'projekt',
        description: 'Faza research projektu UI/UX'
      },
      {
        id: 3,
        title: 'Podstawy Marketingu',
        date: this.formatDateKey(today),
        time: '10:30 - 12:00',
        category: 'uczelnia',
        description: 'Wykład z podstaw marketingu'
      },
      {
        id: 4,
        title: 'Trening: Siłownia',
        date: this.formatDateKey(today),
        time: '18:00',
        category: 'sport',
        description: 'Trening siłowy - dzień nóg'
      },
      {
        id: 5,
        title: 'Oddanie Projektu',
        date: `${year}-${month}-09`,
        time: '',
        category: 'projekt',
        description: 'Termin oddania projektu zaliczeniowego'
      },
      {
        id: 6,
        title: 'Język Angielski',
        date: `${year}-${month}-11`,
        time: '09:00 - 10:30',
        category: 'uczelnia',
        description: 'Lektorat z języka angielskiego'
      },
      {
        id: 7,
        title: 'Spotkanie Koła Nauk.',
        date: `${year}-${month}-17`,
        time: '17:30 - 19:00',
        category: 'prywatne',
        description: 'Spotkanie koła naukowego programistów'
      }
    ];
  }

  /**
   * Save events to API or localStorage
   */
  async saveEvents() {
    if (!USE_API) {
      localStorage.setItem('studify_calendar_events', JSON.stringify(this.events));
    }
  }

  /**
   * Create event via API
   */
  async createEventApi(eventData) {
    const res = await fetch('/api/events', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        title: eventData.title,
        description: eventData.description,
        category: eventData.category,
        eventDate: eventData.date,
        startTime: eventData.startTime || null,
        endTime: eventData.endTime || null,
        allDay: eventData.allDay || false
      })
    });
    if (!res.ok) throw new Error('Failed to create event');
    return res.json();
  }

  /**
   * Update event via API
   */
  async updateEventApi(eventId, eventData) {
    const res = await fetch(`/api/events/${eventId}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        title: eventData.title,
        description: eventData.description,
        category: eventData.category,
        eventDate: eventData.date,
        startTime: eventData.startTime || null,
        endTime: eventData.endTime || null,
        allDay: eventData.allDay || false
      })
    });
    if (!res.ok) throw new Error('Failed to update event');
    return res.json();
  }

  /**
   * Delete event via API
   */
  async deleteEventApi(eventId) {
    const res = await fetch(`/api/events/${eventId}`, {
      method: 'DELETE',
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    });
    if (!res.ok) throw new Error('Failed to delete event');
    return res.json();
  }
}

// Initialize calendar when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  window.calendar = new Calendar();
});
