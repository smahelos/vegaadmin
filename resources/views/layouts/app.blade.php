<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full bg-gray-100">

<head>
    @include('layouts.frontend.partials.head')
    @vite('resources/css/app.css')
    @livewireStyles
</head>

<body class="h-full">
    <div class="flex flex-col h-screen">
        <!-- Page content -->
        <div class="flex-grow">
            @yield('content')
        </div>

        <!-- Footer -->
        @include('layouts.frontend.partials.footer')
    </div>

    @include('layouts.frontend.partials.scripts')
    @vite('resources/js/app.js')
    @livewireScripts

    <script>
        window.translations = {
            ares: @json(__('ares'))
        };
    </script>
</body>

</html>
