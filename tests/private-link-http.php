<?php
// Included by http.php in the guarded loopback environment.
use Dilemma\{Config,Db,Support};
$link_creator = user_fixture('LinkCreator'); $link_guest = user_fixture('LinkGuest');
$private_link = debate_fixture($link_creator['actor'], $link_guest['actor'], false);
$target = '/debate/'.$private_link['id'];
$redirect = http_request($target);
check($redirect['status'] === 303 && $redirect['headers']['cache-control'][0] === 'no-store', 'Private debate opened without cookies offers uncached sign-in');
$destination = $redirect['headers']['location'][0];
parse_str(parse_url($destination, PHP_URL_QUERY), $query);
check(parse_url($destination, PHP_URL_PATH) === '/login' && $query['return_to'] === $target, 'Sign-in retains the exact debate destination');
$login_page = http_request($destination);
check($login_page['status'] === 200 && str_contains($login_page['body'], 'name="return_to" value="'.$target.'"') && !str_contains($login_page['body'], 'Parks should remain accessible.'), 'Sign-in form preserves destination without disclosing the private stance');
check(http_request('/debate/'.Support::id())['status'] === 303, 'Unknown and private debate links have the same guest recovery');
check(http_request('/api/debates/'.$private_link['id'])['status'] === 404, 'Private data endpoint still rejects unauthenticated access');
foreach ([$link_creator, $link_guest] as $member) {
	$response = http_request('/api/login', ['email'=>$member['email'], 'return_to'=>$target, 'locale'=>'sv']);
	check($response['status'] === 200, 'Either participant can request a return to their private debate');
	// Identify the correct return-target token even if two fixture emails share a timestamp.
	$rows = Db::all("SELECT encrypted_payload FROM notification_outbox WHERE user_id=? AND kind='login'", [$member['actor']['user_id']]);
	$verified = null;
	foreach ($rows as $row) {
		$mail = json_decode(Support::unseal($row['encrypted_payload']), true);
		$token = parse_url($mail['data']['url'], PHP_URL_FRAGMENT);
		$link = Db::one('SELECT used_at,return_target FROM email_verifications WHERE token_hash=?', [Support::hash($token)]);
		if ($link && !$link['used_at'] && $link['return_target'] && Support::unseal($link['return_target']) === $target) { $verified = http_request('/api/verify', ['token'=>$token]); break; }
	}
	check($verified && $verified['status'] === 200 && $verified['json']['redirect'] === $target, 'Magic link returns each participant to the same debate on another browser');
	$member_cookie = explode(';', $verified['headers']['set-cookie'][0])[0];
	check(http_request($target, null, [], $member_cookie)['status'] === 200, 'Original creator and invited participant both retain access');
}
$unrelated = http_request($target, null, [], $cookie);
check($unrelated['status'] === 404 && str_contains($unrelated['body'], 'error-state') && !str_contains($unrelated['body'], 'class="home-page"') && str_contains($unrelated['body'], 'return_to='), 'Wrong account remains private and offers account switching with the proper error layout');
$missing_page = http_request('/unknown-page');
check($missing_page['status'] === 404 && str_contains($missing_page['body'], 'class="inner-page"') && str_contains($missing_page['body'], 'error-state'), 'Ordinary errors never inherit the homepage gradient behind light text');
$attack = http_request('/login?return_to=https%3A%2F%2Fevil.example');
check(!str_contains($attack['body'], 'name="return_to"'), 'Login page ignores external return destinations');
check(http_request('/api/login', ['email'=>$link_creator['email'], 'return_to'=>'//evil.example'])['status'] === 422, 'Server rejects open redirects');
