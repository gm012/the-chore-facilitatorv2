<?php
echo '<section class="panel"><h2>Placements for this request</h2><ul>';
foreach(query('SELECT p.id,p.status,p.placement_type,c.full_name FROM placements p JOIN candidates c ON c.id=p.candidate_id WHERE p.client_request_id=? ORDER BY p.id DESC LIMIT 100',[$id])->fetchAll() as $placement){
 echo '<li>'.record_link('placements',(int)$placement['id']).' · '.e($placement['full_name']).' · '.e($placement['placement_type']).' · '.e($placement['status']).'</li>';
}
echo '</ul><h2>Candidate consideration history</h2><ul>';
foreach(query("SELECT h.*,c.full_name AS candidate_name,u.full_name AS staff_name FROM status_history h JOIN client_candidate_matches m ON h.entity_type='matches' AND h.entity_id=m.id JOIN candidates c ON c.id=m.candidate_id LEFT JOIN admin_users u ON u.id=h.admin_user_id WHERE m.client_request_id=? ORDER BY h.id DESC LIMIT 100",[$id])->fetchAll() as $event){
 echo '<li>'.e($event['created_at']).' UTC · '.e($event['candidate_name']).' · '.e($event['old_status']??'Added').' → '.e($event['new_status']).' · '.e($event['staff_name']??'System').'</li>';
}
echo '</ul><p class="muted">Latest 100 entries per section. Earlier history remains in the database.</p></section>';
