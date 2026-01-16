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
            .top-nav {
                background: #0f172a;
                color: #fff;
                padding: 0.75rem 0;
            }
            .top-nav .container {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .top-nav a {
                color: #fff;
                text-decoration: none;
                font-weight: 600;
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
            .builder-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-bottom: 1rem;
            }
            .builder-tabs button {
                border: 1px solid #e2e8f0;
                background: #fff;
                padding: 0.4rem 0.8rem;
                border-radius: 999px;
                cursor: pointer;
                font-size: 0.9rem;
            }
            .builder-tabs button.active {
                background: #0ea5e9;
                color: #fff;
                border-color: #0ea5e9;
            }
            .inline-form {
                display: grid;
                gap: 0.75rem;
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
            .pill {
                display: inline-flex;
                align-items: center;
                padding: 0.2rem 0.6rem;
                border-radius: 999px;
                background: #e2e8f0;
                font-size: 0.8rem;
            }
        </style>
        @livewireStyles
    </head>
    <body>
        <div class="top-nav">
            <div class="container">
                <a href="{{ url('/') }}">Droplinks Builder</a>
                <span class="hint">Low-code studio</span>
            </div>
        </div>

        {{ $slot ?? '' }}
        @yield('content')

        @livewireScripts
    </body>
</html>
