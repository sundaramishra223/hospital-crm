<?php
$current_year = date('Y');
$system_version = getSetting('system_version', '1.0.0');
$last_backup = getSetting('last_backup', 'Never');
$site_title = getSetting('site_title', 'Hospital CRM');
$theme_color = getSetting('theme_color', '#667eea');
$online_users = getOnlineUsersCount();
$system_status = getSystemStatus();
?>

<footer class="main-footer" style="--primary-color: <?php echo $theme_color; ?>;">
    <div class="footer-content">
        <!-- Left Section - Brand & Copyright -->
        <div class="footer-left">
            <div class="footer-brand">
                <div class="brand-logo-wrapper">
                    <img src="<?php echo getSetting('logo', 'assets/images/logo.png'); ?>" alt="Logo" class="footer-logo">
                    <div class="logo-glow"></div>
                </div>
                <div class="brand-info">
                    <span class="brand-name"><?php echo $site_title; ?></span>
                    <span class="copyright">&copy; <?php echo $current_year; ?> All rights reserved</span>
                </div>
            </div>
            <div class="version-info">
                <span class="version-badge">
                    <i class="fa fa-code"></i>
                    v<?php echo $system_version; ?>
                </span>
            </div>
        </div>
        
        <!-- Center Section - System Status -->
        <div class="footer-center">
            <div class="system-status">
                <div class="status-item">
                    <div class="status-icon <?php echo $system_status['database'] ? 'online' : 'offline'; ?>">
                        <i class="fa fa-database"></i>
                    </div>
                    <div class="status-info">
                        <span class="status-label">Database</span>
                        <span class="status-value"><?php echo $system_status['database'] ? 'Online' : 'Offline'; ?></span>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-icon online">
                        <i class="fa fa-users"></i>
                    </div>
                    <div class="status-info">
                        <span class="status-label">Online Users</span>
                        <span class="status-value" id="onlineUsers"><?php echo $online_users; ?></span>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-icon <?php echo $system_status['backup'] ? 'online' : 'warning'; ?>">
                        <i class="fa fa-cloud"></i>
                    </div>
                    <div class="status-info">
                        <span class="status-label">Last Backup</span>
                        <span class="status-value"><?php echo $last_backup; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Section - Quick Actions -->
        <div class="footer-right">
            <div class="footer-actions">
                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                    <a href="modules/system-settings.php" class="footer-action" title="System Settings">
                        <div class="action-icon">
                            <i class="fa fa-cog"></i>
                        </div>
                        <span class="action-text">Settings</span>
                    </a>
                    
                    <a href="modules/backup.php" class="footer-action" title="System Backup">
                        <div class="action-icon">
                            <i class="fa fa-download"></i>
                        </div>
                        <span class="action-text">Backup</span>
                    </a>
                    
                    <a href="modules/logs.php" class="footer-action" title="System Logs">
                        <div class="action-icon">
                            <i class="fa fa-file-text"></i>
                        </div>
                        <span class="action-text">Logs</span>
                    </a>
                <?php endif; ?>
                
                <a href="modules/help.php" class="footer-action" title="Help & Support">
                    <div class="action-icon">
                        <i class="fa fa-question-circle"></i>
                    </div>
                    <span class="action-text">Help</span>
                </a>
                
                <a href="modules/contact.php" class="footer-action" title="Contact Support">
                    <div class="action-icon">
                        <i class="fa fa-envelope"></i>
                    </div>
                    <span class="action-text">Contact</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Footer Bottom - Additional Info -->
    <div class="footer-bottom">
        <div class="footer-stats">
            <div class="stat-item">
                <i class="fa fa-clock-o"></i>
                <span>Uptime: <span id="systemUptime">99.9%</span></span>
            </div>
            <div class="stat-item">
                <i class="fa fa-shield"></i>
                <span>Security: <span class="status-secure">Secure</span></span>
            </div>
            <div class="stat-item">
                <i class="fa fa-heartbeat"></i>
                <span>Health: <span class="status-healthy">Healthy</span></span>
            </div>
        </div>
    </div>
