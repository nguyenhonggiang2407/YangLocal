(function(){
'use strict';
function boot(){
 document.querySelectorAll('[data-yl-campus-map]').forEach(function(el){
  var fallback=el.parentElement?el.parentElement.querySelector('[data-yl-map-fallback]'):null;
  function fail(reason){el.classList.add('is-map-error');el.hidden=true;el.dataset.ylMapStatus='error';if(reason){el.dataset.ylMapError=reason;}if(fallback){fallback.hidden=false;}}
  function start(){
   if(!window.YangLocalMap){fail('map-common-missing');return;}
   var lat=Number(el.dataset.lat),lng=Number(el.dataset.lng);
   if(!Number.isFinite(lat)||!Number.isFinite(lng)||lat<-90||lat>90||lng<-180||lng>180||(lat===0&&lng===0)){fail('invalid-coordinate');return;}
   var points=[],markers=el.querySelector('script[type="application/json"]');
   if(markers){try{points=JSON.parse(markers.textContent||'[]');}catch(ignore){points=[];}markers.remove();}
   window.YangLocalMap.init(el,{center:[lat,lng],zoom:14,decorate:function(map){
    window.L.circleMarker([lat,lng],{radius:9,weight:3,fillOpacity:.95}).addTo(map).bindPopup(el.dataset.name||'Campus');
    points.forEach(function(p){var plat=Number(p.lat),plng=Number(p.lng);if(Number.isFinite(plat)&&Number.isFinite(plng)&&plat>=-90&&plat<=90&&plng>=-180&&plng<=180){window.L.circleMarker([plat,plng],{radius:6,weight:2,fillOpacity:.8}).addTo(map).bindPopup(p.name||'Địa điểm');}});
   }},function(ok,map,reason){if(ok){el.hidden=false;el.classList.remove('is-map-error');el.dataset.ylMapStatus='ready';if(fallback){fallback.hidden=true;}}else{fail(reason||'map-init-failed');}});
  }
  if(window.YangLocalLeafletLoader&&typeof window.YangLocalLeafletLoader.ready==='function'){
   el.dataset.ylMapStatus='loading-leaflet';
   window.YangLocalLeafletLoader.ready(function(ok,reason){if(ok){start();}else{fail(reason||'leaflet-assets-failed');}});
  }else if(typeof window.L!=='undefined'&&typeof window.L.map==='function'){
   start();
  }else{
   fail('leaflet-loader-missing');
  }
 });
}
if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',boot,{once:true});}else{boot();}
})();
