/* Main Application JavaScript */
$(document).ready(function() {
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Sidebar toggle functionality
    $('.sidebar-toggle').click(function() {
        $('.sidebar').toggleClass('collapsed');
        $('.main-content').toggleClass('sidebar-collapsed');
    });
    
    // Theme toggle functionality
    $('.theme-toggle').click(function() {
        $('body').toggleClass('dark-mode');
        const isDark = $('body').hasClass('dark-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        $(this).text(isDark ? '☀️' : '🌙');
    });
    
    // Load saved theme
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        $('body').addClass('dark-mode');
        $('.theme-toggle').text('☀️');
    }
    
    // Form validation
    $('.needs-validation').submit(function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
    
    // Auto-hide alerts after 5 seconds
    $('.alert').each(function() {
        const alert = $(this);
        setTimeout(function() {
            alert.fadeOut();
        }, 5000);
    });
    
    // Search functionality
    $('.search-input').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        const target = $(this).data('target');
        
        $(target + ' tbody tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(searchTerm) > -1);
        });
    });
    
    // DataTable-like functionality
    $('.data-table').each(function() {
        const table = $(this);
        const tbody = table.find('tbody');
        const rows = tbody.find('tr').get();
        
        // Add sorting to headers
        table.find('th[data-sort]').click(function() {
            const column = $(this).data('sort');
            const order = $(this).hasClass('asc') ? 'desc' : 'asc';
            
            // Remove existing sort classes
            table.find('th').removeClass('asc desc');
            $(this).addClass(order);
            
            // Sort rows
            rows.sort(function(a, b) {
                const aVal = $(a).find('td').eq(column).text();
                const bVal = $(b).find('td').eq(column).text();
                
                if (order === 'asc') {
                    return aVal.localeCompare(bVal);
                } else {
                    return bVal.localeCompare(aVal);
                }
            });
            
            // Reorder table
            tbody.empty().append(rows);
        });
    });
    
    // Modal functionality
    $('[data-toggle="modal"]').click(function() {
        const target = $(this).data('target');
        $(target).modal('show');
    });
    
    // Form reset on modal close
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form')[0]?.reset();
        $(this).find('.was-validated').removeClass('was-validated');
    });
    
    // Auto-refresh for real-time data
    if ($('.auto-refresh').length) {
        setInterval(function() {
            $('.auto-refresh').each(function() {
                const url = $(this).data('refresh-url');
                if (url) {
                    $(this).load(url);
                }
            });
        }, 30000); // Refresh every 30 seconds
    }
    
    // Confirmation dialogs
    $('.confirm-action').click(function(e) {
        const message = $(this).data('confirm') || 'Are you sure?';
        if (!confirm(message)) {
            e.preventDefault();
        }
    });
    
    // Number formatting
    $('.currency').each(function() {
        const value = parseFloat($(this).text());
        if (!isNaN(value)) {
            $(this).text('₹' + value.toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        }
    });
    
    // Date formatting
    $('.date').each(function() {
        const date = new Date($(this).text());
        if (!isNaN(date.getTime())) {
            $(this).text(date.toLocaleDateString('en-IN'));
        }
    });
    
    // Time formatting
    $('.time').each(function() {
        const time = $(this).text();
        if (time) {
            const date = new Date('1970-01-01T' + time);
            $(this).text(date.toLocaleTimeString('en-IN', {
                hour: '2-digit',
                minute: '2-digit'
            }));
        }
    });
    
    // Status badges
    $('.status').each(function() {
        const status = $(this).text().toLowerCase();
        $(this).removeClass('badge-primary badge-success badge-warning badge-danger');
        
        switch(status) {
            case 'active':
            case 'completed':
            case 'paid':
                $(this).addClass('badge-success');
                break;
            case 'pending':
            case 'scheduled':
                $(this).addClass('badge-warning');
                break;
            case 'cancelled':
            case 'deleted':
            case 'expired':
                $(this).addClass('badge-danger');
                break;
            default:
                $(this).addClass('badge-primary');
        }
    });
    
    // Print functionality
    $('.print-btn').click(function() {
        window.print();
    });
    
    // Export functionality
    $('.export-btn').click(function() {
        const format = $(this).data('format') || 'csv';
        const table = $($(this).data('table'));
        
        if (format === 'csv') {
            exportTableToCSV(table);
        }
    });
    
    function exportTableToCSV(table) {
        const csv = [];
        const rows = table.find('tr');
        
        rows.each(function() {
            const row = [];
            $(this).find('th, td').each(function() {
                row.push('"' + $(this).text().replace(/"/g, '""') + '"');
            });
            csv.push(row.join(','));
        });
        
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'export.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
    
    // Initialize charts if present
    if (typeof Chart !== 'undefined') {
        $('.chart-canvas').each(function() {
            const canvas = this;
            const type = $(this).data('chart-type') || 'bar';
            const data = $(this).data('chart-data');
            
            if (data) {
                new Chart(canvas, {
                    type: type,
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        });
    }
});