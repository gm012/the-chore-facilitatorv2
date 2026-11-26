<?php
require __DIR__.'/includes/bootstrap.php';$user=require_user();require __DIR__.'/includes/modules.php';require __DIR__.'/includes/layout.php';
try{$name=text_value($_GET,'module',30,true);$m=module($name);$search=text_value($_GET,'q',160);$status=text_value($_GET,'status',60);$role=text_value($_GET,'role',40);$area=text_value($_GET,'area',160);$availability=text_value($_GET,'availability',160);$type=text_value($_GET,'placement_type',40);$date=text_value($_GET,'date',10);if($date!=='')date_value($date);}catch(ValidationError $e){http_response_code(400);exit(e($e->getMessage()));}
$page=max(1,min(100000,(int)($_GET['page']??1)));$where=[];$params=[];
if($status!==''){$where[]='t.status=?';$params[]=$status;}
if($search!==''){
 if(in_array($name,['requests','placements'],true)){$where[]=$name==='requests'?'EXISTS(SELECT 1 FROM clients c WHERE c.id=t.client_id AND c.full_name LIKE ?)':'(EXISTS(SELECT 1 FROM client_requests r JOIN clients c ON c.id=r.client_id WHERE r.id=t.client_request_id AND c.full_name LIKE ?) OR EXISTS(SELECT 1 FROM candidates c WHERE c.id=t.candidate_id AND c.full_name LIKE ?))';$params[]='%'.$search.'%';if($name==='placements')$params[]='%'.$search.'%';}
 else{$where[]='(t.full_name LIKE ? OR t.email LIKE ?)';$params[]='%'.$search.'%';$params[]='%'.$search.'%';}
}
if($role!==''&&$name==='candidates'){$where[]='EXISTS(SELECT 1 FROM candidate_roles cr WHERE cr.candidate_id=t.id AND cr.role=?)';$params[]=$role;}
if($role!==''&&$name==='requests'){$where[]='t.role=?';$params[]=$role;}
if($area!==''&&in_array($name,['clients','candidates'],true)){$where[]='t.area LIKE ?';$params[]='%'.$area.'%';}
if($availability!==''&&$name==='candidates'){$where[]='t.availability LIKE ?';$params[]='%'.$availability.'%';}
if($type!==''&&$name==='placements'){$where[]='t.placement_type=?';$params[]=$type;}
if($date!==''){$where[]='t.created_at>=? AND t.created_at<DATE_ADD(?,INTERVAL 1 DAY)';$params[]=$date;$params[]=$date;}
$clause=$where?' WHERE '.implode(' AND ',$where):'';$total=(int)query('SELECT COUNT(*) FROM '.$m['table'].' t'.$clause,$params)->fetchColumn();
$extra=match($name){'requests'=>'(SELECT full_name FROM clients WHERE id=t.client_id) AS display_name','placements'=>'(SELECT c.full_name FROM clients c JOIN client_requests r ON r.client_id=c.id WHERE r.id=t.client_request_id) AS display_name,(SELECT full_name FROM candidates WHERE id=t.candidate_id) AS candidate_name',default=>'t.full_name AS display_name'};
$rows=query('SELECT t.*,'.$extra.' FROM '.$m['table'].' t'.$clause.' ORDER BY t.id DESC LIMIT 25 OFFSET '.(($page-1)*25),$params)->fetchAll();
page_header($m['title'],$user);if(!in_array($name,['applications','enquiries'],true))echo '<p><a class="button" href="'.e(url('admin/record.php?module='.$name)).'">Create record</a></p>';
echo '<form class="filters" method="get"><input type="hidden" name="module" value="'.e($name).'">';input_field('q','Search name / email',$search);select_field('status','Status',array_merge([''],$m['statuses']),$status);
if(in_array($name,['candidates','requests'],true))select_field('role','Role',array_merge([''],role_names()),$role);
if(in_array($name,['candidates','clients'],true))input_field('area','Area',$area);
if($name==='candidates')input_field('availability','Availability',$availability);
if($name==='placements')select_field('placement_type','Placement type',['','Direct Placement','Chore Payroll'],$type);
input_field('date','Created date',$date,'date');echo '<button>Filter</button></form><p class="muted">'.$total.' records · 25 per page</p><div class="table-wrap" tabindex="0" role="region" aria-label="Scrollable records"><table><thead><tr><th>Reference</th><th>Name / household</th><th>Role / details</th><th>Status</th><th>Created (UTC)</th></tr></thead><tbody>';
foreach($rows as $r){echo '<tr><td>'.record_link($name,(int)$r['id']).'</td><td>'.e($r['display_name']).'</td><td>'.e($r['role']??$r['candidate_name']??$r['area']??'').'</td><td><span class="chip">'.e($r['status']).'</span></td><td>'.e($r['created_at']).'</td></tr>';}
if(!$rows)echo '<tr><td colspan="5">No records found.</td></tr>';echo '</tbody></table></div><nav class="pagination" aria-label="Results pages">';foreach(['Previous'=>$page-1,'Next'=>$page+1] as $label=>$p){if($p<1||($p-1)*25>=$total)continue;echo '<a href="'.e(url('admin/list.php?'.http_build_query(array_merge($_GET,['page'=>$p])))).'">'.$label.'</a>';}echo '</nav>';page_footer();
