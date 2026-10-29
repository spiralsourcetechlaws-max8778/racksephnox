@extends('admin.layouts.app')
@section('page-title', 'Reports')
@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-white">Platform Reports</h1>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4"><p class="text-xs text-gray-400">Total Users</p><p class="text-2xl font-bold text-white">{{ number_format($totalUsers) }}</p></div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4"><p class="text-xs text-gray-400">Total Invested</p><p class="text-2xl font-bold text-gold">KES {{ number_format($totalInvested, 0) }}</p></div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4"><p class="text-xs text-gray-400">Total Deposited</p><p class="text-2xl font-bold text-green-400">KES {{ number_format($totalDeposited, 0) }}</p></div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4"><p class="text-xs text-gray-400">Total Withdrawn</p><p class="text-2xl font-bold text-red-400">KES {{ number_format($totalWithdrawn, 0) }}</p></div>
    </div>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h3 class="text-sm font-semibold text-gold mb-3">Monthly Users</h3>
        @foreach($monthlyUsers as $m)
            <div class="flex justify-between py-1 border-b border-gray-800/50 text-sm"><span class="text-gray-300">{{ $m->month }}</span><span class="text-white">{{ $m->total }}</span></div>
        @endforeach
    </div>
</div>
@endsection
