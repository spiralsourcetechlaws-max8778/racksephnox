@extends('layouts.guest')

@section('content')
<div class="space-y-6 text-center">
    <h2 class="text-2xl font-bold text-white">Create Account</h2>

    {{-- Validation errors --}}
    @if($errors->any())
        <div class="bg-red-500/20 border border-red-500/40 text-red-300 text-sm p-3 rounded-lg text-left">
            <p class="font-semibold mb-1">Please fix these:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Referral indicator if link was used --}}
    @if(!empty($referral_code))
        <div class="bg-green-500/20 border border-green-500/40 text-green-300 text-sm p-3 rounded-lg">
            <i class="fas fa-gift mr-2"></i>
            You were invited — your referrer earns a bonus when you join.
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-3">
        @csrf

        {{-- Carry the ?ref= code through the form submission --}}
        <input type="hidden" name="referral_code" value="{{ old('referral_code', $referral_code ?? '') }}">

        <div class="text-left">
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Full Name"
                   class="w-full px-4 py-2 border @error('name') border-red-500 @else border-gold/30 @enderror rounded-lg bg-gray-900/50 text-white">
            @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="text-left">
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="Email"
                   autocomplete="username"
                   class="w-full px-4 py-2 border @error('email') border-red-500 @else border-gold/30 @enderror rounded-lg bg-gray-900/50 text-white">
            @error('email')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="text-left">
            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="Phone (254...)"
                   class="w-full px-4 py-2 border @error('phone') border-red-500 @else border-gold/30 @enderror rounded-lg bg-gray-900/50 text-white">
            @error('phone')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="text-left">
            <input type="password" name="password" required placeholder="Password"
                   autocomplete="new-password"
                   class="w-full px-4 py-2 border @error('password') border-red-500 @else border-gold/30 @enderror rounded-lg bg-gray-900/50 text-white">
            @error('password')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="text-left">
            <input type="password" name="password_confirmation" required placeholder="Confirm Password"
                   autocomplete="new-password"
                   class="w-full px-4 py-2 border border-gold/30 rounded-lg bg-gray-900/50 text-white">
        </div>

        {{-- Show referral code field ONLY if not already carried via URL --}}
        @if(empty($referral_code))
            <div class="text-left">
                <label for="referral_code" class="block text-xs text-gray-400 mb-1">Referral Code (optional)</label>
                <input type="text" name="referral_code" id="referral_code"
                       value="{{ old('referral_code') }}"
                       placeholder="Enter a friend's code"
                       class="w-full px-4 py-2 border @error('referral_code') border-red-500 @else border-gold/30 @enderror rounded-lg bg-gray-900/50 text-white">
                @error('referral_code')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        @endif

        <label class="flex items-center gap-2 text-sm text-gray-400 justify-center">
            <input type="checkbox" name="terms" value="1" required {{ old('terms') ? 'checked' : '' }}>
            <span>I agree to the <a href="{{ route('legal.terms') }}" class="text-gold hover:underline">Terms</a></span>
        </label>
        @error('terms')<p class="text-red-400 text-xs">{{ $message }}</p>@enderror

        <button type="submit" class="w-full btn-golden">Register</button>
    </form>

    <p class="text-sm text-gray-400">
        Already have an account?
        <a href="{{ route('login') }}" class="text-gold hover:text-yellow-400">Sign in</a>
    </p>
</div>
@endsection
