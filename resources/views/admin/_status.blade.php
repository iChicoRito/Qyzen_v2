{{-- Shared flash + validation-error block for admin pages. --}}
@if (session('status'))
    <div class="kt-alert kt-alert-success mb-5">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="kt-alert kt-alert-destructive mb-5">
        <ul class="list-disc ps-4 mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
