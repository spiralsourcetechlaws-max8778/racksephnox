@extends('layouts.guest')

@section('content')
<div class="space-y-6 text-center">
    <h2 class="text-2xl font-bold text-white">Welcome Back</h2>

    {{-- Session status (e.g. "Your password has been reset") --}}
    @if (session('status'))
        <div class="bg-green-500/20 border border-green-500/40 text-green-300 text-sm p-3 rounded-lg text-left">
            {{ session('status') }}
        </div>
    @endif

    {{-- Error summary --}}
    @if ($errors->any())
        <div class="bg-red-500/20 border border-red-500/40 text-red-300 text-sm p-3 rounded-lg text-left">
            <p class="font-semibold mb-1">Sign-in failed</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-3">
        @csrf

        <div class="text-left">
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="Email"
                required
                autofocus
                autocomplete="username"
                class="w-full px-4 py-2 border @error('email') border-red-500 @else border-gold/30 @enderror rounded-lg bg-gray-900/50 text-white">
            @error('email')
                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="text-left">
            <input
                type="password"
                name="password"
                placeholder="Password"
                required
                autocomplete="current-password"
                class="w-full px-4 py-2 border @error('password') border-red-500 @else border-gold/30 @enderror rounded-lg bg-gray-900/50 text-white">
            @error('password')
                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-gray-400">
                <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }} class="rounded border-gold/30 bg-gray-900">
                <span>Remember me</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-gold hover:text-yellow-400">Forgot password?</a>
            @endif
        </div>

        <button type="submit" class="w-full btn-golden">Sign In</button>
    </form>

    @if (Route::has('register'))
        <p class="text-sm text-gray-400">
            Don't have an account?
            <a href="{{ route('register') }}" class="text-gold hover:text-yellow-400">Create one</a>
        </p>
    @endif
</div>
@endsection
