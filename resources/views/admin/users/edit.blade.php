@extends('admin.layouts.app')

@section('page-title', 'Edit User')
@section('breadcrumbs')
    <a href="{{ route('admin.users.index') }}" class="text-gold hover:text-yellow-400">Users</a>
    <span class="mx-1">/</span>
    <a href="{{ route('admin.users.show', $user) }}" class="text-gold hover:text-yellow-400">{{ $user->name }}</a>
    <span class="mx-1">/</span> Edit
@endsection

@section('content')
@php $me = auth()->user(); $isSuper = $me->hasRole('super_admin'); @endphp

<div class="max-w-3xl mx-auto space-y-4">

    @if($errors->any())
        <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-4 rounded">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="admin-card p-6 space-y-5">
        @csrf @method('PATCH')

        <div>
            <h2 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Identity</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">KYC Status</label>
                    <select name="kyc_status" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                        @foreach(['pending', 'verified', 'rejected'] as $s)
                            <option value="{{ $s }}" {{ old('kyc_status', $user->kyc_status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Account Status</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="flex items-center gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded cursor-pointer hover:bg-gray-800 transition">
                    <input type="hidden" name="is_verified" value="0">
                    <input type="checkbox" name="is_verified" value="1" {{ old('is_verified', $user->is_verified) ? 'checked' : '' }} class="w-4 h-4">
                    <div>
                        <p class="text-sm font-medium text-white">Email Verified</p>
                        <p class="text-xs text-gray-400">User can skip email verification</p>
                    </div>
                </label>

                @if($isSuper && $user->id !== $me->id)
                    <div class="p-3 bg-gray-800/50 border border-gray-700 rounded">
                        <p class="text-sm font-medium text-white mb-2">Admin Role</p>
                        <form method="POST" action="{{ route('admin.users.change-role', $user) }}" class="flex gap-2">
                            @csrf
                            <select name="role" class="flex-1 bg-gray-900 border border-gray-700 text-white rounded px-3 py-1 text-sm">
                                <option value="user" {{ !$user->is_admin ? 'selected' : '' }}>Regular User</option>
                                <option value="support" {{ $user->admin_role === 'support' ? 'selected' : '' }}>Support Admin</option>
                                <option value="finance" {{ $user->admin_role === 'finance' ? 'selected' : '' }}>Finance Admin</option>
                                <option value="super_admin" {{ $user->admin_role === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                            </select>
                            <button type="submit" class="px-3 py-1 bg-gold hover:bg-yellow-500 text-black text-xs font-bold rounded">Apply</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        @if($isSuper)
        <div>
            <h2 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Privileges</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <form method="POST" action="{{ route('admin.users.toggle-vip', $user) }}" class="p-3 bg-gray-800/50 border border-gray-700 rounded flex justify-between items-center">
                    @csrf
                    <div>
                        <p class="text-sm font-medium text-white">VIP Status</p>
                        <p class="text-xs text-gray-400">{{ $user->is_vip ? 'Currently VIP' : 'Not VIP' }}</p>
                    </div>
                    <button class="px-3 py-1 {{ $user->is_vip ? 'bg-red-600 hover:bg-red-700' : 'bg-yellow-600 hover:bg-yellow-700' }} text-white text-xs rounded transition">
                        {{ $user->is_vip ? 'Remove VIP' : 'Make VIP' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.users.toggle-promo', $user) }}" class="p-3 bg-gray-800/50 border border-gray-700 rounded flex justify-between items-center">
                    @csrf
                    <div>
                        <p class="text-sm font-medium text-white">Promo Status</p>
                        <p class="text-xs text-gray-400">
                            {{ $user->has_promo ? 'Active until ' . ($user->promo_expires_at?->format('M d, Y') ?? '?') : 'No active promo' }}
                        </p>
                    </div>
                    <button class="px-3 py-1 {{ $user->has_promo ? 'bg-red-600 hover:bg-red-700' : 'bg-purple-600 hover:bg-purple-700' }} text-white text-xs rounded transition">
                        {{ $user->has_promo ? 'End Promo' : 'Start 7-day Promo' }}
                    </button>
                </form>
            </div>
        </div>
        @endif

        <div class="flex gap-3 pt-4 border-t border-gray-800">
            <button type="submit" class="px-6 py-2 bg-gold hover:bg-yellow-500 text-black font-bold rounded">Save Changes</button>
            <a href="{{ route('admin.users.show', $user) }}" class="px-6 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded">Cancel</a>
        </div>
    </form>

    @if($isSuper && $user->id !== $me->id)
        <div class="admin-card p-6 border-t-4 border-t-red-600">
            <h3 class="text-lg font-semibold text-red-400 mb-2">Danger Zone</h3>
            <p class="text-sm text-gray-400 mb-4">This action is irreversible. The user's wallet, transactions, investments, and all associated data will be permanently removed.</p>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                  onsubmit="return confirm('Permanently delete {{ addslashes($user->name) }}? This cannot be undone.');">
                @csrf @method('DELETE')
                <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded">
                    <i class="fas fa-trash mr-1"></i> Delete User Permanently
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
