<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Ngọc Rồng HDPE</title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
        }

        a {
            color: #60a5fa;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            background: #1e293b;
            border-right: 1px solid #334155;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 50;
        }

        .sidebar-brand {
            padding: 20px 16px;
            font-size: 18px;
            font-weight: 700;
            color: #fbbf24;
            border-bottom: 1px solid #334155;
            text-align: center;
        }

        .sidebar-nav {
            flex: 1;
            padding: 12px 0;
            overflow-y: auto;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: #94a3b8;
            font-size: 14px;
            transition: all .15s;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background: #334155;
            color: #f1f5f9;
            text-decoration: none;
        }

        .sidebar-link.active {
            border-left: 3px solid #fbbf24;
        }

        .sidebar-footer {
            padding: 12px 16px;
            border-top: 1px solid #334155;
        }

        /* Main */
        .main-content {
            flex: 1;
            margin-left: 240px;
        }

        .topbar {
            background: #1e293b;
            border-bottom: 1px solid #334155;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .content {
            padding: 24px;
        }

        /* Cards */
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .card-value {
            font-size: 28px;
            font-weight: 700;
            color: #f1f5f9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        /* Table */
        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th,
        td {
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid #334155;
        }

        th {
            background: #1e293b;
            color: #94a3b8;
            font-weight: 600;
            white-space: nowrap;
        }

        tr:hover td {
            background: rgba(255, 255, 255, .03);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
        }

        .btn:hover {
            text-decoration: none;
        }

        .btn-primary {
            background: #3b82f6;
            color: #fff;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-danger {
            background: #ef4444;
            color: #fff;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 13px;
        }

        /* Forms */
        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .form-input {
            width: 100%;
            padding: 8px 12px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 14px;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, .3);
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check input {
            accent-color: #3b82f6;
        }

        /* Alerts */
        .alert {
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .alert-success {
            background: #166534;
            color: #bbf7d0;
        }

        .alert-error {
            background: #7f1d1d;
            color: #fecaca;
        }

        /* Pagination */
        .pagination {
            display: flex;
            gap: 4px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
        }

        .pagination a {
            background: #334155;
            color: #e2e8f0;
        }

        .pagination a:hover {
            background: #475569;
            text-decoration: none;
        }

        .pagination .active span {
            background: #3b82f6;
            color: #fff;
        }

        /* Utility */
        .flex {
            display: flex;
        }

        .items-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .gap-2 {
            gap: 8px;
        }

        .gap-4 {
            gap: 16px;
        }

        .mb-4 {
            margin-bottom: 16px;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">🐉 NRO HDPE</div>
        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}"
                class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                📊 Dashboard
            </a>
            <a href="{{ route('admin.accounts.index') }}"
                class="sidebar-link {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}">
                👥 Tài khoản
            </a>
            <a href="{{ route('admin.giftcodes.index') }}"
                class="sidebar-link {{ request()->routeIs('admin.giftcodes.*') ? 'active' : '' }}">
                🎁 Giftcode
            </a>
            <a href="{{ route('admin.items.index') }}"
                class="sidebar-link {{ request()->routeIs('admin.items.*') ? 'active' : '' }}">
                🎒 Items
            </a>
        </nav>
        <div class="sidebar-footer">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="btn btn-danger" style="width:100%;">Đăng xuất</button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <div class="main-content">
        <header class="topbar">
            <h1 style="font-size:18px; font-weight:600;">@yield('title', 'Admin Panel')</h1>
            <div class="topbar-user">
                <span>👤 {{ Auth::guard('admin')->user()->username ?? 'Admin' }}</span>
            </div>
        </header>
        <main class="content">
            @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</body>

</html>