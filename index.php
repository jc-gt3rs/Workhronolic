<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect(is_manager() ? 'admin/dashboard.php' : 'dashboard.php');
}
redirect('login.php');
