@extends('admin.layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Add New User</h1>
            <p class="text-sm text-gray-400 mt-1">Create a regular user or assign an admin role.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm">Back</a>
    </div>

    @if($errors->any())
        <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-4 rounded">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}" class="admin-card p-6 space-y-5">
        @csrf

        <div>
            <h2 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Identity</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">KYC Status</label>
                    <select name="kyc_status" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                        <option value="pending">Pending</option>
                        <option value="verified">Verified</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Credentials</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Password * (min 8)</label>
                    <input type="password" name="password" required class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Confirm Password *</label>
                    <input type="password" name="password_confirmation" required class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Role</h2>
            <div class="space-y-2">
                <label class="flex items-start gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded cursor-pointer hover:bg-gray-800">
                    <input type="radio" name="admin_role" value="" {{ old('admin_role') === null || old('admin_role') === '' ? 'checked' : '' }} class="mt-1">
                    <div>
                        <p class="text-sm font-medium text-white">Regular User</p>
                        <p class="text-xs text-gray-400">Standard customer. Cannot access /admin.</p>
                    </div>
                </label>
                <label class="flex items-start gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded cursor-pointer hover:bg-gray-800">
                    <input type="radio" name="admin_role" value="support" {{ old('admin_role') === 'support' ? 'checked' : '' }} class="mt-1">
                    <div>
                        <p class="text-sm font-medium text-blue-400">Support Admin</p>
                        <p class="text-xs text-gray-400">View users + review KYC. Cannot touch money or manage admins.</p>
                    </div>
                </label>
                <label class="flex items-start gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded cursor-pointer hover:bg-gray-800">
                    <input type="radio" name="admin_role" value="finance" {{ old('admin_role') === 'finance' ? 'checked' : '' }} class="mt-1">
                    <div>
                        <p class="text-sm font-medium text-green-400">Finance Admin</p>
                        <p class="text-xs text-gray-400">Deposits, withdrawals, wallet adjustments, loans, reports. Cannot manage users or settings.</p>
                    </div>
                </label>
                <label class="flex items-start gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded cursor-pointer hover:bg-gray-800">
                    <input type="radio" name="admin_role" value="super_admin" {{ old('admin_role') === 'super_admin' ? 'checked' : '' }} class="mt-1">
                    <div>
                        <p class="text-sm font-medium text-red-400">Super Admin</p>
                        <p class="text-xs text-gray-400">Full access. Can create/delete admins, change any setting, configure lottery and plans.</p>
                    </div>
                </label>
            </div>
            <label class="flex items-center gap-2 mt-4 text-sm">
                <input type="checkbox" name="is_verified" value="1" {{ old('is_verified') ? 'checked' : '' }} class="w-4 h-4">
                <span class="text-gray-300">Mark email as verified (skip email step)</span>
            </label>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Optional</h2>
            <label class="block text-sm text-gray-400 mb-1">Starting Wallet Balance (KES)</label>
            <input type="number" step="0.01" min="0" name="initial_balance" value="{{ old('initial_balance', 0) }}"
                   class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">Added on top of the standard 60 KES welcome bonus.</p>
        </div>

        <div class="flex gap-3 pt-4 border-t border-gray-800">
            <button type="submit" class="px-6 py-2 bg-gold hover:bg-yellow-500 text-black font-bold rounded">Create User</button>
            <a href="{{ route('admin.users.index') }}" class="px-6 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded">Cancel</a>
        </div>
    </form>
</div>
@endsection
