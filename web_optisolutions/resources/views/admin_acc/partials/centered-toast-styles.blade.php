<style>
    .toast {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) translateY(10px);
        background: #062744;
        color: #fff;
        padding: 0.85rem 1.5rem;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 500;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 0.6rem;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease, transform 0.25s ease;
        z-index: 2000;
    }
    .toast.show { opacity: 1; transform: translate(-50%, -50%) translateY(0); }
    .toast-success { background: #0E62AA; }
    .toast-error   { background: #c0392b; }
    .toast::before { font-family: "Font Awesome 6 Free"; font-weight: 900; }
    .toast-success::before { content: "\f00c"; }
    .toast-error::before   { content: "\f06a"; }
</style>
