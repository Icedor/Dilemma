<?php
ob_start();
require __DIR__ . '/bootstrap.php';
use Dilemma\{Auth,Config,Db,Debates,Mail,Push,Support};
if(Config::get('MAIL_TRANSPORT')!=='spool')throw new RuntimeException('Spool only');
$a=user_fixture('WorkerA');$b=user_fixture('WorkerB');$d=debate_fixture($a['actor'],$b['actor']);
$u=Auth::session($b['session']['token']);
Push::subscribe($u,['endpoint'=>'https://fcm.googleapis.com/fcm/send/worker-'.$run,'consent'=>true]);
$files=[];$db=Db::connection();$db->beginTransaction();
try{
	// Isolate this run within a rollback, leaving other local queued fixtures untouched.
	Db::run("UPDATE notification_outbox SET available_at=UTC_TIMESTAMP()+INTERVAL 2 HOUR,lease_until=UTC_TIMESTAMP()+INTERVAL 2 HOUR WHERE status IN ('pending','sending')");
	$enqueue=static function()use($a,$b,$d,&$files):array{
		$event=Debates::event($d['id'],$a['actor']['user_id'],'argument_published');Mail::notify($d['id'],$a['actor']['user_id'],'argument',$event);
		$row=Db::one('SELECT * FROM notification_outbox WHERE dedupe_key=?',[Support::hash('event:'.$event.':'.$b['actor']['user_id'])]);
		$data=json_decode(Support::unseal($row['encrypted_payload']),true);$files[]=Config::storage('mail/'.$row['id'].'.eml');
		return [$row,$data];
	};
	[$row,$data]=$enqueue();
	check(Mail::work(1)===['sent'=>0,'failed'=>0],'Worker does not send fallback before the push waiting period');
	Db::run('UPDATE notification_outbox SET available_at=UTC_TIMESTAMP() WHERE id=?',[$row['id']]);
	check(Mail::work(1)===['sent'=>1,'failed'=>0],'Unconfirmed push sends one real local-spool email after the wait');
	check(Db::one('SELECT encrypted_payload FROM notification_outbox WHERE id=?',[$row['id']])['encrypted_payload']==='','Successful worker delivery clears private email payload');
	check(Db::one('SELECT status FROM push_outbox WHERE dedupe_key=?',[$data['data']['push_receipts'][0]])['status']==='cancelled','Sent fallback cancels pending push retries');
	[$row,$data]=$enqueue();
	Db::run("UPDATE push_outbox SET status='sent' WHERE dedupe_key=?",[$data['data']['push_receipts'][0]]);
	Db::run('UPDATE notification_outbox SET available_at=UTC_TIMESTAMP() WHERE id=?',[$row['id']]);
	check(Mail::work(1)===['sent'=>0,'failed'=>0]&&Db::one('SELECT status FROM notification_outbox WHERE id=?',[$row['id']])['status']==='cancelled','Worker cancels duplicate email after confirmed push');
	check(!is_file(Config::storage('mail/'.$row['id'].'.eml')),'No duplicate email file is created');
	[$row,$data]=$enqueue();
	Db::run('UPDATE notification_outbox SET available_at=UTC_TIMESTAMP() WHERE id=?',[$row['id']]);
	Db::run('UPDATE debate_participants SET notify=0 WHERE debate_id=? AND user_id=?',[$d['id'],$b['actor']['user_id']]);
	check(Mail::work(1)===['sent'=>0,'failed'=>0]&&Db::one('SELECT status FROM notification_outbox WHERE id=?',[$row['id']])['status']==='cancelled','Worker rechecks a later opt-out before sending queued email');
}finally{
	$db->rollBack();
	foreach($files as $file)if(is_file($file))unlink($file);
}
echo 'SUCCESS: '.$checks." local-spool delivery worker checks. No external delivery.\n";
