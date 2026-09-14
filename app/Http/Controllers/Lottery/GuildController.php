<?php

namespace App\Http\Controllers\Lottery;

use App\Http\Controllers\Controller;
use App\Models\LotteryGuild;
use App\Services\Lottery\GuildService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuildController extends Controller
{
    public function __construct(protected GuildService $service) {}

    public function index()
    {
        $directory   = $this->service->directory(30);
        $myGuild     = $this->service->userGuild(Auth::user());

        return view('lottery.guilds', compact('directory', 'myGuild'));
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:40|unique:lottery_guilds,name',
            'description' => 'nullable|string|max:300',
        ]);

        try {
            $guild = $this->service->create(Auth::user(), $data['name'], $data['description'] ?? null);
            return redirect()->route('lottery.guilds.show', $guild)->with('success', 'Guild created!');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(LotteryGuild $guild)
    {
        $members = $guild->members()->with('user')->get();
        return view('lottery.guild-show', compact('guild', 'members'));
    }

    public function join(LotteryGuild $guild)
    {
        try {
            $this->service->join(Auth::user(), $guild);
            return redirect()->route('lottery.guilds.show', $guild)->with('success', 'Joined guild!');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function leave()
    {
        try {
            $this->service->leave(Auth::user());
            return redirect()->route('lottery.guilds')->with('success', 'Left guild.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
