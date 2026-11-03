const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const app = fs.readFileSync('public_html/assets/app.js', 'utf8');
const source = app.slice(app.indexOf('if (flowPanel && flowDebate && liveArguments)'), app.indexOf('const fragmentForm ='));
function harness() {
	const state = { calls: 0, hidden: false, fail: false, timers: [], delays: [], update: { hidden: true }, applied: [], cursor: '8', retry: null, response: {next_ordinal:9,arguments:[{id:'new'}]} };
	const button={disabled:false,addEventListener:(_,fn)=>state.retry=fn};
	vm.runInNewContext(source, { preserveReading:fn=>fn(), flowPanel:{querySelector:s=>s==='[data-retry-live]'?button:state.update},flowDebate:'fixture',liveArguments:{dataset:{get liveAfter(){return state.cursor;}}},document:{get hidden(){return state.hidden;}},request:async url=>{state.calls++;state.url=url;if(state.fail)throw Error('network error');return state.response;},applyLiveDebate:data=>{state.applied.push(data);state.cursor=String(data.next_ordinal);},setTimeout:(fn,delay)=>{state.timers.push(fn);state.delays.push(delay);},encodeURIComponent });
	state.tick=async()=>{const next=state.timers.shift();assert.equal(typeof next,'function');await next();};return state;
}
(async()=>{
	let s=harness();for(let i=0;i<24;i++)await s.tick();assert.equal(s.calls,24);assert.equal(s.timers.length,1);assert(s.delays.every(n=>n===20000));
	s.hidden=true;await s.tick();assert.equal(s.calls,24);assert.equal(s.timers.length,1);
	s.hidden=false;await s.tick();assert.equal(s.applied.length,25);assert.equal(s.url,'/api/debates/fixture/live?after=9');assert.equal(s.update.hidden,true);
	s.fail=true;await s.tick();assert.equal(s.timers.length,0);assert.equal(s.update.hidden,false);
	s.fail=false;s.retry();await new Promise(setImmediate);assert.equal(s.timers.length,1);assert.equal(s.update.hidden,true);
	s.response={next_ordinal:29,arguments:Array(20).fill({id:'batch'}),has_more:true};await s.tick();assert.equal(s.cursor,'29');assert.equal(s.timers.length,1);
	console.log('PASS 6 live-poll checks: ongoing delivery, hidden suspension, cursor advance, failure stop, explicit safe retry, bounded catch-up.');
})();
