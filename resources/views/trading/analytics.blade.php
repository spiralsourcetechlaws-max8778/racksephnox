@extends('layouts.app')

@section('title', 'Trading Analytics')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold golden-title">Trading Analytics</h1>
            <a href="{{ route('trading.index') }}" class="btn-outline-silver text-sm">Back to Console</a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="card-golden p-5 text-center">
                <p class="text-xs text-gold-400 uppercase">Total Orders</p>
                <p class="text-3xl font-bold text-gold">{{ number_format($stats['total_orders']) }}</p>
            </div>
            <div class="card-golden p-5 text-center">
                <p class="text-xs text-gold-400 uppercase">Completed</p>
                <p class="text-3xl font-bold text-green-400">{{ number_format($stats['completed_orders']) }}</p>
            </div>
            <div class="card-golden p-5 text-center">
                <p class="text-xs text-gold-400 uppercase">Open Orders</p>
                <p class="text-3xl font-bold text-yellow-400">{{ number_format($stats['open_orders']) }}</p>
            </div>
            <div class="card-golden p-5 text-center">
                <p class="text-xs text-gold-400 uppercase">Total Volume</p>
                <p class="text-2xl font-bold text-gold">KES {{ number_format($stats['total_volume_kes'], 2) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="card-golden p-5">
                <p class="text-xs text-gold-400 uppercase mb-1">Trading Balance</p>
                <p class="text-2xl font-bold text-gold">KES {{ number_format($stats['trading_balance'], 2) }}</p>
            </div>
            <div class="card-golden p-5">
                <p class="text-xs text-gold-400 uppercase mb-1">BTC Balance</p>
                <p class="text-2xl font-bold text-gold">{{ number_format($stats['btc_balance'], 8) }} BTC</p>
            </div>
            <div class="card-golden p-5">
                <p class="text-xs text-gold-400 uppercase mb-1">Realized P&L</p>
                <p class="text-2xl font-bold {{ $stats['total_pnl_kes'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                    KES {{ number_format($stats['total_pnl_kes'], 2) }}
                </p>
            </div>
        </div>

        <div class="card-golden p-5">
            <h2 class="text-xl font-bold text-gold mb-4">Order History (Last 50)</h2>

            @if($recentOrders->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gold/30 text-gold-400 text-xs uppercase">
                            <tr>
                                <th class="text-left p-2">Date</th>
                                <th class="text-left p-2">Side</th>
                                <th class="text-left p-2">Type</th>
                                <th class="text-right p-2">Amount (BTC)</th>
                                <th class="text-right p-2">Price (KES)</th>
                                <th class="text-right p-2">Total (KES)</th>
                                <th class="text-center p-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentOrders as $order)
                                <tr class="border-b border-gold/10 hover:bg-gold/5 transition">
                                    <td class="p-2 text-ivory/70 text-xs">{{ $order->created_at->format('M d, H:i') }}</td>
                                    <td class="p-2"><span class="text-xs font-bold {{ $order->side === 'buy' ? 'text-green-400' : 'text-red-400' }}">{{ strtoupper($order->side) }}</span></td>
                                    <td class="p-2 text-xs text-gold-400 uppercase">{{ $order->order_type }}</td>
                                    <td class="p-2 text-right text-ivory">{{ number_format($order->amount_btc, 8) }}</td>
                                    <td class="p-2 text-right text-ivory">{{ number_format($order->price_per_btc ?? $order->limit_price ?? 0, 2) }}</td>
                                    <td class="p-2 text-right text-gold font-semibold">{{ number_format($order->filled_kes ?? 0, 2) }}</td>
                                    <td class="p-2 text-center">
                                        @php
                                            $badge = match($order->status) {
                                                'completed' => 'bg-green-500/20 text-green-400',
                                                'partial'   => 'bg-yellow-500/20 text-yellow-400',
                                                'pending'   => 'bg-blue-500/20 text-blue-400',
                                                'cancelled' => 'bg-gray-500/20 text-gray-400',
                                                default     => 'bg-red-500/20 text-red-400',
                                            };
                                        @endphp
                                        <span class="text-xs px-2 py-0.5 rounded-full {{ $badge }}">{{ ucfirst($order->status) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-ivory/40 text-sm text-center py-10">No orders yet</p>
            @endif
        </div>

    </div>
</div>
@endsection
