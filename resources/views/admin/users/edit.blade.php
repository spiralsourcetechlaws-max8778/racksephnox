@extends('admin.layouts.app')

@section('page-title', 'Edit User')
@section('breadcrumbs')
    <a href="{{ route('admin.users.index') }}" class="text-gold hover:text-yellow-400">Users</a>
    <span class="mx-1">/</span>
    <a href="{{ route('admin.users.show', $user) }}" class="text-gold hover:text-yellow-400">{{ $user->name }}</a>
    <span class="mx-1">/</span> Edit
@endsection

@section('content')
@php
    // $errors is injected by ShareErrorsFromSession during HTTP requests.
    // Outside HTTP context (tinker, queue, console) it's undefined — give
    // it a fresh bag so the view still renders cleanly.
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();

    $me = auth()->user();
    $isSuper = $me->hasRole('super_admin');
    $isSelf  = $user->id === $me->id;
    $initialIsAdmin = old('is_admin', $user->is_admin) ? 'true' : 'false';
@endphp

<div class="max-w-4xl mx-auto space-y-4 pb-32">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-white">Edit: {{ $user->name }}</h1>
        <a href="{{ route('admin.users.show', $user) }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm">Back</a>
    </div>

    @if($errors->any())
        <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-4 rounded">
            <p class="font-bold mb-1">Fix these before saving:</p>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user) }}" id="edit-user-form">
        @csrf
        @method('PATCH')

        {{-- IDENTITY --}}
        <div class="admin-card p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gold pb-2 border-b border-gray-800">Identity</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 focus:border-gold focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 focus:border-gold focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="2547XXXXXXXX" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 focus:border-gold focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Referral Code</label>
                    <input type="text" value="{{ $user->referral_code }}" readonly class="w-full bg-gray-900 border border-gray-800 text-gray-500 rounded px-3 py-2 cursor-not-allowed">
                </div>
            </div>
        </div>

        {{-- PASSWORD RESET --}}
        <div class="admin-card p-6 space-y-4 mt-4">
            <h2 class="text-lg font-semibold text-gold pb-2 border-b border-gray-800">Change Password</h2>
            <p class="text-sm text-gray-400">Leave both fields blank to keep the current password.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">New Password (min 8)</label>
                    <input type="password" name="password" autocomplete="new-password" placeholder="Leave blank to keep current" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 focus:border-gold focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Confirm New Password</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repeat new password" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 focus:border-gold focus:outline-none">
                </div>
            </div>
        </div>

        {{-- ACCOUNT STATUS --}}
        <div class="admin-card p-6 space-y-4 mt-4">
            <h2 class="text-lg font-semibold text-gold pb-2 border-b border-gray-800">Account Status</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">KYC Status</label>
                    <select name="kyc_status" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                        @foreach(['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected'] as $val => $label)
                            <option value="{{ $val }}" {{ old('kyc_status', $user->kyc_status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">KYC Level</label>
                    <select name="kyc_level" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                        @foreach(['basic' => 'Basic', 'tier1' => 'Tier 1', 'tier2' => 'Tier 2'] as $val => $label)
                            <option value="{{ $val }}" {{ old('kyc_level', $user->kyc_level) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
                <label class="flex items-center gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded {{ $isSelf ? 'opacity-60' : 'cursor-pointer hover:bg-gray-800' }}">
                    @if(!$isSelf)
                        <input type="hidden" name="is_verified" value="0">
                    @endif
                    <input type="checkbox" name="is_verified" value="1" {{ old('is_verified', $user->is_verified) ? 'checked' : '' }} {{ $isSelf ? 'disabled' : '' }} class="w-4 h-4">
                    <div>
                        <p class="text-sm font-medium text-white">Email Verified</p>
                        <p class="text-xs text-gray-400">{{ $isSelf ? 'Locked - cannot unverify yourself' : 'Skip verification step' }}</p>
                    </div>
                </label>
                <label class="flex items-center gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded {{ $isSelf ? 'opacity-60' : 'cursor-pointer hover:bg-gray-800' }}">
                    @if(!$isSelf)
                        <input type="hidden" name="is_active" value="0">
                    @endif
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }} {{ $isSelf ? 'disabled' : '' }} class="w-4 h-4">
                    <div>
                        <p class="text-sm font-medium text-white">Active Account</p>
                        <p class="text-xs text-gray-400">{{ $isSelf ? 'Locked - cannot deactivate yourself' : 'Unchecked = login blocked' }}</p>
                    </div>
                </label>
            </div>
        </div>

        @if($isSuper)
            {{-- ADMIN PRIVILEGES --}}
            <div class="admin-card p-6 space-y-4 mt-4 border-l-4 border-l-red-500"
                 x-data="{ isAdmin: {{ $initialIsAdmin }} }">
                <h2 class="text-lg font-semibold text-red-400 pb-2 border-b border-gray-800">Admin Privileges</h2>

                <label class="flex items-center gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded {{ $isSelf ? 'opacity-60' : 'cursor-pointer hover:bg-gray-800' }}">
                    @if(!$isSelf)
                        <input type="hidden" name="is_admin" value="0">
                    @endif
                    <input type="checkbox" name="is_admin" value="1" x-model="isAdmin" {{ $isSelf ? 'disabled' : '' }} class="w-4 h-4">
                    <div>
                        <p class="text-sm font-medium text-white">Is Administrator</p>
                        <p class="text-xs text-gray-400">{{ $isSelf ? 'Locked - cannot remove your own admin access' : 'Grants access to /admin panel' }}</p>
                    </div>
                </label>

                <div x-show="isAdmin" x-cloak>
                    <label class="block text-sm text-gray-400 mb-1">Admin Role</label>
                    <select name="admin_role" :disabled="!isAdmin" {{ $isSelf ? 'disabled' : '' }}
                            class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                        <option value="super_admin" {{ old('admin_role', $user->admin_role) === 'super_admin' ? 'selected' : '' }}>Super Admin - Full access</option>
                        <option value="finance" {{ old('admin_role', $user->admin_role) === 'finance' ? 'selected' : '' }}>Finance Admin - Deposits, withdrawals, loans, reports</option>
                        <option value="support" {{ old('admin_role', $user->admin_role) === 'support' ? 'selected' : '' }}>Support Admin - User views + KYC review</option>
                    </select>
                    @if($isSelf)
                        <p class="text-xs text-yellow-400 mt-1">Your role is locked to prevent self-lockout</p>
                    @endif
                </div>
            </div>

            {{-- USER PRIVILEGES --}}
            <div class="admin-card p-6 space-y-4 mt-4 border-l-4 border-l-yellow-500">
                <h2 class="text-lg font-semibold text-yellow-400 pb-2 border-b border-gray-800">User Privileges</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded cursor-pointer hover:bg-gray-800">
                        <input type="hidden" name="is_vip" value="0">
                        <input type="checkbox" name="is_vip" value="1" {{ old('is_vip', $user->is_vip) ? 'checked' : '' }} class="w-4 h-4">
                        <div>
                            <p class="text-sm font-medium text-white">VIP Member</p>
                            <p class="text-xs text-gray-400">Access to premium tiers</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded cursor-pointer hover:bg-gray-800">
                        <input type="hidden" name="has_promo" value="0">
                        <input type="checkbox" name="has_promo" value="1" {{ old('has_promo', $user->has_promo) ? 'checked' : '' }} class="w-4 h-4">
                        <div>
                            <p class="text-sm font-medium text-white">Promo Active</p>
                            <p class="text-xs text-gray-400">
                                @if($user->has_promo && $user->promo_expires_at)
                                    Expires {{ $user->promo_expires_at->format('M d, Y') }}
                                @else
                                    Enabling sets 7-day expiry
                                @endif
                            </p>
                        </div>
                    </label>
                </div>
            </div>
        @endif

        {{-- FIXED SAVE BAR --}}
        <div class="fixed bottom-0 left-0 right-0 z-40 bg-gray-950 border-t-2 border-gold shadow-2xl p-4">
            <div class="max-w-4xl mx-auto flex justify-between items-center gap-3">
                <a href="{{ route('admin.users.show', $user) }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded">Cancel</a>
                <button type="submit" form="edit-user-form" class="px-8 py-3 bg-gold hover:bg-yellow-500 text-black font-bold rounded text-base shadow-lg">Save All Changes</button>
            </div>
        </div>
    </form>

    @if($isSuper && !$isSelf)
        <div class="admin-card p-6 border-t-4 border-t-red-600 mt-4">
            <h3 class="text-lg font-semibold text-red-400 mb-2">Danger Zone</h3>
            <p class="text-sm text-gray-400 mb-4">Permanently deletes the user and all associated data. Cannot be undone.</p>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Permanently delete {{ addslashes($user->name) }}? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded">Delete User Permanently</button>
            </form>
        </div>
    @endif
</div>

<style>[x-cloak]{display:none !important}</style>
@endsection
