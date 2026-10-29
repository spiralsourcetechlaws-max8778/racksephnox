@extends('admin.layouts.app')
@section('page-title', 'Lottery Management')
@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-white">Lottery Overview</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.lottery.symbols') }}" class="px-4 py-2 bg-gray-700 text-white rounded text-sm">Symbols</a>
            <a href="{{ route('admin.lottery.jackpots') }}" class="px-4 py-2 bg-gray-700 text-white rounded text-sm">Jackpots</a>
            <a href="{{ route('admin.lottery.analytics') }}" class="px-4 py-2 bg-gray-700 text-white rounded text-sm">Analytics</a>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($games as $game)
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h3 class="text-lg font-bold text-gold">{{ $game->name }}</h3>
            <p class="text-sm text-gray-400">Status: {{ $game->is_active ? 'Active' : 'Disabled' }}</p>
            <a href="{{ route('admin.lottery.edit', $game) }}" class="mt-3 inline-block px-3 py-1 bg-gray-800 text-white text-xs rounded">Edit Game</a>
        </div>
        @endforeach
    </div>
</div>
@endsection
