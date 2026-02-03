/**
 * Settings Module for Studify
 * Handles user profile settings and preferences
 */

const USE_API = true;

class SettingsManager {
  constructor() {
    this.settings = this.getDefaultSettings();
    this.init();
  }

  /**
   * Initialize settings manager
   */
  async init() {
    this.cacheElements();
    this.bindEvents();
    await this.loadSettings();
    this.applySettings();
  }

  /**
   * Cache DOM elements
   */
  cacheElements() {
    this.elements = {
      saveBtn: document.getElementById('saveChangesBtn'),
      fullName: document.getElementById('fullName'),
      email: document.getElementById('email'),
      studentId: document.getElementById('studentId'),
      university: document.getElementById('university'),
      darkModeToggle: document.getElementById('darkModeToggle'),
      emailNotifications: document.getElementById('emailNotifications'),
      languageSelect: document.getElementById('languageSelect'),
      deleteAccountBtn: document.getElementById('deleteAccountBtn'),
      deleteModal: document.getElementById('deleteModal'),
      confirmDelete: document.getElementById('confirmDelete'),
      confirmDeleteBtn: document.getElementById('confirmDeleteBtn'),
      cancelDelete: document.getElementById('cancelDelete')
    };
  }

  /**
   * Bind event listeners
   */
  bindEvents() {
    // Save changes
    this.elements.saveBtn.addEventListener('click', () => this.saveSettings());

    // Dark mode toggle
    this.elements.darkModeToggle.addEventListener('change', (e) => {
      this.toggleDarkMode(e.target.checked);
    });

    // Delete account modal
    this.elements.deleteAccountBtn.addEventListener('click', () => this.openDeleteModal());
    this.elements.cancelDelete.addEventListener('click', () => this.closeDeleteModal());
    this.elements.deleteModal.querySelector('.modal__backdrop').addEventListener('click', () => this.closeDeleteModal());

    // Confirm delete input
    this.elements.confirmDelete.addEventListener('input', (e) => {
      this.elements.confirmDeleteBtn.disabled = e.target.value !== 'USUŃ';
    });

    // Confirm delete button
    this.elements.confirmDeleteBtn.addEventListener('click', () => this.deleteAccount());

    // Integration buttons
    document.querySelectorAll('[data-integration]').forEach(btn => {
      btn.addEventListener('click', (e) => this.handleIntegration(e.target.dataset.integration));
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.closeDeleteModal();
      }
    });
  }

  /**
   * Load settings from API or localStorage
   */
  async loadSettings() {
    if (USE_API) {
      try {
        const res = await fetch('/api/settings/profile', {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin'
        });
        if (!res.ok) throw new Error('Failed to load settings');
        const json = await res.json();
        if (json.success && json.data) {
          this.settings = {
            fullName: json.data.fullName || `${json.data.firstname} ${json.data.lastname}`,
            firstname: json.data.firstname,
            lastname: json.data.lastname,
            email: json.data.email,
            studentId: json.data.studentId || '',
            university: json.data.university || '',
            darkMode: json.data.darkMode || false,
            emailNotifications: json.data.emailNotifications !== false,
            language: 'pl',
            integrations: { google: false, onedrive: false }
          };
        }
      } catch (err) {
        console.error('Error loading settings:', err);
      }
    } else {
      const stored = localStorage.getItem('studify_settings');
      if (stored) {
        this.settings = JSON.parse(stored);
      }
    }
    return this.settings;
  }

  /**
   * Get default settings
   */
  getDefaultSettings() {
    return {
      fullName: 'Jan Kowalski',
      email: 'j.kowalski@email.com',
      studentId: '',
      university: 'Uniwersytet Jagielloński',
      darkMode: false,
      emailNotifications: true,
      language: 'pl',
      integrations: {
        google: false,
        onedrive: true
      }
    };
  }

  /**
   * Apply settings to form
   */
  applySettings() {
    this.elements.fullName.value = this.settings.fullName;
    this.elements.email.value = this.settings.email;
    this.elements.studentId.value = this.settings.studentId || '';
    this.elements.university.value = this.settings.university;
    this.elements.darkModeToggle.checked = this.settings.darkMode;
    this.elements.emailNotifications.checked = this.settings.emailNotifications;
    this.elements.languageSelect.value = this.settings.language;

    // Apply dark mode
    if (this.settings.darkMode) {
      document.documentElement.classList.add('dark');
    }
  }

  /**
   * Save settings
   */
  async saveSettings() {
    // Extract first and last name from full name
    const nameParts = this.elements.fullName.value.trim().split(' ');
    const firstname = nameParts[0] || '';
    const lastname = nameParts.slice(1).join(' ') || '';

    const profileData = {
      firstname,
      lastname,
      email: this.elements.email.value,
      studentId: this.elements.studentId.value,
      university: this.elements.university.value
    };

    const preferencesData = {
      darkMode: this.elements.darkModeToggle.checked,
      emailNotifications: this.elements.emailNotifications.checked
    };

    try {
      if (USE_API) {
        // Update profile
        const profileRes = await fetch('/api/settings/profile', {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify(profileData)
        });

        if (!profileRes.ok) {
          const err = await profileRes.json();
          throw new Error(err.message || 'Failed to update profile');
        }

        // Update preferences
        const prefRes = await fetch('/api/settings/preferences', {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify(preferencesData)
        });

        if (!prefRes.ok) {
          const err = await prefRes.json();
          throw new Error(err.message || 'Failed to update preferences');
        }

        // Update local settings
        this.settings = {
          ...this.settings,
          fullName: this.elements.fullName.value,
          firstname,
          lastname,
          email: this.elements.email.value,
          studentId: this.elements.studentId.value,
          university: this.elements.university.value,
          darkMode: preferencesData.darkMode,
          emailNotifications: preferencesData.emailNotifications,
          language: this.elements.languageSelect.value
        };

        this.showNotification('Zmiany zostały zapisane!');
      } else {
        this.settings = {
          fullName: this.elements.fullName.value,
          email: this.elements.email.value,
          studentId: this.elements.studentId.value,
          university: this.elements.university.value,
          darkMode: this.elements.darkModeToggle.checked,
          emailNotifications: this.elements.emailNotifications.checked,
          language: this.elements.languageSelect.value,
          integrations: this.settings.integrations
        };
        localStorage.setItem('studify_settings', JSON.stringify(this.settings));
        this.showNotification('Zmiany zostały zapisane!');
      }
    } catch (err) {
      console.error('Error saving settings:', err);
      this.showNotification('Błąd: ' + err.message, true);
    }
  }

  /**
   * Toggle dark mode
   */
  toggleDarkMode(enabled) {
    if (enabled) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  }

  /**
   * Handle integration
   */
  handleIntegration(type) {
    if (this.settings.integrations[type]) {
      // Disconnect
      if (confirm(`Czy na pewno chcesz odłączyć integrację?`)) {
        this.settings.integrations[type] = false;
        this.saveSettings();
        location.reload();
      }
    } else {
      // Connect (simulate)
      this.showNotification(`Łączenie z ${type === 'google' ? 'Google Calendar' : 'OneDrive'}...`);
      setTimeout(() => {
        this.settings.integrations[type] = true;
        this.saveSettings();
        this.showNotification('Połączono pomyślnie!');
      }, 1500);
    }
  }

  /**
   * Open delete account modal
   */
  openDeleteModal() {
    this.elements.deleteModal.classList.add('modal--active');
    this.elements.confirmDelete.value = '';
    this.elements.confirmDeleteBtn.disabled = true;
  }

  /**
   * Close delete account modal
   */
  closeDeleteModal() {
    this.elements.deleteModal.classList.remove('modal--active');
  }

  /**
   * Delete account
   */
  deleteAccount() {
    // Clear all data
    localStorage.removeItem('studify_settings');
    localStorage.removeItem('studify_tasks');
    localStorage.removeItem('studify_calendar_events');
    localStorage.removeItem('studify_habits');

    this.showNotification('Konto zostało usunięte.');

    // Redirect to login
    setTimeout(() => {
      window.location.href = '/logout';
    }, 1500);
  }

  /**
   * Show notification
   */
  showNotification(message, isError = false) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.innerHTML = `
      <span class="material-symbols-outlined">${isError ? 'error' : 'check_circle'}</span>
      <span>${message}</span>
    `;

    // Style notification
    Object.assign(notification.style, {
      position: 'fixed',
      bottom: '24px',
      right: '24px',
      display: 'flex',
      alignItems: 'center',
      gap: '12px',
      padding: '16px 24px',
      background: isError ? '#ef4444' : '#10b981',
      color: '#fff',
      borderRadius: '12px',
      fontWeight: '600',
      fontSize: '14px',
      boxShadow: `0 8px 20px ${isError ? 'rgba(239, 68, 68, 0.3)' : 'rgba(16, 185, 129, 0.3)'}`,
      zIndex: '9999',
      animation: 'slideIn 0.3s ease'
    });

    document.body.appendChild(notification);

    // Remove after delay
    setTimeout(() => {
      notification.style.animation = 'slideOut 0.3s ease';
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(100%);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);

// Initialize settings manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  window.settingsManager = new SettingsManager();
});
