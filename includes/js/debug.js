console.log('debug loaded');

// Store logs in an array
const logMessages = [];

// Override console.log to capture messages
const originalConsoleLog = console.log;
console.log = function(...args) {
    originalConsoleLog.apply(console, args);
    logMessages.push(args.join(' ')); // Store the message
};

const originalConsoleErrorLog = console.error;
console.error = function(...args) {
    originalConsoleErrorLog.apply(console, args);
    logMessages.push(args.join(' ')); // Store the message
};

// Function to export logs
function exportLogs() {
    const logContent    = logMessages.join('\n');
    // Download as a file
    const blob          = new Blob([logContent], { type: 'text/plain' });
    const url           = URL.createObjectURL(blob);
    const a             = document.createElement('a');
    a.href              = url;
    a.download          = 'error_logs.txt';

    document.body.appendChild(a);

    a.click();

    document.body.removeChild(a);

    URL.revokeObjectURL(url);
}

// Attach exportLogs to a button click
document.getElementById('exportLogsButton').addEventListener('click', exportLogs);