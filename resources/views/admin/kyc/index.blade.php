@extends('admin.layouts.app')

@section('page-title', 'KYC Verification')
@section('content')

<div class="space-y-4">
    <h1 class="text-2xl font-bold text-white">KYC Documents</h1>
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800/50 text-gray-400 text-xs uppercase">
                <tr><th class="p-3 text-left">User</th><th class="p-3 text-left">Type</th><th class="p-3 text-left">Status</th><th class="p-3 text-right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                <tr class="border-t border-gray-800">
                    <td class="p-3 text-white">{{ $doc->user?->name ?? 'Unknown' }}</td>
                    <td class="p-3 text-gray-400">{{ ucfirst($doc->document_type ?? 'ID') }}</td>
                    <td class="p-3">
                        <span class="px-2 py-0.5 text-xs rounded {{ $doc->status === 'approved' ? 'bg-green-500/20 text-green-400' : ($doc->status === 'rejected' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400') }}">{{ ucfirst($doc->status) }}</span>
                    </td>
                    <td class="p-3 text-right">
                        <a href="{{ route('admin.kyc.show', $doc) }}" class="px-2 py-1 bg-blue-600 text-white text-xs rounded">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="p-6 text-center text-gray-500">No KYC documents.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $documents->links() }}
</div>
@endsection
