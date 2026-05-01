<?php
/**
 * Authentication Handler for Login Form
 * 
 * This PHP script handles user authentication via POST requests from the Fetch API.
 * It validates credentials against a MySQL database using PDO,
 * creates sessions, and returns JSON responses.
 */

// --- Session Management ---
// TODO: Start a PHP session using session_start()
// This must be called before any output is sent to the browser
// Sessions allow us to store user data across multiple pages


// --- Set Response Headers ---
// TODO: Set the Content-Type header to 'application/json'
// This tells the browser that we're sending JSON data back


// TODO: (Optional) Set CORS headers if your frontend and backend are on different domains
// You'll need headers for Access-Control-Allow-Origin, Methods, and Headers


// --- Check Request Method ---
// TODO: Verify that the request method is POST
// Use the $_SERVER superglobal to check the REQUEST_METHOD
// If the request is not POST, return an error response and exit


// --- Get POST Data ---
// TODO: Retrieve the raw POST data
// The Fetch API sends JSON data in the request body
// Use file_get_contents with 'php://input' to read the raw request body


// TODO: Decode the JSON data into a PHP associative array
// Use json_decode with the second parameter set to true


// TODO: Extract the email and password from the decoded data
// Check if both 'email' and 'password' keys exist in the array
// If either is missing, return an error response and exit


// TODO: Store the email and password in variables
// Trim any whitespace from the email


// --- Server-Side Validation (Optional but Recommended) ---
// TODO: Validate the email format on the server side
// Use the appropriate filter function for email validation
// If invalid, return an error response and exit


// TODO: Validate the password length (minimum 8 characters)
// If invalid, return an error response and exit


// --- Database Connection ---
// TODO: Get the database connection using the provided function
// Assume getDBConnection() returns a PDO instance with error mode set to exception
// The function is defined elsewhere (e.g., in a config file or db.php)


// TODO: Wrap database operations in a try-catch block to handle PDO exceptions
// This ensures you can return a proper JSON error response if something goes wrong


    // --- Prepare SQL Query ---
    // TODO: Write a SQL SELECT query to find the user by email
    // Select the following columns: id, name, email, password, is_admin
    // Use a WHERE clause to filter by email
    // IMPORTANT: Use a placeholder (? or :email) for the email value
    // This prevents SQL injection attacks


    // --- Prepare the Statement ---
    // TODO: Prepare the SQL statement using the PDO prepare method
    // Store the result in a variable
    // Prepared statements protect against SQL injection


    // --- Execute the Query ---
    // TODO: Execute the prepared statement with the email parameter
    // Bind the email value to the placeholder


    // --- Fetch User Data ---
    // TODO: Fetch the user record from the database
    // Use the fetch method with PDO::FETCH_ASSOC
    // This returns an associative array of the user data, or false if no user found


    // --- Verify User Exists and Password Matches ---
    // TODO: Check if a user was found
    // The fetch method returns false if no record matches


    // TODO: If user exists, verify the password
    // Use password_verify() to compare the submitted password with the hashed password from the database
    // This function returns true if they match, false otherwise
    //
    // NOTE: This assumes passwords are stored as hashes using password_hash() with PASSWORD_DEFAULT
    // (see database seed data). Never store passwords in plain text!


    // --- Handle Successful Authentication ---
    // TODO: If password verification succeeds:
    
    
        // TODO: Store user information in session variables
        // Store: user_id, user_name, user_email, is_admin, logged_in
        // DO NOT store the password in the session!


        // TODO: Prepare a success response array
        // Include:
        // - 'success' => true
        // - 'message' => 'Login successful'
        // - 'user' => array with safe user details (id, name, email, is_admin)
        //
        // IMPORTANT: Do NOT include the password in the response


        // TODO: Encode the response array as JSON and echo it

        
        // TODO: Exit the script to prevent further execution


    // --- Handle Failed Authentication ---
    // TODO: If user doesn't exist OR password verification fails:
    
    
        // TODO: Prepare an error response array
        // Include:
        // - 'success' => false
        // - 'message' => 'Invalid email or password'
        //
        // SECURITY NOTE: Don't specify whether email or password was wrong
        // This prevents attackers from enumerating valid email addresses


        // TODO: Encode the error response as JSON and echo it
        
        
        // TODO: Exit the script


// TODO: Catch PDO exceptions in the catch block
// Catch PDOException type


    // TODO: Log the error for debugging
    // Use error_log() to write the error message to the server error log
    
    
    // TODO: Return a generic error message to the client
    // DON'T expose database details to the user for security reasons
    // Return a JSON response with success false and a generic message


    // TODO: Exit the script


// --- End of Script ---

?>