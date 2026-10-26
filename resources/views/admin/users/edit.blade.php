@extends('admin.layouts.app')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Edit User: {{ $user->name }}</h2>
    <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded">Back</a>
</div>

<div class="bg-white p-6 rounded-lg shadow max-w-lg">
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf @method('PATCH')
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full border rounded px-3 py-2 text-black">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full border rounded px-3 py-2 text-black">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full border rounded px-3 py-2 text-black">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Admin?</label>
            <select name="is_admin" class="w-full border rounded px-3 py-2 text-black">
                <option value="1" {{ $user->is_admin ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ !$user->is_admin ? 'selected' : '' }}>No</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Verified?</label>
            <select name="is_verified" class="w-full border rounded px-3 py-2 text-black">
                <option value="1" {{ $user->is_verified ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ !$user->is_verified ? 'selected' : '' }}>No</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">KYC Level</label>
            <select name="kyc_level" class="w-full border rounded px-3 py-2 text-black">
                <option value="basic" {{ $user->kyc_level == 'basic' ? 'selected' : '' }}>Basic</option>
                <option value="tier1" {{ $user->kyc_level == 'tier1' ? 'selected' : '' }}>Tier 1</option>
                <option value="tier2" {{ $user->kyc_level == 'tier2' ? 'selected' : '' }}>Tier 2</option>
            </select>
        </div>
        
        <!-- Added VIP and Promo -->
        <div class="mb-4 border-t pt-4">
            <label class="block text-sm font-medium mb-1">VIP Status</label>
            <select name="is_vip" class="w-full border rounded px-3 py-2 text-black">
                <option value="1" {{ $user->is_vip ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ !$user->is_vip ? 'selected' : '' }}>No</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Promo Status</label>
            <select name="has_promo" class="w-full border rounded px-3 py-2 text-black">
                <option value="1" {{ $user->has_promo ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ !$user->has_promo ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Update User</button>
    </form>
</div>
@endsection
