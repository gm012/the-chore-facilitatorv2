<?php
declare(strict_types=1);
ini_set('display_errors','0');error_reporting(E_ALL);date_default_timezone_set('UTC');
define('TCF_ROOT',dirname(__DIR__,2));
set_exception_handler(function(Throwable $error): void {
 if(function_exists('db_ready')&&db_ready()&&db()->inTransaction())db()->rollBack();
 $id=bin2hex(random_bytes(6));error_log('TCF error '.$id.' '.get_class($error).' code '.$error->getCode());
 if(PHP_SAPI==='cli'){fwrite(STDERR,"Operation failed. Reference $id\n");exit(1);}
 http_response_code(503);
 if(defined('TCF_JSON')){header('Content-Type: application/json');echo json_encode(['ok'=>false,'success'=>false,'message'=>'Something went wrong. Please try again. Reference '.$id]);}
 else{header('Content-Type: text/html; charset=utf-8');echo '<!doctype html><html lang="en"><meta name="robots" content="noindex"><title>Temporarily unavailable</title><h1>Temporarily unavailable</h1><p>Please try again or contact your administrator. Reference '.htmlspecialchars($id).'</p></html>';}
 exit;
});
$config=require TCF_ROOT.'/server-config.php';
function config(string $key): mixed {global $config;return $config[$key]??null;}
function private_path(string $child=''): string {
 static $base;
 if(!$base){
  $base=realpath((string)config('private_dir'));$root=realpath($_SERVER['DOCUMENT_ROOT']??TCF_ROOT);
  $normal=fn($p)=>strtolower(rtrim(str_replace('\\','/',(string)$p),'/'));
  if(!$base||!is_dir($base)||$normal($base)===$normal($root)||str_starts_with($normal($base).'/', $normal($root).'/')||str_starts_with($normal($base).'/', $normal(TCF_ROOT).'/'))throw new RuntimeException('Private storage unavailable');
  umask(0077);foreach(['sessions','rate-limits','documents','logs'] as $dir){$p=$base.'/'.$dir;if(!is_dir($p)&&!mkdir($p,0700,true)&&!is_dir($p))throw new RuntimeException('Private storage unavailable');if(is_link($p))throw new RuntimeException('Unsafe private storage');}
  ini_set('log_errors','1');ini_set('error_log',$base.'/logs/php-errors.log');
 }return $base.($child!==''?'/'.$child:'');
}
if(!config('enabled')||!preg_match('/^[a-f0-9]{64}$/D',(string)config('app_key')))throw new RuntimeException('Application not configured');
private_path();
function is_local(): bool {return config('environment')==='local'&&(PHP_SAPI==='cli'||in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true));}
function url(string $path=''): string {return rtrim((string)config('base_url'),'/').'/'.ltrim($path,'/');}
function e(mixed $value): string {return htmlspecialchars((string)($value??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function redirect(string $path): never {header('Location: '.url($path),true,303);exit;}
function json_response(int $status,array $data): never {http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($data,JSON_INVALID_UTF8_SUBSTITUTE);exit;}
if(PHP_SAPI!=='cli'){
 header('Cache-Control: no-store, private');header('X-Content-Type-Options: nosniff');header('X-Robots-Tag: noindex, nofollow');header('Referrer-Policy: no-referrer');
 if(!is_local()&&($_SERVER['HTTPS']??'')!=='on')throw new RuntimeException('HTTPS required');
}
foreach(['db','csrf','validation','audit','auth','mail'] as $include)require_once __DIR__.'/'.$include.'.php';
