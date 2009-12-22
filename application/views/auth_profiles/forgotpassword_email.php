From: lorchard@mozilla.com
Subject: Password recovery for <?php echo $login_name?>.

Someone (possibly you) has triggered an attempt to recover the password for a 
login named "<?php echo $login_name?>" registered with this email address.  If you are 
that someone, follow this link to complete the process:

<?php echo  
url::site('changepassword') . '?' . http_build_query(array(
    'password_reset_token' => $password_reset_token
))
?>
