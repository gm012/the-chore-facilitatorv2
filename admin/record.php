<?php
require __DIR__.'/includes/bootstrap.php';$user=require_user();require __DIR__.'/includes/records.php';require __DIR__.'/includes/layout.php';
$error='';
try{$name=text_value($_GET,'module',30,true);$m=module($name);$id=isset($_GET['id'])?id_value($_GET['id']):null;$row=$id?record($name,$id):[];}catch(ValidationError $ex){http_response_code(404);exit(e($ex->getMessage()));}
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();try{
  $action=text_value($_POST,'action',30,true);
  if($action==='save')$id=save_record($name,$id,$_POST);
  elseif($action==='note'&&$id)add_note($name,$id,text_value($_POST,'body',5000,true),(int)$user['id']);
  elseif($action==='match'&&$id&&$name==='requests')add_match($id,id_value($_POST['candidate_id']??''),(int)$user['id']);
  elseif($action==='match_status'&&$id&&$name==='requests'){
   transaction(function()use($id){$match=query('SELECT * FROM client_candidate_matches WHERE id=? AND client_request_id=? FOR UPDATE',[id_value($_POST['match_id']??''),$id])->fetch();if(!$match)throw new ValidationError('Consideration record not found.');if((int)($_POST['version']??0)!==(int)$match['version'])throw new ValidationError('This consideration changed. Reload first.');$status=enum_value($_POST['match_status']??'',match_statuses());query('UPDATE client_candidate_matches SET status=?,version=version+1 WHERE id=?',[$status,$match['id']]);history('matches',(int)$match['id'],$match['status'],$status);audit('consideration_updated','requests',$id);});
  }else throw new ValidationError('Invalid action.');
  $_SESSION['flash']='Changes saved.';redirect('admin/record.php?module='.$name.'&id='.$id);
 }catch(ValidationError $ex){$error=$ex->getMessage();}
}
if($id)$row=record($name,$id);$values=$error&&($_POST['action']??'')==='save'?array_replace($row,$_POST):$row;
page_header($id?reference($name,$id):'Create '.rtrim($m['title'],'s'),$user);
echo '<p><a href="'.e(url('admin/list.php?module='.$name)).'">← '.$m['title'].'</a></p>';
if($error)echo '<p class="notice error" role="alert">'.e($error).'</p>';
if($id)echo '<p class="muted">Created '.e($row['created_at']).' · Updated '.e($row['updated_at']).' UTC</p>';
if(isset($row['email'])){$duplicates=(int)query('SELECT COUNT(*) FROM '.$m['table'].' WHERE email=? AND id<>?',[$row['email'],$id])->fetchColumn();if($duplicates)echo '<p class="notice">'.$duplicates.' other record(s) share this email. Review before creating another profile; records are not merged automatically.</p>';}
if($name==='requests'&&$id)echo '<p>Client: '.record_link('clients',(int)$row['client_id']).'</p>';
if($name==='placements'&&$id)echo '<p>Request: '.record_link('requests',(int)$row['client_request_id']).' · Candidate: '.record_link('candidates',(int)$row['candidate_id']).'</p>';
if($name==='applications'&&$id){$candidate=query('SELECT id FROM candidates WHERE application_id=?',[$id])->fetchColumn();echo $candidate?'<p>Approved candidate: '.record_link('candidates',(int)$candidate).'</p>':'<p class="notice">Changing status to Approved creates a separate candidate profile. It does not guarantee a placement.</p>';}
echo '<form method="post" class="panel">'.csrf_input().'<input type="hidden" name="action" value="save"><input type="hidden" name="version" value="'.e($row['version']??0).'"><div class="grid">';
foreach($m['fields'] as $field=>$label){
 $value=$values[$field]??($_GET[$field]??'');$options=field_options($field,$name);
 if(in_array($field,['client_id','client_request_id','candidate_id'],true)){if($id){echo '<p>'.e($label).' #'.e($row[$field]).' (linked record)</p>';continue;}input_field($field,$label.' numeric ID',$value,'number',true);continue;}
 if($options!==null){select_field($field,$label,$options,$value?:$options[0]);continue;}
 $type=match($field){'address','experience','message'=>'textarea','email'=>'email','phone'=>'tel','desired_start_date','start_date'=>'date',default=>'text'};input_field($field,$label,$value,$type,in_array($field,['full_name','email','subject','message'],true));
}
echo '</div>';
if($name==='candidates'){$roles=$error?($_POST['roles']??[]):($id?query('SELECT role FROM candidate_roles WHERE candidate_id=?',[$id])->fetchAll(PDO::FETCH_COLUMN):[]);echo '<fieldset><legend>Candidate roles</legend>';foreach(role_names() as $role)echo '<label class="check"><input type="checkbox" name="roles[]" value="'.e($role).'"'.(is_array($roles)&&in_array($role,$roles,true)?' checked':'').'>'.e($role).'</label>';echo '</fieldset>';}
if(in_array($name,['requests','applications'],true)){
 $kind=$name==='requests'?'client':'candidate';$details=$row?json_decode($row['details_json'],true):[];if($error&&is_array($_POST['detail']??null))$details=array_replace($details,$_POST['detail']);
 echo '<details'.(!$id?' open':'').'><summary>Questionnaire details</summary><p class="muted">Keep medical details and identity numbers out of free text. Discuss sensitive considerations privately.</p><div class="grid">';
 foreach(intake_schema($kind) as $field){$key=str_replace('[]','',$field['name']);if(in_array($key,['full_name','email','contact_number','area','residential_area','privacy_consent','service_required','preferred_start_date'],true))continue;$label=ucwords(str_replace('_',' ',$key));$v=$details[$key]??'';
  if(str_ends_with($field['name'],'[]')){echo '<fieldset><legend>'.e($label).'</legend>';foreach($field['allowed'] as $option)echo '<label class="check"><input type="checkbox" name="detail['.e($key).'][]" value="'.e($option).'"'.(is_array($v)&&in_array($option,$v,true)?' checked':'').'>'.e(public_role($option)).'</label>';echo '</fieldset>';continue;}
  if(isset($field['allowed']))select_field('detail['.$key.']',$label,array_merge([''],$field['allowed']),$v);else input_field('detail['.$key.']',$label,$v,in_array($field['type'],['textarea','date','number','tel'],true)?$field['type']:'text',$field['required']);
 }echo '</div></details>';
}
echo '<button>Save record</button></form>';
require __DIR__.'/includes/record-related.php';
if($name==='requests'&&$id)require __DIR__.'/includes/request-history.php';
page_footer();