</footer>

<style>
/* Modern Footer Styles */
.main-footer {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    padding: 20px 30px 10px;
    position: fixed;
    bottom: 0;
    left: 280px;
    right: 0;
    z-index: 1010;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
}

.dark-mode .main-footer {
    background: rgba(26, 26, 26, 0.95);
    border-top-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
}

.sidebar-collapsed .main-footer {
    left: 70px;
}

.footer-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 30px;
    margin-bottom: 15px;
}

/* Left Section - Brand */
.footer-left {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 200px;
}

.footer-brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.brand-logo-wrapper {
    position: relative;
    width: 35px;
    height: 35px;
}

.footer-logo {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    padding: 6px;
    box-shadow: 0 2px 10px rgba(102, 126, 234, 0.2);
}

.logo-glow {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    border-radius: 8px;
    opacity: 0.1;
    filter: blur(8px);
    z-index: -1;
}

.brand-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.brand-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--primary-color);
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.copyright {
    font-size: 11px;
    color: #666;
    opacity: 0.8;
}

.dark-mode .copyright {
    color: #ccc;
}

.version-info {
    display: flex;
    align-items: center;
}

.version-badge {
    display: flex;
    align-items: center;
    gap: 5px;
    background: rgba(102, 126, 234, 0.1);
    color: var(--primary-color);
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.version-badge i {
    font-size: 10px;
}

/* Center Section - System Status */
.footer-center {
    flex: 1;
    display: flex;
    justify-content: center;
}

.system-status {
    display: flex;
    gap: 30px;
    align-items: center;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: rgba(248, 249, 250, 0.8);
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.dark-mode .status-item {
    background: rgba(40, 40, 40, 0.8);
    border-color: rgba(255, 255, 255, 0.1);
}

.status-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.status-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: white;
    position: relative;
}

.status-icon.online {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.status-icon.offline {
    background: linear-gradient(135deg, #dc3545, #fd7e14);
}

.status-icon.warning {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.status-icon::after {
    content: '';
    position: absolute;
    top: -2px;
    right: -2px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
    100% { opacity: 1; transform: scale(1); }
}

.status-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.status-label {
    font-size: 10px;
    color: #666;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.dark-mode .status-label {
    color: #ccc;
}

.status-value {
    font-size: 12px;
    font-weight: 600;
    color: #333;
}

.dark-mode .status-value {
    color: #fff;
}

/* Right Section - Actions */
.footer-right {
    display: flex;
    align-items: center;
}

.footer-actions {
    display: flex;
    gap: 8px;
}

.footer-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 8px 12px;
    color: #666;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
    min-width: 60px;
}

.footer-action:hover {
    color: var(--primary-color);
    background: rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
    text-decoration: none;
}

.action-icon {
    width: 24px;
    height: 24px;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: var(--primary-color);
    transition: all 0.3s ease;
}

.footer-action:hover .action-icon {
    background: var(--primary-color);
    color: white;
    transform: scale(1.1);
}

.action-text {
    font-size: 10px;
    font-weight: 500;
    text-align: center;
}

/* Footer Bottom */
.footer-bottom {
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    padding-top: 10px;
}

.dark-mode .footer-bottom {
    border-top-color: rgba(255, 255, 255, 0.1);
}

.footer-stats {
    display: flex;
    justify-content: center;
    gap: 30px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #666;
}

.dark-mode .stat-item {
    color: #ccc;
}

.stat-item i {
    width: 12px;
    color: var(--primary-color);
}

.status-secure {
    color: #28a745;
    font-weight: 600;
}

.status-healthy {
    color: #20c997;
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .system-status {
        gap: 20px;
    }
    
    .status-item {
        padding: 6px 10px;
    }
}

@media (max-width: 991.98px) {
    .main-footer {
        left: 0;
        padding: 15px 20px 8px;
    }
    
    .footer-content {
        gap: 20px;
    }
    
    .system-status {
        gap: 15px;
    }
    
    .footer-actions {
        gap: 6px;
    }
}

@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .footer-left {
        min-width: auto;
        align-items: center;
    }
    
    .footer-center {
        order: 3;
    }
    
    .system-status {
        justify-content: center;
        gap: 10px;
    }
    
    .status-item {
        padding: 6px 8px;
    }
    
    .status-info {
        display: none;
    }
    
    .footer-right {
        order: 2;
        justify-content: center;
    }
    
    .footer-actions {
        gap: 4px;
    }
    
    .footer-action {
        min-width: 50px;
        padding: 6px 8px;
    }
    
    .action-text {
        display: none;
    }
    
    .footer-stats {
        gap: 20px;
    }
}

@media (max-width: 480px) {
    .main-footer {
        padding: 12px 15px 6px;
    }
    
    .brand-name {
        font-size: 12px;
    }
    
    .copyright {
        font-size: 10px;
    }
    
    .system-status {
        gap: 8px;
    }
    
    .status-icon {
        width: 24px;
        height: 24px;
        font-size: 10px;
    }
    
    .footer-stats {
        gap: 15px;
    }
    
    .stat-item {
        font-size: 10px;
    }
}
</style>

<script>
// Update online users count
function updateOnlineUsers() {
    fetch('api/online-users.php')
        .then(response => response.json())
        .then(data => {
            const onlineUsersElement = document.getElementById('onlineUsers');
            if (onlineUsersElement) {
                onlineUsersElement.textContent = data.count || 1;
            }
        })
        .catch(error => {
            console.log('Error fetching online users:', error);
        });
}

// Update system uptime
function updateSystemUptime() {
    fetch('api/system-status.php')
        .then(response => response.json())
        .then(data => {
            const uptimeElement = document.getElementById('systemUptime');
            if (uptimeElement && data.uptime) {
                uptimeElement.textContent = data.uptime;
            }
        })
        .catch(error => {
            console.log('Error fetching system status:', error);
        });
}

// Enhanced footer functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initial updates
    updateOnlineUsers();
    updateSystemUptime();
    
    // Update every 30 seconds
    setInterval(updateOnlineUsers, 30000);
    setInterval(updateSystemUptime, 60000);
    
    // Footer scroll effect
    let lastScroll = 0;
    const footer = document.querySelector('.main-footer');
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        
        // Show footer when near bottom
        if (currentScroll + windowHeight > documentHeight - 100) {
            footer.style.transform = 'translateY(0)';
        } else if (currentScroll > lastScroll && currentScroll > 200) {
            // Hide footer when scrolling down
            footer.style.transform = 'translateY(100%)';
        } else {
            // Show footer when scrolling up
            footer.style.transform = 'translateY(0)';
        }
        
        lastScroll = currentScroll;
    });
    
    // Status item hover effects
    const statusItems = document.querySelectorAll('.status-item');
    statusItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px) scale(1.02)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Footer action hover effects
    const footerActions = document.querySelectorAll('.footer-action');
    footerActions.forEach(action => {
        action.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        action.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});

// Helper function to get online users count (if not available in PHP)
function getOnlineUsersCount() {
    return <?php echo $online_users; ?>;
}
</script>

<?php
// Helper functions
function getOnlineUsersCount() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'] ?? 1;
    } catch (Exception $e) {
        return 1;
    }
}

function getSystemStatus() {
    global $pdo;
    
    try {
        // Test database connection
        $pdo->query("SELECT 1");
        $database_status = true;
    } catch (Exception $e) {
        $database_status = false;
    }
    
    // Check backup status
    $last_backup = getSetting('last_backup', 'Never');
    $backup_status = ($last_backup != 'Never' && strtotime($last_backup) > strtotime('-24 hours'));
    
    return [
        'database' => $database_status,
        'backup' => $backup_status
    ];
}
?>
