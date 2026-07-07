{{-- H11 / Task 42: shared Account Settings. Two tabs (Personal Information / Security).
     Students: name read-only (email + media + password only). Email is switched only via the
     Google account picker (never typed). user_id / user_type / is_active are never editable. --}}
@php
    $role = $user->primaryRole();
    $navItems = [
        ['label' => 'Dashboard', 'url' => url('/'.$role.'/dashboard'), 'active' => false],
        ['label' => 'Account Settings', 'url' => route('profile.edit'), 'active' => true],
    ];
    $isStudent = $user->hasRole('student');
    $words    = array_filter(preg_split('/\s+/', trim(($user->given_name ?? '').' '.($user->surname ?? ''))));
    $initials = strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 2))));
    $avatarUrl = $user->profile_picture ? asset($user->profile_picture) : null;
    $coverUrl = $user->cover_photo ? asset($user->cover_photo) : null;
@endphp
@extends('layouts.app', ['role' => $role, 'navItems' => $navItems])

@section('title', 'Account Settings')
@section('heading', 'Account Settings')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/cropperjs/cropper.min.css') }}"/>
    {{-- Hover-only, centered edit overlays. Done in plain CSS because the precompiled Metronic
         bundle doesn't ship opacity-0 / group-hover:* utilities. --}}
    <style nonce="{{ $cspNonce ?? '' }}">
        .media-edit { position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
            gap:.5rem; background:rgba(0,0,0,.45); color:#fff; font-size:.875rem; opacity:0; transition:opacity .2s; }
        #cover_banner:hover .media-edit, #avatar_button:hover .media-edit { opacity:1; }
        /* Change/Remove menu — custom (KTUI's floating positioning breaks inside the -translate-y-1/2 row). */
        .media-menu { position:absolute; top:100%; inset-inline-start:0; z-index:50; margin-top:.375rem; min-width:11rem;
            background:var(--popover); color:var(--popover-foreground); border:1px solid var(--border);
            border-radius:.625rem; box-shadow:0 10px 30px rgba(0,0,0,.18); padding:.375rem; }
        .media-menu a { display:flex; align-items:center; gap:.5rem; padding:.5rem .625rem; border-radius:.5rem;
            font-size:.875rem; line-height:1.25rem; cursor:pointer; color:inherit; }
        .media-menu a:hover { background:var(--accent); color:var(--accent-foreground); }
        /* Let the identity block shrink and the long email wrap, so it can't force horizontal scroll. */
        .identity-col { min-width: 0; }
        .identity-email { overflow-wrap: anywhere; max-width: 100%; }
        /* Centered column on mobile; left row with the name bottom-aligned to the avatar on desktop. */
        .profile-media-row { flex-direction: column; align-items: center; text-align: center; }
        @media (min-width: 1024px) {
            .profile-media-row { flex-direction: row; align-items: flex-end; text-align: start; }
        }
    </style>
@endpush

@section('content')
    @include('admin._status')

    {{-- Tabs --}}
    <div class="kt-tabs kt-tabs-line mb-5" data-kt-tabs="true">
        <div class="flex items-center gap-5">
            <button class="kt-tab-toggle py-3 active" data-kt-tab-toggle="#tab_personal">Personal Information</button>
            <button class="kt-tab-toggle py-3" data-kt-tab-toggle="#tab_security">Security</button>
        </div>
    </div>

    {{-- ============================= Personal Information ============================= --}}
    <div id="tab_personal">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">@csrf @method('PUT')

            {{-- Profile Media card — author.html layout: cover as header + avatar row pulled up
                 with -translate-y-1/2 (own stacking context, so it paints above the cover). --}}
            <div class="kt-card mb-5">
                {{-- Cover: click opens a menu to change or remove (no crop). --}}
                <div class="relative">
                    <div id="cover_banner" role="button" tabindex="0"
                         class="group relative kt-card-header p-0 bg-no-repeat bg-cover bg-center kt-card-rounded-t h-40 bg-muted cursor-pointer"
                         @if ($coverUrl) style="background-image: url('{{ $coverUrl }}')" @endif>
                        <span id="cover_placeholder" class="absolute inset-0 flex items-center justify-center text-sm text-secondary-foreground @if ($coverUrl) hidden @endif">No cover photo selected.</span>
                        <div class="media-edit kt-card-rounded-t">
                            <i class="ki-filled ki-picture"></i> Edit cover photo
                        </div>
                    </div>
                    <div id="cover_menu" class="media-menu hidden">
                        <a href="#" id="cover_change"><i class="ki-filled ki-pencil"></i> Change photo</a>
                        <a href="#" id="cover_remove" class="@if (! $coverUrl) hidden @endif"><i class="ki-filled ki-trash"></i> Remove photo</a>
                    </div>
                </div>
                <div class="kt-card-content mb-7.5 p-0">
                    {{-- Jenny Klabber card (author.html): row pulled up with -translate-y-1/2.
                         .profile-media-row = centered column on mobile, left row (name bottom-aligned) on desktop. --}}
                    <div class="flex profile-media-row transform -translate-y-1/2 px-5 lg:px-7.5 gap-1.5">
                        {{-- Avatar (click to pick + crop). Inner wrapper is overflow-hidden for the
                             round image/initials/camera; the status dot is a sibling so it isn't clipped. --}}
                        <div class="shrink-0 relative">
                            <div id="avatar_button" role="button" tabindex="0" class="group relative cursor-pointer" style="width:120px;height:120px">
                                <div class="relative w-full h-full rounded-full overflow-hidden bg-primary/10">
                                    <img id="avatar_preview" alt="avatar" class="w-full h-full object-cover"
                                         src="{{ $avatarUrl ?? asset('assets/img/profile-placeholder.png') }}"/>
                                    <div class="media-edit">
                                        <i class="ki-filled ki-camera text-lg"></i>
                                    </div>
                                </div>
                                <div class="flex size-3 bg-green-500 rounded-full ring-2 ring-background absolute bottom-2" style="inset-inline-start:93px"></div>
                            </div>
                            <div id="avatar_menu" class="media-menu hidden">
                                <a href="#" id="avatar_change"><i class="ki-filled ki-pencil"></i> Change photo</a>
                                <a href="#" id="avatar_remove" class="@if (! $avatarUrl) hidden @endif"><i class="ki-filled ki-trash"></i> Remove photo</a>
                            </div>
                        </div>
                        <div class="flex flex-col justify-end grow identity-col w-full">
                            <div class="flex items-center justify-center lg:justify-between flex-wrap gap-2">
                                <div class="flex flex-col justify-end items-center lg:items-start gap-0.5 identity-col max-w-full">
                                    <div class="flex items-center gap-1.5 flex-wrap justify-center lg:justify-start">
                                        <span class="text-base leading-5 font-medium text-mono">{{ $user->name }}</span>
                                        <svg class="text-primary" fill="none" height="16" viewbox="0 0 15 16" width="15" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M14.5425 6.89749L13.5 5.83999C13.4273 5.76877 13.3699 5.6835 13.3312 5.58937C13.2925 5.49525 13.2734 5.39424 13.275 5.29249V3.79249C13.274 3.58699 13.2324 3.38371 13.1527 3.19432C13.0729 3.00494 12.9565 2.83318 12.8101 2.68892C12.6638 2.54466 12.4904 2.43073 12.2998 2.35369C12.1093 2.27665 11.9055 2.23801 11.7 2.23999H10.2C10.0982 2.24159 9.99722 2.22247 9.9031 2.18378C9.80898 2.1451 9.72371 2.08767 9.65249 2.01499L8.60249 0.957487C8.30998 0.665289 7.91344 0.50116 7.49999 0.50116C7.08654 0.50116 6.68999 0.665289 6.39749 0.957487L5.33999 1.99999C5.26876 2.07267 5.1835 2.1301 5.08937 2.16879C4.99525 2.20747 4.89424 2.22659 4.79249 2.22499H3.29249C3.08699 2.22597 2.88371 2.26754 2.69432 2.34731C2.50494 2.42709 2.33318 2.54349 2.18892 2.68985C2.04466 2.8362 1.93073 3.00961 1.85369 3.20013C1.77665 3.39064 1.73801 3.5945 1.73999 3.79999V5.29999C1.74159 5.40174 1.72247 5.50275 1.68378 5.59687C1.6451 5.691 1.58767 5.77627 1.51499 5.84749L0.457487 6.89749C0.165289 7.19 0.00115967 7.58654 0.00115967 7.99999C0.00115967 8.41344 0.165289 8.80998 0.457487 9.10249L1.49999 10.16C1.57267 10.2312 1.6301 10.3165 1.66878 10.4106C1.70747 10.5047 1.72659 10.6057 1.72499 10.7075V12.2075C1.72597 12.413 1.76754 12.6163 1.84731 12.8056C1.92709 12.995 2.04349 13.1668 2.18985 13.3111C2.3362 13.4553 2.50961 13.5692 2.70013 13.6463C2.89064 13.7233 3.0945 13.762 3.29999 13.76H4.79999C4.90174 13.7584 5.00275 13.7775 5.09687 13.8162C5.191 13.8549 5.27627 13.9123 5.34749 13.985L6.40499 15.0425C6.69749 15.3347 7.09404 15.4988 7.50749 15.4988C7.92094 15.4988 8.31748 15.3347 8.60999 15.0425L9.65999 14C9.73121 13.9273 9.81647 13.8699 9.9106 13.8312C10.0047 13.7925 10.1057 13.7734 10.2075 13.775H11.7075C12.1212 13.775 12.518 13.6106 12.8106 13.3181C13.1031 13.0255 13.2675 12.6287 13.2675 12.215V10.715C13.2659 10.6132 13.285 10.5122 13.3237 10.4181C13.3624 10.324 13.4198 10.2387 13.4925 10.1675L14.55 9.10999C14.6953 8.96452 14.8104 8.79176 14.8887 8.60164C14.9671 8.41152 15.007 8.20779 15.0063 8.00218C15.0056 7.79656 14.9643 7.59311 14.8847 7.40353C14.8051 7.21394 14.6888 7.04197 14.5425 6.89749ZM10.635 6.64999L6.95249 10.25C6.90055 10.3026 6.83864 10.3443 6.77038 10.3726C6.70212 10.4009 6.62889 10.4153 6.55499 10.415C6.48062 10.4139 6.40719 10.3982 6.33896 10.3685C6.27073 10.3389 6.20905 10.2961 6.15749 10.2425L4.37999 8.44249C4.32532 8.39044 4.28169 8.32793 4.25169 8.25867C4.22169 8.18941 4.20593 8.11482 4.20536 8.03934C4.20479 7.96387 4.21941 7.88905 4.24836 7.81934C4.27731 7.74964 4.31999 7.68647 4.37387 7.63361C4.42774 7.58074 4.4917 7.53926 4.56194 7.51163C4.63218 7.484 4.70726 7.47079 4.78271 7.47278C4.85816 7.47478 4.93244 7.49194 5.00112 7.52324C5.0698 7.55454 5.13148 7.59935 5.18249 7.65499L6.56249 9.05749L9.84749 5.84749C9.95296 5.74215 10.0959 5.68298 10.245 5.68298C10.394 5.68298 10.537 5.74215 10.6425 5.84749C10.6953 5.90034 10.737 5.96318 10.7653 6.03234C10.7935 6.1015 10.8077 6.1756 10.807 6.25031C10.8063 6.32502 10.7908 6.39884 10.7612 6.46746C10.7317 6.53608 10.6888 6.59813 10.635 6.64999Z" fill="currentColor"></path>
                                        </svg>
                                        <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $user->user_id }}</span>
                                    </div>
                                    <span class="text-secondary-foreground text-xs identity-email">{{ $user->email }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Detail fields tucked up into the space under the avatar row (author.html
                         uses the same -mt-8 to pull its content row up), so there's no empty void. --}}
                    <div class="-mt-8 px-5 lg:px-7.5 pb-5 grid gap-5">
                        <div class="grid md:grid-cols-2 gap-5">
                            <div class="flex flex-col gap-1">
                                <label class="kt-form-label">Given Name</label>
                                <input name="given_name" class="kt-input disabled:bg-muted" value="{{ old('given_name', $user->given_name) }}" @disabled($isStudent)>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="kt-form-label">Last Name</label>
                                <input name="surname" class="kt-input disabled:bg-muted" value="{{ old('surname', $user->surname) }}" @disabled($isStudent)>
                            </div>
                        </div>
                        @if ($isStudent)
                            <span class="text-xs text-secondary-foreground">Students can update email and media only.</span>
                        @endif

                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label">Email Address</label>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <input type="email" class="kt-input bg-muted grow" value="{{ $user->email }}" readonly>
                                <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-toggle="#change_email_modal">Change Email Address</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Hidden inputs (submit with Save Changes) --}}
                <input id="avatar_input" name="profile_picture" type="file" class="hidden" accept="image/png,image/jpeg,image/webp"/>
                <input id="cover_input" name="cover_photo" type="file" class="hidden" accept="image/png,image/jpeg,image/webp"/>
                <input id="remove_profile_picture" name="remove_profile_picture" type="hidden" value="0"/>
                <input id="remove_cover_photo" name="remove_cover_photo" type="hidden" value="0"/>
            </div>

            <div class="flex justify-end mb-5">
                <button class="kt-btn kt-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>

    {{-- ============================= Security ============================= --}}
    <div class="hidden" id="tab_security">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Password</h3>
            </div>
            <form method="POST" action="{{ route('profile.password') }}" class="kt-card-content p-5 grid gap-5">@csrf @method('PUT')
                <span class="text-sm text-secondary-foreground">Update your password. It must be at least 8 characters and include uppercase, lowercase, a number, and a special character.</span>

                <div class="grid md:grid-cols-2 gap-5">
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label">New Password</label>
                        <label class="kt-input" data-kt-toggle-password="true">
                            <input name="password" type="password" placeholder="Enter new password"/>
                            <div class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true">
                                <span class="kt-toggle-password-active:hidden"><i class="ki-filled ki-eye text-muted-foreground"></i></span>
                                <span class="hidden kt-toggle-password-active:block"><i class="ki-filled ki-eye-slash text-muted-foreground"></i></span>
                            </div>
                        </label>
                        @error('password')<span class="text-xs text-destructive">{{ $message }}</span>@enderror
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label">Confirm Password</label>
                        <label class="kt-input" data-kt-toggle-password="true">
                            <input name="password_confirmation" type="password" placeholder="Re-enter password"/>
                            <div class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true">
                                <span class="kt-toggle-password-active:hidden"><i class="ki-filled ki-eye text-muted-foreground"></i></span>
                                <span class="hidden kt-toggle-password-active:block"><i class="ki-filled ki-eye-slash text-muted-foreground"></i></span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button class="kt-btn kt-btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================= Modals ============================= --}}
    {{-- Crop profile picture --}}
    <button id="crop_modal_trigger" class="hidden" data-kt-modal-toggle="#crop_avatar_modal"></button>
    <div class="kt-modal" data-kt-modal="true" id="crop_avatar_modal">
        <div class="kt-modal-content max-w-[500px] top-[10%]">
            <div class="kt-modal-header">
                <h3 class="kt-modal-title">Crop Profile Picture</h3>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true" id="crop_cancel_x">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="kt-modal-body p-5">
                <div class="max-h-[360px]">
                    <img id="crop_image" alt="Crop" class="block max-w-full" src=""/>
                </div>
                <div class="flex items-center gap-3 mt-4">
                    <span class="text-sm text-secondary-foreground">Zoom</span>
                    <input id="crop_zoom" type="range" min="1" max="3" step="0.01" value="1" class="grow"/>
                </div>
            </div>
            <div class="flex items-center gap-2.5 justify-end p-5 pt-0">
                <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true" id="crop_cancel">Cancel</button>
                <button type="button" class="kt-btn kt-btn-primary" data-kt-modal-dismiss="true" id="crop_apply">Apply</button>
            </div>
        </div>
    </div>

    {{-- Change email via Google --}}
    <div class="kt-modal" data-kt-modal="true" id="change_email_modal">
        {{-- Width via inline style: the static Metronic bundle ships no arbitrary max-w-[…] utility,
             so max-w-[460px] compiled to nothing and the modal rendered full width. Matches the
             standard modal pattern (see components/modal.blade.php, admin/users). (Task 44) --}}
        <div class="kt-modal-content top-[15%]" style="width: 100%; max-width: min(92vw, 460px);">
            <div class="kt-modal-header">
                <h3 class="kt-modal-title">Change email address</h3>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="kt-modal-body p-5">
                <p class="text-sm text-secondary-foreground">You'll pick a Google account on the next screen. Your sign-in email will be changed to that account's email, and your current email will be replaced.</p>
            </div>
            <div class="flex items-center gap-2.5 justify-end p-5 pt-0">
                <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Cancel</button>
                <form method="POST" action="{{ route('profile.email.google') }}">@csrf
                    <button type="submit" class="kt-btn kt-btn-primary">Continue</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('vendor/cropperjs/cropper.min.js') }}"></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        (function () {
            const avatarInput = document.getElementById('avatar_input');
            const coverInput = document.getElementById('cover_input');
            const avatarBtn = document.getElementById('avatar_button');
            const coverBanner = document.getElementById('cover_banner');
            const cropImage = document.getElementById('crop_image');
            const cropZoom = document.getElementById('crop_zoom');
            const cropTrigger = document.getElementById('crop_modal_trigger');
            let cropper = null, baseRatio = 1;

            function toastErr(msg) {
                if (window.KTToast) KTToast.show({ message: msg, variant: 'destructive', appearance: 'outline', dismiss: true });
                else alert(msg);
            }
            function validImage(file) {
                if (!file.type.startsWith('image/')) { toastErr('Please choose an image file.'); return false; }
                if (file.size > 2 * 1024 * 1024) { toastErr('Image size must be 2 MB or less'); return false; }
                return true;
            }

            const removeAvatarFlag = document.getElementById('remove_profile_picture');
            const removeCoverFlag = document.getElementById('remove_cover_photo');
            const avatarRemoveItem = document.getElementById('avatar_remove');
            const coverRemoveItem = document.getElementById('cover_remove');
            const avatarMenu = document.getElementById('avatar_menu');
            const coverMenu = document.getElementById('cover_menu');

            function closeMenus() { avatarMenu.classList.add('hidden'); coverMenu.classList.add('hidden'); }
            // Reparent to <body> + fixed positioning: escapes the transformed row's stacking context
            // so the menu always renders in front of the form fields below.
            function openMenu(menu, trigger) {
                const r = trigger.getBoundingClientRect();
                document.body.appendChild(menu);
                menu.style.position = 'fixed';
                menu.style.zIndex = '1000';
                menu.style.top = (r.bottom + 4) + 'px';
                menu.style.insetInlineStart = r.left + 'px';
                menu.classList.remove('hidden');
            }
            function toggleMenu(menu, trigger) { const willOpen = menu.classList.contains('hidden'); closeMenus(); if (willOpen) openMenu(menu, trigger); }
            avatarBtn.addEventListener('click', e => { e.stopPropagation(); toggleMenu(avatarMenu, avatarBtn); });
            coverBanner.addEventListener('click', e => { e.stopPropagation(); toggleMenu(coverMenu, coverBanner); });
            document.addEventListener('click', closeMenus);

            function on(id, fn) { const el = document.getElementById(id); if (el) el.addEventListener('click', e => { e.preventDefault(); closeMenus(); fn(); }); }

            // Change photo -> open the (hidden) file input.
            on('avatar_change', () => avatarInput.click());
            on('cover_change', () => coverInput.click());

            // Remove photo -> reset the preview + flag the server to clear it on save.
            on('avatar_remove', () => {
                avatarInput.value = '';
                removeAvatarFlag.value = '1';
                const prev = document.getElementById('avatar_preview');
                prev.src = '{{ asset('assets/img/profile-placeholder.png') }}';
                avatarRemoveItem.classList.add('hidden');
            });
            on('cover_remove', () => {
                coverInput.value = '';
                removeCoverFlag.value = '1';
                coverBanner.style.backgroundImage = '';
                document.getElementById('cover_placeholder').classList.remove('hidden');
                coverRemoveItem.classList.add('hidden');
            });

            // Avatar: validate -> crop modal.
            avatarInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;
                if (!validImage(file)) { this.value = ''; return; }
                const reader = new FileReader();
                reader.onload = e => {
                    cropImage.src = e.target.result;
                    cropTrigger.click();
                    if (cropper) cropper.destroy();
                    cropZoom.value = 1;
                    cropper = new Cropper(cropImage, {
                        aspectRatio: 1, viewMode: 1, autoCropArea: 1, dragMode: 'move',
                        cropBoxMovable: false, cropBoxResizable: false, guides: false,
                        ready() { const d = cropper.getImageData(); baseRatio = d.width / d.naturalWidth; }
                    });
                };
                reader.readAsDataURL(file);
            });

            cropZoom.addEventListener('input', function () {
                if (cropper) cropper.zoomTo(baseRatio * parseFloat(this.value));
            });

            // Apply: cropped canvas -> back into the avatar file input (server side unchanged).
            document.getElementById('crop_apply').addEventListener('click', function () {
                if (!cropper) return;
                const canvas = cropper.getCroppedCanvas({ width: 320, height: 320 });
                canvas.toBlob(blob => {
                    const dt = new DataTransfer();
                    dt.items.add(new File([blob], 'avatar.png', { type: 'image/png' }));
                    avatarInput.files = dt.files;
                    const prev = document.getElementById('avatar_preview');
                    prev.src = canvas.toDataURL('image/png');
                    removeAvatarFlag.value = '0';
                    avatarRemoveItem.classList.remove('hidden');
                    cropper.destroy(); cropper = null;
                }, 'image/png');
            });

            // Cancel: drop the selection so nothing uncropped is submitted.
            function cancelCrop() { if (cropper) { cropper.destroy(); cropper = null; } avatarInput.value = ''; }
            document.getElementById('crop_cancel').addEventListener('click', cancelCrop);
            document.getElementById('crop_cancel_x').addEventListener('click', cancelCrop);

            // Cover: validate -> preview immediately (no crop).
            coverInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;
                if (!validImage(file)) { this.value = ''; return; }
                const reader = new FileReader();
                reader.onload = e => {
                    coverBanner.style.backgroundImage = "url('" + e.target.result + "')";
                    const ph = document.getElementById('cover_placeholder');
                    if (ph) ph.classList.add('hidden');
                    removeCoverFlag.value = '0';
                    coverRemoveItem.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            });
        })();
    </script>
@endpush
