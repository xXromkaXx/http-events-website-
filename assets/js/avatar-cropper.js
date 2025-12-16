document.addEventListener('DOMContentLoaded', () => {

    const input   = document.getElementById('avatarInput');
    const wrapper = document.getElementById('avatarCropper');
    const image   = document.getElementById('cropperImage');
    const saveBtn = document.getElementById('cropSave');
    const cancel  = document.getElementById('cropCancel');
    const preview = document.getElementById('avatarPreview');
    const hidden  = document.getElementById('croppedAvatar');

    if (!input || !wrapper || !image || !saveBtn || !cancel || !hidden) return;

    let cropper = null;

    input.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;

        if (cropper) cropper.destroy();

        image.src = URL.createObjectURL(file);
        wrapper.style.display = 'flex';

        cropper = new Cropper(image, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 1,
            responsive: true,
            background: false,
            guides: false,
            center: true,
            highlight: false,
            cropBoxResizable: false,
            cropBoxMovable: false
        });

    });

    saveBtn.addEventListener('click', async () => {
        if (!cropper) return;

        // 1️⃣ Кропаємо
        const canvas = cropper.getCroppedCanvas({ width: 400, height: 400 });
        const c = document.createElement('canvas');
        c.width = c.height = 400;
        const ctx = c.getContext('2d');

        ctx.beginPath();
        ctx.arc(200, 200, 200, 0, Math.PI * 2);
        ctx.clip();
        ctx.drawImage(canvas, 0, 0);

        const base64 = c.toDataURL('image/jpeg', 0.9);

        // 2️⃣ Кладемо в hidden input
        document.getElementById('croppedAvatar').value = base64;

        // 3️⃣ Закриваємо кропер
        cropper.destroy();
        cropper = null;
        document.getElementById('avatarCropper').style.display = 'none';

        // 4️⃣ Готуємо FormData для AJAX
        const form = document.querySelector('form[method="POST"]');
        const fd = new FormData(form);
        fd.append('ajax', 1); // щоб сервер знав, що це AJAX

        // 5️⃣ Відправляємо AJAX замість submit
        const res = await fetch('', {
            method: 'POST',
            body: fd
        });

        const data = await res.json();
        if (!data.success) return;

        // 6️⃣ Оновлюємо DOM аватарки (як для імені та телефону)
        const preview = document.getElementById('avatarPreview');
        if (preview && data.avatar) {
            preview.src = data.avatar + '?t=' + Date.now();
        }
    });



    cancel.addEventListener('click', () => {
        if (cropper) cropper.destroy();
        cropper = null;
        wrapper.style.display = 'none';
        input.value = '';
    });


    const profileForm = document.querySelector('form');

    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const fd = new FormData(profileForm);
        fd.append('ajax', 1);

        const res = await fetch('', {
            method: 'POST',
            body: fd
        });

        const data = await res.json();
        if (!data.success) return;

        // Імʼя та телефон
        document.querySelectorAll('.profile-row').forEach(row => {
            const label = row.querySelector('.label')?.innerText.trim();

            if (label === "Імʼя") {
                row.querySelector('.value').innerText = data.username;
                row.classList.remove('editing');
            }

            if (label === "Телефон") {
                row.querySelector('.value').innerText = data.phone;
                row.classList.remove('editing');
            }
        });

        // Аватар (без кешу)
        if (data.avatar) {
            const img = document.getElementById('avatarPreview');
            img.src = data.avatar + '?t=' + Date.now();
        }
    });



});
