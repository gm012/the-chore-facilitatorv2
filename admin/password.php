<?php
require __DIR__.'/includes/bootstrap.php';$user=require_user();require __DIR__.'/includes/modules.php';require __DIR__.'/includes/layout.php';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();try{
 if(!rate_limit('password:'.$user['id'],8))throw new ValidationError('Too many attempts. Please try again later.');
 $current=text_value($_POST,'current_password',1024,true);$new=text_value($_POST,'new_password',72,true);$confirm=text_value($_POST,'confirm_password',72,true);
 $row=query('SELECT password_hash FROM admin_users WHERE id=?',[$user['id']])->fetch();if(!password_verify($current,$row['password_hash']))throw new ValidationError('Current password is incorrect.');if($new!==$confirm)throw new ValidationError('New passwords do not match.');
 transaction(function()use($user,$new){query('UPDATE admin_users SET password_hash=?,auth_version=auth_version+1 WHERE id=?',[hash_password($new),$user['id']]);query('UPDATE password_resets SET used_at=UTC_TIMESTAMP() WHERE admin_user_id=? AND used_at IS NULL',[$user['id']]);audit('password_changed','admin_users',(int)$user['id']);});end_session();redirect('admin/login.php');
}catch(ValidationError $ex){$error=$ex->getMessage();}}
page_header('Change password',$user);if($error)echo '<p class="notice error">'.e($error).'</p>';echo '<form method="post" class="panel">'.csrf_input();input_field('current_password','Current password','','password',true);input_field('new_password','New password (12–72 bytes)','','password',true);input_field('confirm_password','Confirm new password','','password',true);echo '<button>Change password and sign out</button></form>';page_footer();
