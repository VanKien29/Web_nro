<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin — Ngọc Rồng HDPE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    @vite('resources/js/admin.js')
</head>

<body style="margin:0;">
    <script>
        document.body.style.background=localStorage.getItem('adminTheme')==='light'?'#e7ecef':'#0f1117';
    </script>
    <div id="admin-app"></div>
</body>

</html>
