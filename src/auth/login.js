// --- Element Selections ---
const loginForm = document.getElementById("login-form");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const messageContainer = document.getElementById("message-container");

// --- Functions ---

function displayMessage(message, type) {
  messageContainer.textContent = message;
  messageContainer.className = type;
}

function isValidEmail(email) {
  const emailPattern = /\S+@\S+\.\S+/;
  return emailPattern.test(email);
}

function isValidPassword(password) {
  return password.length >= 8;
}

function handleLogin(event) {
  event.preventDefault();

  const email = emailInput.value.trim();
  const password = passwordInput.value.trim();

  if (!isValidEmail(email)) {
    displayMessage("Invalid email format.", "error");
    return;
  }

  if (!isValidPassword(password)) {
    displayMessage("Password must be at least 8 characters.", "error");
    return;
  }

  displayMessage("Login successful!", "success");

  emailInput.value = "";
  passwordInput.value = "";
}

function setupLoginForm() {
  if (loginForm) {
    loginForm.addEventListener("submit", handleLogin);
  }
}

// --- Initial Page Load ---
setupLoginForm();
