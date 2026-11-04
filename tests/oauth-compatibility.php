<?php
require __DIR__.'/bootstrap.php';
use Dilemma\{OAuth,Connections,Db,Debates,Support,ReadModel,AiSetup};
$a=user_fixture('OAuthA');$b=user_fixture('OAuthB');$c=user_fixture('OAuthC');$d=debate_fixture($a['actor'],$b['actor'],false);$id=$d['id'];
foreach(['none','client_secret_post','client_secret_basic'] as $method){
 $client=OAuth::register(['client_name'=>'Claude fixture','redirect_uris'=>['https://claude.ai/api/mcp/auth_callback'],'token_endpoint_auth_method'=>$method],$run.$method);
 check($client['token_endpoint_auth_method']===$method && is_int($client['client_id_issued_at']),$method.': registration echoes method and issuance');
 $verifier=Support::token();$request=['client_id'=>$client['client_id'],'redirect_uri'=>$client['redirect_uris'][0],'response_type'=>'code','code_challenge_method'=>'S256','code_challenge'=>rtrim(strtr(base64_encode(hash('sha256',$verifier,true)),'+/','-_'),'='),'state'=>'state','approve'=>true,'debate_id'=>$id];
 check(OAuth::request($request)['resource']===Dilemma\Config::url('/mcp'),'Missing legacy resource binds only to this MCP server');
 rejects(fn()=>OAuth::authorize($a['actor'],array_replace($request,['debate_id'=>''])),404,'A debate must be chosen');
 rejects(fn()=>OAuth::authorize($c['actor'],$request),404,'Cannot consent to another user private debate');
 $url=OAuth::authorize($a['actor'],$request)['redirect'];parse_str(parse_url($url,PHP_URL_QUERY),$code);
 $input=['grant_type'=>'authorization_code','client_id'=>$client['client_id'],'redirect_uri'=>$request['redirect_uri'],'code_verifier'=>$verifier,'code'=>$code['code']];$header='';
 if($method==='client_secret_post')$input['client_secret']=$client['client_secret'];
 if($method==='client_secret_basic')$header='Basic '.base64_encode($client['client_id'].':'.$client['client_secret']);
 if($method!=='none'){
  $stored=Db::one('SELECT secret_hash FROM oauth_clients WHERE id=?',[$client['client_id']]);check($stored['secret_hash']===Support::hash($client['client_secret']) && $stored['secret_hash']!==$client['client_secret'],'Confidential secret stored hashed only');
  rejects(fn()=>OAuth::token(array_diff_key($input,['client_secret'=>1]),$run),400,'Confidential client cannot downgrade authentication');
 }
 rejects(fn()=>OAuth::token(array_replace($input,['code_verifier'=>Support::token()]),$run,$header),400,'PKCE remains required');
 $tokens=OAuth::token($input,$run,$header);$actor=Connections::authenticate('Bearer '.$tokens['access_token']);
 check($actor['debate_id']===$id && ReadModel::debate($id,$actor)['id']===$id,'OAuth grant reads only the chosen debate');
 if ($method === 'none') {
	$direct = Connections::current($a['actor'], $id);
	Connections::revoke($a['actor'], $direct['connection_id']);
	check(\Dilemma\AiWaiting::update($a['actor'], ['debate_id' => $id, 'enabled' => true])['waiting']['enabled'], 'Native OAuth supports optional waiting without a direct key');
	\Dilemma\AiWaiting::update($a['actor'], ['debate_id' => $id, 'enabled' => false]);
 }
 $refresh=['grant_type'=>'refresh_token','refresh_token'=>$tokens['refresh_token'],'client_id'=>$client['client_id']];if($method==='client_secret_post')$refresh['client_secret']=$client['client_secret'];
 check(Db::one('SELECT expires_at FROM mcp_tokens WHERE token_hash=?',[Support::hash($tokens['refresh_token'])])['expires_at']===null,'Refresh grant remains available until revoked');
 $next=OAuth::token($refresh,$run,$header);check($next['refresh_token']!==$tokens['refresh_token'],'Refresh rotates credentials');
 rejects(fn()=>OAuth::token($refresh,$run,$header),400,'Refresh replay is rejected');
 rejects(fn()=>Connections::authenticate('Bearer '.$next['access_token']),401,'Replay revokes the connection');
}
$default=OAuth::register(['redirect_uris'=>['https://client.example.test/callback']],$run.'default');check($default['token_endpoint_auth_method']==='client_secret_basic' && isset($default['client_secret']),'RFC7591 omitted authentication defaults to basic');
$native=AiSetup::connector($id,'sv');check(!str_contains($native,'Authorization:') && !str_contains($native,'Bearer '),'Native connector prompt contains no bearer credential');
rejects(fn()=>OAuth::request(['client_id'=>$default['client_id'],'redirect_uri'=>'https://evil.example/callback']),400,'Unregistered callback rejected');
echo 'SUCCESS: '.$checks." OAuth compatibility checks.\n";
