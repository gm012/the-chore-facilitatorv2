<?php
function db_ready(): bool {return isset($GLOBALS['tcf_pdo']);}
function db(): PDO {
 if(!db_ready()){$dsn='mysql:host='.config('db_host').';port='.(int)config('db_port').';dbname='.config('db_name').';charset=utf8mb4';$GLOBALS['tcf_pdo']=new PDO($dsn,(string)config('db_user'),(string)config('db_password'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);$GLOBALS['tcf_pdo']->exec("SET time_zone = '+00:00'");}return $GLOBALS['tcf_pdo'];
}
function query(string $sql,array $params=[]): PDOStatement {$s=db()->prepare($sql);$s->execute($params);return $s;}
function transaction(callable $work): mixed {db()->beginTransaction();try{$result=$work();db()->commit();return $result;}catch(Throwable $e){db()->rollBack();throw $e;}}
