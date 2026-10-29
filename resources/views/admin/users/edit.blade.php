@extends('admin.layouts.app')

@section('page-title', 'Edit User')
@section('content')

@php
    $me = auth()->user();
    $isSuper = $me->hasRole('super_admin');
    $isSelf  = $user->id === $me->id;
    $initialIsAdmin = old('is_admin', $user->is_admin) ? 'true' : 'false';
@endphp

<div class="max-w-4xl mx-auto space-y-4">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-white">Edit: {{ $user->name }}</h1>
        <a href="{{ route('admin.users.show', $user) }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm">Back</a>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PATCH')

        {{-- IDENTITY --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gold pb-2 border-b border-gray-800">Identity</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Referral Code</label>
                    <input type="text" value="{{ $user->referral_code }}" readonly class="w-full bg-gray-900 border border-gray-800 text-gray-500 rounded px-3 py-2 cursor-not-allowed">
                </div>
            </div>
        </div>

        {{-- PASSWORD --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-4 mt-4">
            <h2 class="text-lg font-semibold text-gold pb-2 border-b border-gray-800">Change Password</h2>
            <p class="text-sm text-gray-400">Leave blank to keep current password.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="password" name="password" autocomplete="new-password" placeholder="New password (min 8)" class="bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Confirm new password" class="bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
            </div>
        </div>

        {{-- STATUS --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-4 mt-4">
            <h2 class="text-lg font-semibold text-gold pb-2 border-b border-gray-800">Account Status</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">KYC Status</label>
                    <select name="kyc_status" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                        @foreach(['pending', 'verified', 'rejected'] as $s)
                            <option value="{{ $s }}" {{ old('kyc_status', $user->kyc_status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
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
                <label class="flex items-center gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded {{ $isSelf ? 'opacity-60' : 'cursor-pointer' }}">
                    @if(!$isSelf) <input type="hidden" name="is_verified" value="0"> @endif
                    <input type="checkbox" name="is_verified" value="1" {{ old('is_verified', $user->is_verified) ? 'checked' : '' }} {{ $isSelf ? 'disabled' : '' }}>
                    <div>
                        <p class="text-sm font-medium text-white">Email Verified</p>
                        <p class="text-xs text-gray-400">{{ $isSelf ? 'Cannot unverify yourself' : 'Skip verification step' }}</p>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded {{ $isSelf ? 'opacity-60' : 'cursor-pointer' }}">
                    @if(!$isSelf) <input type="hidden" name="is_active" value="0"> @endif
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }} {{ $isSelf ? 'disabled' : '' }}>
                    <div>
                        <p class="text-sm font-medium text-white">Active Account</p>
                        <p class="text-xs text-gray-400">{{ $isSelf ? 'Cannot deactivate yourself' : 'Unchecked = login blocked' }}</p>
                    </div>
                </label>
            </div>
        </div>

        @if($isSuper)
            {{-- ADMIN PRIVILEGES --}}
            <div class="bg-gray-900 border border-gray-800 border-l-4 border-l-red-500 rounded-xl p-6 space-y-4 mt-4" x-data="{ isAdmin: {{ $initialIsAdmin }} }">
                <h2 class="text-lg font-semibold text-red-400 pb-2 border-b border-gray-800">Admin Privileges</h2>

                <label class="flex items-center gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded {{ $isSelf ? 'opacity-60' : 'cursor-pointer' }}">
                    @if(!$isSelf) <input type="hidden" name="is_admin" value="0"> @endif
                    <input type="checkbox" name="is_admin" value="1" x-model="isAdmin" {{ $isSelf ? 'disabled' : '' }}>
                    <div>
                        <p class="text-sm font-medium text-white">Is Administrator</p>
                        <p class="text-xs text-gray-400">{{ $isSelf ? 'Cannot remove own admin access' : 'Grants access to /admin panel' }}</p>
                    </div>
                </label>

                <div x-show="isAdmin" x-cloak>
                    <label class="block text-sm text-gray-400 mb-1">Admin Role</label>
                    <select name="admin_role" :disabled="!isAdmin" {{ $isSelf ? 'disabled' : '' }} class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                        <option value="super_admin" {{ old('admin_role', $user->admin_role) === 'super_admin' ? 'selected' : '' }}>Super Admin — Full access</option>
                        <option value="finance" {{ old('admin_role', $user->admin_role) === 'finance' ? 'selected' : '' }}>Finance Admin — Money movement + reports</option>
                        <option value="support" {{ old('admin_role', $user->admin_role) === 'support' ? 'selected' : '' }}>Support Admin — Users + KYC</option>
                    </select>
                    @if($isSelf)
                        <p class="text-xs text-yellow-400 mt-1">Role locked to prevent self-lockout</p>
                    @endif
                </div>
            </div>

            {{-- USER PRIVILEGES --}}
            <div class="bg-gray-900 border border-gray-800 border-l-4 border-l-yellow-500 rounded-xl p-6 space-y-4 mt-4">
                <h2 class="text-lg font-semibold text-yellow-400 pb-2 border-b border-gray-800">User Privileges</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded cursor-pointer">
                        <input type="hidden" name="is_vip" value="0">
                        <input type="checkbox" name="is_vip" value="1" {{ old('is_vip', $user->is_vip) ? 'checked' : '' }}>
                        <div>
                            <p class="text-sm font-medium text-white">VIP Member</p>
                            <p class="text-xs text-gray-400">Premium tiers</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded cursor-pointer">
                        <input type="hidden" name="has_promo" value="0">
                        <input type="checkbox" name="has_promo" value="1" {{ old('has_promo', $user->has_promo) ? 'checked' : '' }}>
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

        {{-- Sticky SAVE bar --}}
        <div class="sticky bottom-4 bg-gray-900 border border-gray-800 rounded-xl p-4 flex justify-between items-center shadow-2xl mt-4">
            <a href="{{ route('admin.users.show', $user) }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-gold hover:bg-yellow-500 text-black font-bold rounded">Save All Changes</button>
        </div>
    </form>

    @if($isSuper && !$isSelf)
        <div class="bg-gray-900 border border-red-800 border-t-4 border-t-red-600 rounded-xl p-6 mt-6">
            <h3 class="text-lg font-semibold text-red-400 mb-2">Danger Zone</h3>
            <p class="text-sm text-gray-400 mb-4">Permanently deletes the user and all associated data.</p>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Permanently delete {{ addslashes($user->name) }}?');">
                @csrf @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold px-6 py-2 rounded">Delete User Permanently</button>
            </form>
        </div>
    @endif
</div>
@endsection
