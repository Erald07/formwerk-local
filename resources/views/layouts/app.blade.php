<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <?php 
            $company = company();
            if ($company) {
                echo "<title>$company->company_name</title>";
                if(isset($company->company_favicon)) {
                    echo '<link rel="shortcut icon" href="/storage/'.$company->company_favicon.'" type="image/x-icon">';
                }
            } else {
                echo " <title>".config('app.name', 'Form Werk')."</title>";
            }

        ?>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">
        <link rel="stylesheet" href="{{ asset("css/halfdata-plugin/fontawesome-all.min.css") }}">

        <!-- Styles -->
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

        @yield('custom-head')

        @yield('custom-css')

        <!-- Scripts -->
        <script src="{{ asset('js/app.js') }}" defer></script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            {{-- <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header> --}}

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script> -->
        <!-- <script id="leform-remote" src="http://localhost:8070/content/plugins/halfdata-green-forms/js/leform.min.js?ver=1.35" data-handler="http://localhost:8070/ajax.php"></script> -->

        @yield('custom-js')
        @stack('custom-scripts')
    </body>
</html>
