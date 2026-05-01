/*
  Requirement: Add client-side validation to the login form.

  Instructions:
  1. This file is already linked to your HTML via a <script> tag with the 'defer' attribute
     at the bottom of the <body> in login.html.
  
  2. In your login.html, a <div id="message-container"> has been added *after* the </fieldset>
     but *before* the </form> closing tag. This div will be used to display success or error messages.
  
  3. Implement the JavaScript functionality as described in the TODO comments.
*/

// --- Element Selections ---
// We can safely select elements here because 'defer' guarantees
// the HTML document is parsed before this script runs.

// TODO: Select the login form by its id "login-form".

// TODO: Select the email input element by its ID.

// TODO: Select the password input element by its ID.

// TODO: Select the message container element by its ID.

// --- Functions ---

/**
 * TODO: Implement the displayMessage function.
 * This function takes two arguments:
 * 1. message (string): The message to display.
 * 2. type (string): "success" or "error".
 *
 * It should:
 * 1. Set the text content of `messageContainer` to the `message`.
 * 2. Set the class name of `messageContainer` to `type`
 * (this will allow for CSS styling of 'success' and 'error' states).
 */
function displayMessage(message, type) {
  // ... your implementation here ...
}

/**
 * TODO: Implement the isValidEmail function.
 * This function takes one argument:
 * 1. email (string): The email string to validate.
 *
 * It should:
 * 1. Use a regular expression to check if the email format is valid.
 * 2. Return `true` if the email is valid (e.g., "test@example.com").
 * 3. Return `false` if the email is invalid (e.g., "test@", "test.com", "test@.com").
 *
 * A simple regex for this purpose is: /\S+@\S+\.\S+/
 */
function isValidEmail(email) {
  // ... your implementation here ...
}

/**
 * TODO: Implement the isValidPassword function.
 * This function takes one argument:
 * 1. password (string): The password string to validate.
 *
 * It should:
 * 1. Check if the password length is 8 characters or more.
 * 2. Return `true` if the password is valid.
 * 3. Return `false` if the password is not valid.
 */
function isValidPassword(password) {
  // ... your implementation here ...
}

/**
 * TODO: Implement the handleLogin function.
 * This function will be the event handler for the form's "submit" event.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the `value` from `emailInput` and `passwordInput`, trimming any whitespace.
 * 3. Validate the email using `isValidEmail()`.
 * - If invalid, call `displayMessage("Invalid email format.", "error")` and stop.
 * 4. Validate the password using `isValidPassword()`.
 * - If invalid, call `displayMessage("Password must be at least 8 characters.", "error")` and stop.
 * 5. If both email and password are valid:
 * - Call `displayMessage("Login successful!", "success")`.
 * - (Optional) Clear the email and password input fields.
 */
function handleLogin(event) {
  // ... your implementation here ...
}

/**
 * TODO: Implement the setupLoginForm function.
 * This function will be called once to set up the form.
 * It should:
 * 1. Check if `loginForm` exists.
 * 2. If it exists, add a "submit" event listener to it.
 * 3. The event listener should call the `handleLogin` function.
 */
function setupLoginForm() {
  // ... your implementation here ...
}

// --- Initial Page Load ---
// Call the main setup function to attach the event listener.
setupLoginForm();
