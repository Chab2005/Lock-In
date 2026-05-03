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
    // ESC closes all app modals
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') ModalSystem.closeAll();
    });

    // Click on the modal backdrop (the .app-modal element itself) closes it
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('app-modal')) {
            const id = e.target.id.replace('modal-', '');
            ModalSystem.close(id);
        }
    });

    // Close button delegation
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-close-modal]');
        if (btn) {
            ModalSystem.close(btn.dataset.closeModal);
        }
    });
});
