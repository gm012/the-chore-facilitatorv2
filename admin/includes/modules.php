<?php
// Table/column identifiers come only from this server-owned allowlist, never requests.
function modules(): array {return [
 'clients'=>['table'=>'clients','title'=>'Clients','prefix'=>'CLI','note'=>'client_id','statuses'=>['New','Active','Inactive','Archived'],'fields'=>['full_name'=>'Name','email'=>'Email','phone'=>'Phone','alternative_contact'=>'Alternative contact','area'=>'Area / suburb','address'=>'Full address (only if needed)','status'=>'Status']],
 'requests'=>['table'=>'client_requests','title'=>'Client Requests','prefix'=>'REQ','note'=>'request_id','statuses'=>['New','Reviewing','Brief Confirmed','Candidate Search','Candidates Presented','Placement Pending','Placement Confirmed','Closed','Archived'],'fields'=>['client_id'=>'Client','role'=>'Role required','desired_start_date'=>'Desired start date','status'=>'Status']],
 'applications'=>['table'=>'candidate_applications','title'=>'Applications','prefix'=>'APP','note'=>'application_id','statuses'=>['New','Reviewing','Contacted','Verification Required','Approved','Rejected','Archived'],'fields'=>['full_name'=>'Name','email'=>'Email','phone'=>'Phone','area'=>'Area / suburb','status'=>'Status']],
 'candidates'=>['table'=>'candidates','title'=>'Candidates','prefix'=>'CAN','note'=>'candidate_id','statuses'=>['Available','Being Considered','Placement Pending','Placed','Unavailable','Inactive','Archived'],'fields'=>['full_name'=>'Name','email'=>'Email','phone'=>'Phone','area'=>'Area / suburb','experience'=>'Experience','languages'=>'Languages','availability'=>'Availability','living_preference'=>'Live-in / Live-out','drivers_license'=>'Driver licence','own_vehicle'=>'Own vehicle','first_aid'=>'First Aid','references_available'=>'References available','status'=>'Status']],
 'placements'=>['table'=>'placements','title'=>'Placements','prefix'=>'PLC','note'=>'placement_id','statuses'=>['New','Candidate Selected','Agreement Pending','Confirmed','Active','Completed','Cancelled','Archived'],'fields'=>['client_request_id'=>'Client request','candidate_id'=>'Candidate','placement_type'=>'Placement type','start_date'=>'Start date','status'=>'Status']],
 'enquiries'=>['table'=>'enquiries','title'=>'Enquiries','prefix'=>'ENQ','note'=>'enquiry_id','statuses'=>['New','Read','Responded','Closed','Archived'],'fields'=>['full_name'=>'Name','email'=>'Email','phone'=>'Phone','enquiry_type'=>'Enquiry type','subject'=>'Subject','message'=>'Message','status'=>'Status']],
];}
function module(string $name): array {return modules()[$name]??throw new ValidationError('Unknown section.');}
function record(string $module,int $id,bool $lock=false): array {$m=module($module);return query('SELECT * FROM '.$m['table'].' WHERE id=?'.($lock?' FOR UPDATE':''),[$id])->fetch()?:throw new ValidationError('Record not found.');}
function reference(string $module,int $id): string {return module($module)['prefix'].'-'.str_pad((string)$id,6,'0',STR_PAD_LEFT);}
function match_statuses(): array {return ['Considering','Contacted','Presented to Client','Interview / Discussion','Not Suitable','Selected'];}
function field_options(string $field,string $module): ?array {return match($field){
 'status'=>module($module)['statuses'],'role'=>array_merge(role_names(),['To Confirm']),
 'placement_type'=>['Direct Placement','Chore Payroll'],'living_preference'=>['','Live-in','Live-out','Either'],
 'drivers_license','own_vehicle'=>['Unknown','Yes','No'],'first_aid'=>['Unknown','Yes','No','Expired'],'references_available'=>['Unknown','Yes','No','Some'],
 'enquiry_type'=>['client-placement','candidate-application','payroll','general'],default=>null};}
