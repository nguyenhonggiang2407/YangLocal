(function(){
'use strict';
if(window.YangLocalLeafletLoader){return;}

var state={status:'idle',callbacks:[],cssReady:false,jsReady:typeof window.L!=='undefined'};
var cssUrls=[
 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css',
 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css',
 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
];
var jsUrls=[
 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js',
 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js',
 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
];
var cssIndex=0,jsIndex=0;

function flush(ok,reason){
 if(state.status==='ready'||state.status==='failed'){return;}
 state.status=ok?'ready':'failed';
 state.reason=reason||'';
 var callbacks=state.callbacks.slice();
 state.callbacks.length=0;
 callbacks.forEach(function(fn){try{fn(!!ok,state.reason);}catch(ignore){}});
 try{document.dispatchEvent(new CustomEvent(ok?'yanglocal:leaflet-ready':'yanglocal:leaflet-failed',{detail:{reason:state.reason}}));}catch(ignore){}
}
function maybeReady(){
 if(state.cssReady&&state.jsReady&&typeof window.L!=='undefined'&&typeof window.L.map==='function'){
  flush(true,'');
 }
}
function loadCss(){
 if(state.cssReady){maybeReady();return;}
 if(cssIndex>=cssUrls.length){state.cssReady=true;document.documentElement.classList.add('yl-leaflet-css-fallback');maybeReady();return;}
 var href=cssUrls[cssIndex++];
 var link=document.createElement('link');
 var settled=false;
 link.rel='stylesheet';
 link.href=href;
 link.dataset.ylLeafletCss='1';
 function next(ok){
  if(settled){return;}
  settled=true;
  window.clearTimeout(timer);
  if(ok){state.cssReady=true;maybeReady();}
  else{try{link.remove();}catch(ignore){}loadCss();}
 }
 link.onload=function(){next(true);};
 link.onerror=function(){next(false);};
 var timer=window.setTimeout(function(){next(false);},5500);
 document.head.appendChild(link);
}
function loadJs(){
 if(typeof window.L!=='undefined'&&typeof window.L.map==='function'){
  state.jsReady=true;maybeReady();return;
 }
 if(jsIndex>=jsUrls.length){flush(false,'leaflet-js-unavailable');return;}
 var src=jsUrls[jsIndex++];
 var script=document.createElement('script');
 var settled=false;
 script.src=src;
 script.async=true;
 script.dataset.ylLeafletJs='1';
 function next(ok){
  if(settled){return;}
  settled=true;
  window.clearTimeout(timer);
  if(ok&&typeof window.L!=='undefined'&&typeof window.L.map==='function'){
   state.jsReady=true;maybeReady();
  }else{
   try{script.remove();}catch(ignore){}
   loadJs();
  }
 }
 script.onload=function(){next(true);};
 script.onerror=function(){next(false);};
 var timer=window.setTimeout(function(){next(false);},6500);
 document.head.appendChild(script);
}
function start(){
 if(state.status!=='idle'){return;}
 state.status='loading';
 loadCss();
 loadJs();
}
window.YangLocalLeafletLoader={
 ready:function(callback){
  if(typeof callback!=='function'){return;}
  if(state.status==='ready'){callback(true,'');return;}
  if(state.status==='failed'){callback(false,state.reason||'leaflet-unavailable');return;}
  state.callbacks.push(callback);
  start();
 },
 status:function(){return state.status;}
};
})();
