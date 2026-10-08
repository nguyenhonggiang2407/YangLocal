(function(){
  'use strict';
  var data=(window.YangLocalSchools&&Array.isArray(window.YangLocalSchools.items))?window.YangLocalSchools.items:[];
  function norm(s){
    var value=(s||'').toString().toLowerCase().replace(/đ/g,'d');
    try{ value=value.normalize('NFD').replace(/[\u0300-\u036f]/g,''); }catch(e){}
    return value.replace(/[^a-z0-9\s]/g,' ').replace(/\s+/g,' ').trim();
  }
  document.querySelectorAll('[data-yl-school-picker]').forEach(function(box,boxIndex){
    var search=box.querySelector('[data-yl-school-search]'), hidden=box.querySelector('[data-yl-school-value]'), results=box.querySelector('[data-yl-school-results]'), error=box.querySelector('[data-yl-school-error]');
    if(!search||!hidden||!results)return;
    var active=-1, rows=[];
    if(!results.id)results.id='yl-school-results-'+boxIndex;
    search.setAttribute('aria-controls',results.id);
    function options(){return Array.prototype.slice.call(results.querySelectorAll('.yl-school-picker__option'));}
    function setActive(next){
      var opts=options(); if(!opts.length){active=-1;search.removeAttribute('aria-activedescendant');return;}
      active=Math.max(0,Math.min(next,opts.length-1));
      opts.forEach(function(opt,i){opt.classList.toggle('is-active',i===active);opt.setAttribute('aria-selected',i===active?'true':'false');});
      var current=opts[active]; search.setAttribute('aria-activedescendant',current.id); current.scrollIntoView({block:'nearest'});
    }
    function choose(x){
      hidden.value=x.slug;hidden.dispatchEvent(new Event('change',{bubbles:true}));search.value=x.name;results.hidden=true;search.setAttribute('aria-expanded','false');search.removeAttribute('aria-invalid');if(error)error.hidden=true;active=-1;search.removeAttribute('aria-activedescendant');
    }
    function render(q){
      var nq=norm(q); rows=data.filter(function(x){return !nq||norm([x.name,x.short,(x.aliases||[]).join(' '),x.district].join(' ')).indexOf(nq)!==-1;}).slice(0,10);
      results.innerHTML=''; active=-1; search.removeAttribute('aria-activedescendant');
      rows.forEach(function(x,i){
        var b=document.createElement('button');b.type='button';b.id=results.id+'-opt-'+i;b.className='yl-school-picker__option';b.setAttribute('role','option');b.setAttribute('aria-selected','false');b.innerHTML='<strong></strong><span></span>';
        b.querySelector('strong').textContent=x.short&&x.short!==x.name?x.short+' · '+x.name:x.name;
        b.querySelector('span').textContent=(x.district||'Hà Nội')+(x.verified?' · Đã xác minh':' · Đang bổ sung');
        b.addEventListener('click',function(){choose(x);});results.appendChild(b);
      });
      results.hidden=!rows.length; search.setAttribute('aria-expanded',rows.length?'true':'false');
    }
    function resolveTyped(){
      if(hidden.value)return true;
      var typed=norm(search.value); if(!typed)return false;
      var exact=data.find(function(x){return [x.name,x.short].concat(x.aliases||[]).some(function(v){return norm(v)===typed;});});
      if(exact){choose(exact);return true;}
      search.setAttribute('aria-invalid','true');if(error)error.hidden=false;render(search.value);return false;
    }
    box._ylResolveSchool=resolveTyped;
    var parentForm=box.closest('form');
    if(parentForm&&!parentForm.hasAttribute('data-yl-school-validation-bound')){
      parentForm.setAttribute('data-yl-school-validation-bound','1');
      parentForm.addEventListener('submit',function(e){
        var typed=search.value.trim();
        if(typed&&!hidden.value&&!resolveTyped()){e.preventDefault();search.focus();}
      });
    }
    search.addEventListener('focus',function(){render(search.value);});
    search.addEventListener('input',function(){search.removeAttribute('aria-invalid');if(error)error.hidden=true;if(hidden.value){hidden.value='';hidden.dispatchEvent(new Event('change',{bubbles:true}));}render(search.value);});
    search.addEventListener('keydown',function(e){
      if(e.key==='ArrowDown'){e.preventDefault();if(results.hidden)render(search.value);setActive(active<0?0:active+1);}
      else if(e.key==='ArrowUp'){e.preventDefault();if(results.hidden)render(search.value);setActive(active<0?Math.max(0,rows.length-1):active-1);}
      else if(e.key==='Enter'&&!results.hidden){var opts=options();if(active>=0&&rows[active]){e.preventDefault();choose(rows[active]);}else if(resolveTyped()){e.preventDefault();}}
      else if(e.key==='Escape'){results.hidden=true;search.setAttribute('aria-expanded','false');active=-1;search.removeAttribute('aria-activedescendant');}
    });
    document.addEventListener('click',function(e){if(!box.contains(e.target)){results.hidden=true;search.setAttribute('aria-expanded','false');}});
  });
  document.querySelectorAll('[data-yl-campus-jump]').forEach(function(form){form.addEventListener('submit',function(e){
    var v=form.querySelector('[data-yl-school-value]'),box=form.querySelector('[data-yl-school-picker]');
    if(v&&!v.value&&box&&typeof box._ylResolveSchool==='function'){if(!box._ylResolveSchool()){e.preventDefault();return;}}
    if(v&&v.value){e.preventDefault();window.location.href=(form.getAttribute('data-base')||'/gan-truong/').replace(/\/$/,'/')+encodeURIComponent(v.value)+'/';}
  });});
})();
