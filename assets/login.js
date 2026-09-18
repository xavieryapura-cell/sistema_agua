const toggle = document.querySelector('.password-toggle');
const password = document.querySelector('#contrasena');
if (toggle && password) {
  toggle.hidden = false;
  toggle.addEventListener('click', () => {
    const show = password.type === 'password';
    password.type = show ? 'text' : 'password';
    toggle.textContent = show ? 'Ocultar' : 'Mostrar';
    toggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
    toggle.setAttribute('aria-pressed', String(show));
  });
}
