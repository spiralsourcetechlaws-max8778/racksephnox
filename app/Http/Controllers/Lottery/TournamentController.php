<?php

namespace App\Http\Controllers\Lottery;

use App\Http\Controllers\Controller;
use App\Models\LotteryTournament;
use App\Services\Lottery\TournamentService;
use Illuminate\Support\Facades\Auth;

class TournamentController extends Controller
{
    public function __construct(protected TournamentService $service) {}

    public function index()
    {
        $active   = LotteryTournament::active()->orderByDesc('prize_pool')->get();
        $upcoming = LotteryTournament::upcoming()->orderBy('start_date')->get();
        $past     = LotteryTournament::where('end_date', '<', now())
            ->orderByDesc('end_date')
            ->limit(10)
            ->get();

        return view('lottery.tournaments', compact('active', 'upcoming', 'past'));
    }

    public function show(LotteryTournament $tournament)
    {
        $leaderboard = $this->service->leaderboard($tournament, 25);
        $me = Auth::user();
        $myEntry = $tournament->entries()->where('user_id', $me->id)->first();

        return view('lottery.tournament-show', compact('tournament', 'leaderboard', 'myEntry'));
    }
}
