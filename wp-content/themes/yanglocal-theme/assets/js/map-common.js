(function(){
'use strict';
var PROVIDERS=[
 {
  name:'osm-standard',
  url:'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
  options:{maxZoom:19,minZoom:3,attribution:'&copy; OpenStreetMap contributors'}
 },
 {
  name:'osm-hot-fallback',
  url:'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
  options:{maxZoom:19,minZoom:3,subdomains:'abc',attribution:'&copy; OpenStreetMap contributors · Tiles style by HOT'}
 }
];
function finish(done,ok,map,reason){if(typeof done==='function'){done(!!ok,map||null,reason||'');}}
function validCoord(value,min,max){return Number.isFinite(value)&&value>=min&&value<=max;}
function init(el,options,done){
 if(!el){finish(done,false,null,'missing-container');return;}
 if(typeof window.L==='undefined'||typeof window.L.map!=='function'){finish(done,false,null,'leaflet-missing');return;}
 options=options||{};
 var map=null,settled=false,providerIndex=0,currentLayer=null,providerTimer=null,resizeObserver=null;
 try{
  var center=options.center||[];
  var lat=Number(center[0]),lng=Number(center[1]);
  if(!validCoord(lat,-90,90)||!validCoord(lng,-180,180)||(lat===0&&lng===0)){finish(done,false,null,'invalid-coordinate');return;}
  el.hidden=false;
  el.dataset.ylMapStatus='loading';
  map=window.L.map(el,{scrollWheelZoom:false,zoomControl:true,attributionControl:true,preferCanvas:false}).setView([lat,lng],options.zoom||15);
  if(typeof options.decorate==='function'){options.decorate(map);}

  function resize(){try{map.invalidateSize(false);}catch(ignore){}}
  function pass(provider){
   if(settled){return;}
   settled=true;
   if(providerTimer){window.clearTimeout(providerTimer);providerTimer=null;}
   el.dataset.ylMapStatus='ready';
   el.dataset.ylMapProvider=provider.name;
   delete el.dataset.ylMapError;
   finish(done,true,map,'');
  }
  function fail(reason){
   if(settled){return;}
   settled=true;
   if(providerTimer){window.clearTimeout(providerTimer);providerTimer=null;}
   el.dataset.ylMapStatus='error';
   el.dataset.ylMapError=reason||'tile-failure';
   try{if(resizeObserver){resizeObserver.disconnect();}}catch(ignore){}
   try{map.remove();}catch(ignore){}
   finish(done,false,null,reason||'tile-failure');
  }
  function nextProvider(lastReason){
   if(settled){return;}
   if(currentLayer){try{currentLayer.off();map.removeLayer(currentLayer);}catch(ignore){}currentLayer=null;}
   if(providerTimer){window.clearTimeout(providerTimer);providerTimer=null;}
   if(providerIndex>=PROVIDERS.length){fail(lastReason||'tile-providers-unavailable');return;}
   var provider=PROVIDERS[providerIndex++];
   var loaded=0,errors=0,finishedProvider=false;
   var layerOptions=Object.assign({tileSize:256,detectRetina:false,updateWhenIdle:true,keepBuffer:2},provider.options||{});
   currentLayer=window.L.tileLayer(provider.url,layerOptions);
   function providerPass(){if(finishedProvider||settled){return;}finishedProvider=true;pass(provider);}
   function providerFail(reason){if(finishedProvider||settled){return;}finishedProvider=true;nextProvider(reason);}
   currentLayer.on('tileload',function(){loaded++;if(loaded>=1){providerPass();}});
   currentLayer.on('load',function(){if(loaded>0){providerPass();}});
   currentLayer.on('tileerror',function(){errors++;if(errors>=8&&loaded===0){providerFail(provider.name+'-tile-error');}});
   currentLayer.addTo(map);
   providerTimer=window.setTimeout(function(){if(loaded>0){providerPass();}else{providerFail(provider.name+'-tile-timeout');}},6500);
   window.requestAnimationFrame(resize);
  }

  window.requestAnimationFrame(function(){resize();window.setTimeout(resize,220);nextProvider('initial');});
  window.setTimeout(resize,900);
  if(typeof window.ResizeObserver!=='undefined'){
   resizeObserver=new window.ResizeObserver(resize);
   resizeObserver.observe(el);
   map.once('unload',function(){try{resizeObserver.disconnect();}catch(ignore){}});
  }
 }catch(error){
  el.dataset.ylMapStatus='error';
  el.dataset.ylMapError='init-exception';
  if(error&&error.name){el.dataset.ylMapErrorType=String(error.name).slice(0,80);}
  try{if(map){map.remove();}}catch(ignore){}
  finish(done,false,null,'init-exception');
 }
}
window.YangLocalMap={init:init,providers:PROVIDERS.slice()};
})();
