<?php
// Runs inside the isolated HTTP test server; all credentials stay in memory.
foreach (['none','client_secret_post','client_secret_basic'] as $method) {
 $reg=http_request('/oauth/register',['client_name'=>'Claude HTTP','redirect_uris'=>['https://claude.ai/api/mcp/auth_callback'],'token_endpoint_auth_method'=>$method]);
 check($reg['status']===201 && $reg['json']['token_endpoint_auth_method']===$method,'HTTP DCR registration: '.$method);
 $client=$reg['json'];$verifier=\Dilemma\Support::token();
 $args=['client_id'=>$client['client_id'],'redirect_uri'=>$client['redirect_uris'][0],'response_type'=>'code','code_challenge_method'=>'S256','code_challenge'=>rtrim(strtr(base64_encode(hash('sha256',$verifier,true)),'+/','-_'),'='),'approve'=>true,'debate_id'=>$setup_debate['id']];
 $page=http_request('/oauth/authorize?'.http_build_query($args),null,[],$cookie);
 check($page['status']===200 && str_contains($page['body'],'name="debate_id"') && str_contains($page['body'],'claude.ai'),'Consent shows debate choice and redirect hostname');
 $consent=http_request('/api/oauth/authorize',$args,$session_headers,$cookie);
 check($consent['status']===200,'HTTP consent accepts only the human-selected debate');
 parse_str(parse_url($consent['json']['redirect'],PHP_URL_QUERY),$code);
 $form=['client_id'=>$client['client_id'],'grant_type'=>'authorization_code','code'=>$code['code'],'code_verifier'=>$verifier,'redirect_uri'=>$args['redirect_uri']];
 $headers=['Content-Type: application/x-www-form-urlencoded'];
 if($method==='client_secret_basic')$headers[]='Authorization: Basic '.base64_encode($client['client_id'].':'.$client['client_secret']);
 if($method==='client_secret_post')$form['client_secret']=$client['client_secret'];
 $curl=curl_init(\Dilemma\Config::url('/oauth/token'));curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>5,CURLOPT_HTTPHEADER=>$headers,CURLOPT_POSTFIELDS=>http_build_query($form)]);
 $body=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$token=json_decode($body,true);
 check($status===200 && isset($token['access_token'],$token['refresh_token']),'Form-encoded exchange works: '.$method);
 $tool=http_request('/mcp',['jsonrpc'=>'2.0','id'=>1,'method'=>'tools/call','params'=>['name'=>'dilemma_get_debate','arguments'=>['debate_id'=>$setup_debate['id']]]],['Authorization: Bearer '.$token['access_token'],'MCP-Protocol-Version: 2025-11-25']);
 check($tool['status']===200 && !$tool['json']['result']['isError'],'OAuth-issued token reads the chosen debate through MCP');
}
