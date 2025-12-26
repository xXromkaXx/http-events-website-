document.addEventListener('DOMContentLoaded', () => {
    // ===== Редагування полів профілю =====
    document.querySelectorAll('.profile-row').forEach(row => {
        const editBtn = row.querySelector('.edit-btn');
        const cancelBtn = row.querySelector('.cancel-btn');
        const input = row.querySelector('.edit-input');
        const value = row.querySelector('.value');

        if (!editBtn) return;

        editBtn.addEventListener('click', () => {
            row.classList.add('editing');
            input.value = value.dataset.raw || value.textContent.trim();
            input.focus();
        });

        cancelBtn?.addEventListener('click', () => {
            row.classList.remove('editing');
            input.value = value.dataset.raw || '';
        });
    });

    // ===== AJAX зміна Email =====
    const emailForm = document.getElementById('emailForm');
    const codeForm  = document.getElementById('codeForm');
    const emailMsg  = document.getElementById('emailMsg');
    const codeMsg   = document.getElementById('codeMsg');

    if (!emailForm || !codeForm) return;

    /* === STEP 1: НАДСИЛАННЯ КОДУ === */
    emailForm.addEventListener('submit', async e => {
        e.preventDefault();

        emailMsg.textContent = 'Відправляємо код…';
        emailMsg.style.color = '#aaa';

        const fd = new FormData(emailForm);
        fd.append('ajax', 1);

        try {
            const res = await fetch('', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.error) {
                emailMsg.textContent = data.error;
                emailMsg.style.color = '#ff6b6b';
                return;
            }

            emailMsg.textContent = data.success;
            emailMsg.style.color = '#2ecc71';

            /* step transition */
            emailForm.classList.add('step-hidden');
            setTimeout(() => {
                emailForm.style.display = 'none';
                codeForm.style.display = 'block';
                codeForm.classList.remove('step-hidden');
                codeForm.querySelector('input[name="code"]').focus();
            }, 300);

        } catch {
            emailMsg.textContent = 'Помилка зʼєднання';
            emailMsg.style.color = '#ff6b6b';
        }
    });

    /* === STEP 2: ПІДТВЕРДЖЕННЯ КОДУ === */
    codeForm.addEventListener('submit', async e => {
        e.preventDefault();

        codeMsg.textContent = 'Перевіряємо код…';
        codeMsg.style.color = '#aaa';

        const fd = new FormData(codeForm);
        fd.append('ajax', 1);
        fd.append('action', 'confirm_email');

        try {
            const res = await fetch('', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.error) {
                codeMsg.textContent = data.error;
                codeMsg.style.color = '#ff6b6b';
                return;
            }

            codeMsg.textContent = data.success;
            codeMsg.style.color = '#2ecc71';

            setTimeout(() => location.reload(), 1200);

        } catch {
            codeMsg.textContent = 'Помилка зʼєднання';
            codeMsg.style.color = '#ff6b6b';
        }
    });

});
