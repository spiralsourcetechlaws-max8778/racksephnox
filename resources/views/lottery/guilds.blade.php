@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">

        <a href="{{ route('lottery.index') }}" class="text-gold-400 text-sm">
            <i class="fas fa-arrow-left"></i> Back to Lottery
        </a>

        <h1 class="text-3xl font-bold golden-title mt-4 mb-6">⚔️ Guilds</h1>

        @if($myGuild)
            <div class="card-golden p-5 mb-6">
                <p class="text-xs uppercase text-gold-400">Your Guild</p>
                <h2 class="text-2xl font-bold text-gold">{{ $myGuild->name }}</h2>
                <p class="text-xs text-ivory/60">{{ $myGuild->member_count }} members</p>
                <div class="mt-3 flex gap-2">
                    <a href="{{ route('lottery.guilds.show', $myGuild) }}" class="btn-golden text-sm px-4 py-1">View Guild</a>
                    <form method="POST" action="{{ route('lottery.guilds.leave') }}">
                        @csrf
                        <button class="btn-outline-silver text-sm px-4 py-1">Leave</button>
                    </form>
                </div>
            </div>
        @else
            <div class="card-golden p-5 mb-6">
                <h2 class="text-lg font-bold text-gold mb-3">Create Your Guild</h2>
                <form method="POST" action="{{ route('lottery.guilds.create') }}">
                    @csrf
                    <input type="text" name="name" placeholder="Guild name" class="input-golden w-full mb-2" required maxlength="40">
                    <textarea name="description" class="input-golden w-full mb-2" rows="2" placeholder="Describe your guild" maxlength="300"></textarea>
                    <button class="btn-golden">Create (5,000 KES)</button>
                </form>
                @if($errors->any())
                    <p class="text-red-400 text-xs mt-2">{{ $errors->first() }}</p>
                @endif
            </div>
        @endif

        <h2 class="text-lg font-bold text-gold mb-3">Guild Directory</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($directory as $g)
                <div class="card-golden p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-gold">{{ $g->name }}</h3>
                            <p class="text-xs text-ivory/60">{{ $g->members_count }} members</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('lottery.guilds.show', $g) }}" class="text-gold-400 text-xs">View</a>
                            @unless($myGuild)
                                <form method="POST" action="{{ route('lottery.guilds.join', $g) }}">
                                    @csrf
                                    <button class="text-green-400 text-xs">Join</button>
                                </form>
                            @endunless
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-ivory/50">No guilds yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
