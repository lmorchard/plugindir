From: changeme@gmail.com
Subject: Undo email address change for <?php echo $login_name?>?

Someone (possibly you) has triggered an attempt to change the email address for 
a login named "<?=$login_name?>" to <?=$new_email?>.  

If you did not request this change, you can cancel or undo it by following this 
link:

<?= 
url::full('verifyemail', false, array(
    'email_verification_token' => $email_verification_token
))
?>
