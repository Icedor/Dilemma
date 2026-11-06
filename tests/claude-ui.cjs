const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public_html/assets/app.js', 'utf8');
const code = source.slice(source.indexOf('function chooseAiApp'), source.indexOf('function renderSetup'));
const choice = {value:'native',addEventListener(){}}, status = {dataset:{ready:'false'}}, section = {dataset:{debateAi:'debate-one'}};
const button = {setAttribute(){},removeAttribute(){}}, copy = {dataset:{}}, nativeCopy = {}, text = {}, steps = {open:true};
const panel = {dataset:{appRoute:'native'}}, direct = {dataset:{appRoute:'direct'}};
const nodes = {'#ai-app-choice':choice,'#claude-status':status,'[data-debate-ai]':section,'[data-ai-copy]':copy,'#native-copy':nativeCopy,'#native-text':text,'#claude-connect-steps':steps,'[data-claude-check]':button,'#ai-native-setup':{value:''},'.setup-text-disclosure':{}};
let response={ready:false,verified:false,native_setup:'current rules'},fail=false,calls=0;
const context={
 document:{querySelector:selector=>nodes[selector],querySelectorAll:()=>[panel,direct],addEventListener(){}},window:{addEventListener(){}},
 location:{pathname:'/debate/debate-one'},user:'user-one',storageGet:()=>null,storageSet(){},t:key=>key,Date,encodeURIComponent,
 request:async url=>{calls++;assert.equal(url,'/api/connections/native?debate_id=debate-one');if(fail)throw Error('denied');return response;}
};
vm.createContext(context);vm.runInContext(code,context);
(async()=>{
 assert.equal(nativeCopy.disabled,true);assert.equal(text.hidden,true);assert.equal(panel.hidden,false);assert.equal(direct.hidden,true);
 await context.checkClaudeConnection(true);assert.equal(nativeCopy.disabled,true);assert.equal(status.textContent,'claude_pending');
 response={ready:true,verified:false,native_setup:'new rules'};await context.checkClaudeConnection(true);
	const concurrentBefore=calls;const first=context.checkClaudeConnection(true);const second=context.checkClaudeConnection(true);assert.equal(first,second,'A quick copy joins the ongoing check');await second;assert.equal(calls,concurrentBefore+1);
 assert.equal(nativeCopy.disabled,false);assert.equal(text.hidden,false);assert.equal(steps.open,false);assert.equal(status.textContent,'claude_ready');assert.equal(nodes['#ai-native-setup'].value,'new rules');
 const before=calls;await context.checkClaudeConnection();assert.equal(calls,before,'Focus events are throttled');
 response={...response,verified:true};await context.checkClaudeConnection(true);assert.equal(status.textContent,'claude_verified');
 fail=true;await context.checkClaudeConnection(true);assert.equal(nativeCopy.disabled,true);assert.equal(status.textContent,'claude_check_failed');
 const after=calls;await context.checkClaudeConnection();assert.equal(calls,after,'Failure stops automatic checks');
 section.dataset.nativeKey='true';context.chooseAiApp();assert.equal(nativeCopy.disabled,false,'Explicit key fallback remains available');
 choice.value='direct';context.chooseAiApp();assert.equal(copy.hidden,false);assert.equal(panel.hidden,true);assert.equal(direct.hidden,false);
 console.log('PASS Claude steps, readiness, current prompt, receipt distinction, focus throttling, failure stop and explicit fallback.');
})().catch(error=>{console.error(error);process.exitCode=1;});
