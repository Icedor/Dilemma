<!doctype html>
<html lang="<?= $en ? 'en' : 'sv' ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="color-scheme" content="light"><meta name="supported-color-schemes" content="light">
	<title><?= $e($subject) ?></title>
	<style>
		body, table, td, a { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
		table, td { mso-table-lspace:0pt; mso-table-rspace:0pt; }
		table { border-collapse:collapse; }
		a { text-underline-offset:3px; }
		@media screen and (max-width:480px) { .outer { padding:16px 10px !important; } .panel { padding:28px 24px 32px !important; } .headline { font-size:36px !important; } .footer { padding:22px 14px !important; } }
	</style>
</head>
<body style="margin:0;padding:0;width:100%;background-color:#f0eee7;color:#191918;font-family:Arial,Helvetica,sans-serif;">
	<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;"><?= $e($preview) ?></div>
	<table role="presentation" width="100%" bgcolor="#f0eee7" style="width:100%;background-color:#f0eee7;"><tr><td class="outer" align="center" style="padding:40px 16px;">
	<!--[if mso]><table role="presentation" width="600" align="center"><tr><td><![endif]-->
	<table role="presentation" width="100%" style="width:100%;max-width:600px;">
		<?php if ($kind === 'invitation'): ?><tr><td bgcolor="#191918"><img src="<?= $e(\Dilemma\Config::url('/assets/share-change-your-mind.jpg')) ?>" width="600" alt="<?= $en ? 'What would change your mind?' : 'Vad skulle få dig att ändra dig?' ?>" style="display:block;width:100%;max-width:600px;height:auto;border:0;color:#ffdb24;"></td></tr><?php endif; ?>
		<tr><td class="panel" bgcolor="#ffdb24" style="padding:36px 40px 42px;background-color:#ffdb24;background-image:linear-gradient(120deg,#ffe12b 0%,#ffdb24 50%,#ffc127 100%);color:#191918;">
			<table role="presentation" width="100%" style="width:100%;">
				<tr><td style="padding:0 0 40px;font-family:Arial,Helvetica,sans-serif;font-size:34px;line-height:38px;font-weight:900;letter-spacing:-2px;color:#191918;">dilemma</td></tr>
				<tr><td><h1 class="headline" style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:44px;line-height:1.08;letter-spacing:-1.8px;font-weight:800;color:#191918;"><?= $e($heading) ?></h1></td></tr>
				<?php if ($title !== ''): ?><tr><td style="padding:22px 0 0;"><p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:20px;line-height:1.5;color:#191918;overflow-wrap:anywhere;word-break:break-word;"><?php if ($kind === 'invitation'): ?><span style="display:block;font-size:13px;font-weight:bold;margin-bottom:8px;"><?= $en ? 'Question for discussion' : 'Frågan att diskutera' ?></span>“<?= $e($title) ?>”<?php else: ?><?= $e($title) ?><?php endif; ?></p></td></tr><?php endif; ?>
				<?php if ($kind === 'login'): ?><tr><td style="padding:18px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.6;color:#383019;"><?= $e($expiry) ?></td></tr><?php endif; ?>
				<?php if ($kind === 'invitation'): ?><tr><td style="padding:18px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:17px;line-height:1.6;color:#191918;"><?= $e($invitation_copy) ?></td></tr><?php endif; ?>
				<tr><td style="padding:30px 0 0;">
					<table role="presentation" style="border-collapse:separate;"><tr><td bgcolor="#191918" align="center" style="background-color:#191918;mso-padding-alt:17px 26px;"><a href="<?= $e($data['url']) ?>" target="_blank" style="display:inline-block;border:1px solid #191918;padding:17px 26px;font-family:Arial,Helvetica,sans-serif;font-size:17px;line-height:22px;font-weight:bold;text-align:center;text-decoration:none;background-color:#191918;color:#fffdf7;mso-padding-alt:0;"><?= $e($action) ?></a></td></tr></table>
				</td></tr>
				<?php if ($chat !== ''): ?><tr><td style="padding:24px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;"><a href="<?= $e($chat) ?>" target="_blank" style="color:#191918;text-decoration:underline;"><?= $e($chat_label) ?></a></td></tr><?php endif; ?>
			</table>
		</td></tr>
		<tr><td class="footer" style="padding:24px 40px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.7;color:#54514a;">
			<?php if ($kind === 'login'): ?><p style="margin:0 0 14px;"><?= $e($ignore) ?></p><?php endif; ?>
			<?php if ($kind === 'invitation'): ?><p style="margin:0 0 14px;"><?= $e($invitation_access) ?></p><?php endif; ?>
			<?php if ($settings): ?><p style="margin:0 0 14px;"><a href="<?= $e(\Dilemma\Config::url('/settings')) ?>" target="_blank" style="color:#54514a;text-decoration:underline;"><?= $e($settings_label) ?></a></p><?php endif; ?>
			<p style="margin:0;"><a href="<?= $e(\Dilemma\Config::url()) ?>" target="_blank" style="color:#54514a;text-decoration:none;">dilemma.se</a></p>
		</td></tr>
	</table>
	<!--[if mso]></td></tr></table><![endif]-->
	</td></tr></table>
</body>
</html>
