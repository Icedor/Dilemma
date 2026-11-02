// A copy failure leaves selectable text instead of discarding a one-time credential.
export async function copyText(text, target = null) {
	try { await navigator.clipboard.writeText(text); return true; } catch { /* Older/denied clipboard: use selection. */ }
	let temporary = false;
	if (!target || !('value' in target)) {
		target = document.createElement('textarea');
		target.value = text; target.readOnly = true; target.className = 'visually-hidden';
		document.body.append(target); temporary = true;
	}
	const details = target.closest('details');
	if (details) details.open = true;
	target.focus(); target.select();
	let copied = false;
	try { copied = document.execCommand('copy'); } catch { /* Keep manual selection. */ }
	if (temporary) target.remove();
	return copied;
}
