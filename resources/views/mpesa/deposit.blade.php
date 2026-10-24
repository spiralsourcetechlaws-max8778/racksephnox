@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto mt-6 px-4">
<div class="rounded-lg border border-gold-400/40 bg-gradient-to-br from-gold-500/10 to-amber-500/5 p-4">
<p class="text-xs uppercase tracking-wider text-gold-300 mb-1">Deposit via M-Pesa</p>
<p class="text-xs text-ivory/50 mb-1">Pochi la Biashara</p>
<p class="font-mono text-lg font-bold text-gold-200 select-all">{{ config('mpesa.pochi_number') }}</p>
<p class="text-xs text-ivory/50 mt-2 mb-1">Paybill &middot; Account</p>
<p class="text-sm font-semibold text-gold-200"><span class="font-mono select-all">{{ config('mpesa.paybill_number') }}</span> &middot; <span class="font-mono select-all">{{ config('mpesa.paybill_account') }}</span></p>
</div>
</div>
<div x-data="depositManager()" x-init="init()" class="py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold golden-title">Deposit Funds</h1>
            <p class="text-gold-400 mt-2">Add funds to your wallet via M-Pesa</p>
        </div>

        <div class="card-golden p-6">
            <form @submit.prevent="submitDeposit()" class="space-y-6">
                <!-- Phone Number -->
                <div>
                    <label class="block text-gold-400 text-sm font-medium mb-2">M-Pesa Phone Number</label>
                    <input type="tel" 
                           x-model="phone" 
                           placeholder="254712345678" 
                           class="input-golden w-full"
                           :class="{'border-red-500': phoneError}"
                           @input="validatePhone()">
                    <p x-show="phoneError" x-text="phoneError" class="text-red-400 text-xs mt-1"></p>
                    <p class="text-ivory/50 text-xs mt-1">Format: 254XXXXXXXXX (no spaces)</p>
                </div>

                <!-- Amount -->
                <div>
                    <label class="block text-gold-400 text-sm font-medium mb-2">Amount (KES)</label>
                    <input type="number" 
                           x-model="amount" 
                           step="10" 
                           min="10" 
                           max="150000"
                           class="input-golden w-full"
                           :class="{'border-red-500': amountError}"
                           @input="validateAmount()">
                    <p x-show="amountError" x-text="amountError" class="text-red-400 text-xs mt-1"></p>
                    <div class="flex gap-2 mt-2">
                        <button type="button" @click="amount = 1000" class="text-xs bg-gold/20 px-3 py-1 rounded-full text-gold-400 hover:bg-gold/30">KES 1,000</button>
                        <button type="button" @click="amount = 5000" class="text-xs bg-gold/20 px-3 py-1 rounded-full text-gold-400 hover:bg-gold/30">KES 5,000</button>
                        <button type="button" @click="amount = 10000" class="text-xs bg-gold/20 px-3 py-1 rounded-full text-gold-400 hover:bg-gold/30">KES 10,000</button>
                    </div>
                </div>

                <!-- Fee Preview -->
                <div class="bg-gold/5 rounded-lg p-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-ivory/60">Deposit Amount</span>
                        <span class="text-gold" x-text="'KES ' + formatNumber(amount)"></span>
                    </div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-ivory/60">M-Pesa Fee</span>
                        <span class="text-ivory/60" x-text="'KES ' + formatNumber(fee)"></span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t border-gold/30">
                        <span class="text-gold-400">Total to Pay</span>
                        <span class="text-gold" x-text="'KES ' + formatNumber(amount + fee)"></span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        :disabled="!isValid || isSubmitting"
                        class="btn-golden w-full disabled:opacity-50 disabled:cursor-not-allowed">
                    <i x-show="isSubmitting" class="fas fa-spinner fa-spin mr-2"></i>
                    <span x-text="isSubmitting ? 'Processing...' : 'Proceed to Payment'"></span>
                </button>
            </form>

            <!-- Info Box -->
            <div class="mt-6 p-4 bg-gold/5 rounded-lg">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-gold-400 mt-1"></i>
                    <div class="text-sm text-ivory/70">
                        <p>You will receive an STK push on your phone. Enter your M-Pesa PIN to complete the transaction.</p>
                        <p class="mt-2">Maximum deposit: KES 150,000 per transaction.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Modal -->
        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="relative bg-cosmic-deep border border-gold/30 rounded-2xl p-6 max-w-md w-full mx-4">
                <div class="text-center">
                    <i class="fas text-4xl mb-3" :class="modalType === 'success' ? 'fa-check-circle text-green-400' : 'fa-exclamation-circle text-red-400'"></i>
                    <h3 class="text-xl font-bold text-gold mb-2" x-text="modalTitle"></h3>
                    <p class="text-ivory/70 mb-4" x-text="modalMessage"></p>
                    <button @click="closeModal()" class="btn-golden w-full">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function depositManager() {
    return {
        phone: '',
        amount: 0,
        fee: 0,
        isSubmitting: false,
        phoneError: '',
        amountError: '',
        showModal: false,
        modalTitle: '',
        modalMessage: '',
        modalType: '',
        
        init() {
            this.validatePhone();
        },
        
        validatePhone() {
            const phoneRegex = /^254[0-9]{9}$/;
            if (!this.phone) {
                this.phoneError = '';
                return false;
            }
            if (!phoneRegex.test(this.phone)) {
                this.phoneError = 'Please enter a valid Kenyan phone number (e.g., 254712345678)';
                return false;
            }
            this.phoneError = '';
            return true;
        },
        
        validateAmount() {
            if (!this.amount || this.amount < 10) {
                this.amountError = 'Minimum deposit is KES 10';
                return false;
            }
            if (this.amount > 150000) {
                this.amountError = 'Maximum deposit is KES 150,000';
                return false;
            }
            this.amountError = '';
            this.fee = this.amount >= 100 ? Math.min(100, Math.ceil(this.amount * 0.01)) : 0;
            return true;
        },
        
        get isValid() {
            return this.validatePhone() && this.validateAmount();
        },
        
        formatNumber(num) {
            return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        
        async submitDeposit() {
            if (!this.isValid) return;
            
            this.isSubmitting = true;
            
            try {
                const response = await fetch('{{ route('mpesa.deposit.initiate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        phone: this.phone,
                        amount: this.amount
                    })
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.error || 'Deposit initiation failed');
                }
                
                this.modalType = 'success';
                this.modalTitle = 'STK Push Sent!';
                this.modalMessage = 'Please check your phone and enter your M-Pesa PIN to complete the deposit.';
                this.showModal = true;
                
            } catch (error) {
                this.modalType = 'error';
                this.modalTitle = 'Deposit Failed';
                this.modalMessage = error.message || 'Unable to initiate deposit. Please try again.';
                this.showModal = true;
            } finally {
                this.isSubmitting = false;
            }
        },
        
        closeModal() {
            this.showModal = false;
            if (this.modalType === 'success') {
                window.location.href = '{{ route('wallet') }}';
            }
        }
    }
}
</script>
@endsection
