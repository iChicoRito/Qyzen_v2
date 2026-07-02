{{-- F2: users list. Exact layout from demo1 network/user-table/team-crew.html
     (KTDataTable: search + status/sort selects + 3-dots row menu). Columns mapped to real data. --}}
@extends('admin.layout')

@section('title', 'Users')
@section('heading', 'Users')

@section('toolbar')
    <a href="{{ route('admin.users.import.template') }}" class="kt-btn kt-btn-sm kt-btn-outline">Download template</a>
    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-kt-modal-toggle="#kt_import_modal">Import students</button>
    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary" data-kt-modal-toggle="#kt_user_add_modal">Add user</button>
@endsection

@section('content')
    @include('admin._status')

    <x-data-table id="users_table" search-placeholder="Search users">
        <x-slot:filters>
            <select data-filter="status" class="kt-select w-36">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <select data-filter="type" class="kt-select w-36">
                <option value="">All types</option>
                @foreach (['admin','educator','student'] as $t)
                    <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </x-slot:filters>
        <x-slot:head>
            <thead>
                <tr>
                    <th class="w-[60px] text-center">
                        <input class="kt-checkbox kt-checkbox-sm" data-kt-datatable-check="true" type="checkbox" />
                    </th>
                    <th class="min-w-[300px]"><span class="kt-table-col"><span class="kt-table-col-label">Member</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Role</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">User ID</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Verified</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($users as $u)
            @php $initial = strtoupper(mb_substr($u->given_name ?: $u->name, 0, 1)); @endphp
            <tr>
                <td class="text-center">
                    <input class="kt-checkbox kt-checkbox-sm" data-kt-datatable-row-check="true" type="checkbox" value="{{ $u->id }}" />
                </td>
                <td>
                    <div class="flex items-center gap-2.5">
                        @if ($u->profile_picture)
                            <img alt="{{ $u->name }}" class="rounded-full size-9 shrink-0" src="{{ asset('storage/'.$u->profile_picture) }}" />
                        @else
                            <span class="inline-flex items-center justify-center rounded-full size-9 shrink-0 bg-primary/10 text-primary text-sm font-semibold">{{ $initial }}</span>
                        @endif
                        <div class="flex flex-col">
                            <a class="text-sm font-medium text-mono hover:text-primary mb-px" href="{{ route('admin.users.show', $u) }}">{{ $u->name }}</a>
                            <a class="text-sm text-secondary-foreground font-normal hover:text-primary" href="mailto:{{ $u->email }}">{{ $u->email }}</a>
                        </div>
                    </div>
                </td>
                <td class="text-foreground font-normal">{{ $u->roles->pluck('name')->join(', ') ?: ucfirst($u->user_type) }}</td>
                <td>
                    <span data-filter-value="status" data-filter-key="{{ $u->is_active ? 'active' : 'inactive' }}" hidden></span>
                    <span data-filter-value="type" data-filter-key="{{ $u->user_type }}" hidden></span>
                    <span class="kt-badge kt-badge-{{ $u->is_active ? 'success' : 'destructive' }} kt-badge-outline rounded-[30px]">
                        <span class="kt-badge-dot size-1.5"></span>{{ $u->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-foreground font-normal">{{ $u->user_id }}</td>
                <td>
                    @if ($u->email_verified_at)
                        <span class="kt-badge kt-badge-success kt-badge-outline rounded-[30px]"><span class="kt-badge-dot size-1.5"></span>Verified</span>
                    @else
                        <span class="kt-badge kt-badge-warning kt-badge-outline rounded-[30px]"><span class="kt-badge-dot size-1.5"></span>Pending</span>
                    @endif
                </td>
                <td class="text-center">
                    <x-table-actions
                        :view="route('admin.users.show', $u)"
                        edit="#"
                        edit-class="js-user-edit"
                        :edit-attributes="'data-action=\''.route('admin.users.update', $u).'\''
                            .' data-user_type=\''.$u->user_type.'\''
                            .' data-user_id=\''.e($u->user_id).'\''
                            .' data-given_name=\''.e($u->given_name).'\''
                            .' data-surname=\''.e($u->surname).'\''
                            .' data-email=\''.e($u->email).'\''
                            .' data-is_active=\''.(int) $u->is_active.'\''
                            .' data-roles=\''.e($u->roles->pluck('name')->join(',')).'\''
                            .' data-name=\''.e($u->name).'\''"
                        :delete="route('admin.users.destroy', $u)"
                        confirm="Delete this user? This cannot be undone.">
                        @if ($u->email_verified_at === null)
                            <div class="kt-menu-item">
                                <a class="kt-menu-link" href="#" data-confirm="Resend the verification email to this user?" data-confirm-title="Resend verification" data-confirm-button="Resend">
                                    <span class="kt-menu-icon"><i class="ki-filled ki-sms"></i></span>
                                    <span class="kt-menu-title">Resend verification</span>
                                </a>
                                <form method="POST" action="{{ route('admin.users.resend-verification', $u) }}" class="hidden">@csrf</form>
                            </div>
                        @endif
                    </x-table-actions>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-secondary-foreground py-5">No users.</td></tr>
        @endforelse
    </x-data-table>

    {{-- F3: import modal --}}
    <div class="kt-modal" data-kt-modal="true" id="kt_import_modal">
        <div class="kt-modal-content top-[15%]" style="width: 100%; max-width: min(92vw, 500px);">
            <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="kt-modal-header">
                    <h3 class="kt-modal-title">Import students (xlsx)</h3>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>
                <div class="kt-modal-body flex flex-col gap-3">
                    <p class="text-sm text-secondary-foreground">Columns: user_id, given_name, surname, email, status.</p>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" class="kt-input" required>
                </div>
                <div class="kt-modal-footer justify-end">
                    <button type="submit" class="kt-btn kt-btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add user modal (empty form → store) --}}
    <div class="kt-modal kt-modal-center" data-kt-modal="true" id="kt_user_add_modal">
        <div class="kt-modal-content" style="width: 100%; max-width: min(92vw, 440px);">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="kt-modal-header">
                    <h3 class="kt-modal-title">Add user</h3>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>
                <div class="kt-modal-body">
                    @include('admin.users._fields', ['user' => null])
                </div>
                <div class="kt-modal-footer justify-end gap-2">
                    <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Cancel</button>
                    <button type="submit" class="kt-btn kt-btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit user modal (shared; JS fills fields + action from the clicked row's data-* attributes) --}}
    <div class="kt-modal kt-modal-center" data-kt-modal="true" id="kt_user_edit_modal">
        <div class="kt-modal-content" style="width: 100%; max-width: min(92vw, 440px);">
            <form method="POST" id="kt_user_edit_form">
                @csrf @method('PUT')
                <div class="kt-modal-header">
                    <h3 class="kt-modal-title">Edit <span id="edit_user_name">user</span></h3>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>
                <div class="kt-modal-body">
                    <div class="grid grid-cols-2 gap-5">
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label">User Type</label>
                            <select name="user_type" id="edit_user_type" class="kt-select">
                                @foreach (['student','educator','admin'] as $t)
                                    <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label">User ID</label>
                            <input name="user_id" id="edit_user_id" class="kt-input" placeholder="YYYY-NNNNN">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label">Given Name</label>
                            <input name="given_name" id="edit_given_name" class="kt-input">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label">Surname</label>
                            <input name="surname" id="edit_surname" class="kt-input">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label">Email</label>
                            <input name="email" id="edit_email" type="email" class="kt-input">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label">Status</label>
                            <select name="is_active" id="edit_is_active" class="kt-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5 col-span-2">
                            <label class="kt-form-label">Roles</label>
                            @php $roleBlurbs = ['admin' => 'Full access — manage users, roles, and settings.', 'educator' => 'Create assessments, quizzes, and grade students.', 'student' => 'Enroll in sections and take assigned quizzes.']; @endphp
                            <div class="grid grid-cols-1 gap-2">
                                @foreach ($roles as $role)
                                    <label class="flex items-start gap-2 border border-border rounded-lg p-3 cursor-pointer">
                                        <input class="kt-checkbox kt-checkbox-sm js-edit-role mt-1" type="checkbox" name="role_names[]" value="{{ $role->name }}">
                                        <span class="flex flex-col gap-1">
                                            <span class="text-sm font-medium text-mono">{{ ucfirst($role->name) }}</span>
                                            <span class="text-xs text-secondary-foreground">{{ $roleBlurbs[strtolower($role->name)] ?? ($role->description ?: 'Custom role.') }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="kt-modal-footer justify-end gap-2">
                    <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Cancel</button>
                    <button type="submit" class="kt-btn kt-btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- The datatable redraw → KTMenu.init() re-init now lives in <x-data-table>. This block
         only wires the user-specific edit modal. --}}
    @push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function () {
            // Edit: fill the shared modal from the clicked row's data-* attributes, then open it.
            // Delegated on document so it survives datatable re-renders.
            var editForm = document.querySelector('#kt_user_edit_form');
            var editModal = document.querySelector('#kt_user_edit_modal');
            document.addEventListener('click', function (e) {
                var link = e.target.closest('.js-user-edit');
                if (!link || !editForm || !editModal) return;
                e.preventDefault();

                editForm.setAttribute('action', link.dataset.action);
                document.querySelector('#edit_user_name').textContent = link.dataset.name || 'user';
                document.querySelector('#edit_user_type').value = link.dataset.user_type || 'student';
                document.querySelector('#edit_user_id').value = link.dataset.user_id || '';
                document.querySelector('#edit_given_name').value = link.dataset.given_name || '';
                document.querySelector('#edit_surname').value = link.dataset.surname || '';
                document.querySelector('#edit_email').value = link.dataset.email || '';
                document.querySelector('#edit_is_active').value = link.dataset.is_active || '1';

                var roles = (link.dataset.roles || '').split(',').filter(Boolean);
                editForm.querySelectorAll('.js-edit-role').forEach(function (cb) {
                    cb.checked = roles.indexOf(cb.value) !== -1;
                });

                if (typeof KTModal !== 'undefined') {
                    KTModal.getOrCreateInstance(editModal).show();
                }
            });
        });
    </script>
    @endpush
@endsection
