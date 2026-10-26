<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Racksephnox</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cosmic-void text-white font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="w-64 admin-sidebar bg-gray-900 border-r border-gray-800 flex flex-col">
            <div class="p-4 border-b border-gray-800">
                <h2 class="text-gold text-xl font-bold tracking-wider">Admin Panel</h2>
            </div>
            <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                <a href="{{ route('admin.dashboard') }}" class="block py-2 px-3 rounded hover:bg-gray-800 text-gold-400 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800 text-gold' : '' }}">
                    Dashboard
                </a>
                <a href="{{ route('admin.users.index') }}" class="block py-2 px-3 rounded hover:bg-gray-800 text-gold-400 {{ request()->routeIs('admin.users.*') ? 'bg-gray-800 text-gold' : '' }}">
                    Users
                </a>
                <a href="{{ route('admin.deposits.index') }}" class="block py-2 px-3 rounded hover:bg-gray-800 text-gold-400 {{ request()->routeIs('admin.deposits.*') ? 'bg-gray-800 text-gold' : '' }}">
                    Deposits
                </a>
                <a href="{{ route('admin.withdrawals.index') }}" class="block py-2 px-3 rounded hover:bg-gray-800 text-gold-400 {{ request()->routeIs('admin.withdrawals.*') ? 'bg-gray-800 text-gold' : '' }}">
                    Withdrawals
                </a>
                <a href="{{ route('admin.kyc.index') }}" class="block py-2 px-3 rounded hover:bg-gray-800 text-gold-400 {{ request()->routeIs('admin.kyc.*') ? 'bg-gray-800 text-gold' : '' }}">
                    KYC Verification
                </a>
                <a href="{{ route('admin.loans.index') }}" class="block py-2 px-3 rounded hover:bg-gray-800 text-gold-400 {{ request()->routeIs('admin.loans.*') ? 'bg-gray-800 text-gold' : '' }}">
                    Loans
                </a>
                <a href="{{ route('admin.lottery.index') }}" class="block py-2 px-3 rounded hover:bg-gray-800 text-gold-400 {{ request()->routeIs('admin.lottery.*') ? 'bg-gray-800 text-gold' : '' }}">
                    Lottery Management
                </a>
                <a href="{{ route('admin.plans.index') }}" class="block py-2 px-3 rounded hover:bg-gray-800 text-gold-400 {{ request()->routeIs('admin.plans.*') ? 'bg-gray-800 text-gold' : '' }}">
                    Investment Plans
                </a>
                <a href="{{ route('admin.settings.index') }}" class="block py-2 px-3 rounded hover:bg-gray-800 text-gold-400 {{ request()->routeIs('admin.settings.*') ? 'bg-gray-800 text-gold' : '' }}">
                    Settings
                </a>
            </nav>
            <div class="p-4 border-t border-gray-800">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left py-2 px-3 rounded text-red-400 hover:bg-gray-800">
                        Log Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto bg-cosmic-void p-6">
            @yield('content')
        </main>
        
    </div>
</body>
</html>
