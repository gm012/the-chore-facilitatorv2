<?php
function page_header(string $title,?array $user=null): void {
 $GLOBALS['tcf_shell_open']=(bool)$user;
 header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
 header('X-Frame-Options: DENY');
 echo '<!doctype html><html lang="en-ZA"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>'.e($title).' | TCF Admin</title><link rel="stylesheet" href="'.e(url('admin/assets/admin.css')).'"></head><body><a class="skip" href="#content">Skip to content</a>';
 if($user){
  echo '<header class="top"><a href="'.e(url('admin/dashboard.php')).'">THE CHORE FACILITATOR <small>Placement desk</small></a><span>'.e($user['full_name']).' · '.e(str_replace('_',' ',$user['role'])).'</span><form method="post" action="'.e(url('admin/logout.php')).'">'.csrf_input().'<button>Log out</button></form></header><div class="shell"><nav class="sidebar" aria-label="Admin navigation">';
  echo '<a href="'.e(url('admin/dashboard.php')).'">Dashboard</a>';
  foreach(modules() as $name=>$m)echo '<a href="'.e(url('admin/list.php?module='.$name)).'">'.e($m['title']).'</a>';
  if($user['role']==='super_admin')foreach(['documents'=>'Documents','users'=>'Users','audit'=>'Audit log','settings'=>'Settings'] as $name=>$label)echo '<a href="'.e(url('admin/'.$name.'.php')).'">'.$label.'</a>';
  echo '<a href="'.e(url('admin/password.php')).'">My password</a></nav>';
 }
 echo '<main id="content" class="'.($user?'workspace':'login').'" tabindex="-1"><h1>'.e($title).'</h1>';
 if(isset($_SESSION['flash'])){echo '<p class="notice" role="status">'.e($_SESSION['flash']).'</p>';unset($_SESSION['flash']);}
}
function page_footer(): void {echo '</main>'.(!empty($GLOBALS['tcf_shell_open'])?'</div>':'').'<script src="'.e(url('admin/assets/admin.js')).'" defer></script></body></html>';}
function input_field(string $name,string $label,mixed $value='',string $type='text',bool $required=false): void {
 echo '<label class="field">'.e($label);
 if($type==='textarea')echo '<textarea name="'.e($name).'" rows="4" maxlength="5000"'.($required?' required':'').'>'.e($value).'</textarea>';
 else echo '<input name="'.e($name).'" type="'.e($type).'" value="'.e($value).'"'.($required?' required':'').' maxlength="'.($type==='password'?'72':'255').'"'.($type==='password'?' autocomplete="new-password"':'').'>';
 echo '</label>';
}
function select_field(string $name,string $label,array $choices,mixed $value=''): void {echo '<label class="field">'.e($label).'<select name="'.e($name).'">';foreach($choices as $key=>$text){$v=array_is_list($choices)&&$name!=='active'?$text:$key;echo '<option value="'.e($v).'"'.((string)$v===(string)$value?' selected':'').'>'.e($text===''?'Not specified':$text).'</option>';}echo '</select></label>';}
function record_link(string $module,int $id,string $label=''): string {return '<a href="'.e(url('admin/record.php?module='.$module.'&id='.$id)).'">'.e($label?:reference($module,$id)).'</a>';}
