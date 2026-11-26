<?php
function current_user(): ?array {
 if(empty($_SESSION['admin_id']))return null;if(time()-($_SESSION['last_activity']??0)>1800||time()-($_SESSION['login_time']??0)>28800){end_session();return null;}
 $user=query('SELECT id,full_name,email,role,active,auth_version FROM admin_users WHERE id=?',[$_SESSION['admin_id']])->fetch();if(!$user||!$user['active']||(int)$user['auth_version']!==(int)($_SESSION['auth_version']??0)){end_session();return null;}$_SESSION['last_activity']=time();return $user;
}
function require_user(bool $super=false): array {start_session();$user=current_user();if(!$user)redirect('admin/login.php');if($super&&$user['role']!=='super_admin'){http_response_code(403);exit('You do not have permission to access this section.');}return $user;}
function end_session(): void {if(session_status()!==PHP_SESSION_ACTIVE)return;$_SESSION=[];$p=session_get_cookie_params();setcookie(session_name(),'', ['expires'=>time()-42000,'path'=>$p['path'],'secure'=>$p['secure'],'httponly'=>true,'samesite'=>'Strict']);session_destroy();}
function login_user(string $email,string $password): bool {
 $email=strtolower(trim($email));$ipAllowed=rate_limit('login-ip:'.request_ip(),20);$accountAllowed=rate_limit('login-account:'.$email,8);if(!$ipAllowed||!$accountAllowed){audit('login_rate_limited','admin_users');return false;}
 $user=query('SELECT * FROM admin_users WHERE email=?',[$email])->fetch();$dummy='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';$valid=password_verify($password,$user['password_hash']??$dummy);if(!$user||!$valid||!$user['active']){audit('login_failed','admin_users');return false;}
 session_regenerate_id(true);$_SESSION=['admin_id'=>(int)$user['id'],'auth_version'=>(int)$user['auth_version'],'login_time'=>time(),'last_activity'=>time(),'csrf'=>bin2hex(random_bytes(32))];
 if(password_needs_rehash($user['password_hash'],defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_BCRYPT))query('UPDATE admin_users SET password_hash=? WHERE id=?',[hash_password($password),$user['id']]);query('UPDATE admin_users SET last_login_at=UTC_TIMESTAMP() WHERE id=?',[$user['id']]);audit('login','admin_users',(int)$user['id']);return true;
}
