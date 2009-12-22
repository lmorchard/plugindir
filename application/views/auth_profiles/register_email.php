From: lorchard@mozilla.com
Subject: New user registration for <?php echo $login_name?>.

Someone (possibly you) has registered for a login named "<?php echo $login_name?>".  If you are that someone, follow this link to verify your email address and complete the process:

<?php echo  
url::site('verifyemail') . '?' . http_build_query(array(
    'email_verification_token' => $email_verification_token
))
?>
