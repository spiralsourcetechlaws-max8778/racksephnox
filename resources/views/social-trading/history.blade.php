@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold text-white mb-6">Copy Trading History</h1>
    <div class="bg-gray-900 border border-gray-800 rounded-lg p-6">
        @if($history->isEmpty())
            <p class="text-gray-400">No copy trades executed yet.</p>
        @else
            <table class="w-full text-left text-sm text-gray-300">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-800">
                    <tr>
                        <th class="pb-2">Date</th>
                        <th class="pb-2">Trader</th>
                        <th class="pb-2">Symbol</th>
                        <th class="pb-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $trade)
                    <tr class="border-b border-gray-800">
                        <td class="py-2">{{ optional($trade->created_at)->format('M d, Y') ?? '—' }}</td>
                        <td class="py-2">{{ $trade->trader->name ?? 'Unknown' }}</td>
                        <td class="py-2">{{ $trade->symbol ?? '—' }}</td>
                        <td class="py-2 text-right">${{ number_format($trade->amount ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
