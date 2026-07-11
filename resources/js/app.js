// Messaging currently runs on optimized polling (BROADCAST_CONNECTION=log), so Echo is intentionally
// NOT imported here — importing it would open a WebSocket to a broadcaster that isn't running.
// To re-enable real-time (Reverb on a VPS, or Pusher on shared hosting): restore `import './echo';`
// below and flip BROADCAST_CONNECTION. See docs/architecture/REALTIME_MESSAGING_TRANSPORTS.md.
//
// import './echo';

import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const toolbar = [
    [{ header: [1, 2, 3, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['blockquote', 'code-block', 'link'],
];

window.initAnnouncementEditors = function initAnnouncementEditors(root = document) {
    root.querySelectorAll('[data-quill-editor]:not([data-quill-ready])').forEach((editor) => {
        const form = editor.closest('[data-announcement-form]');
        const value = form && form.querySelector('[data-quill-value]');
        const quill = new Quill(editor, { modules: { toolbar }, theme: 'snow' });
        const sync = () => {
            if (value) value.value = quill.root.innerHTML;
        };

        editor.dataset.quillReady = 'true';
        quill.on('text-change', sync);
        if (form) form.addEventListener('submit', sync);
        sync();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.initAnnouncementEditors());
} else {
    window.initAnnouncementEditors();
}
