<?php
/**
 * Koperasi SaaS Application - Main Entry Point
 * Redirects to the main dashboard
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: frontend/pages/auth/login.html');
    exit;
}

// Redirect based on user role
$user_role = $_SESSION['user_role'];

switch ($user_role) {
    case 'super_admin':
        header('Location: frontend/dashboards/admin/super_admin_dashboard.html');
        break;
    case 'admin':
        header('Location: frontend/dashboards/admin/admin_dashboard.html');
        break;
    case 'member':
        header('Location: frontend/dashboards/member/member_dashboard.html');
        break;
    case 'kasir':
        header('Location: frontend/dashboards/staff/kasir_dashboard.html');
        break;
    case 'teller':
        header('Location: frontend/dashboards/staff/teller_dashboard.html');
        break;
    case 'mantri':
        header('Location: frontend/dashboards/staff/mantri_dashboard.html');
        break;
    case 'surveyor':
        header('Location: frontend/dashboards/staff/surveyor_dashboard.html');
        break;
    case 'collector':
        header('Location: frontend/dashboards/staff/collector_dashboard.html');
        break;
    default:
        header('Location: frontend/pages/dashboard.html');
        break;
}

exit;
?>
