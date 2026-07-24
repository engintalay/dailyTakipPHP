/**
 * dailyTakip - Main JavaScript
 * Alpine.js compatible
 */

// Toast notification system
window.showToast = function(message, type = 'success') {
    const existing = document.querySelector('.toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'opacity 0.3s, transform 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

// Format date to YYYY-MM-DD
window.formatDateOnly = function(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

// Format date to DD MMM YYYY
window.formatDateShort = function(date) {
    const d = new Date(date);
    const months = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
};

// Escape HTML
window.escapeHtml = function(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
};

// Format file size
window.formatFileSize = function(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

// API helper
window.api = {
    get: function(url, params = {}) {
        const query = new URLSearchParams(params).toString();
        return fetch(window.APP_URL + 'api/' + url + (query ? '?' + query : ''), {
            headers: { 'Content-Type': 'application/json' }
        }).then(r => r.json());
    },

    post: function(url, data = {}) {
        data.csrf_token = window.CSRF_TOKEN;
        return fetch(window.APP_URL + 'api/' + url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => r.json());
    },

    put: function(url, data = {}) {
        data.csrf_token = window.CSRF_TOKEN;
        return fetch(window.APP_URL + 'api/' + url, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => r.json());
    },

    delete: function(url, data = {}) {
        data.csrf_token = window.CSRF_TOKEN;
        return fetch(window.APP_URL + 'api/' + url, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => r.json());
    }
};

// Confirm dialog
window.confirmAction = function(message, callback) {
    if (confirm(message)) {
        callback();
    }
};

// Debounce function
window.debounce = function(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss flash messages
    const flash = document.querySelector('[role="alert"]');
    if (flash) {
        setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transition = 'opacity 0.5s';
            setTimeout(() => flash.remove(), 500);
        }, 5000);
    }

    // Mobile sidebar toggle
    window.toggleSidebar = function() {
        const sidebar = document.querySelector('aside');
        if (sidebar) {
            sidebar.classList.toggle('hidden');
        }
    };

    // Close sidebar on link click (mobile)
    document.querySelectorAll('aside a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                const sidebar = document.querySelector('aside');
                if (sidebar) sidebar.classList.add('hidden');
            }
        });
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[placeholder*="ara" i], input[placeholder*="search" i]');
        if (searchInput) searchInput.focus();
    }

    // Escape to close modals/forms
    if (e.key === 'Escape') {
        const forms = document.querySelectorAll('form[style*="display: none"], form.hidden, form[class*="hidden"]');
        forms.forEach(f => {
            const cancelBtn = f.querySelector('button[type="button"]');
            if (cancelBtn) cancelBtn.click();
        });
    }
});

// Export for Alpine.js
window.DailyTakip = {
    formatDateOnly: window.formatDateOnly,
    formatDateShort: window.formatDateShort,
    escapeHtml: window.escapeHtml,
    formatFileSize: window.formatFileSize,
    showToast: window.showToast,
    api: window.api,
    confirmAction: window.confirmAction,
    debounce: window.debounce
};

// Dark mode toggle
window.toggleDarkMode = function() {
    var html = document.documentElement;
    var isDark = html.classList.toggle('dark');
    localStorage.setItem('dailyTakip-dark', isDark ? 'true' : 'false');
    var icons = ['darkModeIcon', 'mobileDarkModeIcon'];
    var labels = ['darkModeLabel', 'mobileDarkModeLabel'];
    for (var i = 0; i < icons.length; i++) {
        var icon = document.getElementById(icons[i]);
        if (icon) icon.textContent = isDark ? '☀️' : '🌙';
    }
    for (var j = 0; j < labels.length; j++) {
        var label = document.getElementById(labels[j]);
        if (label) label.textContent = isDark ? 'Aydınlık Mod' : 'Karanlık Mod';
    }
};

window.updateDarkModeControls = function() {
    var isDark = document.documentElement.classList.contains('dark');
    var icons = document.querySelectorAll('#darkModeIcon, #mobileDarkModeIcon');
    var labels = document.querySelectorAll('#darkModeLabel, #mobileDarkModeLabel');
    for (var i = 0; i < icons.length; i++) {
        icons[i].textContent = isDark ? '☀️' : '🌙';
    }
    for (var j = 0; j < labels.length; j++) {
        labels[j].textContent = isDark ? 'Aydınlık Mod' : 'Karanlık Mod';
    }
};

document.addEventListener('DOMContentLoaded', function() {
    window.updateDarkModeControls();
});
