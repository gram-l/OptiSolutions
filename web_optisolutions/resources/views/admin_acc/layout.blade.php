<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>@yield('title', 'Polyclinic Admin')</title>
    @vite(['resources/css/admin_css/patients.css','resources/css/admin_css/user_management.css', 'resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css'])
</head>
<body>
    @include('admin_acc.header')
    @include('admin_acc.sidebar')
    <main>
        @yield('content')
    </main>
</body>
</html>