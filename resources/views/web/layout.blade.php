<!doctype html>
<html lang="sr">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css"
              integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">

        <link href="{{ asset('css/style.css') }}" rel="stylesheet">

        <title>@yield('title')</title>
    </head>
    <body>
        @include('web.layout.header')
        @include('web.layout.main')
        @include('web.layout.footer')
    </body>
</html>