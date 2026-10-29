@extends('admin.layouts.app')

@section('page-title', 'KYC Review')
@section('content')

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-white">KYC Review: {{ $document->user?->name ?? 'Unknown' }}</h1>
        <a href="{{ route('admin.kyc.index') }}" class="px-4 py-2 bg-gray-700 text-white rounded text-sm">Back</a>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <p class="text-sm text-gray-400">Document Type</p>
        <p class="text-white font-medium mb-4">{{ ucfirst($document->document_type ?? 'ID') }}</p>

        <p class="text-sm text-gray-400">Status</p>
        <p class="text-{{ $document->status === 'approved' ? 'green' : ($document->status === 'rejected' ? 'red' : 'yellow') }}-400 font-bold mb-4">{{ strtoupper($document->status) }}</p>

        @if($document->document_path)
            <p class="text-sm text-gray-400 mb-2">Document File</p>
            <a href="{{ asset('storage/' . $document->document_path) }}" target="_blank" class="text-gold hover:underline">View Document →</a>
        @endif

        <div class="flex gap-3 mt-6 pt-4 border-t border-gray-800">
            <form method="POST" action="{{ route('admin.kyc.approve', $document) }}">
                @csrf
                <button class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-bold rounded">Approve</button>
            </form>
            <form method="POST" action="{{ route('admin.kyc.reject', $document) }}" class="flex gap-2">
                @csrf
                <input type="text" name="reason" required placeholder="Rejection reason" class="bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                <button class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded">Reject</button>
            </form>
        </div>
    </div>
</div>
@endsection
