<?php
// includes/footer.php
?>
</main>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Global Tooltip Initializer -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"], [title]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            // Only init if title is present and bootstrap is loaded
            if (window.bootstrap && tooltipTriggerEl.getAttribute('title')) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    trigger: 'hover',
                    boundary: 'window'
                });
            }
        });
    });
</script>
</body>
</html>