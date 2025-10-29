<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title ?? 'EIR Document' }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; font-size: 10px;">
    <!-- Header -->
    <div style="position: fixed; top: -90px; left: 15px; right: 15px;">
        @include('pdf.partials.header', $headerData ?? [])
    </div>
    
    <!-- Footer -->
    <div style="position: fixed; bottom: -70px; left: 15px; right: 15px;">
        @include('pdf.partials.footer', $footerData ?? [])
    </div>
    
    <!-- Content -->
    <div style="margin-top: 0; padding-top: 0;">
        @yield('content')
    </div>
</body>
</html>