@extends('layouts.auth')

@section('title', 'Verify your account')

@section('card')
    <form method="POST" class="kt-card-content flex flex-col gap-5 p-10" autocomplete="one-time-code">
        @csrf

        <div class="text-center mb-2.5">
            <div class="mx-auto mb-4 inline-flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                <i class="ki-filled ki-shield-tick text-xl"></i>
            </div>
            <h1 class="text-lg font-medium text-mono leading-none mb-2.5">Verify your account</h1>
            <p class="text-sm text-secondary-foreground">
                Enter the six-digit verification code sent to your email address:
                <span class="text-primary">{{ $email }}</span>
            </p>
        </div>

        <input type="hidden" name="code" value="{{ old('code') }}" data-verification-code />

        <div class="flex justify-center gap-2" role="group" aria-label="Six-digit verification code">
            @foreach (range(1, 6) as $digit)
                <input
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="1"
                    class="kt-input size-11 p-0 text-center text-lg"
                    aria-label="Verification digit {{ $digit }} of 6"
                    data-verification-digit
                    @if ($digit === 1) autofocus @endif
                />
            @endforeach
        </div>
        @error('code')<span class="text-center text-xs text-destructive">{{ $message }}</span>@enderror

        <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">Verify My Account</button>
    </form>
@endsection

@push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        const verificationForm = document.querySelector('[data-verification-code]')?.form;
        const verificationDigits = [...document.querySelectorAll('[data-verification-digit]')];
        const verificationCode = document.querySelector('[data-verification-code]');

        const syncVerificationCode = () => {
            verificationCode.value = verificationDigits.map((input) => input.value).join('');
        };

        verificationDigits.forEach((input, index) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(-1);
                syncVerificationCode();
                if (input.value && verificationDigits[index + 1]) verificationDigits[index + 1].focus();
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && !input.value && verificationDigits[index - 1]) {
                    verificationDigits[index - 1].focus();
                }
            });

            input.addEventListener('paste', (event) => {
                event.preventDefault();
                const digits = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6).split('');
                digits.forEach((digit, digitIndex) => {
                    if (verificationDigits[digitIndex]) verificationDigits[digitIndex].value = digit;
                });
                syncVerificationCode();
                verificationDigits[Math.min(digits.length, 5)]?.focus();
            });
        });

        verificationForm?.addEventListener('submit', syncVerificationCode);
    </script>
@endpush
