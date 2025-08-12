@include('backend.include.header')

<body>
    <div>
        @include('backend.include.sidebar')
        <main class="dashboard-main">
            @include('backend.include.topbar')

            @yield('content')

            @include('backend.include.footer')
            @include('backend.include.scripts')

    </div>



    @yield('script')


</body>

</html>
