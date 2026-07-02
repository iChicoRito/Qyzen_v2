{{-- Reusable modal shell (from demo1 markup). The body is filled by _modal-loader via fetch,
     or with static @slot content. Trigger with a button carrying
     data-modal-url + data-modal-title + data-modal-target="#{id}".
     `width` is a CSS max-width (px/rem) applied inline — the static Metronic bundle doesn't ship
     arbitrary max-w-[…] utilities, so we set it via style (CSP allows inline styles). --}}
@props([
    'id',
    'title' => '',
    'width' => '560px',
])
{{-- Flatten a fragment's own kt-card so it doesn't double the modal's border/padding. Scoped to
     this modal's body; the standalone full-page form keeps its card. --}}
<style nonce="{{ $cspNonce ?? '' }}">
    #{{ $id }} [data-modal-body] .kt-card { border: 0; box-shadow: none; background: transparent; }
    #{{ $id }} [data-modal-body] .kt-card-content { padding: 0; }
    {{-- Cap the modal height and scroll the BODY (not the page) when content is tall.
         kt-modal-content is already display:flex/column; header stays fixed, body flexes + scrolls. --}}
    #{{ $id }} .kt-modal-content { max-height: 85vh; }
    #{{ $id }} .kt-modal-header { flex-shrink: 0; }
    {{-- No bottom padding: a sticky footer inside the body sits flush against the bottom edge,
         so scrolled content can't peek beneath it. --}}
    #{{ $id }} [data-modal-body] { flex: 1 1 auto; padding-bottom: 0; }
</style>
<div class="kt-modal kt-modal-center" data-kt-modal="true" id="{{ $id }}">
    <div class="kt-modal-content top-[8%]" style="width: 100%; max-width: min(92vw, {{ $width }});">
        <div class="kt-modal-header">
            <h3 class="kt-modal-title" data-modal-title>{{ $title }}</h3>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="kt-modal-body kt-scrollable-y" data-modal-body>
            {{ $slot }}
        </div>
    </div>
</div>
