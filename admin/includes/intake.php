<?php
function intake_schema(string $kind): array {
 $all=json_decode(file_get_contents(TCF_ROOT.'/form-schema.json'),true,512,JSON_THROW_ON_ERROR);
 if(!isset($all[$kind]))throw new ValidationError('Invalid form.');return $all[$kind];
}
function validate_intake(string $kind,array $input): array {
 $out=[];
 foreach(intake_schema($kind) as $field){
  $array=str_ends_with($field['name'],'[]');$name=$array?substr($field['name'],0,-2):$field['name'];
  if($array){
   $values=$input[$name]??[];if(!is_array($values)||count($values)>8)throw new ValidationError('Check your role selections.');
   $out[$name]=array_values(array_unique(array_map(fn($v)=>enum_value($v,$field['allowed']),$values)));
   if(($field['required']??false)&&!$out[$name])throw new ValidationError('Choose at least one role.');continue;
  }
  $value=text_value($input,$name,$field['max']??($field['type']==='textarea'?5000:255),$field['required']);
  if(isset($field['allowed'])&&$value!=='')enum_value($value,$field['allowed']);
  if($field['type']==='email')$value=email_value($value);
  if($field['type']==='tel')$value=phone_value($value,$field['required']);
  if($field['type']==='date')date_value($value);
  if($field['type']==='number'&&$value!==''&&(!ctype_digit($value)||(int)$value>30))throw new ValidationError('Check the number of children.');
  $out[$name]=$value;
 }
 if(($out['privacy_consent']??'')!=='on')throw new ValidationError('Please confirm the privacy notice.');
 return $out;
}
function store_intake(string $kind,array $data,string $key): int {
 return transaction(function()use($kind,$data,$key){
  // The unique receipt prevents duplicate records on browser retries, even if an
  // earlier response was lost. Different sessions have different keyed receipts.
  query('INSERT IGNORE INTO submission_receipts(idempotency_hash,form_type) VALUES(?,?)',[$key,$kind]);
  $receipt=query('SELECT entity_id FROM submission_receipts WHERE idempotency_hash=? FOR UPDATE',[$key])->fetch();
  if($receipt['entity_id'])return (int)$receipt['entity_id'];
  $details=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
  if($kind==='client'){
   // Never merge solely on an unauthenticated email: that could overwrite another household.
   $alternative=trim(($data['alternative_contact_name']??'').' '.($data['alternative_contact_number']??''));
   if(mb_strlen($alternative)>255)throw new ValidationError('Please shorten the alternative contact name.');
   query('INSERT INTO clients(full_name,email,phone,area,alternative_contact) VALUES(?,?,?,?,?)',[$data['full_name'],$data['email'],$data['contact_number'],$data['residential_area'],$alternative]);
   $client=(int)db()->lastInsertId();history('clients',$client,null,'New');audit('public_client_created','clients',$client);
   query('INSERT INTO client_requests(client_id,role,desired_start_date,details_json) VALUES(?,?,?,?)',[$client,public_role($data['service_required']),date_value($data['preferred_start_date']??''),$details]);$entity='requests';
  }elseif($kind==='candidate'){
   $roles=array_map('public_role',$data['roles']);
   query('INSERT INTO candidate_applications(full_name,email,phone,area,roles_json,details_json) VALUES(?,?,?,?,?,?)',[$data['full_name'],$data['email'],$data['contact_number'],$data['area'],json_encode($roles),$details]);$entity='applications';
  }else{
   query('INSERT INTO enquiries(full_name,email,phone,enquiry_type,subject,message) VALUES(?,?,?,?,?,?)',[$data['full_name'],$data['email'],$data['contact_number'],$data['enquiry_type'],$data['subject'],$data['message']]);$entity='enquiries';
  }
  $id=(int)db()->lastInsertId();history($entity,$id,null,'New');audit('public_submission',$entity,$id);queue_notification($entity,$id);
  query('UPDATE submission_receipts SET entity_id=? WHERE idempotency_hash=?',[$id,$key]);return $id;
 });
}
