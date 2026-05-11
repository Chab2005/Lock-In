const ModalSystem = {
    open(id) {
        const el = document.getElementById(`modal-${id}`);
        if (!el) return;
        el.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    },
    close(id) {
        const el = document.getElementById(`modal-${id}`);
        if (!el) return;
        el.style.display = 'none';
        // Only restore scroll if no other app-modal is open
        if (!document.querySelector('.app-modal[style*="flex"]')) {
            document.body.style.overflow = '';
        }
    },
    closeAll() {
        document.querySelectorAll('.app-modal').forEach(el => {
            el.style.display = 'none';
        });
        document.body.style.overflow = '';
    },
};

window.ModalSystem = ModalSystem;

document.addEventListener('DOMContentLoaded', () => {
    // Close button delegation
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-close-modal]');
        if (btn) {
            ModalSystem.close(btn.dataset.closeModal);
        }
    });
});
