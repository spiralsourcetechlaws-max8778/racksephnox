@extends('admin.layouts.app')

@section('page-title', 'KYC Review Queue')
@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-white">KYC Review Queue</h2>
            <p class="text-sm text-gray-400">Verify user identity documents. Approved users can access withdrawals and higher tiers.</p>
        </div>
    </div>

    @if(session('success')) <div class="bg-green-500/20 text-green-400 p-3 rounded">{{ session('success') }}</div> @endif

    <div class="admin-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800/50 border-b border-gray-800 text-gray-400 text-xs uppercase">
                <tr>
                    <th class="text-left p-3">User</th>
                    <th class="text-left p-3 hidden md:table-cell">Document Type</th>
                    <th class="text-left p-3 hidden lg:table-cell">Submitted</th>
                    <th class="text-right p-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition">
                        <td class="p-3">
                            <p class="text-white font-medium">{{ $doc->user->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-gray-500">{{ $doc->user->email ?? '' }}</p>
                        </td>
                        <td class="p-3 hidden md:table-cell">
                            <span class="px-2 py-0.5 bg-gray-700 text-gray-300 text-xs rounded uppercase">
                                {{ $doc->document_type ?? 'ID' }}
                            </span>
                        </td>
                        <td class="p-3 hidden lg:table-cell text-xs text-gray-500">{{ $doc->created_at->format('M d, Y H:i') }}</td>
                        <td class="p-3 text-right">
                            <a href="{{ route('admin.kyc.show', $doc) }}"
                               class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition">
                                <i class="fas fa-eye mr-1"></i> Review
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-12 text-center">
                            <i class="fas fa-id-card text-4xl text-gray-700 mb-3"></i>
                            <p class="text-gray-500">No pending KYC documents. 🎉</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $documents->links() }}</div>
</div>
@endsection
