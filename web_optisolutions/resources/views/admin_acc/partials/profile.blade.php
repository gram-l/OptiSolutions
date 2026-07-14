<div class="profile-modal-overlay" id="profileModalOverlay">
  <div class="profile-modal">

    <div class="profile-modal-header">
      <h5>My Profile</h5>
      <button type="button" class="profile-modal-close" id="profileModalClose" aria-label="Close">&times;</button>
    </div>

    <div class="profile-modal-body">

      @if (session('success'))
        <div class="profile-alert-success">{{ session('success') }}</div>
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
            @error('photo') <div class="profile-field-error">{{ $message }}</div> @enderror
          </div>
        </div>

        <div class="profile-field">
          <label>Name</label>
          <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}">
          @error('name') <div class="profile-field-error">{{ $message }}</div> @enderror
        </div>

        <div class="profile-field">
          <label>Email</label>
          <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}">
          @error('email') <div class="profile-field-error">{{ $message }}</div> @enderror
        </div>

        <div class="profile-field">
          <label>Birthday</label>
          <input type="date" name="birthday"
                 value="{{ old('birthday', Auth::user()->birthday ? \Carbon\Carbon::parse(Auth::user()->birthday)->format('Y-m-d') : '') }}">
          @error('birthday') <div class="profile-field-error">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="profile-save-btn">Save Changes</button>
      </form>

    </div>
  </div>
</div>

<style>
.profile-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.profile-modal-overlay.open {
    display: flex;
}
.profile-modal {
    background: #fff;
    width: 100%;
    max-width: 420px;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
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
    color: #1A237E;
}
.profile-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    line-height: 1;
    cursor: pointer;
    color: #666;
}
.profile-modal-body {
    padding: 16px 24px 24px;
}
.profile-alert-success {
    background: #d1f7dc;
    color: #157347;
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 0.9rem;
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
    background: #1A237E;
    color: #fff;
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
}
.profile-field {
    margin-bottom: 14px;
}
.profile-field label {
    display: block;
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 4px;
}
.profile-field input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d9d9e3;
    border-radius: 8px;
    font-size: 0.9rem;
}
.profile-field-error {
    color: #dc3545;
    font-size: 0.8rem;
    margin-top: 4px;
}
.profile-save-btn {
    width: 100%;
    background: #1A237E;
    color: #fff;
    border: none;
    padding: 10px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 6px;
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

    @if (session('success') || $errors->any())
        openProfileModal();
    @endif
</script>