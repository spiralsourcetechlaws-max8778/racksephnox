@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <h1 class="text-3xl font-bold golden-title mb-6 text-center">👥 Social Hub</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <a href="{{ route('lottery.guilds') }}" class="card-golden p-6 hover:scale-[1.02] transition">
                <i class="fas fa-users text-4xl text-gold mb-3"></i>
                <h2 class="text-xl font-bold text-gold mb-2">Guilds</h2>
                <p class="text-sm text-ivory/70">Join or lead a guild to compete in collective tournaments.</p>
            </a>
            <a href="{{ route('lottery.tournaments') }}" class="card-golden p-6 hover:scale-[1.02] transition">
                <i class="fas fa-trophy text-4xl text-gold mb-3"></i>
                <h2 class="text-xl font-bold text-gold mb-2">Tournaments</h2>
                <p class="text-sm text-ivory/70">Compete with players worldwide for prize pools.</p>
            </a>
        </div>
    </div>
</div>
@endsection
