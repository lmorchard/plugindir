From: lorchard@mozilla.com
Subject: Undo email address change for <?php echo $login_name?>?

Someone (possibly you) has triggered an attempt to change the email address for 
a login named "<?php echo $login_name?>" to <?php echo $new_email?>.  

If you did not request this change, you can cancel or undo it by following this 
link:

<?php echo  
url::site('verifyemail') . '?' . http_build_query(array(
    'email_verification_token' => $email_verification_token
))
?>
