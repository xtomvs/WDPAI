const form = document.querySelector('form');

if (form) {
  const emailInput = form.querySelector('input[name="email"]');
  const passwordInput = form.querySelector('input[name="password"]');
  const confirmedPasswordInput = form.querySelector('input[name="password2"]');
  const firstNameInput = form.querySelector('input[name="firstName"]');
  const lastNameInput = form.querySelector('input[name="lastName"]');

  const debounce = (fn, delay = 400) => {
    let timerId;
    return (...args) => {
      clearTimeout(timerId);
      timerId = setTimeout(() => fn(...args), delay);
    };
  };

  const isEmail = (email) => /\S+@\S+\.\S+/.test(email);

  const isStrongPassword = (password) => password.length >= 6;

  const arePasswordsSame = (password, confirmedPassword) =>
    password === confirmedPassword;

  const isValidName = (value) => {
    const trimmed = value.trim();
    if (trimmed.length < 2) {
      return false;
    }
    const regex = /^[A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż]+([ -][A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż]+)*$/;
    return regex.test(trimmed);
  };

  const markValidation = (element, condition) => {
    if (!element) {
      return;
    }
    !condition
      ? element.classList.add('no-valid')
      : element.classList.remove('no-valid');
  };

  const validateEmail = () => {
    markValidation(emailInput, isEmail(emailInput.value));
  };

  const validatePasswordStrength = () => {
    markValidation(passwordInput, isStrongPassword(passwordInput.value));
  };

  const validatePasswordMatch = () => {
    const condition = arePasswordsSame(
      passwordInput.value,
      confirmedPasswordInput.value
    );
    markValidation(confirmedPasswordInput, condition);
  };

  const validateFirstName = () => {
    markValidation(firstNameInput, isValidName(firstNameInput.value));
  };

  const validateLastName = () => {
    markValidation(lastNameInput, isValidName(lastNameInput.value));
  };

  const debouncedValidateEmail = debounce(validateEmail, 600);
  const debouncedValidatePasswordStrength = debounce(validatePasswordStrength, 600);
  const debouncedValidatePasswordMatch = debounce(validatePasswordMatch, 600);
  const debouncedValidateFirstName = debounce(validateFirstName, 600);
  const debouncedValidateLastName = debounce(validateLastName, 600);

  emailInput?.addEventListener('input', debouncedValidateEmail);
  passwordInput?.addEventListener('input', debouncedValidatePasswordStrength);
  passwordInput?.addEventListener('input', debouncedValidatePasswordMatch);
  confirmedPasswordInput?.addEventListener('input', debouncedValidatePasswordMatch);
  firstNameInput?.addEventListener('input', debouncedValidateFirstName);
  lastNameInput?.addEventListener('input', debouncedValidateLastName);

  form.addEventListener('submit', (event) => {
    validateEmail();
    validatePasswordStrength();
    validatePasswordMatch();
    validateFirstName();
    validateLastName();

    const isFormValid =
      isEmail(emailInput.value) &&
      isStrongPassword(passwordInput.value) &&
      arePasswordsSame(passwordInput.value, confirmedPasswordInput.value) &&
      isValidName(firstNameInput.value) &&
      isValidName(lastNameInput.value);

    if (!isFormValid) {
      event.preventDefault();
    }
  });
}
