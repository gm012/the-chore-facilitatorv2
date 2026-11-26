<?php
require_once __DIR__.'/modules.php';require_once __DIR__.'/intake.php';
function change_status(string $module,int $id,string $status): void {
 $m=module($module);enum_value($status,$m['statuses']);$old=record($module,$id,true);
 if($old['status']===$status)return;
 query('UPDATE '.$m['table'].' SET status=?,version=version+1 WHERE id=?',[$status,$id]);history($module,$id,$old['status'],$status);audit('status_changed',$module,$id);
}
function candidate_from_application(int $id): int {
 $app=record('applications',$id,true);
 $existing=query('SELECT id FROM candidates WHERE application_id=?',[$id])->fetchColumn();if($existing)return (int)$existing;
 $d=json_decode($app['details_json'],true);$yesNo=fn($v)=>['yes'=>'Yes','no'=>'No','expired'=>'Expired','some'=>'Some'][$v??'']??'Unknown';
 query('INSERT INTO candidates(application_id,full_name,email,phone,area,experience,languages,availability,living_preference,drivers_license,own_vehicle,first_aid,references_available) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)',[$id,$app['full_name'],$app['email'],$app['phone'],$app['area'],$d['experience_summary']??'',$d['languages']??'',$d['availability']??'',['live-in'=>'Live-in','live-out'=>'Live-out','either'=>'Either'][$d['preferred_arrangement']??'']??'',$yesNo($d['drivers_license']??''),$yesNo($d['own_vehicle']??''),$yesNo($d['first_aid']??''),$yesNo($d['references_available']??'')]);
 $candidate=(int)db()->lastInsertId();foreach(json_decode($app['roles_json'],true) as $role){enum_value($role,role_names());query('INSERT INTO candidate_roles(candidate_id,role) VALUES(?,?)',[$candidate,$role]);}
 history('candidates',$candidate,null,'Available');audit('application_approved','applications',$id);audit('candidate_created','candidates',$candidate);return $candidate;
}
function save_record(string $name,?int $id,array $input): int {
 $m=module($name);
 return transaction(function()use($name,$id,$input,$m){
  $old=$id?record($name,$id,true):null;
  if($old&&(int)($input['version']??0)!==(int)$old['version'])throw new ValidationError('This record changed in another session. Reload before saving.');
  if(!$old&&in_array($name,['applications','enquiries'],true))throw new ValidationError('These records originate from public submissions.');
  $data=[];
  foreach($m['fields'] as $field=>$label){
   if(in_array($field,['client_id','client_request_id','candidate_id'],true)){
    $data[$field]=$old?$old[$field]:id_value($input[$field]??'');
    record(['client_id'=>'clients','client_request_id'=>'requests','candidate_id'=>'candidates'][$field],(int)$data[$field],true);continue;
   }
   $options=field_options($field,$name);
   $max=match($field){'full_name','area','availability'=>160,'email'=>190,'phone'=>40,'address','experience','message'=>5000,default=>255};
   $value=text_value($input,$field,$max,in_array($field,['full_name','email','subject','message'],true));
   if($options!==null)$value=enum_value($value,$options);
   if($field==='email')$value=email_value($value);
   if($field==='phone')$value=phone_value($value,$name==='candidates');
   if(str_ends_with($field,'date'))$value=date_value($value);
   $data[$field]=$value;
  }
  if(in_array($name,['requests','applications'],true)){
   $kind=$name==='requests'?'client':'candidate';
   $details=$old?json_decode($old['details_json'],true):[];
   if(isset($input['detail'])&&!is_array($input['detail']))throw new ValidationError('Invalid questionnaire fields.');
   $details=array_replace($details,$input['detail']??[]);
   if($name==='applications')$details['roles']=$input['detail']['roles']??[];
   if($name==='requests'){$client=record('clients',(int)$data['client_id']);$details=array_replace($details,['full_name'=>$client['full_name'],'email'=>$client['email'],'contact_number'=>$client['phone'],'residential_area'=>$client['area'],'privacy_consent'=>'on']);$details['service_required']=array_flip(['nanny'=>'Nanny','domestic'=>'Domestic','night-nurse'=>'Night Nurse','housekeeper'=>'Housekeeper','not-sure'=>'To Confirm'])[$data['role']];$details['preferred_start_date']=$data['desired_start_date']??'';}
   else{$details=array_replace($details,['full_name'=>$data['full_name'],'email'=>$data['email'],'contact_number'=>$data['phone'],'area'=>$data['area'],'privacy_consent'=>'on']);}
   $details=validate_intake($kind,$details);$data['details_json']=json_encode($details,JSON_THROW_ON_ERROR);
   if($name==='applications')$data['roles_json']=json_encode(array_map('public_role',$details['roles']));
  }
  if($name==='candidates'){
   $roles=$input['roles']??[];if(!is_array($roles)||!$roles||count($roles)>4)throw new ValidationError('Choose at least one candidate role.');foreach($roles as $r)enum_value($r,role_names());
  }
  if($name==='placements'){
   $candidate=record('candidates',(int)$data['candidate_id'],true);$request=record('requests',(int)$data['client_request_id'],true);
   if(!$old&&(in_array($candidate['status'],['Inactive','Archived','Unavailable'],true)||in_array($request['status'],['Closed','Archived'],true)))throw new ValidationError('Choose an active request and available candidate.');
   if(in_array($data['status'],['Confirmed','Active'],true)){
    $conflict=query("SELECT id FROM placements WHERE (candidate_id=? OR client_request_id=?) AND status IN ('Confirmed','Active') AND id<>?",[$data['candidate_id'],$data['client_request_id'],$id??0])->fetchColumn();
    if($conflict)throw new ValidationError('This candidate or request already has a confirmed or active placement. Resolve that placement first.');
    if(in_array($candidate['status'],['Archived','Inactive','Unavailable'],true)||in_array($request['status'],['Closed','Archived'],true))throw new ValidationError('Reopen the candidate and request before confirming this placement.');
   }
   if(!$old&&query('SELECT id FROM placements WHERE client_request_id=? AND candidate_id=?',[$data['client_request_id'],$data['candidate_id']])->fetchColumn())throw new ValidationError('A placement already exists for this request and candidate. Open that record instead.');
  }
  $columns=array_keys($data);
  if($old){query('UPDATE '.$m['table'].' SET '.implode(',',array_map(fn($c)=>$c.'=?',$columns)).',version=version+1 WHERE id=?',[...array_values($data),$id]);}
  else{query('INSERT INTO '.$m['table'].' ('.implode(',',$columns).') VALUES('.implode(',',array_fill(0,count($columns),'?')).')',array_values($data));$id=(int)db()->lastInsertId();}
  if($name==='candidates'){query('DELETE FROM candidate_roles WHERE candidate_id=?',[$id]);foreach(array_unique($roles) as $r)query('INSERT INTO candidate_roles(candidate_id,role) VALUES(?,?)',[$id,$r]);}
  history($name,$id,$old['status']??null,$data['status']);audit($old?'record_updated':'record_created',$name,$id);
  if($name==='applications'&&$data['status']==='Approved')candidate_from_application($id);
  if($name==='placements'){
   if(!$old||in_array($data['status'],['Candidate Selected','Agreement Pending','Confirmed','Active'],true)){
    $match=query('SELECT * FROM client_candidate_matches WHERE client_request_id=? AND candidate_id=? FOR UPDATE',[$data['client_request_id'],$data['candidate_id']])->fetch();
    if($match){query("UPDATE client_candidate_matches SET status='Selected',version=version+1 WHERE id=?",[$match['id']]);history('matches',(int)$match['id'],$match['status'],'Selected');}
    else{query("INSERT INTO client_candidate_matches(client_request_id,candidate_id,status,created_by) VALUES(?,?,'Selected',?)",[$data['client_request_id'],$data['candidate_id'],$_SESSION['admin_id']]);history('matches',(int)db()->lastInsertId(),null,'Selected');}
   }
   if(in_array($data['status'],['Confirmed','Active'],true)){change_status('candidates',(int)$data['candidate_id'],'Placed');change_status('requests',(int)$data['client_request_id'],'Placement Confirmed');}
   // Closing a placement does not silently make a person available again. Staff review availability.
  }
  return $id;
 });
}
function add_note(string $name,int $id,string $body,int $actor): void {record($name,$id);$m=module($name);$body=text_value(['body'=>$body],'body',5000,true);transaction(function()use($m,$id,$body,$actor,$name){query('INSERT INTO notes('.$m['note'].',admin_user_id,body) VALUES(?,?,?)',[$id,$actor,$body]);audit('note_added',$name,$id);});}
function add_match(int $request,int $candidate,int $actor): void {
 transaction(function()use($request,$candidate,$actor){
  $r=record('requests',$request,true);$c=record('candidates',$candidate,true);
  if(in_array($r['status'],['Archived','Closed'],true)||in_array($c['status'],['Archived','Inactive','Unavailable','Placed'],true))throw new ValidationError('This request or candidate is not available for consideration.');
  $insert=query('INSERT IGNORE INTO client_candidate_matches(client_request_id,candidate_id,created_by) VALUES(?,?,?)',[$request,$candidate,$actor]);
  if($insert->rowCount()){history('matches',(int)db()->lastInsertId(),null,'Considering');audit('candidate_associated','requests',$request);}
 });
}
