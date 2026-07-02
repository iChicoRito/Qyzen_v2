{{-- Bare layout for AJAX modal fragments: renders only the form card (@section('content')),
     no sidebar/header. Create/edit views select this via a ternary @extends when ?modal=1.
     The fragment's own kt-card is flattened by the modal shell's scoped style (components/modal),
     so it doesn't double the modal border/padding. --}}
@yield('content')
