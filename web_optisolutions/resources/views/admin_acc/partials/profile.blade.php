<div class="profile-modal-overlay" id="profileModalOverlay">
  <div class="profile-modal">

    <button type="button" class="profile-modal-close" id="profileModalClose" aria-label="Close">
      <i class="bi bi-arrow-left"></i>
    </button>

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="_profile_form" value="1">

      {{-- Avatar + camera badge + role pill --}}
      <div class="profile-modal-photo-wrap">
        <div class="profile-modal-photo-circle">
          <div class="profile-modal-photo" id="profileModalPhoto">
            @if (Auth::user()->profile_photo)
              <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Profile photo">
            @else
              {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
            @endif
          </div>
          <button type="button" class="profile-modal-photo-edit" id="profilePhotoBtn" aria-label="Change profile photo">
            <i class="bi bi-camera-fill"></i>
          </button>
        </div>
        <div class="profile-modal-badge">Clinic Admin</div>
        <div class="profile-photo-hint" id="profilePhotoHint" hidden>New photo selected — click Save Changes to apply.</div>
        @error('photo') <div class="profile-field-error">{{ $message }}</div> @enderror

        <input type="file" name="photo" id="profilePhotoInput" accept=".jpg,.jpeg,.png" hidden>
      </div>

      @if (session('profile_success'))
        <div class="profile-alert-success">{{ session('profile_success') }}</div>
      @endif

      <div class="profile-modal-section-title">Personal Information</div>

      <div class="profile-modal-field">
        <i class="bi bi-person"></i>
        <div class="profile-modal-field-body">
          <label class="profile-modal-field-label" for="profileName">Name</label>
          <input type="text" id="profileName" name="name" value="{{ old('name', Auth::user()->name) }}">
          @error('name') <div class="profile-field-error">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="profile-modal-field">
        <i class="bi bi-envelope"></i>
        <div class="profile-modal-field-body">
          <label class="profile-modal-field-label" for="profileEmail">Email</label>
          <input type="email" id="profileEmail" name="email" value="{{ old('email', Auth::user()->email) }}">
          @error('email') <div class="profile-field-error">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="profile-modal-field">
        <i class="bi bi-calendar-event"></i>
        <div class="profile-modal-field-body">
          <label class="profile-modal-field-label" for="profileBirthday">Birthday</label>
          <input type="date" id="profileBirthday" name="birthday"
                 value="{{ old('birthday', Auth::user()->birthday ? \Carbon\Carbon::parse(Auth::user()->birthday)->format('Y-m-d') : '') }}">
          @error('birthday') <div class="profile-field-error">{{ $message }}</div> @enderror
        </div>
      </div>

      <button type="submit" class="profile-save-btn">Save Changes</button>
    </form>

  </div>
</div>

<style>
    :root {
        --primary-light: #9DBCD4;
        --primary-main: #0E62AA;
        --primary-dark: #062744;
        --white: #FFFFFF;
        --light-gray: #ECF0F1;
        --text-dark: #333333;
        --shadow: rgba(0, 0, 0, 0.1);
        --danger: #e74c3c;
    }

    /* Overlay + card — same shape as the staff profile modal */
    .profile-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .profile-modal-overlay.open {
        display: flex;
    }
    .profile-modal {
        position: relative;
        background: var(--white);
        width: 380px;
        max-width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        padding: 28px 24px;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        animation: profileModalIn 0.2s ease;
    }
    @keyframes profileModalIn {
        from { opacity: 0; transform: translateY(-10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Back arrow, top-left */
    .profile-modal-close {
        position: absolute;
        top: 16px;
        left: 16px;
        background: none;
        border: none;
        font-size: 1.2rem;
        line-height: 1;
        cursor: pointer;
        color: var(--primary-main);
        transition: color 0.2s ease;
    }
    .profile-modal-close:hover {
        color: var(--primary-dark);
    }

    /* Avatar, camera badge, role pill */
    .profile-modal-photo-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-top: 20px;
        margin-bottom: 12px;
    }
    .profile-modal-photo-circle {
        position: relative;
        width: 110px;
        height: 110px;
    }
    .profile-modal-photo {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: var(--light-gray);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
        font-weight: 600;
        color: var(--primary-dark);
        overflow: hidden;
        border: 3px solid var(--white);
        box-shadow: 0 0 0 1px var(--light-gray);
    }
    .profile-modal-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .profile-modal-photo-edit {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 28px;
        height: 28px;
        padding: 0;
        background: var(--primary-main);
        border: 2px solid var(--white);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 0.85rem;
        cursor: pointer;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.25);
    }
    .profile-modal-photo-edit:hover {
        filter: brightness(0.9);
    }
    .profile-modal-badge {
        background: var(--light-gray);
        color: var(--text-dark);
        font-size: 0.8rem;
        padding: 4px 14px;
        border-radius: 20px;
        margin-top: 4px;
    }
    .profile-photo-hint {
        margin-top: 8px;
        font-size: 0.75rem;
        color: #7f8c8d;
        text-align: center;
    }

    .profile-alert-success {
        background: #e8f8f0;
        color: var(--primary-main);
        padding: 8px 12px;
        border-radius: 8px;
        margin-top: 12px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* "Personal Information" + icon-chip rows */
    .profile-modal-section-title {
        font-weight: 600;
        color: var(--primary-dark);
        margin: 18px 0 10px;
    }
    .profile-modal-field {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid var(--light-gray);
    }
    /* Icon chip — same treatment as the dashboard's .card-icon-badge:
       blue icon on a light-blue tint */
    .profile-modal-field > i {
        flex-shrink: 0;
        background: rgba(14, 98, 170, 0.1);
        color: var(--primary-main);
        width: 34px;
        height: 34px;
        border-radius: 10px;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .profile-modal-field-body {
        flex: 1;
        min-width: 0;
    }
    .profile-modal-field-label {
        display: block;
        font-size: 0.75rem;
        color: #7f8c8d;
        margin-bottom: 2px;
    }
    .profile-modal-field-body input {
        width: 100%;
        padding: 0.45rem 0.65rem;
        border: 1px solid var(--light-gray);
        border-radius: 8px;
        background: var(--white);
        color: var(--text-dark);
        font-family: inherit;
        font-size: 0.95rem;
        outline: none;
        transition: all 0.3s ease;
    }
    .profile-modal-field-body input:focus {
        border-color: var(--primary-main);
        box-shadow: 0 0 0 2px rgba(14, 98, 170, 0.2);
    }
    .profile-field-error {
        color: var(--danger);
        font-size: 0.8rem;
        margin-top: 4px;
    }

    .profile-save-btn {
        width: 100%;
        background: var(--primary-main);
        color: var(--white);
        border: none;
        padding: 0.7rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 18px;
        transition: all 0.3s ease;
    }
    .profile-save-btn:hover {
        background: #0b4f8a;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(14, 98, 170, 0.3);
    }
</style>

<script>
    const profileModalOverlay = document.getElementById('profileModalOverlay');
    const profileModalClose   = document.getElementById('profileModalClose');

    function openProfileModal() {
        profileModalOverlay.classList.add('open');
    }

    profileModalClose.addEventListener('click', () => {
        profileModalOverlay.classList.remove('open');
    });

    profileModalOverlay.addEventListener('click', (e) => {
        if (e.target === profileModalOverlay) {
            profileModalOverlay.classList.remove('open');
        }
    });

    // Camera badge opens the (hidden) file picker; picking a file shows a
    // local preview only — nothing is uploaded until "Save Changes".
    const profilePhotoInput = document.getElementById('profilePhotoInput');
    document.getElementById('profilePhotoBtn').addEventListener('click', () => {
        profilePhotoInput.click();
    });
    profilePhotoInput.addEventListener('change', () => {
        const file = profilePhotoInput.files[0];
        if (!file) return;
        const photoEl = document.getElementById('profileModalPhoto');
        photoEl.innerHTML = '';
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.alt = 'Selected photo';
        photoEl.appendChild(img);
        document.getElementById('profilePhotoHint').hidden = false;
    });

    @if (session('profile_success') || (old('_profile_form') && $errors->any()))
        openProfileModal();
    @endif
</script>
