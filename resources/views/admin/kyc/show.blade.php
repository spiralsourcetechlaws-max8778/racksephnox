@extends('admin.layouts.app')

@section('page-title', 'Review KYC')
@section('breadcrumbs')
    <a href="{{ route('admin.kyc.index') }}" class="text-gold hover:text-yellow-400">KYC</a>
    <span class="mx-1">/</span> Review
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    @if(session('success')) <div class="bg-green-500/20 text-green-400 p-3 rounded">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="bg-red-500/20 text-red-400 p-3 rounded">{{ $errors->first() }}</div> @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Document image --}}
        <div class="admin-card p-5">
            <h3 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Uploaded Document</h3>

            @php
                $fileUrl = asset('storage/' . $doc->document_path);
                $ext = strtolower(pathinfo($doc->document_path ?? '', PATHINFO_EXTENSION));
            @endphp

            @if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp']))
                <img src="{{ $fileUrl }}" alt="KYC Document" class="w-full rounded border border-gray-700">
            @else
                <div class="text-center p-8 bg-gray-800/50 rounded border border-gray-700">
                    <i class="fas fa-file text-4xl text-gray-500 mb-3"></i>
                    <p class="text-gray-400 mb-3 text-sm">{{ strtoupper($ext) }} document</p>
                    <a href="{{ $fileUrl }}" target="_blank" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded">
                        <i class="fas fa-download mr-1"></i> Download to Review
                    </a>
                </div>
            @endif
        </div>

        {{-- Action panel --}}
        <div class="admin-card p-5 space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Applicant</h3>
                <div class="space-y-2 text-sm">
                    <p><span class="text-gray-400">Name:</span> <span class="text-white font-medium">{{ $doc->user->name ?? '—' }}</span></p>
                    <p><span class="text-gray-400">Email:</span> <span class="text-white">{{ $doc->user->email ?? '—' }}</span></p>
                    <p><span class="text-gray-400">Phone:</span> <span class="text-white">{{ $doc->user->phone ?? '—' }}</span></p>
                    <p><span class="text-gray-400">Doc type:</span> <span class="text-white uppercase">{{ $doc->document_type ?? 'ID' }}</span></p>
                    <p><span class="text-gray-400">Submitted:</span> <span class="text-white">{{ $doc->created_at->format('M d, Y H:i') }}</span></p>
                    <p><span class="text-gray-400">Current status:</span>
                        <span class="px-2 py-0.5 text-xs rounded {{ $doc->status === 'approved' ? 'bg-green-500/20 text-green-400' : ($doc->status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400') }}">
                            {{ ucfirst($doc->status) }}
                        </span>
                    </p>
                </div>
                <a href="{{ route('admin.users.show', $doc->user_id) }}" class="inline-block mt-3 text-xs text-gold hover:text-yellow-400">
                    <i class="fas fa-external-link-alt mr-1"></i> View full user profile
                </a>
            </div>

            <div class="pt-4 border-t border-gray-800 space-y-3">
                <form method="POST" action="{{ route('admin.kyc.approve', $doc) }}"
                      onsubmit="return confirm('Approve this KYC and mark {{ addslashes($doc->user->name ?? 'user') }} as verified?');">
                    @csrf
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded transition">
                        <i class="fas fa-check-circle mr-2"></i> Approve & Verify User
                    </button>
                </form>

                <div class="relative">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-800"></div></div>
                    <div class="relative flex justify-center"><span class="bg-gray-950 px-3 text-xs text-gray-500">OR</span></div>
                </div>

                <form method="POST" action="{{ route('admin.kyc.reject', $doc) }}">
                    @csrf
                    <label class="block text-sm text-gray-400 mb-1">Reason for rejection (shown to user)</label>
                    <input type="text" name="reason" required placeholder="e.g. Image is blurry, please re-upload"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm mb-2">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded transition">
                        <i class="fas fa-times-circle mr-2"></i> Reject Document
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
