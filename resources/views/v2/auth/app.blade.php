<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">
    <title>@yield('title')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-shell min-h-screen text-[#2d2d2d] font-sans">
    <header class="bg-white border-b border-[#e4e2e0]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-xl font-extrabold tracking-[-0.04em] text-[#2d2d2d]">
                Best Way <span class="text-[#2557a7]">Jobs</span>
            </a>
            <a href="{{ route('home') }}" class="text-sm font-semibold text-[#2557a7] hover:underline">Back to jobs</a>
        </div>
    </header>

    <main class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-10">
        @yield('content')
    </main>

    @stack('extra-js')
</body>
</html>
