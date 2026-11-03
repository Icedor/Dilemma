export function initAvatars(t) {
	for (const editor of document.querySelectorAll('[data-avatar-editor]')) {
		const input = editor.querySelector('[name="avatar"]');
		const preview = editor.querySelector('.gradient-preview');
		const svg = editor.querySelector('svg');
		const colors = editor.querySelector('[data-avatar-colors]');
		const defs = svg.querySelector('defs');
		const swatches = editor.querySelector('[data-avatar-swatches]');
		const handles = editor.querySelector('[data-avatar-handles]');
		const add = editor.querySelector('[data-avatar-add]');
		const status = editor.querySelector('[data-avatar-status]');
		const points = JSON.parse(input.value);
		const prefix = 'avatar-' + crypto.randomUUID();
		const clamp = value => Math.max(0, Math.min(100, Math.round(value)));
		const element = (tag, attributes) => {
			const node = document.createElementNS('http://www.w3.org/2000/svg', tag);
			for (const [name, value] of Object.entries(attributes)) node.setAttribute(name, value);
			return node;
		};
		function paint() {
			defs.replaceChildren(); colors.replaceChildren();
			colors.append(element('rect', { width: 100, height: 100, fill: points[0].color }));
			points.slice(1).forEach((point, offset) => {
				const id = prefix + '-' + offset;
				const gradient = element('radialGradient', { id, cx: point.x + '%', cy: point.y + '%', r: '85%' });
				gradient.append(element('stop', { offset: 0, 'stop-color': point.color }), element('stop', { offset: 1, 'stop-color': point.color, 'stop-opacity': 0 }));
				defs.append(gradient); colors.append(element('rect', { width: 100, height: 100, fill: `url(#${id})` }));
				const handle = handles.children[offset];
				handle.style.left = point.x + '%'; handle.style.top = point.y + '%';
				handle.style.backgroundColor = point.color;
				const rgb = point.color.match(/[a-f0-9]{2}/gi).map(hex => { const value = parseInt(hex, 16) / 255; return value <= .04045 ? value / 12.92 : ((value + .055) / 1.055) ** 2.4; });
				handle.style.color = rgb[0] * .2126 + rgb[1] * .7152 + rgb[2] * .0722 > .179 ? '#000000' : '#ffffff';
				handle.setAttribute('aria-label', `${t('color_point')} ${offset + 2}: ${point.x}, ${point.y}. ${t('gradient_keyboard')}`);
			});
			input.value = JSON.stringify(points);
		}
		function render() {
			swatches.replaceChildren(); handles.replaceChildren();
			points.forEach((point, index) => {
				const row = document.createElement('div'); row.className = 'gradient-color';
				const label = document.createElement('label');
				const picker = document.createElement('input'); picker.type = 'color'; picker.value = point.color;
				const name = document.createElement('span'); name.textContent = index ? `${t('color_point')} ${index + 1}` : t('base_color');
				label.append(picker, name); row.append(label);
				picker.addEventListener('input', () => { point.color = picker.value; paint(); });
				if (index) {
					const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'text-button'; remove.textContent = '×'; remove.setAttribute('aria-label', `${t('remove_color')} ${index + 1}`);
					remove.addEventListener('click', () => { points.splice(index, 1); render(); add.focus(); });
					row.append(remove);
					const handle = document.createElement('button'); handle.type = 'button'; handle.className = 'gradient-point'; handle.textContent = String(index + 1);
					handle.addEventListener('pointerdown', event => { if (event.button !== 0) return; handle.setPointerCapture(event.pointerId); handle.focus(); });
					handle.addEventListener('pointermove', event => {
						if (!handle.hasPointerCapture(event.pointerId)) return;
						const rect = preview.getBoundingClientRect();
						point.x = clamp((event.clientX - rect.left) / rect.width * 100); point.y = clamp((event.clientY - rect.top) / rect.height * 100); paint();
					});
					handle.addEventListener('keydown', event => {
						const directions = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] };
						if (!directions[event.key]) return;
						event.preventDefault(); const [x, y] = directions[event.key]; const step = event.shiftKey ? 10 : 1;
						point.x = clamp(point.x + x * step); point.y = clamp(point.y + y * step); paint(); status.textContent = `${t('color_point')} ${index + 1}: ${point.x}, ${point.y}`;
					});
					handles.append(handle);
				}
				swatches.append(row);
			});
			add.hidden = points.length >= 3; paint();
		}
		add.addEventListener('click', () => {
			if (points.length >= 3) return;
			points.push({ color: points.length === 1 ? '#f16a16' : '#107b83', x: points.length === 1 ? 75 : 20, y: points.length === 1 ? 25 : 80 });
			render(); swatches.lastElementChild.querySelector('input').focus();
		});
		render();
	}
}
