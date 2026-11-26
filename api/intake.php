<?php
declare(strict_types=1);
define('TCF_JSON',true);
require dirname(__DIR__).'/admin/includes/bootstrap.php';
require TCF_ROOT.'/admin/includes/intake.php';
try {
 $method=$_SERVER['REQUEST_METHOD']??'';
 if(!in_array($method,['GET','POST'],true)){header('Allow: GET, POST');json_response(405,['ok'=>false,'message'=>'Method not allowed.']);}
 $kind=$method==='GET'?($_GET['form']??''):($_POST['form_type']??'');
 if(!is_string($kind)||!in_array($kind,['client','candidate','contact'],true)||(isset($expected_kind)&&$expected_kind!==$kind))throw new ValidationError('Invalid form.');
 start_session(false);
 if($method==='GET'){
  query('SELECT 1');$_SESSION['form_started'][$kind]??=time();
  json_response(200,['ready'=>true,'token'=>csrf_token()]);
 }
 if((int)($_SERVER['CONTENT_LENGTH']??0)>65536)json_response(413,['ok'=>false,'message'=>'Please shorten your submission.']);
 $base=parse_url((string)config('base_url'));$origin=$base['scheme'].'://'.$base['host'].(isset($base['port'])?':'.$base['port']:'');
 if(($_SERVER['HTTP_ORIGIN']??'')!==$origin)json_response(403,['ok'=>false,'message'=>'Please submit from the website.']);
 check_csrf();
 if(!empty($_FILES)||!empty($_POST['website']))throw new ValidationError('Unable to accept this submission.');
 if(time()-($_SESSION['form_started'][$kind]??time())<2)throw new ValidationError('Please take a moment to review the form and try again.');
 if(!rate_limit('public:'.request_ip(),10,900)){header('Retry-After: 900');json_response(429,['ok'=>false,'message'=>'Too many attempts. Please try again in fifteen minutes.']);}
 $data=validate_intake($kind,$_POST);
 $key=hash_hmac('sha256',session_id().'|'.$kind.'|'.json_encode($data),(string)config('app_key'));
 store_intake($kind,$data,$key);
 json_response(200,['ok'=>true,'success'=>true,'message'=>'Thank you. Your information has been received.']);
}catch(ValidationError $error){json_response(422,['ok'=>false,'success'=>false,'message'=>$error->getMessage()]);}
