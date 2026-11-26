<?php
function send_mail(string $to,string $subject,string $body): bool {if(!config('mail_enabled'))return false;if(!filter_var($to,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$to)||!filter_var(config('sender'),FILTER_VALIDATE_EMAIL))return false;return @mail($to,$subject,$body,['From'=>'The Chore Facilitator <'.config('sender').'>','MIME-Version'=>'1.0','Content-Type'=>'text/plain; charset=UTF-8']);}
function queue_notification(string $entity,int $id): void {query('INSERT INTO notification_queue(event_type,entity_id) VALUES(?,?)',[$entity,$id]);}
