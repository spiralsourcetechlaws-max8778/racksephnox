<?php

namespace App\Http\Controllers\Lottery;

use App\Http\Controllers\Controller;
use App\Models\LotteryFairSeed;
use App\Services\Lottery\RngService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LotteryFairController extends Controller
{
    public function __construct(protected RngService $rng) {}

    public function index()
    {
        $seed = LotteryFairSeed::where('user_id', Auth::id())
            ->where('revealed', false)
            ->latest()
            ->first();

        $revealed = LotteryFairSeed::where('user_id', Auth::id())
            ->where('revealed', true)
            ->latest()
            ->limit(10)
            ->get();

        return view('lottery.fair', compact('seed', 'revealed'));
    }

    public function rotate()
    {
        $this->rng->rotateChain(Auth::user());
        return back()->with('success', 'Seed rotated. Previous seed revealed.');
    }

    public function setClientSeed(Request $request)
    {
        $data = $request->validate(['client_seed' => 'required|string|min:4|max:64']);

        $seed = LotteryFairSeed::where('user_id', Auth::id())
            ->where('revealed', false)
            ->latest()
            ->firstOrFail();

        $seed->client_seed = $data['client_seed'];
        $seed->save();

        return back()->with('success', 'Client seed updated.');
    }
}
