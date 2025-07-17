/**
 * Hospital CRM - Modern UI Application JavaScript
 */

// Global application object
const HospitalCRM = {
    charts: {},
    sidebar: {
        isCollapsed: false,
        isMobileOpen: false
    },
    theme: {
        current: 'light'
    }
};

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Main application initialization
 */
function initializeApp() {
    initializeSidebar();
    initializeTheme();
    initializeCharts();
    initializeNotifications();
    initializeTooltips();
    setupEventListeners();
    
    console.log('Hospital CRM Application Initialized');
}

/**
 * Sidebar functionality
 */
function initializeSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('mainSidebar');
    const body = document.body;
    
    // Toggle sidebar
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                body.classList.toggle('sidebar-open');
                HospitalCRM.sidebar.isMobileOpen = !HospitalCRM.sidebar.isMobileOpen;
            } else {
                body.classList.toggle('sidebar-collapsed');
                HospitalCRM.sidebar.isCollapsed = !HospitalCRM.sidebar.isCollapsed;
                
                // Resize charts after sidebar toggle
                setTimeout(() => {
                    Object.values(HospitalCRM.charts).forEach(chart => {
                        if (chart && chart.resize) {
                            chart.resize();
                        }
                    });
                }, 300);
            }
        });
    }
    
    // Submenu toggle
    document.querySelectorAll('[data-toggle="submenu"]').forEach(function(element) {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.closest('.nav-item');
            const isOpen = parent.classList.contains('open');
            
            // Close all other submenus
            document.querySelectorAll('.nav-item.has-submenu.open').forEach(function(item) {
                if (item !== parent) {
                    item.classList.remove('open');
                }
            });
            
            // Toggle current submenu
            parent.classList.toggle('open');
            
            // Add ripple effect
            addRippleEffect(this, e);
        });
    });
    
    // Auto-open submenu if current page is in submenu
    const currentUrl = window.location.pathname;
    document.querySelectorAll('.submenu-link').forEach(function(link) {
        if (link.getAttribute('href') && currentUrl.includes(link.getAttribute('href'))) {
            const submenu = link.closest('.nav-item');
            if (submenu) {
                submenu.classList.add('open');
                link.classList.add('active');
            }
        }
    });
    
    // Create mobile overlay
    if (window.innerWidth <= 768 && !document.querySelector('.sidebar-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.addEventListener('click', function() {
            body.classList.remove('sidebar-open');
            HospitalCRM.sidebar.isMobileOpen = false;
        });
        body.appendChild(overlay);
    }
    
    // Add tooltips for collapsed sidebar
    updateSidebarTooltips();
}

/**
 * Update sidebar tooltips for collapsed state
 */
function updateSidebarTooltips() {
    document.querySelectorAll('.nav-link').forEach(function(link) {
        const textElement = link.querySelector('.nav-text');
        if (textElement) {
            link.setAttribute('data-title', textElement.textContent);
        }
    });
}

/**
 * Theme functionality
 */
function initializeTheme() {
    const savedTheme = localStorage.getItem('hospital-crm-theme') || 'light';
    setTheme(savedTheme);
    
    // Update theme icon
    updateThemeIcon(savedTheme);
}

function toggleTheme() {
    const currentTheme = HospitalCRM.theme.current;
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    setTheme(newTheme);
    updateSetting('theme_mode', newTheme);
}

function setTheme(theme) {
    const body = document.body;
    
    // Remove existing theme classes
    body.classList.remove('light-mode', 'dark-mode');
    
    // Add new theme class
    body.classList.add(theme + '-mode');
    
    // Update global state
    HospitalCRM.theme.current = theme;
    
    // Save to localStorage
    localStorage.setItem('hospital-crm-theme', theme);
    
    // Update theme icon
    updateThemeIcon(theme);
}

function updateThemeIcon(theme) {
    const icon = document.getElementById('themeIcon');
    if (icon) {
        icon.className = theme === 'light' ? 'fa fa-moon-o' : 'fa fa-sun-o';
    }
}

/**
 * Charts initialization and management
 */
function initializeCharts() {
    // Revenue Chart
    const revenueChartCanvas = document.getElementById('revenueChart');
    if (revenueChartCanvas) {
        HospitalCRM.charts.revenue = initializeRevenueChart();
    }
    
    // Staff Distribution Chart
    const staffChartCanvas = document.getElementById('staffChart');
    if (staffChartCanvas) {
        HospitalCRM.charts.staff = initializeStaffChart();
    }
    
    // Patient Statistics Chart
    const patientChartCanvas = document.getElementById('patientChart');
    if (patientChartCanvas) {
        HospitalCRM.charts.patients = initializePatientChart();
    }
    
    // Appointment Trends Chart
    const appointmentChartCanvas = document.getElementById('appointmentChart');
    if (appointmentChartCanvas) {
        HospitalCRM.charts.appointments = initializeAppointmentChart();
    }
}

/**
 * Revenue Chart
 */
