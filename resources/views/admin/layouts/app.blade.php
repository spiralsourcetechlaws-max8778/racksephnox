<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin - {{ config('app.name', 'Racksephnox') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-900 text-gray-300 font-sans antialiased" x-data="{ sidebarOpen: false }">
    
    <div class="flex h-screen overflow-hidden">

        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" 
             class="fixed inset-0 z-20 bg-black bg-opacity-50 transition-opacity lg:hidden"></div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
               class="fixed inset-y-0 left-0 z-30 w-64 bg-gray-950 border-r border-gray-800 flex flex-col transition-transform duration-300 ease-in-out transform lg:static lg:translate-x-0">
            
            <div class="flex items-center justify-center h-16 border-b border-gray-800">
                <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold text-gold tracking-widest uppercase">
                    Racksephnox Admin
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 mt-2">Main</p>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800 text-gold' : 'text-gray-400 hover:bg-gray-800 hover:text-white transition' }}">
                    <i class="fas fa-tachometer-alt w-6 text-center mr-2"></i> Dashboard
                </a>
                <a href="{{ route('admin.users.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-gray-800 text-gold' : 'text-gray-400 hover:bg-gray-800 hover:text-white transition' }}">
                    <i class="fas fa-users w-6 text-center mr-2"></i> Users
                </a>
                <a href="{{ route('admin.kyc.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.kyc.*') ? 'bg-gray-800 text-gold' : 'text-gray-400 hover:bg-gray-800 hover:text-white transition' }}">
                    <i class="fas fa-id-card w-6 text-center mr-2"></i> KYC Verification
                </a>

                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 mt-4">Finance</p>
                <a href="{{ route('admin.deposits.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.deposits.*') ? 'bg-gray-800 text-gold' : 'text-gray-400 hover:bg-gray-800 hover:text-white transition' }}">
                    <i class="fas fa-arrow-down w-6 text-center mr-2 text-green-400"></i> Deposits
                </a>
                <a href="{{ route('admin.withdrawals.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.withdrawals.*') ? 'bg-gray-800 text-gold' : 'text-gray-400 hover:bg-gray-800 hover:text-white transition' }}">
                    <i class="fas fa-arrow-up w-6 text-center mr-2 text-red-400"></i> Withdrawals
                </a>
                <a href="{{ route('admin.loans.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.loans.*') ? 'bg-gray-800 text-gold' : 'text-gray-400 hover:bg-gray-800 hover:text-white transition' }}">
                    <i class="fas fa-hand-holding-usd w-6 text-center mr-2"></i> Loans
                </a>
                <a href="{{ route('admin.plans.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.plans.*') ? 'bg-gray-800 text-gold' : 'text-gray-400 hover:bg-gray-800 hover:text-white transition' }}">
                    <i class="fas fa-chart-line w-6 text-center mr-2"></i> Investment Plans
                </a>

                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 mt-4">Gaming</p>
                <a href="{{ route('admin.lottery.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.lottery.*') ? 'bg-gray-800 text-gold' : 'text-gray-400 hover:bg-gray-800 hover:text-white transition' }}">
                    <i class="fas fa-dice w-6 text-center mr-2"></i> Lottery Config
                </a>
                
                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 mt-4">System</p>
                <a href="{{ route('admin.reports.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.reports.*') ? 'bg-gray-800 text-gold' : 'text-gray-400 hover:bg-gray-800 hover:text-white transition' }}">
                    <i class="fas fa-file-invoice-dollar w-6 text-center mr-2"></i> Reports
                </a>
                <a href="{{ route('admin.settings.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.settings.*') ? 'bg-gray-800 text-gold' : 'text-gray-400 hover:bg-gray-800 hover:text-white transition' }}">
                    <i class="fas fa-cog w-6 text-center mr-2"></i> Settings
                </a>
            </nav>

            <div class="border-t border-gray-800 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-red-500 transition" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- Topbar -->
            <header class="bg-gray-900 border-b border-gray-800 h-16 flex items-center justify-between px-4 sm:px-6">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-400 hover:text-white focus:outline-none lg:hidden">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <div class="flex items-center space-x-4 ml-auto">
                    <a href="{{ url('/') }}" target="_blank" class="text-sm text-gray-400 hover:text-gold flex items-center">
                        <i class="fas fa-external-link-alt mr-2"></i> View Site
                    </a>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-[#0a0a0a]">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
