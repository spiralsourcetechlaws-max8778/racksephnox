@extends('layouts.guest')
@section('content')
<div class="space-y-6 text-center"><h2 class="text-2xl font-bold text-white">Create Account</h2>
<form method="POST" action="{{ route('register') }}">@csrf
<input type="text" name="name" placeholder="Full Name" class="w-full px-4 py-2 border border-gold/30 rounded-lg bg-gray-900/50 text-white">
<input type="email" name="email" placeholder="Email" class="w-full px-4 py-2 border border-gold/30 rounded-lg bg-gray-900/50 text-white">
<input type="tel" name="phone" placeholder="Phone (254...)" class="w-full px-4 py-2 border border-gold/30 rounded-lg bg-gray-900/50 text-white">
<input type="password" name="password" placeholder="Password" class="w-full px-4 py-2 border border-gold/30 rounded-lg bg-gray-900/50 text-white">
<input type="password" name="password_confirmation" placeholder="Confirm Password" class="w-full px-4 py-2 border border-gold/30 rounded-lg bg-gray-900/50 text-white">
<input type="checkbox" name="terms" required> <label>I agree to the Terms</label>
<button type="submit" class="w-full btn-golden">Register</button>
</form></div>
<div class="mt-4">
    <label for="referral_code">Referral Code (optional)</label>
    <input type="text" name="referral_code" id="referral_code"
           value="{{ old('referral_code', $referral_code ?? '') }}"
           class="input-golden w-full" placeholder="Enter a friend's code">
</div>
@endsection

