<?php
require __DIR__ . '/bootstrap.php';
use Dilemma\{AiGuide,AiSetup,Arguments,Config,Db,Debates,Support};
$a=user_fixture('A'); $b=user_fixture('B'); $c=user_fixture('C');
$make=static function () use ($a):array {
	$d=Debates::create($a['actor'],['idempotency_key'=>Support::token(),'title'=>'A shared question','topic'=>'Tests','thesis'=>'The premise belongs on the invitation','introduction'=>'Context from the person inviting.','stance'=>'My starting view is worth explaining.','left_label'=>'No','right_label'=>'Yes','locale'=>'en','processing_consent'=>true,'public_consent'=>false,'position_consent'=>false]);
	return $d;
};
$d=$make(); $id=$d['debate_id'];
$link=Debates::invite($a['actor'],$id,['idempotency_key'=>Support::token()]); $token=parse_url($link['invitation_url'],PHP_URL_FRAGMENT);
$preview=Debates::invitation(null,$token);
check($preview['debate']['thesis']==='The premise belongs on the invitation' && $preview['debate']['introduction']==='Context from the person inviting.', 'Bearer invitation shows premise and introduction before login');
check($preview['creator']['stance']==='My starting view is worth explaining.' && $preview['sign_in_required'], 'Bearer invitation shows creator view and requires sign-in to join');
check(array_keys($preview['creator'])===['alias','stance','public_consent'], 'Invitation never exposes creator email, IDs, avatar internals or access keys');
check(!isset($preview['messages']) && !isset($preview['workflow']), 'Invitation grants no full history or MCP context');
rejects(fn()=>Debates::readable($id),404,'Invitation preview does not make private debate public');
rejects(fn()=>Debates::invitation(null,Support::token()),404,'Unknown invitation cannot be enumerated');
check(!Debates::invitation($b['actor'],$token)['sign_in_required'],'Signed-in recipient can proceed');
check(Debates::invitation($a['actor'],$token)['already_member'] && !Debates::invitation($b['actor'],$token)['already_member'], 'Existing participant gets a debate link instead of an impossible self-invitation');
Db::run('UPDATE debate_invitations SET expires_at=? WHERE token_hash=?',[gmdate('Y-m-d H:i:s',time()-1),Support::hash($token)]);
rejects(fn()=>Debates::invitation(null,$token),404,'Expired invitation cannot disclose premise');
$d2=$make();$id2=$d2['debate_id'];
$email_inv=Debates::invite($a['actor'],$id2,['idempotency_key'=>Support::token(),'email'=>$b['email']]);
// Read the targeted invitation's protected token from its private storage.
$target=Db::one('SELECT encrypted_token FROM debate_invitations WHERE debate_id=? AND email=?',[$id2,$b['email']]);$target_token=Support::unseal($target['encrypted_token']);
check(Debates::invitation(null,$target_token)===['sign_in_required'=>true], 'Targeted invitation hides all question data until matching sign-in');
rejects(fn()=>Debates::invitation($c['actor'],$target_token),404,'Wrong email cannot view targeted invitation');
check(Debates::invitation($b['actor'],$target_token)['debate']['id']===$id2,'Matching email sees targeted premise');
Db::run('UPDATE debates SET hidden=1 WHERE id=?',[$id2]);
rejects(fn()=>Debates::invitation(null,$target_token),409,'Hidden debate preview blocked for guests');
Db::run('UPDATE debates SET hidden=0 WHERE id=?',[$id2]);
Debates::accept($b['actor'],['token'=>$target_token,'idempotency_key'=>Support::token(),'stance'=>'My opposing view for the question.','accept_terms'=>true,'processing_consent'=>true,'public_consent'=>false,'position_consent'=>false]);
rejects(fn()=>Debates::invitation(null,$target_token),404,'Used invitation no longer exposes a private debate');
$setup=AiSetup::text(Support::token(),$id,null,'en');
check(str_contains($setup,'persistent goal') && str_contains($setup,'replaces earlier Dilemma-generated setup'),'Current copied setup explicitly requests persistence and supersedes old generated draft instructions');
check(!str_contains(AiGuide::RULES,'five minutes') && !str_contains(AiGuide::RULES,'six published'), 'No arbitrary time or post cap');
check(str_contains(AiGuide::RULES,'20 seconds') && str_contains(AiGuide::RULES,'actual app execution limit'), 'Waiting is paced and honest about client limits');
check(str_contains(AiGuide::RULES,'length its reasoning and evidence require') && str_contains(AiGuide::RULES,'Private chat status is one or two plain sentences'), 'Full reasoning belongs on the site and status is brief');
echo 'SUCCESS: '.$checks." invitation and continuous-guide checks.\n";
