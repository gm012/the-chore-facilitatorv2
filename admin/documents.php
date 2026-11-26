<?php
require __DIR__.'/includes/bootstrap.php';$user=require_user(true);require __DIR__.'/includes/modules.php';require __DIR__.'/includes/layout.php';$error='';
$types=['CV','ID','Passport','Certificate','First Aid','Reference','Driver licence','Other'];
if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();try{
 $action=text_value($_POST,'action',20,true);
 if($action==='upload'){
  if(!rate_limit('upload:'.$user['id'],30,3600))throw new ValidationError('Upload limit reached. Try again later.');
  $candidate=id_value($_POST['candidate_id']??'');record('candidates',$candidate);$type=enum_value($_POST['document_type']??'',$types);$file=$_FILES['document']??[];
  if(!isset($file['error'])||is_array($file['error'])||$file['error']!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']))throw new ValidationError('Choose a file and check the server upload limit.');
  if($file['size']<1||$file['size']>8*1024*1024)throw new ValidationError('Files must be no larger than 8 MB.');
  $original=basename(str_replace('\\','/',$file['name']));$extension=strtolower(pathinfo($original,PATHINFO_EXTENSION));$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$allowed=['pdf'=>'application/pdf','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg'];
  if(!isset($allowed[$extension])||$allowed[$extension]!==$mime||preg_match('/[\x00-\x1f\x7f]/',$original)||strlen($original)>190)throw new ValidationError('Only valid PDF, PNG and JPEG files are accepted.');
  if($mime!=='application/pdf'&&!getimagesize($file['tmp_name']))throw new ValidationError('Invalid image file.');
  $stored=bin2hex(random_bytes(32));$destination=private_path('documents/'.$stored);
  if(!move_uploaded_file($file['tmp_name'],$destination))throw new RuntimeException('Private file write failed');chmod($destination,0600);
  try{transaction(function()use($candidate,$user,$type,$original,$stored,$mime,$file){query('INSERT INTO documents(candidate_id,uploaded_by,document_type,original_name,stored_name,mime_type,size_bytes) VALUES(?,?,?,?,?,?,?)',[$candidate,$user['id'],$type,$original,$stored,$mime,$file['size']]);audit('document_uploaded','documents',(int)db()->lastInsertId());});}catch(Throwable $ex){unlink($destination);throw $ex;}
 }elseif($action==='archive'){
  $doc=id_value($_POST['id']??'');transaction(function()use($doc){$d=query('SELECT id FROM documents WHERE id=? FOR UPDATE',[$doc])->fetch();if(!$d)throw new ValidationError('Document not found.');query('UPDATE documents SET archived=1 WHERE id=?',[$doc]);audit('document_archived','documents',$doc);});
 }else throw new ValidationError('Invalid action.');
 $_SESSION['flash']='Document action completed.';redirect('admin/documents.php');
}catch(ValidationError $ex){$error=$ex->getMessage();}}
$candidate=is_string($_GET['candidate_id']??null)&&ctype_digit($_GET['candidate_id'])?(int)$_GET['candidate_id']:0;$page=max(1,(int)($_GET['page']??1));$where=$candidate?' WHERE d.candidate_id=?':'';
$rows=query('SELECT d.*,c.full_name FROM documents d JOIN candidates c ON c.id=d.candidate_id'.$where.' ORDER BY d.id DESC LIMIT 25 OFFSET '.(($page-1)*25),$candidate?[$candidate]:[])->fetchAll();
page_header('Private documents',$user);if($error)echo '<p class="notice error">'.e($error).'</p>';echo '<p>Super Admin access only. Files are private and downloaded as attachments. Upload only necessary, authorised documents.</p><form method="get" class="filters">';input_field('candidate_id','Filter by candidate ID',$candidate?:'','number');echo '<button>Filter</button></form><div class="table-wrap" tabindex="0" role="region" aria-label="Scrollable records"><table><thead><tr><th>Candidate</th><th>Type</th><th>File</th><th>Status</th><th>Action</th></tr></thead><tbody>';
foreach($rows as $d)echo '<tr><td>'.record_link('candidates',(int)$d['candidate_id'],$d['full_name']).'</td><td>'.e($d['document_type']).'</td><td><a href="'.e(url('admin/download.php?id='.$d['id'])).'">'.e($d['original_name']).'</a> ('.round($d['size_bytes']/1024).' KB)</td><td>'.($d['archived']?'Archived':'Active').'</td><td>'.(!$d['archived']?'<form method="post" data-confirm="Archive this document?">'.csrf_input().'<input type="hidden" name="action" value="archive"><input type="hidden" name="id" value="'.(int)$d['id'].'"><button>Archive</button></form>':'').'</td></tr>';
echo '</tbody></table></div><p><a href="?'.e(http_build_query(['candidate_id'=>$candidate,'page'=>$page+1])).'">Next page</a></p><section class="panel"><h2>Upload supporting document</h2><form method="post" enctype="multipart/form-data">'.csrf_input().'<input type="hidden" name="action" value="upload">';input_field('candidate_id','Candidate ID',$candidate?:'','number',true);select_field('document_type','Document type',$types);echo '<label class="field">PDF / PNG / JPEG, maximum 8 MB<input type="file" name="document" accept=".pdf,.png,.jpg,.jpeg" required></label><button>Upload privately</button></form></section>';page_footer();
