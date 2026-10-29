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
    $me = auth()->user();
    $isSuper = $me->hasRole('super_admin');
    $isSelf  = $user->id === $me->id;
    $initialIsAdmin = old('is_admin', $user->is_admin) ? 'true' : 'false';
@endphp

<div class="max-w-4xl mx-auto space-y-4 pb-32" x-data="{ submitting: false }">

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

    <form method="POST" action="{{ route('admin.users.update', $user) }}" id="edit-user-form" @submit="submitting = true">
        @csrf
        @method('PATCH')

        {{-- IDENTITY --}}
        <div class="admin-card p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gold pb-2 border-b border-gray-800">Identity</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full bg-gray-800 border @error('name') border-red-500 @else border-gray-700 @enderror text-white rounded px-3 py-2 focus:border-gold focus:outline-none">
                    @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full bg-gray-800 border @error('email') border-red-500 @else border-gray-700 @enderror text-white rounded px-3 py-2 focus:border-gold focus:outline-none">
                    @error('email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="2547XXXXXXXX" class="w-full bg-gray-800 border @error('phone') border-red-500 @else border-gray-700 @enderror text-white rounded px-3 py-2 focus:border-gold focus:outline-none">
                    @error('phone') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
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
                    <input type="password" name="password" autocomplete="new-password" placeholder="Leave blank to keep current" class="w-full bg-gray-800 border @error('password') border-red-500 @else border-gray-700 @enderror text-white rounded px-3 py-2 focus:border-gold focus:outline-none">
                    @error('password') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Confirm New Password</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repeat new password" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 focus:border-gold focus:outline-none">
                </div>
            </div>
        </div>

        {{-- ACCOUNT STATUS & SECURITY --}}
        <div class="admin-card p-6 space-y-4 mt-4">
            <h2 class="text-lg font-semibold text-gold pb-2 border-b border-gray-800">Account Status & Security</h2>
            
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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-800/50">
                {{-- Email Verified --}}
                <div class="flex items-center justify-between p-3 bg-gray-800/50 border border-gray-700 rounded {{ $isSelf ? 'opacity-60' : '' }}">
                    <div>
                        <p class="text-sm font-medium text-white">Email Verified</p>
                        <p class="text-xs text-gray-400">{{ $isSelf ? 'Locked' : 'Skip verification' }}</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        @if(!$isSelf) <input type="hidden" name="is_verified" value="0"> @endif
                        <input type="checkbox" name="is_verified" value="1" class="sr-only peer" {{ old('is_verified', $user->is_verified) ? 'checked' : '' }} {{ $isSelf ? 'disabled' : '' }}>
                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                    </label>
                </div>

                {{-- Active Account --}}
                <div class="flex items-center justify-between p-3 bg-gray-800/50 border border-gray-700 rounded {{ $isSelf ? 'opacity-60' : '' }}">
                    <div>
                        <p class="text-sm font-medium text-white">Active Account</p>
                        <p class="text-xs text-gray-400">{{ $isSelf ? 'Locked' : 'Unchecked = blocked' }}</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        @if(!$isSelf) <input type="hidden" name="is_active" value="0"> @endif
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }} {{ $isSelf ? 'disabled' : '' }}>
                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                    </label>
                </div>

                {{-- Reset 2FA (NEW) --}}
                <div class="flex items-center justify-between p-3 bg-gray-800/50 border border-gray-700 rounded">
                    <div>
                        <p class="text-sm font-medium text-white">Force 2FA Reset</p>
                        <p class="text-xs text-gray-400">Clears two_factor_*</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="reset_2fa" value="0">
                        <input type="checkbox" name="reset_2fa" value="1" class="sr-only peer" {{ old('reset_2fa') ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                    </label>
                </div>
            </div>
        </div>

        @if($isSuper)
            {{-- ADMIN PRIVILEGES --}}
            <div class="admin-card p-6 space-y-4 mt-4 border-l-4 border-l-red-500" x-data="{ isAdmin: {{ $initialIsAdmin }} }">
                <h2 class="text-lg font-semibold text-red-400 pb-2 border-b border-gray-800">Admin Privileges</h2>
                
                <div class="flex items-center justify-between p-3 bg-gray-800/50 border border-gray-700 rounded {{ $isSelf ? 'opacity-60' : '' }}">
                    <div>
                        <p class="text-sm font-medium text-white">Is Administrator</p>
                        <p class="text-xs text-gray-400">{{ $isSelf ? 'Locked - cannot remove own access' : 'Grants access to /admin panel' }}</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        @if(!$isSelf) <input type="hidden" name="is_admin" value="0"> @endif
                        <input type="checkbox" name="is_admin" value="1" x-model="isAdmin" class="sr-only peer" {{ $isSelf ? 'disabled' : '' }}>
                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-500"></div>
                    </label>
                </div>

                <div x-show="isAdmin" x-cloak class="mt-4 pt-4 border-t border-gray-800/50">
                    <label class="block text-sm text-gray-400 mb-1">Admin Role</label>
                    <select name="admin_role" :disabled="!isAdmin" {{ $isSelf ? 'disabled' : '' }} class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                        <option value="super_admin" {{ old('admin_role', $user->admin_role) === 'super_admin' ? 'selected' : '' }}>Super Admin - Full access</option>
                        <option value="finance" {{ old('admin_role', $user->admin_role) === 'finance' ? 'selected' : '' }}>Finance Admin - Money movement</option>
                        <option value="support" {{ old('admin_role', $user->admin_role) === 'support' ? 'selected' : '' }}>Support Admin - Users & KYC</option>
                    </select>
                    @error('admin_role') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    @if($isSelf)
                        <p class="text-xs text-yellow-400 mt-1">Your role is locked to prevent self-lockout</p>
                    @endif
                </div>
            </div>

            {{-- USER PRIVILEGES --}}
            <div class="admin-card p-6 space-y-4 mt-4 border-l-4 border-l-yellow-500">
                <h2 class="text-lg font-semibold text-yellow-400 pb-2 border-b border-gray-800">User Privileges & Features</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center justify-between p-3 bg-gray-800/50 border border-gray-700 rounded">
                        <div>
                            <p class="text-sm font-medium text-white">VIP Member</p>
                            <p class="text-xs text-gray-400">Premium tiers & bonuses</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="is_vip" value="0">
                            <input type="checkbox" name="is_vip" value="1" class="sr-only peer" {{ old('is_vip', $user->is_vip) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-500"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-800/50 border border-gray-700 rounded">
                        <div>
                            <p class="text-sm font-medium text-white">Promo Active</p>
                            <p class="text-xs text-gray-400">
                                @if($user->has_promo && $user->promo_expires_at)
                                    Expires {{ $user->promo_expires_at->format('M d, Y') }}
                                @else
                                    7-day expiry
                                @endif
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="has_promo" value="0">
                            <input type="checkbox" name="has_promo" value="1" class="sr-only peer" {{ old('has_promo', $user->has_promo) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-500"></div>
                        </label>
                    </div>

                    {{-- Onboarding Toggle (NEW) --}}
                    <div class="flex items-center justify-between p-3 bg-gray-800/50 border border-gray-700 rounded">
                        <div>
                            <p class="text-sm font-medium text-white">Onboarding Complete</p>
                            <p class="text-xs text-gray-400">Bypass wizard</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="onboarding_completed" value="0">
                            <input type="checkbox" name="onboarding_completed" value="1" class="sr-only peer" {{ old('onboarding_completed', $user->onboarding_completed) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500"></div>
                        </label>
                    </div>

                    {{-- Free Spins Input (NEW) --}}
                    <div class="p-3 bg-gray-800/50 border border-gray-700 rounded">
                        <label class="block text-sm font-medium text-white mb-1">Free Lottery Spins</label>
                        <input type="number" name="free_spins_available" value="{{ old('free_spins_available', $user->free_spins_available ?? 0) }}" min="0" class="w-full bg-gray-900 border border-gray-700 text-white rounded px-3 py-1 text-sm">
                        @error('free_spins_available') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        @endif

        {{-- FIXED STICKY SAVE BAR with LOADING STATE --}}
        <div class="fixed bottom-0 left-0 right-0 z-40 bg-gray-950 border-t-2 border-gold shadow-2xl p-4">
            <div class="max-w-4xl mx-auto flex justify-between items-center gap-3">
                <a href="{{ route('admin.users.show', $user) }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded">Cancel</a>
                <button type="submit" form="edit-user-form" :disabled="submitting" class="px-8 py-3 bg-gold hover:bg-yellow-500 text-black font-bold rounded text-base shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <svg x-show="submitting" class="animate-spin h-5 w-5 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-show="!submitting"><i class="fas fa-save mr-1"></i> Save All Changes</span>
                    <span x-show="submitting">Saving...</span>
                </button>
            </div>
        </div>
    </form>

    {{-- DANGER ZONE --}}
    @if($isSuper && !$isSelf)
        <div class="admin-card p-6 border-t-4 border-t-red-600 mt-4">
            <h3 class="text-lg font-semibold text-red-400 mb-2">Danger Zone</h3>
            <p class="text-sm text-gray-400 mb-4">Permanently deletes the user and all associated data. Cannot be undone.</p>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Permanently delete {{ addslashes($user->name) }}? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded">
                    <i class="fas fa-trash mr-1"></i> Delete User Permanently
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
