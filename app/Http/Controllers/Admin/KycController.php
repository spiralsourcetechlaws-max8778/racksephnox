<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use Illuminate\Http\Request;

class KycController extends Controller
{
    public function index()
    {
        $documents = KycDocument::with('user')->latest()->paginate(20);
        return view('admin.kyc.index', compact('documents'));
    }

    public function show(KycDocument $document)
    {
        $document->load('user');
        return view('admin.kyc.show', compact('document'));
    }

    public function approve(KycDocument $document)
    {
        $document->update(['status' => 'approved', 'verified_at' => now()]);
        if ($document->user) {
            $document->user->update(['kyc_status' => 'verified', 'is_verified' => true]);
        }
        return back()->with('success', 'KYC approved.');
    }

    public function reject(Request $request, KycDocument $document)
    {
        $request->validate(['reason' => 'required|string']);
        $document->update(['status' => 'rejected', 'rejection_reason' => $request->reason]);
        return back()->with('success', 'KYC rejected.');
    }
}
