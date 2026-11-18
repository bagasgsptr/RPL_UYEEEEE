<?php
// includes/header.php
require_once __DIR__ . '/../config/session.php';
$current_page = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>Aplikasi Peminjaman Barang</title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#0084ff',
                            600: '#0070df',
                            700: '#005bb5',
                            dark: '#0a192f',
                            header: '#0b1b36',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-slate-100 text-slate-800 antialiased font-sans min-h-screen flex justify-center selection:bg-blue-100 selection:text-blue-600">

<!-- App Container: Mobile-first centered frame on desktop, 100% on mobile -->
<div class="w-full max-w-[430px] min-h-screen bg-slate-50 relative flex flex-col shadow-2xl overflow-x-hidden border-x border-slate-200/80">
