document.addEventListener('DOMContentLoaded', () => {
    // ===== Редагування полів профілю =====
    document.querySelectorAll('.profile-row').forEach(row => {
        const editBtn = row.querySelector('.edit-btn');
        const cancelBtn = row.querySelector('.cancel-btn');
        const input = row.querySelector('.edit-input');
        const value = row.querySelector('.value');

        if (!editBtn) return;

        editBtn.onclick = () => {
            row.classList.add('editing');
            input.focus();
        };

        cancelBtn.onclick = () => {
            row.classList.remove('editing');
            input.value = value.dataset.raw || '';
        };
    });

    // ===== AJAX зміна Email =====
    const emailForm = document.getElementById('emailForm');
    const codeForm = document.getElementById('codeForm');

    if (emailForm && codeForm) {
        emailForm.addEventListener('submit', async e => {
            e.preventDefault();

            const fd = new FormData(emailForm);
            fd.append('ajax', 1);

            const res = await fetch('', {
                method: 'POST',
                body: fd
            });

            const data = await res.json();
            const msg = document.getElementById('emailMsg');

            if (data.error) {
                msg.textContent = data.error;
                msg.style.color = 'red';
                return;
            }

            msg.textContent = data.success;
            msg.style.color = 'green';

            emailForm.style.display = 'none';
            codeForm.style.display = 'block';
            codeForm.querySelector('input[name="code"]').focus();
        });

        codeForm.addEventListener('submit', async e => {
            e.preventDefault();

            const fd = new FormData(codeForm);
            fd.append('ajax', 1);
            fd.append('action', 'confirm_email');

            const res = await fetch('', {
                method: 'POST',
                body: fd
            });

            const data = await res.json();
            const msg = document.getElementById('codeMsg');

            if (data.error) {
                msg.textContent = data.error;
                msg.style.color = 'red';
                return;
            }

            msg.textContent = data.success;
            msg.style.color = 'green';

            setTimeout(() => location.reload(), 1200);
        });
    }
});
