<?php

return [
    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'ico' => 'Company ID',
        'dic' => 'Tax ID',
        'shortcut' => 'Shortcut',
        'street' => 'Street',
        'city' => 'City',
        'zip' => 'ZIP code',
        'country' => 'Country',
        'description' => 'Description',
        'czech_republic' => 'Czech Republic',
        'current_password' => 'Current password',
        'current_password_hint' => 'Enter your current password for confirmation',
        'password_hint' => 'Password must be at least 8 characters long',
        'password' => 'Password',
        'new_password' => 'New password',
        'password_confirmation' => 'Password confirmation',
        'password_confirmation_hint' => 'Enter the password again for confirmation',
        'remember_me' => 'Remember me',
        
        // Hints and placeholders
        'email_placeholder' => 'Email',
        'password_placeholder' => 'Password',
    ],
    
    'titles' => [
        'edit_profile' => 'Edit Profile',
        'login' => 'Login',
        'register' => 'Register',
        'system_name' => 'Invoicing System',
    ],
    
    'errors' => [
        'login_errors' => 'There were errors during login:',
    ],
    
    'sections' => [
        'basic_info' => 'Basic Information',
        'address' => 'Address',
        'change_password' => 'Change Password',
        'security' => 'Security',
    ],
    
    'actions' => [
        'create' => 'Create Supplier',
        'edit' => 'Edit Supplier',
        'delete' => 'Delete Supplier',
        'save' => 'Save Changes',
        'cancel' => 'Cancel',
        'logout' => 'Logout',
        'back_to_dashboard' => 'Back to Dashboard',
        'update_password' => 'Update Password',
        'login' => 'Login',
        'register' => 'Register',
        'forgot_password' => 'Forgot Password?',
        'back_to_login' => 'Back to Login',
    ],
    'messages' => [
        'profile_updated' => 'Profile was successfully updated.',
        'password_updated' => 'Profile and password were successfully changed.',
        'profile_error_update' => 'Error updating profile: ',
        'profile_error' => 'Error loading profile: ',
        'profile_error_update_password' => 'Error changing profile and password: ',
        'profile_error_update_password_current' => 'Current password is incorrect.',
        'profile_error_update_password_empty' => 'Password is empty.',
        'error_edit_client' => 'Error editing client: ',
        'error_create_client' => 'Error creating client: ',
        'error_delete_client' => 'Error deleting client: ',
        'error_update_client' => 'Error updating client: ',
        'no_account' => 'Don\'t have an account?',
        'register_prompt' => 'Register now',
        'have_account' => 'Already have an account?',
        'login_prompt' => 'Log in',
    ],

    'validation' => [
        'name_required' => 'Name is required',
        'email_required' => 'Email is required',
        'email_email' => 'Please enter a valid email address',
        'email_unique' => 'This email is already being used',
        'street_required' => 'Street is required',
        'city_required' => 'City is required',
        'zip_required' => 'ZIP code is required',
        'country_required' => 'Country is required',
        'password_min' => 'Password must be at least :min characters long',
        'password_required' => 'Password is required',
        'password_confirmed' => 'Passwords do not match',
        'required_field' => 'This field is required',
    ],

    'placeholders' => [
        'name' => 'Enter name',
        'email' => 'Enter email',
        'phone' => 'Enter phone number',
        'ico' => 'Enter Company ID',
        'dic' => 'Enter Tax ID',
        'shortcut' => 'Enter shortcut',
        'street' => 'Enter street',
        'city' => 'Enter city',
        'zip' => 'Enter ZIP code',
        'country' => 'Select country',
    ],

    'status' => [
        'user_statuses' => 'User Statuses',
    ],
];
