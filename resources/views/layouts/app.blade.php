<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
        <style>
            body {
                background: #f6f7fb;
            }
            .builder-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin: 2rem 0 1.5rem;
            }
            .builder-card {
                background: #fff;
                border-radius: 16px;
                padding: 1.5rem;
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
                margin-bottom: 1.5rem;
            }
            .builder-grid {
                display: grid;
                gap: 1rem;
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
            .builder-actions {
                display: flex;
                align-items: center;
                gap: 1rem;
                margin-top: 1rem;
            }
            .hint {
                color: #5b6472;
                font-size: 0.9rem;
            }
            .error {
                color: #b42318;
            }
        </style>
        @livewireStyles
    </head>
    <body>
        {{ $slot ?? '' }}
        @yield('content')

        @livewireScripts
    </body>
</html>
