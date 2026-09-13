<div class="profile-modal-overlay" id="profileModalOverlay">
  <div class="profile-modal">

    <div class="profile-modal-header">
      <h5>My Profile</h5>
      <button type="button" class="profile-modal-close" id="profileModalClose" aria-label="Close">&times;</button>
    </div>

    <div class="profile-modal-body">

      @if (session('profile_success'))
        <div class="profile-alert-success">{{ session('profile_success') }}</div>
      @endif

      <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="profile-photo-row">
          @if (Auth::user()->profile_photo)
            <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" class="profile-photo-preview">
          @else
            <div class="profile-photo-placeholder">
              {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
            </div>
          @endif
          <div class="profile-photo-input">
            <label>Profile Photo</label>
            <input type="file" name="photo" accept=".jpg,.jpeg,.png">
            @error('photo', 'profile') <div class="profile-field-error">{{ $message }}</div> @enderror
          </div>
        </div>

        <div class="profile-field">
          <label>Name</label>
          <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}">
          @error('name', 'profile') <div class="profile-field-error">{{ $message }}</div> @enderror
        </div>

        <div class="profile-field">
          <label>Email</label>
          <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}">
          @error('email', 'profile') <div class="profile-field-error">{{ $message }}</div> @enderror
        </div>

        <div class="profile-field">
          <label>Birthday</label>
          <input type="date" name="birthday"
                 value="{{ old('birthday', Auth::user()->birthday ? \Carbon\Carbon::parse(Auth::user()->birthday)->format('Y-m-d') : '') }}">
          @error('birthday', 'profile') <div class="profile-field-error">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="profile-save-btn">Save Changes</button>
      </form>

    </div>
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

    .profile-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .profile-modal-overlay.open {
        display: flex;
    }
    .profile-modal {
        background: var(--white);
        width: 100%;
        max-width: 420px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        max-height: 90vh;
        overflow-y: auto;
    }
    .profile-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px 0;
    }
    .profile-modal-header h5 {
        margin: 0;
        font-weight: 700;
        color: var(--primary-dark);
    }
    .profile-modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        line-height: 1;
        cursor: pointer;
        color: #7f8c8d;
        transition: color 0.2s ease;
    }
    .profile-modal-close:hover {
        color: var(--text-dark);
    }
    .profile-modal-body {
        padding: 16px 24px 24px;
    }
    .profile-alert-success {
        background: #e8f8f0;
        color: var(--primary-main);
        padding: 8px 12px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 0.9rem;
        font-weight: 500;
    }
    .profile-photo-row {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 20px;
    }
    .profile-photo-preview {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        object-fit: cover;
    }
    .profile-photo-placeholder {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
        color: var(--white);
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .profile-photo-input label {
        display: block;
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 4px;
        color: var(--text-dark);
    }
    .profile-field {
        margin-bottom: 14px;
    }
    .profile-field label {
        display: block;
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 4px;
        color: var(--text-dark);
    }
    .profile-field input {
        width: 100%;
        padding: 0.7rem;
        border: 1px solid var(--light-gray);
        border-radius: 8px;
        font-family: inherit;
        font-size: 0.9rem;
        outline: none;
        transition: all 0.3s ease;
    }
    .profile-field input:focus {
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
        margin-top: 6px;
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

    @if (session('profile_success') || $errors->profile->any())
        openProfileModal();
    @endif
</script>