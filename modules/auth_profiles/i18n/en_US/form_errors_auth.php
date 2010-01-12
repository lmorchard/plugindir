<?php
/**
 * Registration form error messages
 *
 * @author  l.m.orchard@pobox.com
 */
$lang = array(
    'login_name' => array(
        'default'
            => _('Valid login name is required.'),
        'required'             
            => _('Login name is required.'),
        'length'               
            => _('Login name must be between 3 and 64 characters in length'),
        'alpha_dash'           
            => _('Login name must contain only alphanumeric characters'),
        'isLoginNameAvailable' 
            => _('Login name is not available.'),
    ),
    'email' => array(
        'default'
            => _('Valid email is required.'),
        'required' 
            => _('Email is required.'),
        'email'
            => _('Valid email is required.'),
        'is_email_available' 
            => _('A login has already been registered using this email address.'),
        ),
    'email_confirm' => array(
        'default'
            => _('Valid email confirmation is required.'),
        'required' 
            => _('Email confirmation is required.'),
        'email'
            => _('Valid email confirmation is required.'),
        'matches'
            => _('Email confirmation does not match email.'),
    ),
    'password' => array(
        'default'
            => _('Password is invalid.'),
        'required' 
            => _('Password is required.'),
    ),
    'password_confirm' => array(
        'required' 
            => _('Password confirmation is required.'),
        'matches'  
            => _('Password and confirmation must match.'),
    ),
    'screen_name' => array(
        'required'              
            => _('Screen name is required.'),
        'length'                
            => _('Screen name must be between 3 and 64 characters in length'),
        'alpha_dash'            
            => _('Screen name must contain only alphanumeric characters'),
        'isScreenNameAvailable' 
            => _('Screen name is not available.'),
    ),
    'full_name' => array(
        'required'      
            => _('Full name is required'),
        'standard_text' 
            => _('Full name must contain only alphanumeric characters'),
    ),
    'captcha' => array(
        'default' 
            => _('Valid captcha response is required.'),
    ),
    'old_password' => array(
        'default'
            => _('Old password is invalid.'),
        'required'
            => _('Old password is required'),
    ),
    'new_password' => array(
        'required'
            => _('New password is required'),
    ),
    'new_password_confirm' => array(
        'required'
            => _('New password confirmation required'),
        'matches'  
            => _('Password and confirmation must match.'),
    ),
    'new_email' => array(
        'default' 
            => _('A valid new email is required'),
        'is_email_available' 
            => _('This email address is used by another login'),
    ),
    'new_email_confirm' => array(
        'default' 
            => _('A valid new email confirmation is required'),
    ),
);
