<?php
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/layout.php';start_session();$message='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();try{
  $action=text_value($_POST,'action',20,true);
  if($action==='request'){
   $email=strtolower(text_value($_POST,'email',190,true));$ipOk=rate_limit('reset-ip:'.request_ip(),10,3600);$emailOk=rate_limit('reset-email:'.$email,3,3600);
   if($ipOk&&$emailOk){$u=query('SELECT id,email FROM admin_users WHERE email=? AND active=1',[$email])->fetch();
    if($u){$token=bin2hex(random_bytes(32));transaction(function()use($u,$token){query('UPDATE password_resets SET used_at=UTC_TIMESTAMP() WHERE admin_user_id=? AND used_at IS NULL',[$u['id']]);query('INSERT INTO password_resets(admin_user_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 MINUTE))',[$u['id'],hash('sha256',$token)]);audit('password_reset_requested','admin_users',(int)$u['id']);});
     // Token is never logged or stored in notification_queue; only its hash is stored.
     $sent=send_mail($u['email'],'Reset your TCF staff password',"Use this single-use link within 30 minutes:\n".url('admin/reset.php#token='.$token)."\nIf you did not request this, ignore this message.");
     if(!$sent)audit('reset_email_unavailable','admin_users',(int)$u['id']);
    }
   }
   $message='If an active account exists and email is available, a reset link will be sent. Please check your inbox or contact your Super Admin.';
  }elseif($action==='complete'){
   if(!rate_limit('reset-complete:'.request_ip(),10))throw new ValidationError('Too many attempts. Please try again later.');
   $token=text_value($_POST,'token',64,true);if(!preg_match('/^[a-f0-9]{64}$/D',$token))throw new ValidationError('This reset link is invalid or expired.');
   $password=text_value($_POST,'password',72,true);if($password!==text_value($_POST,'confirmation',72,true))throw new ValidationError('Passwords do not match.');$hash=hash_password($password);
   transaction(function()use($token,$hash){$reset=query('SELECT * FROM password_resets WHERE token_hash=? AND used_at IS NULL AND expires_at>UTC_TIMESTAMP() FOR UPDATE',[hash('sha256',$token)])->fetch();if(!$reset)throw new ValidationError('This reset link is invalid or expired.');$u=query('SELECT id FROM admin_users WHERE id=? AND active=1 FOR UPDATE',[$reset['admin_user_id']])->fetch();if(!$u)throw new ValidationError('This reset link is invalid or expired.');query('UPDATE admin_users SET password_hash=?,auth_version=auth_version+1 WHERE id=?',[$hash,$u['id']]);query('UPDATE password_resets SET used_at=UTC_TIMESTAMP() WHERE admin_user_id=? AND used_at IS NULL',[$u['id']]);audit('password_reset_completed','admin_users',(int)$u['id']);});
   end_session();redirect('admin/login.php');
  }else throw new ValidationError('Invalid action.');
 }catch(ValidationError $ex){$error=$ex->getMessage();}
}
page_header('Password reset');if($message)echo '<p class="notice">'.e($message).'</p>';if($error)echo '<p class="notice error" role="alert">'.e($error).'</p>';
$completing=($_POST['action']??'')==='complete';
echo '<form method="post" id="reset-request"'.($completing?' hidden':'').'>'.csrf_input().'<input type="hidden" name="action" value="request">';input_field('email','Staff email','','email',true);echo '<button>Request reset link</button></form><form method="post" id="reset-complete"'.(!$completing?' hidden':'').'>'.csrf_input().'<input type="hidden" name="action" value="complete"><input type="hidden" name="token" value="'.e(is_string($_POST['token']??null)?$_POST['token']:'').'">';input_field('password','New password (12–72 bytes)','','password',true);input_field('confirmation','Confirm new password','','password',true);echo '<button>Reset password</button></form><p><a href="'.e(url('admin/login.php')).'">Back to sign in</a></p><noscript>Use a browser with JavaScript to open a reset link.</noscript>';page_footer();
