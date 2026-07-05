// Messaging currently runs on optimized polling (BROADCAST_CONNECTION=log), so Echo is intentionally
// NOT imported here — importing it would open a WebSocket to a broadcaster that isn't running.
// To re-enable real-time (Reverb on a VPS, or Pusher on shared hosting): restore `import './echo';`
// below and flip BROADCAST_CONNECTION. See docs/REALTIME_MESSAGING_TRANSPORTS.md.
//
// import './echo';
