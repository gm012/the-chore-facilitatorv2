<?php
function audit(string $action,string $entity,int|null $id=null,?int $actor=null): void {$actor=$actor??($_SESSION['admin_id']??null);query('INSERT INTO audit_logs(admin_user_id,action,entity_type,entity_id,ip_hash) VALUES(?,?,?,?,?)',[$actor,$action,$entity,$id,hash_hmac('sha256',request_ip(),(string)config('app_key'))]);}
function history(string $entity,int $id,?string $old,string $new): void {if($old===$new)return;query('INSERT INTO status_history(entity_type,entity_id,old_status,new_status,admin_user_id) VALUES(?,?,?,?,?)',[$entity,$id,$old,$new,$_SESSION['admin_id']??null]);}
