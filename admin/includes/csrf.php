<?php
function start_session(bool $admin=true): void {
 if(session_status()===PHP_SESSION_ACTIVE)return;
 ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.gc_maxlifetime','28800');session_save_path(private_path('sessions'));session_name($admin?'tcf_admin':'tcf_form_session');
 $base=parse_url((string)config('base_url'),PHP_URL_PATH)?:'';session_set_cookie_params(['lifetime'=>0,'path'=>rtrim($base,'/').($admin?'/admin/':'/'),'secure'=>!is_local(),'httponly'=>true,'samesite'=>'Strict']);if(!session_start())throw new RuntimeException('Session unavailable');
}
function csrf_token(): string {if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function csrf_input(): string {return '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">';}
function check_csrf(): void {$value=$_POST['csrf_token']??'';if(!is_string($value)||!hash_equals(csrf_token(),$value)){http_response_code(403);if(defined('TCF_JSON'))json_response(403,['ok'=>false,'message'=>'Your session expired. Please refresh and try again.']);exit('Your session expired. Reload this page and try again.');}}
function rate_limit(string $bucket,int $max=10,int $seconds=900): bool {
 $file=private_path('rate-limits/'.hash_hmac('sha256',$bucket,(string)config('app_key')).'.json');$f=fopen($file,'c+');if(!$f||!flock($f,LOCK_EX))throw new RuntimeException('Rate limiter unavailable');$data=json_decode(stream_get_contents($f)?:'{}',true)?:[];if(time()-($data['start']??0)>=$seconds)$data=['start'=>time(),'count'=>0];$allowed=$data['count']<$max;$data['count']++;rewind($f);ftruncate($f,0);fwrite($f,json_encode($data));fflush($f);flock($f,LOCK_UN);fclose($f);return $allowed;
}
function request_ip(): string {return $_SERVER['REMOTE_ADDR']??'cli';}
