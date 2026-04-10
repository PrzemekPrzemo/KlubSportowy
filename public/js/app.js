// KlubSportowy — front-end stub
// Global scripts placed here. Bootstrap 5 bundle loaded from CDN in layout.
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert.alert-dismissible').forEach(function(el) {
        setTimeout(function() {
            var closeBtn = el.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        }, 5000);
    });
});
