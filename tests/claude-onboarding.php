<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
use Dilemma\{Connections,Db,OAuth,Support,ToolRegistry,Views,ReadModel,AiGuide};
$a=user_fixture('ClaudeA'); $b=user_fixture('ClaudeB'); $c=user_fixture('ClaudeC');
$debate=debate_fixture($a['actor'],$b['actor'],false); $other=debate_fixture($a['actor'],$c['actor'],false);
$id=$debate['id'];
check(!Connections::native_status($a['actor'],$id)['ready'],'Direct access is not presented as a completed Claude login');
rejects(fn()=>Connections::native_status($c['actor'],$id),404,'Status is private to the participant');
$client=OAuth::register(['client_name'=>'Claude','redirect_uris'=>['https://claude.ai/api/mcp/auth_callback'],'token_endpoint_auth_method'=>'none'],$run);
check(!Connections::native_status($a['actor'],$id)['ready'],'Client registration alone does not unlock the start prompt');
$verifier=Support::token();
$args=['client_id'=>$client['client_id'],'redirect_uri'=>$client['redirect_uris'][0],'response_type'=>'code','code_challenge_method'=>'S256','code_challenge'=>rtrim(strtr(base64_encode(hash('sha256',$verifier,true)),'+/','-_'),'='),'approve'=>true,'debate_id'=>$id];
parse_str(parse_url(OAuth::authorize($a['actor'],$args)['redirect'],PHP_URL_QUERY),$code);
check(!Connections::native_status($a['actor'],$id)['ready'],'Consent without a completed exchange still waits');
$tokens=OAuth::token(['client_id'=>$client['client_id'],'grant_type'=>'authorization_code','code'=>$code['code'],'code_verifier'=>$verifier,'redirect_uri'=>$args['redirect_uri']],$run);
$actor=Connections::authenticate('Bearer '.$tokens['access_token']);
$status=Connections::native_status($a['actor'],$id);
check($status['ready'] && !$status['verified'],'Completed login is distinguished from a real app test');
check(!str_contains(Support::json($status),$tokens['access_token']) && !str_contains(Support::json($status),$tokens['refresh_token']),'Read-only status contains no access credentials');
check(!Connections::native_status($a['actor'],$other['id'])['ready'] && !Connections::native_status($b['actor'],$id)['ready'],'Other debates and participants stay unconnected');
$guide=ToolRegistry::invoke($actor,'dilemma_get_instructions',(object)['debate_id'=>$id,'locale'=>'sv']);
check(str_contains($guide['messages'][0]['content']['text'],AiGuide::RULES) && str_contains($guide['messages'][1]['content']['text'],$id),'Native tool supplies full source, waiting and publishing rules with scoped context');
rejects(fn()=>ToolRegistry::invoke($actor,'dilemma_get_instructions',(object)['debate_id'=>$other['id']]),403,'Guidance cannot read a different debate');
check(str_contains($status['native_setup'],'dilemma_get_instructions') && !str_contains($status['native_setup'],'prompts/get'),'Copied native prompt only requires callable tools');
Connections::test($actor,['idempotency_key'=>Support::id()],true);
check(Connections::native_status($a['actor'],$id)['verified'],'A stored harmless test is shown separately');
Db::run("UPDATE mcp_tokens SET expires_at=? WHERE connection_id=? AND kind='access'",[gmdate('Y-m-d H:i:s',time()-10),$actor['connection_id']]);
check(Connections::native_status($a['actor'],$id)['ready'],'Normal access-token expiry keeps a valid refresh connection ready');
Connections::revoke($a['actor'],$actor['connection_id']);
check(!Connections::native_status($a['actor'],$id)['ready'],'Revocation removes readiness immediately');
foreach(['sv','en']as$locale){
 Dilemma\I18n::locale($locale);
 $html=Views::render('debate-ai',['actor'=>$a['actor'],'user'=>Db::one('SELECT * FROM users WHERE id=?',[$a['actor']['user_id']]),'debate'=>ReadModel::debate($id,$a['actor'])]);
 check(str_contains($html,'id="claude-connect-steps" open') && str_contains($html,'id="claude-status"') && str_contains($html,'data-ready="false"'),'Unfinished connection renders its first step and actual state: '.$locale);
}
echo 'SUCCESS: '.$checks." Claude onboarding checks.\n";
