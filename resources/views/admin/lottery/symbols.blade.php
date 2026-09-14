@extends('admin.layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gold">🔮 Lottery Symbols</h1>
        <a href="{{ route('admin.lottery.index') }}" class="text-gold-400 text-sm">← Back</a>
    </div>

    {{-- ADD SYMBOL --}}
    <div class="admin-card p-5">
        <h3 class="text-lg font-bold text-gold mb-3">Add Symbol</h3>
        <form method="POST" action="{{ route('admin.lottery.symbols.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            @csrf
            <input type="text" name="name" placeholder="name (seven)" class="input-golden" required>
            <input type="text" name="display_name" placeholder="Display Name" class="input-golden">
            <input type="text" name="icon" placeholder="fa-7" class="input-golden">
            <input type="number" step="0.01" name="multiplier" placeholder="Multiplier" class="input-golden" required>
            <button class="btn-golden">Add</button>
        </form>
        <form method="POST" action="{{ route('admin.lottery.symbols.import') }}" enctype="multipart/form-data" class="mt-3 flex gap-2">
            @csrf
            <input type="file" name="file" class="input-golden flex-1" accept=".csv,.json,.txt" required>
            <button class="btn-outline-silver px-4">Import CSV/JSON</button>
        </form>
    </div>

    {{-- SYMBOLS TABLE --}}
    <div class="admin-card p-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-gold/30 text-gold-400 text-xs uppercase">
                <tr>
                    <th class="text-left p-2">Name</th>
                    <th class="text-left p-2">Display</th>
                    <th class="text-left p-2">Icon</th>
                    <th class="text-right p-2">Multiplier</th>
                    <th class="text-center p-2">Divine</th>
                    <th class="text-right p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($symbols as $s)
                    <tr class="border-b border-gold/10">
                        <form method="POST" action="{{ route('admin.lottery.symbols.update', $s) }}">
                            @csrf
                            <td class="p-2">{{ $s->name }}</td>
                            <td class="p-2"><input type="text" name="display_name" value="{{ $s->display_name }}" class="input-golden text-xs py-1"></td>
                            <td class="p-2"><input type="text" name="icon" value="{{ $s->icon }}" class="input-golden text-xs py-1"></td>
                            <td class="p-2 text-right"><input type="number" step="0.01" name="multiplier" value="{{ $s->multiplier }}" class="input-golden text-xs py-1 w-24"></td>
                            <td class="p-2 text-center">
                                <input type="checkbox" name="is_divine" value="1" {{ $s->is_divine ? 'checked' : '' }}>
                            </td>
                            <td class="p-2 text-right">
                                <button class="text-gold-400 text-xs">Save</button>
                        </form>
                                <form method="POST" action="{{ route('admin.lottery.symbols.destroy', $s) }}" class="inline" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-400 text-xs ml-2">Delete</button>
                                </form>
                            </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
