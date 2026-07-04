// Toast notification helper
function showToast(message) {
    const toast = document.getElementById('toastMessage');
    if (toast) {
        toast.textContent = message;
        toast.style.display = 'block';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 2500);
    }
}

// Update appointment status
function updateStatus(aptId, newStatus) {
    fetch(`/staff/appointment/${aptId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Appointment updated successfully!');
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}