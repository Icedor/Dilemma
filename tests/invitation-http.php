<?php
use Dilemma\{Debates, Support};
$invite_author = user_fixture('inviter');
$invite_draft = Debates::create($invite_author['actor'], ['idempotency_key' => Support::token(), 'title' => 'Invitation preview HTTP fixture', 'thesis' => 'The premise before sign-in.', 'stance' => 'A clear creator opinion before sign-in.', 'processing_consent' => true, 'public_consent' => false]);
$invite_link = Debates::invite($invite_author['actor'], $invite_draft['debate_id'], ['idempotency_key'=>Support::token()]);
$invite_token = parse_url($invite_link['invitation_url'], PHP_URL_FRAGMENT);
$invite_preview = http_request('/api/invitation', ['token'=>$invite_token]);
check($invite_preview['status'] === 200 && $invite_preview['json']['debate']['thesis'] === 'The premise before sign-in.', 'HTTP: guest can preview a shared invitation');
check($invite_preview['headers']['cache-control'][0] === 'no-store' && $invite_preview['headers']['referrer-policy'][0] === 'no-referrer', 'HTTP: private invitation preview cannot be cached or leaked by referrers');
check(http_request('/api/invitation', ['token'=>$invite_token], ['Origin: https://attacker.test'])['status'] === 403, 'HTTP: invitation preview rejects foreign origin');
check(http_request('/api/accept', ['token'=>$invite_token])['status'] === 401, 'HTTP: preview never grants anonymous acceptance');
foreach (['sv','en'] as $invite_language) {
	$page=http_request('/invite?lang='.$invite_language);
	check($page['status']===200 && str_contains($page['body'],'invitation-hero') && str_contains($page['body'],'id="invitation-empty"'), 'HTTP: invitation has an intentional empty state in '.$invite_language);
	check(!str_contains($page['body'],'A clear creator opinion') && !str_contains($page['body'],$invite_token), 'HTTP: invitation page source never embeds someone else’s private premise or token');
}

$target_user=user_fixture('InvitationTarget');
$target_link=Debates::invite($invite_author['actor'],$invite_draft['debate_id'],['idempotency_key'=>Support::token(),'email'=>$target_user['email']]);
$target_token=parse_url($target_link['invitation_url'],PHP_URL_FRAGMENT);
$wrong=http_request('/api/invitation',['token'=>$target_token],[],'dilemma_session='.$invite_author['session']['token']);
check($wrong['status']===200&&$wrong['json']===['sign_in_required'=>true,'different_account'=>true],'HTTP: wrong account gets a clear recovery state without recipient or premise');
check(http_request('/api/invitation',['token'=>$target_token])['json']===['sign_in_required'=>true],'HTTP: addressed invitation stays private for guests');
check(http_request('/api/invitation',['token'=>$target_token],[],'dilemma_session='.$target_user['session']['token'])['json']['debate']['id']===$invite_draft['debate_id'],'HTTP: matching account can view the addressed invitation');
foreach(['sv','en'] as $locale){
	$cookie='dilemma_session='.$target_user['session']['token'];
	foreach(['/new','/invite'] as $path){
		$page=http_request($path.'?lang='.$locale,null,[],$cookie);
		$dom=new DOMDocument();@$dom->loadHTML($page['body']);$xpath=new DOMXPath($dom);
		check($xpath->query('//input[@name="notify" and @checked]')->length===1,'HTTP: visible default notification choice on '.$path.' in '.$locale);
	}
	$page=http_request('/invite?lang='.$locale,null,[],$cookie);
	check(str_contains($page['body'],'data-invitation-account-help'),'HTTP: wrong-account recovery is rendered in '.$locale);
}
