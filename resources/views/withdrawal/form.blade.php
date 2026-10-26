@extends('layouts.app')
@section('content')
<div class="py-12">
    <div class="max-w-md mx-auto">
        <div class="card-golden p-6">
            <h1 class="text-2xl font-bold text-gold mb-4">💳 Withdraw Funds</h1>
            
            @if(session('success'))
                <div class="bg-green-500/20 text-green-400 p-3 rounded mb-4">{{ session('success') }}</div>
            @endif
            
            @if($errors->any())
                <div class="bg-red-500/20 text-red-400 p-3 rounded mb-4">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('withdrawal.submit') }}">
                @csrf
                <div>
                    <!-- Updated label and min value -->
                    <label>Amount (KES, min 100)</label>
                    <input type="number" name="amount" min="100" step="1" class="input-golden w-full" placeholder="e.g. 500" required>
                </div>
                
                <div class="mt-3">
                    <label>Bank Account</label>
                    <select name="bank_account_id" class="input-golden w-full" required>
                        <option value="">Select account</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->bank_name }} - {{ $acc->account_number }}</option>
                        @endforeach
                    </select>
                </div>
                
                <button type="submit" class="btn-golden w-full mt-4">Request Withdrawal</button>
            </form>
            
            <hr class="my-4 border-gold/20">
            <a href="{{ route('bank-accounts.index') }}" class="text-gold-400 text-sm">+ Add bank account</a>
        </div>
    </div>
</div>
@endsection
