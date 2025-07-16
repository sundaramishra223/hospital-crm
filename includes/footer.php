<?php
$current_year = date('Y');
$system_version = getSetting('system_version', '1.0.0');
$last_backup = getSetting('last_backup', 'Never');
?>

<footer class="main-footer">
    <div class="footer-content">
        <div class="footer-left">
            <span>&copy; <?php echo $current_year; ?> <?php echo getSetting('site_title', 'Hospital CRM'); ?>. All rights reserved.</span>
            <span class="version">Version <?php echo $system_version; ?></span>
        </div>
        
        <div class="footer-center">
            <div class="system-info">
                <span class="info-item">
                    <i class="fa fa-database"></i>
                    Last Backup: <?php echo $last_backup; ?>
                </span>
                <span class="info-item">
                    <i class="fa fa-users"></i>
                    Online Users: <span id="onlineUsers">1</span>
                </span>
            </div>
        </div>
        
        <div class="footer-right">
            <div class="quick-links">
                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                    <a href="modules/system-settings.php" class="footer-link">
                        <i class="fa fa-cog"></i> Settings
                    </a>
                    <a href="modules/backup.php" class="footer-link">
                        <i class="fa fa-download"></i> Backup
                    </a>
                <?php endif; ?>
                <a href="modules/help.php" class="footer-link">
                    <i class="fa fa-question-circle"></i> Help
                </a>
            </div>
        </div>
    </div>
</footer>

<style>
/* Footer Styles */
.main-footer {
    background: #fff;
    border-top: 1px solid #e9ecef;
    padding: 15px 30px;
    position: fixed;
    bottom: 0;
    left: 280px;
    right: 0;
    z-index: 1010;
    height: 60px;
    transition: left 0.3s ease;
}

.dark-mode .main-footer {
    background: #1a1a1a;
    border-top-color: #333;
}

.sidebar-collapsed .main-footer {
    left: 70px;
}

.footer-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 100%;
    font-size: 13px;
}

.footer-left {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.footer-left span {
    color: #666;
}

.dark-mode .footer-left span {
    color: #ccc;
}

.version {
    font-size: 11px;
    opacity: 0.7;
}

.footer-center {
    flex: 1;
    display: flex;
    justify-content: center;
}

.system-info {
    display: flex;
    gap: 20px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
    font-size: 12px;
}

.dark-mode .info-item {
    color: #ccc;
}

.info-item i {
    width: 12px;
    text-align: center;
}

.footer-right {
    display: flex;
    align-items: center;
}

.quick-links {
    display: flex;
    gap: 15px;
}

.footer-link {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
    text-decoration: none;
    font-size: 12px;
    transition: color 0.3s ease;
}

.footer-link:hover {
    color: var(--primary-color);
    text-decoration: none;
}

.dark-mode .footer-link {
    color: #ccc;
}

.footer-link i {
    font-size: 11px;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .main-footer {
        left: 0;
        padding: 10px 15px;
        height: 50px;
    }
    
    .footer-content {
        font-size: 11px;
    }
    
    .footer-center {
        display: none;
    }
    
    .quick-links {
        gap: 10px;
    }
    
    .footer-link span {
        display: none;
    }
}

@media (max-width: 480px) {
    .footer-left {
        font-size: 10px;
    }
    
    .footer-right {
        display: none;
    }
}
</style>

<script>
// Update online users count
function updateOnlineUsers() {
    fetch('api/online-users.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('onlineUsers').textContent = data.count;
        })
        .catch(error => {
            console.log('Error fetching online users:', error);
        });
}

// Update every 30 seconds
setInterval(updateOnlineUsers, 30000);
</script>