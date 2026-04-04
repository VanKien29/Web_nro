<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Ngọc Rồng HDPE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #111c2d;
            color: #c8d8e4;
            min-height: 100vh;
            display: flex;
        }

        a {
            color: #5d87ff;
            text-decoration: none;
        }

        a:hover {
            color: #7ea6ff;
            text-decoration: none;
        }

        /* ─── Sidebar ─── */
        .sidebar {
            width: 270px;
            background: linear-gradient(180deg, #1c2940 0%, #111c2d 100%);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 50;
            border-right: 1px solid rgba(255, 255, 255, .06);
            transition: transform .3s;
        }

        .sidebar-brand {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #5d87ff, #49beff);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            color: #fff;
        }

        .sidebar-brand-text {
            font-size: 17px;
            font-weight: 700;
            color: #fff;
            letter-spacing: .5px;
        }

        .sidebar-section {
            padding: 4px 16px;
            font-size: 11px;
            font-weight: 600;
            color: #5a6a85;
            text-transform: uppercase;
            letter-spacing: .8px;
            margin-top: 16px;
            margin-bottom: 4px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 8px 0;
            overflow-y: auto;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            margin: 2px 12px;
            border-radius: 8px;
            color: #8da4bf;
            font-size: 14px;
            font-weight: 500;
            transition: all .2s;
        }

        .sidebar-link .material-icons-round {
            font-size: 20px;
        }

        .sidebar-link:hover {
            background: rgba(93, 135, 255, .08);
            color: #c8d8e4;
        }

        .sidebar-link.active {
            background: linear-gradient(135deg, #5d87ff, #49beff);
            color: #fff;
            box-shadow: 0 4px 12px rgba(93, 135, 255, .35);
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255, 255, 255, .06);
        }

        /* ─── Main ─── */
        .main-content {
            flex: 1;
            margin-left: 270px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: rgba(17, 28, 45, .8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, .06);
            padding: 0 28px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-left h1 {
            font-size: 17px;
            font-weight: 600;
            color: #e4ecf4;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .topbar-icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .06);
            color: #8da4bf;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s;
            font-size: 20px;
        }

        .topbar-icon-btn:hover {
            background: rgba(93, 135, 255, .12);
            color: #5d87ff;
        }

        .topbar-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #5d87ff, #49beff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            margin-left: 4px;
        }

        .topbar-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            position: relative;
        }

        .topbar-user-name {
            font-size: 14px;
            font-weight: 500;
            color: #e4ecf4;
        }

        .topbar-user-role {
            font-size: 11px;
            color: #5a6a85;
        }

        /* ─── Content ─── */
        .content {
            padding: 28px;
            flex: 1;
        }

        /* ─── Breadcrumb ─── */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #5a6a85;
            margin-bottom: 20px;
        }

        .breadcrumb a {
            color: #5d87ff;
        }

        .breadcrumb span.sep {
            color: #3a4a5f;
        }

        /* ─── Cards ─── */
        .card {
            background: #1c2940;
            border: 1px solid rgba(255, 255, 255, .06);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .card-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #e4ecf4;
        }

        .stat-card {
            background: #1c2940;
            border: 1px solid rgba(255, 255, 255, .06);
            border-radius: 12px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #5d87ff, #49beff);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #13deb9, #0bc4a0);
        }

        .stat-icon.amber {
            background: linear-gradient(135deg, #ffae1f, #fa896b);
        }

        .stat-icon.red {
            background: linear-gradient(135deg, #fa896b, #f3704d);
        }

        .stat-title {
            font-size: 13px;
            color: #5a6a85;
            margin-bottom: 2px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #e4ecf4;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        /* ─── Table ─── */
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
            padding: 12px 16px;
            text-align: left;
        }

        th {
            color: #5a6a85;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
            border-bottom: 1px solid rgba(255, 255, 255, .06);
        }

        td {
            border-bottom: 1px solid rgba(255, 255, 255, .04);
            color: #c8d8e4;
        }

        tr:hover td {
            background: rgba(93, 135, 255, .03);
        }

        /* ─── Buttons ─── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
        }

        .btn:hover {
            text-decoration: none;
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #5d87ff, #49beff);
            color: #fff;
            box-shadow: 0 4px 12px rgba(93, 135, 255, .3);
        }

        .btn-primary:hover {
            box-shadow: 0 6px 16px rgba(93, 135, 255, .4);
            color: #fff;
        }

        .btn-success {
            background: linear-gradient(135deg, #13deb9, #0bc4a0);
            color: #fff;
            box-shadow: 0 4px 12px rgba(19, 222, 185, .3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #fa896b, #f3704d);
            color: #fff;
            box-shadow: 0 4px 12px rgba(250, 137, 107, .3);
        }

        .btn-danger:hover {
            box-shadow: 0 6px 16px rgba(250, 137, 107, .4);
            color: #fff;
        }

        .btn-outline {
            background: transparent;
            color: #8da4bf;
            border: 1px solid rgba(255, 255, 255, .1);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, .04);
            color: #c8d8e4;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 6px;
        }

        /* ─── Forms ─── */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #8da4bf;
            margin-bottom: 6px;
        }

        .form-input,
        select.form-input {
            width: 100%;
            padding: 10px 14px;
            background: #111c2d;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 8px;
            color: #e4ecf4;
            font-size: 14px;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #5d87ff;
            box-shadow: 0 0 0 3px rgba(93, 135, 255, .15);
        }

        textarea.form-input {
            resize: vertical;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #5d87ff;
            cursor: pointer;
        }

        /* ─── Alerts ─── */
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(19, 222, 185, .12);
            color: #13deb9;
            border: 1px solid rgba(19, 222, 185, .2);
        }

        .alert-error {
            background: rgba(250, 137, 107, .12);
            color: #fa896b;
            border: 1px solid rgba(250, 137, 107, .2);
        }

        /* ─── Badges ─── */
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(19, 222, 185, .15);
            color: #13deb9;
        }

        .badge-danger {
            background: rgba(250, 137, 107, .15);
            color: #fa896b;
        }

        .badge-warning {
            background: rgba(255, 174, 31, .15);
            color: #ffae1f;
        }

        .badge-info {
            background: rgba(93, 135, 255, .15);
            color: #5d87ff;
        }

        /* ─── Pagination ─── */
        .pagination {
            display: flex;
            gap: 4px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
        }

        .pagination a {
            background: rgba(255, 255, 255, .04);
            color: #8da4bf;
            border: 1px solid rgba(255, 255, 255, .06);
        }

        .pagination a:hover {
            background: rgba(93, 135, 255, .1);
            color: #5d87ff;
        }

        .pagination .active span {
            background: #5d87ff;
            color: #fff;
            border-radius: 6px;
        }

        /* ─── Utility ─── */
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

        .gap-3 {
            gap: 12px;
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

        /* ─── Mobile toggle ─── */
        .sidebar-toggle {
            display: none;
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: flex;
            }
        }

        /* ─── Scrollbar ─── */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .1);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, .2);
        }

        @yield('styles')
    </style>
