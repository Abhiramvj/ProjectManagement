<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Favicon links -->
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicon/apple-touch-icon.png') }}" />
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon/favicon-32x32.png') }}" />
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicon/favicon-16x16.png') }}" />
        <link rel="shortcut icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
        <link rel="manifest" href="{{ asset('assets/img/favicon/site.webmanifest') }}" />
        <meta name="theme-color" content="#ffffff" />

        <!-- Scripts -->
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia

        <!--
        ======================================================================
        Load jQuery and OrgChart plugin globally using reliable CDNs.
        Removing integrity hashes for local development simplicity.
        ======================================================================
        -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/orgchart/3.1.3/js/jquery.orgchart.min.js"></script>
    </body>
</html>
