const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const app=fs.readFileSync('public_html/assets/app.js','utf8');
const source=app.slice(app.indexOf('async function invitation()'),app.indexOf('function showFlag()'));
(async()=>{
for(const different of [false,true]){
	const help={textContent:'invite_private_help'},link={textContent:'login'},status={hidden:false},locked={hidden:true,querySelector:s=>s.includes('account-help')?help:link};
	const map={'#invitation':{},'#invitation-status':status,'#invitation-locked':locked};
	const token='x'.repeat(43);let stored,requested;
	const context={document:{querySelector:s=>{assert(s in map);return map[s];}},location:{hash:'#'+token,pathname:'/invite',search:''},history:{replaceState(){}},storageSet:(_,v)=>{stored=v;},storageGet:()=>stored,t:k=>k,request:async(_,body)=>{requested=body;return {sign_in_required:true,...(different?{different_account:true}:{})};}};
	await vm.runInNewContext(source,context);
	assert.equal(requested.token,token);assert.equal(stored,token);assert.equal(locked.hidden,false);assert.equal(status.hidden,true);
	assert.equal(help.textContent,different?'invite_other_account':'invite_private_help');
	assert.equal(link.textContent,different?'invite_switch_account':'login');
}
console.log('PASS Guest and wrong-account invitation recovery, private context stays hidden and return token survives.');
})().catch(e=>{console.error(e);process.exitCode=1;});
