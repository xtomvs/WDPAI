function initPasswordToggle(inputId, iconId) {
  const toggleIcon = document.getElementById(iconId);
  const passwordInput = document.getElementById(inputId);

  if (!toggleIcon || !passwordInput) return;

  toggleIcon.addEventListener('click', () => {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    toggleIcon.classList.toggle('fa-eye');
    toggleIcon.classList.toggle('fa-eye-slash');
  });

  toggleIcon.style.cursor = 'pointer';
}
