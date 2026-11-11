document.addEventListener('DOMContentLoaded', () => {
    const input = document.querySelector('[data-password-input]');
    const toggle = document.querySelector('[data-password-toggle]');

    if (!(input instanceof HTMLInputElement) || !(toggle instanceof HTMLButtonElement)) return;

    toggle.addEventListener('click', () => {
        const reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';
        toggle.setAttribute('aria-label', reveal ? 'Ocultar contraseña' : 'Mostrar contraseña');
        toggle.setAttribute('aria-pressed', String(reveal));
        const label = toggle.querySelector('[data-password-toggle-label]');
        if (label) label.textContent = reveal ? 'Ocultar' : 'Mostrar';
    });
});
