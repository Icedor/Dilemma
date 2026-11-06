const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public_html/assets/app.js', 'utf8');
const code = source.slice(source.indexOf('function chooseAiApp'), source.indexOf('function renderSetup'));
const choice = {value:'native',addEventListener(){}}, status = {dataset:{ready:'false'},textContent:''}, section = {dataset:{debateAi:'debate-one'}};
const button = {hidden:true,setAttribute(){},removeAttribute(){}}, copy = {dataset:{}}, newChat = {hidden:false}, setup = {value:''};
const steps = {connect:{dataset:{claudeStep:'connect'},hidden:false}, enable:{dataset:{claudeStep:'enable'},hidden:true}};
const guide = {dataset:{claudeState:'connect'},querySelectorAll:()=>Object.values(steps)};
const native = {dataset:{appRoute:'native'}}, server = {dataset:{appRoute:'server'}}, direct = {dataset:{appRoute:'direct'}}, other = {dataset:{appRoute:'other'}};
const nodes = {'#ai-app-choice':choice,'#claude-status':status,'[data-debate-ai]':section,'[data-ai-copy]':copy,'[data-claude-check]':button,'#ai-native-setup':setup,'.setup-text-disclosure':{},'[data-claude-state]':guide,'[data-claude-new]':newChat};
let response={ready:false,verified:false,state:'connect',native_setup:'current rules'},fail=false,calls=0,timers=[];
const context={
 document:{hidden:false,querySelector:selector=>nodes[selector],querySelectorAll:()=>[native,server,direct,other],addEventListener(){}},window:{addEventListener(){}},
 location:{pathname:'/debate/debate-one'},user:'user-one',storageGet:()=>null,storageSet(){},t:key=>key,Date,encodeURIComponent,
 setInterval:fn=>{timers.push(fn);return timers.length;},clearInterval(){},
 request:async url=>{calls++;assert.equal(url,'/api/connections/native?debate_id=debate-one');if(fail)throw Error('denied');return response;}
};
vm.createContext(context);vm.runInContext(code,context);
(async()=>{
 assert.equal(native.hidden,false);assert.equal(server.hidden,true,'Copy step stays hidden until Claude is connected');assert.equal(direct.hidden,true);assert.equal(copy.hidden,true);
 context.startClaudeWait();assert.equal(status.textContent,'claude_pending','Opening Claude shows a waiting status');assert.equal(timers.length,1,'One bounded status check runs while waiting');
 await context.checkClaudeConnection(true);assert.equal(server.hidden,true);assert.equal(steps.connect.hidden,false);assert.equal(status.textContent,'claude_pending');
 response={ready:false,verified:false,state:'enable',native_setup:'rules'};await context.checkClaudeConnection(true);
 assert.equal(steps.connect.hidden,true);assert.equal(steps.enable.hidden,false,'A connected account only needs the one-click enable');assert.equal(server.hidden,true);
 response={ready:true,verified:false,state:'ready',native_setup:'new rules'};
 const before=calls;const first=context.checkClaudeConnection(true);const second=context.checkClaudeConnection(true);assert.equal(first,second,'A quick copy joins the ongoing check');await second;assert.equal(calls,before+1);
 assert.equal(steps.enable.hidden,true);assert.equal(steps.connect.hidden,true);assert.equal(server.hidden,false,'Ready shows only the copy step');assert.equal(status.textContent,'claude_ready');assert.equal(setup.value,'new rules');assert.equal(newChat.hidden,false);
 const throttled=calls;await context.checkClaudeConnection();assert.equal(calls,throttled,'Focus events are throttled');
 response={...response,verified:true};await context.checkClaudeConnection(true);assert.equal(status.textContent,'claude_verified');
 fail=true;await context.checkClaudeConnection(true);assert.equal(server.hidden,true,'A failed check never offers stale copy');assert.equal(status.textContent,'claude_check_failed');assert.equal(button.hidden,false);
 const after=calls;await context.checkClaudeConnection();assert.equal(calls,after,'Failure stops automatic checks');
 choice.value='other';context.chooseAiApp();assert.equal(server.hidden,false,'Other MCP apps can copy the same short text');assert.equal(other.hidden,false);assert.equal(newChat.hidden,true);
 choice.value='direct';context.chooseAiApp();assert.equal(copy.hidden,false);assert.equal(native.hidden,true);assert.equal(server.hidden,true);assert.equal(direct.hidden,false);
 console.log('PASS Claude install, enable and copy states, bounded waiting, focus throttling, failure stop and other routes.');
})().catch(error=>{console.error(error);process.exitCode=1;});