</head>

<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">N</div>
            <div class="sidebar-brand-text">NRO HDPE</div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">Tổng quan</div>
            <a href="{{ route('admin.dashboard') }}"
                class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="material-icons-round">dashboard</span> Dashboard
            </a>

            <div class="sidebar-section">Quản lý</div>
            <a href="{{ route('admin.accounts.index') }}"
                class="sidebar-link {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}">
                <span class="material-icons-round">people</span> Tài khoản
            </a>
            <a href="{{ route('admin.giftcodes.index') }}"
                class="sidebar-link {{ request()->routeIs('admin.giftcodes.*') ? 'active' : '' }}">
                <span class="material-icons-round">card_giftcard</span> Giftcode
            </a>
            <a href="{{ route('admin.items.index') }}"
                class="sidebar-link {{ request()->routeIs('admin.items.*') ? 'active' : '' }}">
                <span class="material-icons-round">inventory_2</span> Items
            </a>
        </nav>
        <div class="sidebar-footer">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline" style="width:100%; justify-content: center;">
                    <span class="material-icons-round" style="font-size:18px;">logout</span> Đăng xuất
                </button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <div class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-icon-btn sidebar-toggle"
                    onclick="document.getElementById('sidebar').classList.toggle('open')">
                    <span class="material-icons-round">menu</span>
                </button>
                <h1>@yield('title', 'Admin Panel')</h1>
            </div>
            <div class="topbar-right">
                <div class="topbar-user-info">
                    <div>
                        <div class="topbar-user-name">{{ Auth::guard('admin')->user()->username ?? 'Admin' }}</div>
                        <div class="topbar-user-role">Administrator</div>
                    </div>
                    <div class="topbar-avatar">{{ strtoupper(substr(Auth::guard('admin')->user()->username ?? 'A', 0,
                        1)) }}</div>
                </div>
            </div>
        </header>
        <main class="content">
            @if(session('status'))
            <div class="alert alert-success">
                <span class="material-icons-round" style="font-size:18px;">check_circle</span>
                {{ session('status') }}
            </div>
            @endif
            @yield('content')
        </main>
    </div>

    @yield('scripts')
</body>

</html>