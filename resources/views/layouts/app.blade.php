<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TungMa Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <style>
            /* TungMa brand palette inspired by logo: deep red, orange, dark text */
            :root { 
                --tm-primary:#b32020; 
                --tm-primary-hover:#8f1919;
                --tm-accent:#ff8c1a; 
                --tm-accent-light:#ffb366;
                --tm-bg:#fff7f2; 
                --tm-surface:#ffffff; 
                --tm-text:#1c1c1c; 
                --tm-muted:#6b6b6b;
                --tm-border:#e5e7eb;
                --tm-success:#10b981;
                --tm-danger:#ef4444;
            }
        html, body { height:100%; }
        body { font-family:'Inter', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background:var(--tm-bg); color:var(--tm-text); line-height:1.6; }
        
        /* Layout */
        .tm-shell { display:flex; min-height:100vh; }
        .tm-sidebar { width:280px; background:var(--tm-surface); border-right:1px solid var(--tm-border); padding:24px 16px; position:sticky; top:0; height:100vh; overflow-y:auto; display:flex; flex-direction:column; }
        .tm-content { flex:1; padding:32px; max-width:1400px; }
        
        /* Sidebar */
        .tm-logo { font-weight:700; font-size:18px; letter-spacing:-0.02em; display:flex; align-items:center; gap:10px; color:var(--tm-primary); margin-bottom:8px; }
        .tm-logo i { font-size:24px; }
        .tm-user-badge { background:#f9fafb; border-radius:8px; padding:8px 12px; margin:16px 0; font-size:13px; }
        .tm-user-badge .name { font-weight:600; color:var(--tm-text); display:block; margin-bottom:2px; }
        .tm-user-badge .role { color:var(--tm-muted); font-size:12px; }
        
        /* Navigation */
        .tm-nav { display:flex; flex-direction:column; gap:2px; flex:1; }
        .tm-nav a { display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:8px; color:var(--tm-text); text-decoration:none; font-size:14px; font-weight:500; transition:all 0.2s ease; }
        .tm-nav a i { font-size:18px; width:20px; opacity:0.7; }
        .tm-nav a:hover { background:#f3f4f6; color:var(--tm-primary); transform:translateX(2px); }
        .tm-nav a:hover i { opacity:1; }
        .tm-nav a.active { background:linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); color:var(--tm-primary); border-left:3px solid var(--tm-primary); padding-left:11px; }
        .tm-nav a.active i { opacity:1; color:var(--tm-primary); }
        .tm-nav-divider { height:1px; background:var(--tm-border); margin:12px 0; }
        
        /* Header & Breadcrumbs */
        .tm-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:28px; }
        .tm-header h2 { font-size:28px; font-weight:700; margin-bottom:4px; }
        .tm-header .text-muted { font-size:14px; }
        .tm-breadcrumb { font-size:13px; margin-bottom:16px; }
        .tm-breadcrumb a { color:var(--tm-muted); text-decoration:none; }
        .tm-breadcrumb a:hover { color:var(--tm-primary); }
        .tm-breadcrumb .bi { font-size:12px; margin:0 6px; }
        
        /* Cards */
        .tm-card { background:var(--tm-surface); border:1px solid var(--tm-border); border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.06); transition:all 0.3s ease; }
        .tm-card:hover { box-shadow:0 4px 12px rgba(0,0,0,0.08); }
        .tm-card .tm-card-body { padding:24px; }
        .tm-card-header { padding:20px 24px; border-bottom:1px solid var(--tm-border); font-weight:600; font-size:16px; }
        
        /* KPI Cards */
        .tm-kpi { display:flex; flex-direction:column; gap:8px; }
        .tm-kpi .label { color:var(--tm-muted); font-size:13px; font-weight:500; text-transform:uppercase; letter-spacing:0.5px; }
        .tm-kpi .value { font-size:28px; font-weight:700; color:var(--tm-primary); line-height:1; }
        .tm-kpi .trend { font-size:12px; color:var(--tm-success); margin-top:4px; }
        .tm-kpi .trend i { margin-right:2px; }
        
        /* Tables */
        .tm-table .table { background:var(--tm-surface); margin-bottom:0; font-size:14px; }
        .tm-table .table thead th { background:#f9fafb; color:var(--tm-text); font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; padding:14px 16px; border-bottom:2px solid var(--tm-border); }
        .tm-table .table tbody td { padding:16px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
        .tm-table .table tbody tr { transition:all 0.2s ease; }
        .tm-table .table tbody tr:hover { background:#fafbfc; }
        .tm-table .table tbody tr:last-child td { border-bottom:none; }
        .tm-table .table a { color:var(--tm-primary); text-decoration:none; font-weight:500; }
        .tm-table .table a:hover { text-decoration:underline; }
        .tm-empty-state { text-align:center; padding:60px 20px; color:var(--tm-muted); }
        .tm-empty-state i { font-size:48px; opacity:0.3; margin-bottom:16px; display:block; }
        .tm-empty-state .title { font-size:18px; font-weight:600; color:var(--tm-text); margin-bottom:8px; }
        
        /* Forms */
        .form-label { font-weight:500; font-size:14px; color:var(--tm-text); margin-bottom:6px; }
        .form-control, .form-select { border-radius:8px; border:1px solid var(--tm-border); padding:10px 14px; font-size:14px; transition:all 0.2s ease; }
        .form-control:focus, .form-select:focus { border-color:var(--tm-primary); box-shadow:0 0 0 3px rgba(179,32,32,0.1); }
        .form-text { font-size:12px; color:var(--tm-muted); margin-top:4px; }
        .invalid-feedback { font-size:12px; }
        
        /* Buttons */
        .btn { border-radius:8px; padding:10px 20px; font-weight:500; font-size:14px; transition:all 0.2s ease; border:none; }
        .btn-primary { background:linear-gradient(135deg, var(--tm-primary) 0%, var(--tm-primary-hover) 100%); color:#fff; box-shadow:0 2px 4px rgba(179,32,32,0.2); }
        .btn-primary:hover { background:linear-gradient(135deg, var(--tm-primary-hover) 0%, #7a1515 100%); transform:translateY(-1px); box-shadow:0 4px 8px rgba(179,32,32,0.3); }
        .btn-outline-secondary { border:1px solid var(--tm-border); color:var(--tm-text); background:#fff; }
        .btn-outline-secondary:hover { background:#f9fafb; border-color:var(--tm-primary); color:var(--tm-primary); }
        .btn-sm { padding:6px 14px; font-size:13px; }
        .btn i { margin-right:6px; }
        
        /* Alerts */
        .alert { border-radius:10px; border:none; padding:14px 18px; font-size:14px; }
        .alert-success { background:#ecfdf5; color:#065f46; border-left:3px solid var(--tm-success); }
        .alert-danger { background:#fef2f2; color:#991b1b; border-left:3px solid var(--tm-danger); }
        
        /* Badges */
        .badge { padding:4px 10px; font-weight:500; font-size:11px; border-radius:6px; }
        .badge-brand { background:linear-gradient(90deg, var(--tm-accent), var(--tm-primary)); color:#fff; }
        
        /* Footer */
        .tm-footer { margin-top:auto; padding-top:24px; color:var(--tm-muted); font-size:11px; text-align:center; border-top:1px solid var(--tm-border); }
        
        /* Utilities */
        .text-muted { color:var(--tm-muted) !important; }
        .text-primary { color:var(--tm-primary) !important; }
        
        /* Animations */
        @keyframes slideIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        .tm-card, .tm-header { animation:slideIn 0.3s ease; }
        
        /* Responsive */
        @media (max-width:768px) {
            .tm-sidebar { width:240px; }
            .tm-content { padding:20px; }
            .tm-header { flex-direction:column; gap:16px; align-items:flex-start; }
        }
    </style>
</head>
<body>
<div class="tm-shell">
    <aside class="tm-sidebar">
        <div class="tm-logo">
            <i class="bi bi-truck"></i>
            <span>Tung Ma Express</span>
        </div>
        
        @auth
        <div class="tm-user-badge">
            <span class="name">{{ auth()->user()->name }}</span>
            <span class="role">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</span>
        </div>
        @endauth
        
        <nav class="tm-nav">
            @auth
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-house"></i> Dashboard
            </a>
            <a href="{{ route('companies.index') }}" class="{{ request()->routeIs('companies.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i> Companies
            </a>
            <a href="{{ route('admins.index') }}" class="{{ request()->routeIs('admins.*') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i> Admins
            </a>
            <a href="{{ route('staff.index') }}" class="{{ request()->routeIs('staff.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Staff
            </a>
            <a href="{{ route('policies.index') }}" class="{{ request()->routeIs('policies.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i> Policies
            </a>
            <a href="{{ route('bills.index') }}" class="{{ request()->routeIs('bills.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> Bills
            </a>
            <div class="tm-nav-divider"></div>
            <a href="{{ route('analytics.index') }}" class="{{ request()->routeIs('analytics.*') ? 'active' : '' }}">
                <i class="bi bi-graph-up"></i> Analytics
            </a>
            <a href="{{ route('storage.metrics') }}" class="{{ request()->routeIs('storage.*') ? 'active' : '' }}">
                <i class="bi bi-hdd"></i> Storage
            </a>
            <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <i class="bi bi-person-circle"></i> Profile
            </a>
            @else
            <a href="{{ route('login') }}">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </a>
            @endauth
        </nav>
        
        @auth
        <form method="post" action="{{ route('logout') }}" class="mt-auto">
            @csrf
            <button class="btn btn-outline-secondary w-100">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
        @endauth
        
        <div class="tm-footer">© {{ date('Y') }} TungMa Express</div>
    </aside>
    <main class="tm-content">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
