<!-- Bootstrap 5 JS (needed for navbar toggle, modals, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Your JS files -->
    <script src="/internship_tracker/assets/js/validation.js"></script>
    <script src="/internship_tracker/assets/js/main.js"></script>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<!-- Admin Profile Modal -->
<div class="modal fade" id="adminProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card" id="adminProfileModalContent">
            <!-- Content loaded via AJAX -->
            <div class="modal-body text-center text-white py-5">
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('adminProfileModal');
    if (!modalEl) return;
    const modalContent = document.getElementById('adminProfileModalContent');
    const bsModal = new bootstrap.Modal(modalEl);

    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('.view-user-trigger');
        if (!trigger) return;
        
        e.preventDefault();
        const userId = trigger.getAttribute('data-user-id');
        if (!userId) return;

        modalContent.innerHTML = `
            <div class="modal-body text-center text-white py-5">
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>`;
        
        bsModal.show();

        fetch(`/internship_tracker/admin/get_user_details.php?id=${userId}`)
            .then(response => response.text())
            .then(html => {
                modalContent.innerHTML = html;
            })
            .catch(err => {
                modalContent.innerHTML = `
                    <div class="modal-header border-0">
                        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-danger text-center py-4">
                        Error loading profile details. Please try again.
                    </div>`;
            });
    });
});
</script>
<?php endif; ?>

</body>
</html>