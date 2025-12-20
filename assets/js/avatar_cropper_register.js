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

    // 📂 Вибір файлу
    input.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;

        if (cropper) cropper.destroy();

        image.src = URL.createObjectURL(file);
        wrapper.style.display = 'flex';

        cropper = new Cropper(image, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 1,
            dragMode: 'move',
            background: false,
            guides: false,
            center: true,
            highlight: false,
            cropBoxResizable: false,
            cropBoxMovable: false
        });
    });

    // 💾 Зберегти кроп
    saveBtn.addEventListener('click', () => {
        if (!cropper) return;

        const canvas = cropper.getCroppedCanvas({
            width: 400,
            height: 400
        });

        const c = document.createElement('canvas');
        c.width = c.height = 400;

        const ctx = c.getContext('2d');
        ctx.beginPath();
        ctx.arc(200, 200, 200, 0, Math.PI * 2);
        ctx.clip();
        ctx.drawImage(canvas, 0, 0);

        const base64 = c.toDataURL('image/jpeg', 0.9);

        // ✅ КЛЮЧОВЕ — кладемо в hidden input

// hidden input
        hidden.value = base64;

// 🔥 ОНОВЛЕННЯ АВАТАРА
        const avatarBox = document.querySelector('.avatar-box');
        let preview = document.getElementById('avatarPreview');

        if (!preview) {
            const span = avatarBox.querySelector('span');
            if (span) span.remove();

            preview = document.createElement('img');
            preview.id = 'avatarPreview';
            avatarBox.prepend(preview);
        }

        preview.src = base64;


        cropper.destroy();
        cropper = null;
        wrapper.style.display = 'none';
    });

    // ❌ Скасувати
    cancel.addEventListener('click', () => {
        if (cropper) cropper.destroy();
        cropper = null;
        wrapper.style.display = 'none';
        input.value = '';
    });

});
