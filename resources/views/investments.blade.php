@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-white">My Investments</h1>
        <a href="{{ route('investments.store') }}" class="btn-golden px-4 py-2">New Investment</a>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <p class="text-gray-400">Your active and past investments will appear here.</p>
    </div>
</div>
@endsection
