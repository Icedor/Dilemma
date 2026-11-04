const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const app = fs.readFileSync('public_html/assets/app.js', 'utf8');
const source = app.slice(app.indexOf('async function loadArgument'), app.indexOf('async function loadEvents'));
class Element {
	constructor(tag, text = '', className = '') { this.tag = tag; this.textContent = text; this.className = className; this.children = []; this.attributes = {}; }
	append(...children) { this.children.push(...children); }
	replaceChildren(...children) { this.children = children; this.textContent = ''; }
	setAttribute(name, value) { this.attributes[name] = value; }
	get childElementCount() { return this.children.length; }
	set innerHTML(value) { throw Error('Untrusted HTML must never be rendered'); }
}
async function render(sources) {
	const output = new Element('div');
	const details = { dataset: { argument: 'fixture' }, querySelector: () => output };
	const data = { body: 'Claim (Source, 2026).', client_label: 'Fixture', subarguments: [], objections: [], rationale: 'Reason', conclusion: 'Conclusion', agent_assessment: '', amendments: [{created_at:'2026-11-04',clarification:'Later correction'}], reply_to: ['previous'], replies: [], sources };
	const context = { URL, encodeURIComponent, request: async () => data, t: key => key, element: (...args) => new Element(...args), messageLink: (id, text) => new Element('a', text), details };
	vm.createContext(context);
	vm.runInContext(source, context);
	await context.loadArgument(details);
	return {output, details};
}
(async () => {
	const {output, details} = await render([{url:'https://example.org/paper?x=1&y=2',label:'Source (2026) <img src=x onerror=alert(1)>'}]);
	const footer = output.children.at(-1);
	assert.equal(details.dataset.loaded, 'true');
	assert.equal(footer.className, 'argument-sources');
	assert.equal(footer.children[0].textContent, 'sources');
	assert.equal(footer.attributes['aria-labelledby'], 'sources-fixture');
	const item = footer.children[1].children[0];
	assert.equal(item.children[0].textContent, 'Source (2026) <img src=x onerror=alert(1)>');
	assert.equal(item.children[0].target, '_blank');
	assert.equal(item.children[0].rel, 'noopener noreferrer');
	assert.equal(item.children[1].textContent, 'example.org');
	assert.equal(output.children[0].textContent, 'Claim (Source, 2026).');
	const unsafe = await render([{url:'javascript:alert(1)',label:'Unsafe'},{url:'broken url',label:'Broken'},{url:'https://secret@example.org/paper',label:'Credentials'}]);
	assert.equal(unsafe.details.dataset.loaded, 'true');
	assert(!unsafe.output.children.some(node => node.className === 'argument-sources'));
	assert.equal(unsafe.output.children[0].textContent, 'Claim (Source, 2026).');
	const empty = await render([]);
	assert(!empty.output.children.some(node => node.className === 'argument-sources'));
	console.log('PASS Reference footer follows argument, corrections and replies; safe text/links, malformed legacy URL and empty-source handling.');
})();
