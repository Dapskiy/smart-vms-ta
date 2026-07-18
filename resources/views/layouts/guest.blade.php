<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Tamu - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>
    @livewireScripts
    <script>
        (function() {
            const registerHook = () => {
                Livewire.hook('request', ({ fail }) => {
                    fail(({ status, preventDefault }) => {
                        if (status === 419) {
                            preventDefault();
                            window.location.reload();
                        }
                    });
                });
            };
            if (window.Livewire) {
                registerHook();
            } else {
                document.addEventListener('livewire:init', registerHook);
            }
        })();
    </script>
</body>
</html>
