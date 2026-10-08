(function(){
'use strict';
function boot(){
 var retried=false;
 var el=document.querySelector('[data-yl-map]');
 var fallback=document.querySelector('[data-yl-map-fallback]');
 function loadFallbackFrame(){if(!fallback){return;}var frame=fallback.querySelector('iframe[data-src]');if(frame&&!frame.getAttribute('src')){frame.setAttribute('src',frame.dataset.src);}}
 function fail(reason){if(el){el.hidden=true;el.dataset.ylMapStatus='error';el.setAttribute('aria-busy','false');if(reason){el.dataset.ylMapError=reason;}}if(fallback){fallback.hidden=false;loadFallbackFrame();}}
 if(!el){return;}
 function start(){
  if(!window.YangLocalMap){fail('map-common-missing');return;}
  var lat=Number(el.dataset.lat),lng=Number(el.dataset.lng);
  if(!Number.isFinite(lat)||!Number.isFinite(lng)||lat<-90||lat>90||lng<-180||lng>180||(lat===0&&lng===0)){fail('invalid-coordinate');return;}
  window.YangLocalMap.init(el,{center:[lat,lng],zoom:15,decorate:function(map){
   window.L.circleMarker([lat,lng],{radius:8,weight:3,fillOpacity:.9}).addTo(map).bindPopup(el.dataset.name||'Địa điểm');
  }},function(ok,map,reason){
   if(ok){el.hidden=false;el.dataset.ylMapStatus='ready';el.setAttribute('aria-busy','false');if(fallback){fallback.hidden=true;}}
   else if(!retried){retried=true;window.setTimeout(start,450);}
   else{fail(reason||'map-init-failed');}
  });
 }
 if(window.YangLocalLeafletLoader&&typeof window.YangLocalLeafletLoader.ready==='function'){
  el.dataset.ylMapStatus='loading-leaflet';
  window.YangLocalLeafletLoader.ready(function(ok,reason){if(ok){start();}else{fail(reason||'leaflet-assets-failed');}});
 }else if(typeof window.L!=='undefined'&&typeof window.L.map==='function'){
  start();
 }else{
  fail('leaflet-loader-missing');
 }
}
if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',boot,{once:true});}else{boot();}
})();
