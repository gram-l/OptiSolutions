<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
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