function initializeRevenueChart() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(37, 99, 235, 0.3)');
    gradient.addColorStop(1, 'rgba(37, 99, 235, 0.05)');
    
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Revenue (₹)',
                data: [45000, 52000, 48000, 61000, 55000, 67000, 73000, 69000, 78000, 82000, 88000, 95000],
                borderColor: '#2563eb',
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#2563eb',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#2563eb',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: ₹' + context.raw.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#64748b'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(148, 163, 184, 0.1)'
                    },
                    ticks: {
                        color: '#64748b',
                        callback: function(value) {
                            return '₹' + (value / 1000) + 'k';
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

/**
 * Staff Distribution Chart
 */
function initializeStaffChart() {
    const ctx = document.getElementById('staffChart').getContext('2d');
    
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Doctors', 'Nurses', 'Staff', 'Pharmacy', 'Lab Tech'],
            datasets: [{
                data: [25, 45, 30, 12, 8],
                backgroundColor: [
                    '#10b981',
                    '#2563eb',
                    '#f59e0b',
                    '#06b6d4',
                    '#8b5cf6'
                ],
                borderWidth: 0,
                cutout: '60%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw + ' staff';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Notifications system
 */
function initializeNotifications() {
    loadNotifications();
    
    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);
}

function loadNotifications() {
    // Simulate loading notifications
    const notificationCount = document.getElementById('notificationCount');
    const notificationList = document.getElementById('notificationList');
    
    if (notificationCount && notificationList) {
        // Mock data - replace with actual API call
        const notifications = [
            {
                id: 1,
                type: 'appointment',
                title: 'New Appointment',
                message: 'Dr. Smith has a new appointment at 2:00 PM',
                time: '5 minutes ago',
                unread: true
            },
            {
                id: 2,
                type: 'patient',
                title: 'Patient Registered',
                message: 'New patient John Doe has been registered',
                time: '10 minutes ago',
                unread: true
            },
            {
                id: 3,
                type: 'emergency',
                title: 'Emergency Alert',
                message: 'Emergency patient admitted to ICU',
                time: '15 minutes ago',
                unread: false
            }
        ];
        
        notificationCount.textContent = notifications.filter(n => n.unread).length;
        
        notificationList.innerHTML = notifications.map(notification => `
            <div class="dropdown-item ${notification.unread ? 'unread' : ''}">
                <div class="notification-content">
                    <h6>${notification.title}</h6>
                    <p>${notification.message}</p>
                    <small>${notification.time}</small>
                </div>
            </div>
        `).join('');
    }
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    // Initialize Bootstrap tooltips if available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Window resize handler
    window.addEventListener('resize', function() {
        handleWindowResize();
    });
    
    // Dropdown toggles
    document.querySelectorAll('[data-toggle="dropdown"]').forEach(function(element) {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            toggleDropdown(this);
        });
    });
    
    // Click outside to close dropdowns
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            closeAllDropdowns();
        }
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Handle window resize
 */
function handleWindowResize() {
    const body = document.body;
    
    if (window.innerWidth > 768) {
        body.classList.remove('sidebar-open');
        HospitalCRM.sidebar.isMobileOpen = false;
        
        // Remove mobile overlay if exists
        const overlay = document.querySelector('.sidebar-overlay');
        if (overlay && window.innerWidth > 768) {
            overlay.remove();
        }
    } else {
        // Create mobile overlay if doesn't exist
        if (!document.querySelector('.sidebar-overlay')) {
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            overlay.addEventListener('click', function() {
                body.classList.remove('sidebar-open');
                HospitalCRM.sidebar.isMobileOpen = false;
            });
            body.appendChild(overlay);
        }
    }
    
    // Resize charts
    setTimeout(() => {
        Object.values(HospitalCRM.charts).forEach(chart => {
            if (chart && chart.resize) {
                chart.resize();
            }
        });
    }, 100);
}

/**
 * Dropdown functionality
 */
function toggleDropdown(trigger) {
    const dropdown = trigger.closest('.dropdown');
    const menu = dropdown.querySelector('.dropdown-menu');
    
    // Close other dropdowns
    closeAllDropdowns();
    
    // Toggle current dropdown
    if (menu) {
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        dropdown.classList.toggle('show');
    }
}

function closeAllDropdowns() {
    document.querySelectorAll('.dropdown').forEach(function(dropdown) {
        const menu = dropdown.querySelector('.dropdown-menu');
        if (menu) {
            menu.style.display = 'none';
            dropdown.classList.remove('show');
        }
    });
}

/**
 * Ripple effect for buttons
 */
function addRippleEffect(element, event) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    ripple.classList.add('ripple');
    
    element.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

/**
 * Update setting via AJAX
 */
function updateSetting(key, value) {
    fetch('api/update-setting.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({key: key, value: value})
    }).catch(error => {
        console.error('Error updating setting:', error);
    });
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fa fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}"></i>
            <span>${message}</span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

/**
 * Loading state management
 */
function showLoading(element) {
    element.classList.add('loading');
}

function hideLoading(element) {
    element.classList.remove('loading');
}

/**
 * Utility functions
 */
const Utils = {
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR'
        }).format(amount);
    },
    
    formatDate: function(date) {
        return new Intl.DateTimeFormat('en-IN').format(new Date(date));
    },
    
    formatTime: function(time) {
        return new Intl.DateTimeFormat('en-IN', {
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(time));
    },
    
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Global functions for backward compatibility
window.toggleTheme = toggleTheme;
window.showToast = showToast;
window.HospitalCRM = HospitalCRM;