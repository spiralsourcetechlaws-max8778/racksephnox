@extends('admin.layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gold">💸 Loans Management</h1>
        <form method="GET" class="flex gap-2">
            <select name="status" class="input-golden text-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                @foreach(['pending','approved','active','completed','rejected','defaulted','cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="admin-card p-4 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-gold/30 text-gold-400">
                <tr>
                    <th class="text-left p-2">Reference</th>
                    <th class="text-left p-2">User</th>
                    <th class="text-left p-2">Product</th>
                    <th class="text-right p-2">Principal</th>
                    <th class="text-right p-2">Balance</th>
                    <th class="text-left p-2">Status</th>
                    <th class="text-right p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                    <tr class="border-b border-gold/10 hover:bg-gold/5">
                        <td class="p-2 font-mono text-xs">{{ $loan->reference }}</td>
                        <td class="p-2">{{ $loan->user->name ?? '—' }}</td>
                        <td class="p-2">{{ $loan->product_name }}</td>
                        <td class="p-2 text-right">{{ number_format($loan->principal, 2) }}</td>
                        <td class="p-2 text-right text-red-400">{{ number_format($loan->balance, 2) }}</td>
                        <td class="p-2"><span class="{{ $loan->status_color }}">{{ ucfirst($loan->status) }}</span></td>
                        <td class="p-2 text-right">
                            <a href="{{ route('admin.loans.show', $loan) }}" class="text-gold-400 text-xs hover:text-gold">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-4 text-center text-ivory/50">No loans yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">{{ $loans->links() }}</div>
    </div>
</div>
@endsection
