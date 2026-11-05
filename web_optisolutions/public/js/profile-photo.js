(() => {
    // Keep the multipart request below even Nginx's default 1 MB limit.
    const maxBytes = 512 * 1024;

    async function preparePhoto(file) {
        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            throw new Error('Please choose a JPG or PNG photo.');
        }
        const url = URL.createObjectURL(file);
        try {
            const image = new Image();
            await new Promise((resolve, reject) => {
                image.onload = resolve;
                image.onerror = () => reject(new Error('This photo could not be opened. Please choose another JPG or PNG.'));
                image.src = url;
            });
            if (file.size <= maxBytes && Math.max(image.naturalWidth, image.naturalHeight) <= 1024) return file;

            const canvas = document.createElement('canvas');
            const scale = Math.min(1, 1024 / Math.max(image.naturalWidth, image.naturalHeight));
            canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
            canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
            const context = canvas.getContext('2d');
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(image, 0, 0, canvas.width, canvas.height);
            for (const quality of [0.85, 0.7, 0.55, 0.4]) {
                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', quality));
                if (blob && blob.size <= maxBytes) {
                    return new File([blob], 'profile-photo.jpg', { type: 'image/jpeg' });
                }
            }
            throw new Error('This photo is too large to upload. Please choose a smaller photo.');
        } finally {
            URL.revokeObjectURL(url);
        }
    }

    document.querySelectorAll('[data-profile-photo-form]').forEach(form => {
        const input = form.querySelector('[name="profile_photo"]');
        const status = document.getElementById('profilePhotoStatus');
        const submit = form.querySelector('[type="submit"]');
        let busy = false;
        form.addEventListener('submit', event => {
            if (busy) event.preventDefault();
        });
        input.addEventListener('change', async () => {
            const file = input.files[0];
            if (!file || busy) return;
            busy = true;
            input.disabled = true;
            if (submit) submit.disabled = true;
            status.hidden = false;
            status.style.color = '';
            status.textContent = 'Preparing photo…';
            let ready = false;
            try {
                const photo = await preparePhoto(file);
                const transfer = new DataTransfer();
                transfer.items.add(photo);
                input.files = transfer.files;
                const preview = document.createElement('img');
                const previewUrl = URL.createObjectURL(photo);
                preview.onload = preview.onerror = () => URL.revokeObjectURL(previewUrl);
                preview.src = previewUrl;
                preview.alt = 'Selected profile photo';
                document.getElementById('profileModalPhoto').replaceChildren(preview);
                status.textContent = form.dataset.autoUpload === 'true'
                    ? 'Saving photo…' : 'New photo ready — click Save Changes to apply.';
                ready = true;
            } catch (error) {
                input.value = '';
                status.style.color = '#c0392b';
                status.textContent = error.message || 'Unable to prepare photo. Please try another photo.';
            } finally {
                busy = false;
                input.disabled = false;
                if (submit) submit.disabled = false;
            }
            if (ready && form.dataset.autoUpload === 'true') form.requestSubmit();
        });
    });
})();
