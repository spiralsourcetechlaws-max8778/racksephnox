@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold golden-title">Racksephnox Loans</h1>
            <p class="text-gold-400 mt-2">Borrow with dignity · Repay with honour · 528 Hz</p>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="stat-card p-4 text-center">
                <p class="text-xs text-gold-400 uppercase">Credit Score</p>
                <p class="text-2xl font-bold text-gold">{{ $stats['credit_score'] }}</p>
                <p class="text-xs text-green-400">{{ $stats['credit_tier'] }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-xs text-gold-400 uppercase">Total Borrowed</p>
                <p class="text-xl font-bold text-gold">KES {{ number_format($stats['total_borrowed'], 0) }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-xs text-gold-400 uppercase">Outstanding</p>
                <p class="text-xl font-bold text-red-400">KES {{ number_format($stats['total_outstanding'], 0) }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-xs text-gold-400 uppercase">Active</p>
                <p class="text-2xl font-bold text-gold">{{ $stats['active_loans'] }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-xs text-gold-400 uppercase">Completed</p>
                <p class="text-2xl font-bold text-green-400">{{ $stats['completed_loans'] }}</p>
            </div>
        </div>

        {{-- Products --}}
        <h2 class="text-xl font-bold text-gold mb-4">Available Loan Products</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-10">
            @forelse($products as $product)
                <div class="card-golden p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br {{ $product->color }} flex items-center justify-center">
                            <i class="fas {{ $product->icon }} text-white"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gold">{{ $product->name }}</h3>
                            <p class="text-xs text-gold-400/70">{{ $product->range_label }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-ivory/70 mb-4 min-h-[40px]">{{ $product->description }}</p>
                    <div class="grid grid-cols-2 gap-2 text-xs mb-4">
                        <div class="bg-gold/5 rounded p-2">
                            <p class="text-gold-400">Rate</p>
                            <p class="text-ivory font-bold">{{ $product->interest_rate }}%</p>
                        </div>
                        <div class="bg-gold/5 rounded p-2">
                            <p class="text-gold-400">Duration</p>
                            <p class="text-ivory font-bold">{{ $product->min_duration_days }}–{{ $product->max_duration_days }}d</p>
                        </div>
                    </div>
                    <div class="flex gap-2 text-xs text-gold-400/60 mb-4">
                        @if($product->requires_guarantor) <span><i class="fas fa-user-shield"></i> Guarantor</span> @endif
                        @if($product->requires_collateral) <span><i class="fas fa-lock"></i> Collateral</span> @endif
                        <span><i class="fas fa-wave-square"></i> {{ $product->frequency_hz }} Hz</span>
                    </div>
                    <a href="{{ route('loans.apply', $product) }}" class="btn-golden w-full text-center block">
                        Apply Now →
                    </a>
                </div>
            @empty
                <p class="text-ivory/50">No loan products available at the moment.</p>
            @endforelse
        </div>

        {{-- User Loans --}}
        <h2 class="text-xl font-bold text-gold mb-4">My Loans</h2>
        @if($loans->count())
            <div class="space-y-3">
                @foreach($loans as $loan)
                    <a href="{{ route('loans.show', $loan) }}" class="card-golden p-4 block hover:scale-[1.01] transition">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                            <div>
                                <p class="font-bold text-gold">{{ $loan->product_name }}</p>
                                <p class="text-xs text-gold-400/60">{{ $loan->reference }} · {{ $loan->created_at->format('M d, Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-gold">KES {{ number_format($loan->principal, 2) }}</p>
                                <p class="text-xs text-ivory/60">Principal</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-red-400">KES {{ number_format($loan->balance, 2) }}</p>
                                <p class="text-xs text-ivory/60">Balance</p>
                            </div>
                            <div class="text-right min-w-[100px]">
                                <p class="text-xs uppercase {{ $loan->status_color }}">{{ $loan->status }}</p>
                                <div class="w-24 bg-gray-700 rounded-full h-1.5 mt-2">
                                    <div class="bg-gold h-1.5 rounded-full" style="width: {{ $loan->progress_percent }}%"></div>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="card-golden p-10 text-center">
                <i class="fas fa-hand-holding-usd text-4xl text-gold/40 mb-4"></i>
                <p class="text-ivory/60">You have no loans yet.</p>
            </div>
        @endif

    </div>
</div>
@endsection
