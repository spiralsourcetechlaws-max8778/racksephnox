@extends('admin.layouts.app')

@section('page-title', 'Users')
@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-gold hover:text-yellow-400">Dashboard</a>
    <span class="mx-1">/</span> Users
@endsection

@section('content')
@php
    $me = auth()->user();
    $canManage = $me->hasRole('super_admin');
@endphp

<div class="space-y-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-white">Users & Admins</h1>
            <p class="text-sm text-gray-400">{{ number_format($users->total()) }} total &middot; {{ $users->count() }} on this page</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            @if($canManage)
                <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-gold hover:bg-yellow-500 text-black font-bold rounded text-sm">Add User / Admin</a>
                <a href="{{ route('admin.users.export') }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm">Export</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-500/20 text-green-400 p-3 rounded">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-500/20 text-red-400 p-3 rounded">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('admin.users.index') }}" class="admin-card p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, phone, referral..." class="md:col-span-2 bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm focus:border-gold focus:outline-none">
            <select name="role" class="bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm">
                <option value="">All Roles</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Any Admin</option>
                <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                <option value="finance" {{ request('role') === 'finance' ? 'selected' : '' }}>Finance Admin</option>
                <option value="support" {{ request('role') === 'support' ? 'selected' : '' }}>Support Admin</option>
                <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>Regular Users</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded py-2 text-sm font-medium">Apply</button>
                @if(request()->hasAny(['search', 'role']))
                    <a href="{{ route('admin.users.index') }}" class="px-3 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm">Reset</a>
                @endif
            </div>
        </div>
    </form>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-800/50 border-b border-gray-800 text-gray-400 text-xs uppercase">
                    <tr>
                        <th class="text-left p-3">User</th>
                        <th class="text-left p-3 hidden md:table-cell">Role</th>
                        <th class="text-right p-3">Wallet</th>
                        <th class="text-left p-3 hidden sm:table-cell">KYC</th>
                        <th class="text-right p-3 hidden lg:table-cell">Joined</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition">
                            <td class="p-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-gold to-yellow-700 flex items-center justify-center text-black font-bold text-sm flex-shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.users.show', $user) }}" class="text-white font-medium hover:text-gold block truncate">
                                            {{ $user->name }}@if($user->id === auth()->id()) <span class="text-xs text-gold ml-1">(you)</span>@endif
                                        </a>
                                        <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 hidden md:table-cell">
                                @php
                                    $roleClass = match($user->admin_role) {
                                        'super_admin' => 'bg-red-500/20 text-red-400',
                                        'finance'     => 'bg-green-500/20 text-green-400',
                                        'support'     => 'bg-blue-500/20 text-blue-400',
                                        default       => 'bg-gray-700 text-gray-300',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 {{ $roleClass }} text-xs rounded font-medium">{{ $user->role_label }}</span>
                                @if($user->is_vip)<span class="ml-1 px-2 py-0.5 bg-yellow-500/20 text-yellow-400 text-xs rounded">VIP</span>@endif
                                @if($user->has_promo)<span class="ml-1 px-2 py-0.5 bg-purple-500/20 text-purple-400 text-xs rounded">Promo</span>@endif
                            </td>
                            <td class="p-3 text-right">
                                <span class="font-mono text-xs {{ ($user->wallet?->balance ?? 0) > 0 ? 'text-green-400' : 'text-gray-500' }}">KES {{ number_format($user->wallet?->balance ?? 0, 2) }}</span>
                            </td>
                            <td class="p-3 hidden sm:table-cell">
                                <span class="px-2 py-0.5 text-xs rounded {{ in_array($user->kyc_status, ['verified','approved']) ? 'bg-green-500/20 text-green-400' : ($user->kyc_status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : ($user->kyc_status === 'rejected' ? 'bg-red-500/20 text-red-400' : 'bg-gray-700 text-gray-400')) }}">
                                    {{ ucfirst($user->kyc_status ?? 'pending') }}
                                </span>
                            </td>
                            <td class="p-3 text-right text-xs text-gray-500 hidden lg:table-cell">{{ $user->created_at?->format('M d, Y') }}</td>
                            <td class="p-3">
                                <div class="flex justify-end gap-1 flex-wrap">
                                    <a href="{{ route('admin.users.show', $user) }}" class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded" title="View">View</a>
                                    @if($canManage && $user->id !== auth()->id())
                                        <a href="{{ route('admin.users.edit', $user) }}" class="px-2 py-1 bg-gray-600 hover:bg-gray-500 text-white text-xs rounded" title="Edit">Edit</a>
                                        <form method="POST" action="{{ route('admin.users.change-role', $user) }}" class="inline">
                                            @csrf
                                            <select name="role" onchange="this.form.submit()" class="px-1 py-1 bg-gray-800 border border-gray-700 text-white text-xs rounded hover:border-gold focus:outline-none cursor-pointer">
                                                <option value="user" {{ !$user->is_admin ? 'selected' : '' }}>User</option>
                                                <option value="support" {{ $user->admin_role === 'support' ? 'selected' : '' }}>Support</option>
                                                <option value="finance" {{ $user->admin_role === 'finance' ? 'selected' : '' }}>Finance</option>
                                                <option value="super_admin" {{ $user->admin_role === 'super_admin' ? 'selected' : '' }}>Super</option>
                                            </select>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Permanently delete {{ addslashes($user->name) }}?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded" title="Delete">Delete</button>
                                        </form>
                                    @elseif($user->id === auth()->id())
                                        <span class="px-2 py-1 bg-gray-700 text-gray-500 text-xs rounded">(you)</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-gray-500">
                                @if(request()->hasAny(['search', 'role']))
                                    No users match your filters. <a href="{{ route('admin.users.index') }}" class="text-gold hover:text-yellow-400">Clear filters</a>
                                @else
                                    No users yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
@endsection
