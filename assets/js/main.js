// Delete confirmation — attach to any delete button
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this?');
}

// Password visibility toggle
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}