'use strict';

document.addEventListener('DOMContentLoaded', function() {
    // Handle close button
    document.querySelectorAll('.remove-button').forEach(button => {
        button.addEventListener('click', function() {
            const toastId = this.getAttribute('data-toast-id');
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.remove();
            }

            // Clean URL
            const url = new URL(window.location.href);
            url.searchParams.delete('msg');
            url.searchParams.delete('r');
            window.history.replaceState({}, document.title, url.toString());
        });
    });

    // Auto-remove toast after 5 seconds
    setTimeout(() => {
        const toasts = document.querySelectorAll('.toast');
        toasts.forEach(toast => toast.remove());

        // Clean URL
        const url = new URL(window.location.href);
        url.searchParams.delete('msg');
        url.searchParams.delete('r');
        window.history.replaceState({}, document.title, url.toString());
    }, 5000);
});
