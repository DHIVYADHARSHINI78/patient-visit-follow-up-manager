<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user_id'])) {
  
    header("Location: /patient-visit-follow-up-manager/login.php");
    exit; 
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare Manager</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: 700; letter-spacing: 1px; }

        @media (min-width: 992px) {
            .nav-item.dropdown:hover .dropdown-menu {
                display: block;
                margin-top: 0; 
                border-top: 3px solid #0dcaf0; 
                animation: fadeIn 0.2s ease-in;
            }
        }

   
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

      
        .dropdown-item:hover {
            background-color: #f1f3f5;
            color: #000;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand text-info" href="/patient-visit-follow-up-manager/index.php">
            <i class="bi bi-hospital"></i> CLINIC PRO
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/patient-visit-follow-up-manager/index.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/patient-visit-follow-up-manager/patients/list.php">Patients</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/patient-visit-follow-up-manager/visits/list.php">Visits</a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="reportDrop" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Reports
                    </a>
                    <ul class="dropdown-menu shadow border-0 py-2">
                        <li>
                            <a class="dropdown-item" href="/patient-visit-follow-up-manager/reports/followups.php">
                                <i class="bi bi-calendar-check-fill text-primary me-2"></i> Follow-ups
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/patient-visit-follow-up-manager/reports/birthdays.php">
                                <i class="bi bi-cake2-fill text-danger me-2"></i> Birthdays
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/patient-visit-follow-up-manager/reports/monthly.php">
                                <i class="bi bi-bar-chart-line-fill text-success me-2"></i> Monthly Report
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="/patient-visit-follow-up-manager/reports/summary.php">
                                <i class="bi bi-file-earmark-medical-fill text-warning me-2"></i> Summary
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link text-warning fw-bold" href="/patient-visit-follow-up-manager/register.php">
                        <i class="bi bi-person-plus"></i> Add Staff
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center">
                <span class="text-light me-3 small d-none d-lg-inline">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'admin') ?>
                </span>
                <a href="/patient-visit-follow-up-manager/logout.php" class="btn btn-outline-danger btn-sm px-3">
                    Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<main class="container">