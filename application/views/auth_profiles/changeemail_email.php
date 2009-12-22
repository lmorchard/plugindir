From: lorchard@mozilla.com
Subject: Email address change for <?php echo $login_name?>.

Someone (possibly you) has triggered an attempt to change the email address for 
a login named "<?php echo $login_name?>".  If you are that someone, follow this link to 
complete the process:

<?php echo  
url::site('verifyemail') . '?' . http_build_query(array(
    'email_verification_token' => $email_verification_token
))
?>
