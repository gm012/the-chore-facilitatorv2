<?php
class ValidationError extends RuntimeException {}
function text_value(array $input,string $key,int $max=255,bool $required=false): string {$value=$input[$key]??'';if(!is_string($value))throw new ValidationError('Invalid '.str_replace('_',' ',$key).'.');$value=trim($value);if(str_contains($value,"\0")||!preg_match('//u',$value)||mb_strlen($value)>$max||($required&&$value===''))throw new ValidationError('Please check '.str_replace('_',' ',$key).' (maximum '.$max.' characters).');return $value;}
function email_value(string $value): string {if(!filter_var($value,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$value)||strlen($value)>190)throw new ValidationError('Enter a valid email address.');return strtolower($value);}
function phone_value(string $value,bool $required=false): string {if(($required||$value!=='')&&!preg_match('/^[+()0-9 .-]{6,40}$/D',$value))throw new ValidationError('Enter a valid contact number.');return $value;}
function enum_value(mixed $value,array $allowed): string {if(!is_string($value)||!in_array($value,$allowed,true))throw new ValidationError('Please select a valid option.');return $value;}
function date_value(string $value): ?string {if($value==='')return null;$d=DateTimeImmutable::createFromFormat('!Y-m-d',$value);if(!$d||$d->format('Y-m-d')!==$value)throw new ValidationError('Enter a valid date.');return $value;}
function id_value(mixed $value): int {if(!is_scalar($value)||!preg_match('/^[1-9][0-9]{0,17}$/D',(string)$value))throw new ValidationError('Invalid record reference.');return (int)$value;}
function password_value(string $value): string {if(strlen($value)<12||strlen($value)>72)throw new ValidationError('Use a password of 12–72 bytes (a long passphrase is recommended).');return $value;}
function hash_password(string $value): string {return password_hash(password_value($value),defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_BCRYPT);}
function role_names(): array {return ['Nanny','Domestic','Night Nurse','Housekeeper'];}
function public_role(string $value): string {return ['nanny'=>'Nanny','domestic'=>'Domestic','night-nurse'=>'Night Nurse','housekeeper'=>'Housekeeper','not-sure'=>'To Confirm'][$value]??throw new ValidationError('Select a supported role.');}
