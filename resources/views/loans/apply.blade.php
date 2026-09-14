@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">

        <a href="{{ route('loans.index') }}" class="text-gold-400 text-sm">
            <i class="fas fa-arrow-left"></i> Back to Loans
        </a>

        <div class="card-golden p-6 mt-4">
            <h1 class="text-2xl font-bold golden-title mb-1">{{ $product->name }}</h1>
            <p class="text-gold-400 text-sm mb-6">{{ $product->description }}</p>

            <div class="grid grid-cols-3 gap-3 mb-6 text-center text-xs">
                <div class="bg-gold/5 rounded p-2">
                    <p class="text-gold-400">Credit Score</p>
                    <p class="text-ivory font-bold">{{ $creditScore->score }} ({{ $creditScore->tier }})</p>
                </div>
                <div class="bg-gold/5 rounded p-2">
                    <p class="text-gold-400">Rate</p>
                    <p class="text-ivory font-bold">{{ $product->interest_rate }}%</p>
                </div>
                <div class="bg-gold/5 rounded p-2">
                    <p class="text-gold-400">Frequency</p>
                    <p class="text-ivory font-bold">{{ $product->frequency_hz }} Hz</p>
                </div>
            </div>

            <form method="POST" action="{{ route('loans.store', $product) }}" id="loanForm">
                @csrf

                <div class="mb-4">
                    <label class="text-xs text-gold-400">Loan Amount (KES)</label>
                    <input type="number" name="amount" id="amount" class="input-golden w-full"
                           min="{{ $product->min_amount }}" max="{{ $product->max_amount }}"
                           step="100" value="{{ old('amount', $product->min_amount) }}" required>
                    <p class="text-xs text-gold-400/60 mt-1">
                        Range: {{ $product->range_label }}
                    </p>
                </div>

                <div class="mb-4">
                    <label class="text-xs text-gold-400">Duration (days)</label>
                    <input type="number" name="duration" id="duration" class="input-golden w-full"
                           min="{{ $product->min_duration_days }}" max="{{ $product->max_duration_days }}"
                           value="{{ old('duration', $product->min_duration_days) }}" required>
                </div>

                <div class="mb-4">
                    <label class="text-xs text-gold-400">Repayment Frequency</label>
                    <select name="frequency" id="frequency" class="input-golden w-full">
                        <option value="monthly">Monthly</option>
                        <option value="weekly">Weekly</option>
                        <option value="daily">Daily</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="text-xs text-gold-400">Purpose (optional)</label>
                    <textarea name="purpose" class="input-golden w-full" rows="2"
                              placeholder="Brief description of what the loan is for">{{ old('purpose') }}</textarea>
                </div>

                {{-- Live preview --}}
                <div class="bg-gold/5 rounded-xl p-4 mb-6">
                    <h3 class="text-sm font-bold text-gold mb-3">Projection</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                        <div>
                            <p class="text-gold-400">Principal</p>
                            <p class="text-ivory font-bold" id="pvPrincipal">—</p>
                        </div>
                        <div>
                            <p class="text-gold-400">Total Interest</p>
                            <p class="text-ivory font-bold" id="pvInterest">—</p>
                        </div>
                        <div>
                            <p class="text-gold-400">Total Payable</p>
                            <p class="text-ivory font-bold" id="pvTotal">—</p>
                        </div>
                        <div>
                            <p class="text-gold-400">Installment</p>
                            <p class="text-ivory font-bold" id="pvInstallment">—</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-golden w-full">Submit Application</button>
            </form>

            @if($errors->any())
                <p class="text-red-400 text-sm mt-3">{{ $errors->first() }}</p>
            @endif
        </div>
    </div>
</div>

<script>
    const productId = {{ $product->id }};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    async function updatePreview() {
        const amount = document.getElementById('amount').value;
        const duration = document.getElementById('duration').value;
        const frequency = document.getElementById('frequency').value;
        if (!amount || !duration) return;

        try {
            const res = await fetch(`{{ route('loans.preview', $product) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ amount, duration, frequency }),
            });
            if (!res.ok) return;
            const d = await res.json();
            document.getElementById('pvPrincipal').textContent   = 'KES ' + (+d.principal).toLocaleString();
            document.getElementById('pvInterest').textContent    = 'KES ' + (+d.total_interest).toLocaleString();
            document.getElementById('pvTotal').textContent       = 'KES ' + (+d.total_payable).toLocaleString();
            document.getElementById('pvInstallment').textContent = 'KES ' + (+d.installment_amount).toLocaleString();
        } catch (e) { console.error(e); }
    }

    ['amount','duration','frequency'].forEach(id => {
        document.getElementById(id).addEventListener('input', updatePreview);
    });
    updatePreview();
</script>
@endsection
