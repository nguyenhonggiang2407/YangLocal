(function () {
  'use strict';

  var config = window.YangLocalPlacePhotos || {};
  if (!config.ajaxUrl) return;

  var modal = null;
  var activeTrigger = null;
  var viewer = null;
  var viewerPhotos = [];
  var viewerIndex = 0;
  var viewerZoom = 1;
  var pendingViewerIndex = null;
  var viewerReturnFocus = null;

  function esc(value) {
    return String(value || '').replace(/[&<>'"]/g, function (ch) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch];
    });
  }


  function buildViewer() {
    if (viewer) return viewer;
    var v = document.createElement('div');
    v.className = 'yl-photo-viewer';
    v.hidden = true;
    v.setAttribute('role','dialog');
    v.setAttribute('aria-modal','true');
    v.setAttribute('aria-label','Trình xem ảnh toàn màn hình');
    v.innerHTML = '<div class="yl-photo-viewer__bar"><span class="yl-photo-viewer__counter"></span><div class="yl-photo-viewer__controls"><button type="button" data-yl-zoom-out aria-label="Thu nhỏ">−</button><button type="button" data-yl-zoom-reset aria-label="Đặt zoom 100%">100%</button><button type="button" data-yl-zoom-in aria-label="Phóng to">+</button><button type="button" data-yl-viewer-close aria-label="Đóng">×</button></div></div><div class="yl-photo-viewer__stage"><button type="button" class="yl-photo-viewer__prev" data-yl-viewer-prev aria-label="Ảnh trước">‹</button><img alt=""><button type="button" class="yl-photo-viewer__next" data-yl-viewer-next aria-label="Ảnh sau">›</button></div><div class="yl-photo-viewer__caption"></div>';
    document.body.appendChild(v);
    v.addEventListener('click', function(e){
      if (e.target.closest('[data-yl-viewer-close]')) closeViewer();
      else if (e.target.closest('[data-yl-viewer-prev]')) stepViewer(-1);
      else if (e.target.closest('[data-yl-viewer-next]')) stepViewer(1);
      else if (e.target.closest('[data-yl-zoom-in]')) setViewerZoom(viewerZoom + .25);
      else if (e.target.closest('[data-yl-zoom-out]')) setViewerZoom(viewerZoom - .25);
      else if (e.target.closest('[data-yl-zoom-reset]')) setViewerZoom(1);
    });
    viewer = v;
    return viewer;
  }

  function setViewerZoom(value) {
    viewerZoom = Math.max(1, Math.min(3, value));
    if (viewer) {
      var img = viewer.querySelector('.yl-photo-viewer__stage img');
      if (img) img.style.transform = 'scale(' + viewerZoom + ')';
      var reset = viewer.querySelector('[data-yl-zoom-reset]');
      if (reset) reset.textContent = Math.round(viewerZoom * 100) + '%';
    }
  }

  function renderViewer() {
    if (!viewer || !viewerPhotos.length) return;
    viewerIndex = (viewerIndex + viewerPhotos.length) % viewerPhotos.length;
    var photo = viewerPhotos[viewerIndex] || {};
    var img = viewer.querySelector('.yl-photo-viewer__stage img');
    img.src = photo.full_url || photo.url || '';
    img.removeAttribute('srcset');
    img.alt = 'Ảnh ' + (viewerIndex + 1) + ' / ' + viewerPhotos.length;
    viewer.querySelector('.yl-photo-viewer__counter').textContent = (viewerIndex + 1) + ' / ' + viewerPhotos.length;
    var prev = viewer.querySelector('[data-yl-viewer-prev]'), next = viewer.querySelector('[data-yl-viewer-next]');
    prev.hidden = next.hidden = viewerPhotos.length < 2;
    var source = photo.open_url || photo.source_url || '';
    var credit = photo.attribution || '';
    var caption = photo.caption || '';
    var license = photo.license || '';
    if (!credit && Array.isArray(photo.attributions)) credit = photo.attributions.map(function(a){return a.name || '';}).filter(Boolean).join(', ');
    viewer.querySelector('.yl-photo-viewer__caption').innerHTML = (caption ? '<span>' + esc(caption) + '</span> ' : '') + (credit ? '<span>Ảnh: ' + esc(credit) + '.</span> ' : '') + (license ? '<span>' + esc(license) + '.</span> ' : '') + (source ? '<a href="' + esc(source) + '" target="_blank" rel="noopener noreferrer">Nguồn ảnh ↗</a>' : '');
    setViewerZoom(1);
  }

  function openViewer(index) {
    if (!viewerPhotos.length) return;
    buildViewer(); viewerReturnFocus = document.activeElement; viewerIndex = Number(index) || 0; viewer.hidden = false; document.documentElement.classList.add('yl-photo-viewer-open'); renderViewer();
    var close = viewer.querySelector('[data-yl-viewer-close]'); if (close) close.focus();
  }
  function closeViewer(){ if(!viewer) return; viewer.hidden=true; document.documentElement.classList.remove('yl-photo-viewer-open'); setViewerZoom(1); if(viewerReturnFocus && typeof viewerReturnFocus.focus==='function'){ viewerReturnFocus.focus(); } viewerReturnFocus=null; }
  function stepViewer(delta){ viewerIndex += delta; renderViewer(); }

  function buildModal() {
    if (modal) return modal;
    var wrapper = document.createElement('div');
    wrapper.className = 'yl-photo-modal';
    wrapper.hidden = true;
    wrapper.innerHTML = '' +
      '<div class="yl-photo-modal__backdrop" data-yl-photo-close></div>' +
      '<section class="yl-photo-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="yl-photo-title">' +
        '<button type="button" class="yl-photo-modal__close" data-yl-photo-close aria-label="Đóng">×</button>' +
        '<div class="yl-photo-modal__head">' +
          '<p class="yl-eyebrow">Ảnh & vị trí</p>' +
          '<h2 id="yl-photo-title">Địa điểm</h2>' +
          '<p class="yl-photo-modal__address"></p>' +
        '</div>' +
        '<div class="yl-photo-modal__content">' +
          '<div class="yl-photo-modal__photos"><div class="yl-photo-loading">Đang tìm ảnh đúng địa điểm…</div></div>' +
          '<div class="yl-photo-modal__map"><div class="yl-photo-map-placeholder">Đang tải Google Maps…</div></div>' +
        '</div>' +
        '<div class="yl-photo-modal__actions"></div>' +
        '<p class="yl-photo-modal__note">Ảnh trong cửa sổ này đến từ thư viện của địa điểm hoặc nguồn ảnh bên ngoài. Bạn có thể mở Google Maps để kiểm tra hình ảnh mới nhất trước khi đi.</p>' +
      '</section>';
    document.body.appendChild(wrapper);
    wrapper.addEventListener('click', function (event) {
      if (event.target.closest('[data-yl-photo-close]')) closeModal();
    });
    document.addEventListener('keydown', function (event) {
      if (viewer && !viewer.hidden) {
        if (event.key === 'Escape') { event.preventDefault(); closeViewer(); return; }
        if (event.key === 'ArrowLeft') { event.preventDefault(); stepViewer(-1); return; }
        if (event.key === 'ArrowRight') { event.preventDefault(); stepViewer(1); return; }
        if (event.key === '+' || event.key === '=') { event.preventDefault(); setViewerZoom(viewerZoom + .25); return; }
        if (event.key === '-') { event.preventDefault(); setViewerZoom(viewerZoom - .25); return; }
        if (event.key === '0') { event.preventDefault(); setViewerZoom(1); return; }
        if (event.key === 'Tab') {
          var viewerFocusable = Array.prototype.slice.call(viewer.querySelectorAll('a[href],button:not([disabled])')).filter(function(el){return !el.hidden && el.offsetParent!==null;});
          if (viewerFocusable.length) {
            var vf=viewerFocusable[0], vl=viewerFocusable[viewerFocusable.length-1];
            if(event.shiftKey && document.activeElement===vf){event.preventDefault();vl.focus();}
            else if(!event.shiftKey && document.activeElement===vl){event.preventDefault();vf.focus();}
          }
          return;
        }
      }
      if (wrapper.hidden) return;
      if (event.key === 'Escape') {
        closeModal();
        return;
      }
      if (event.key !== 'Tab') return;
      var focusable = Array.prototype.slice.call(wrapper.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'))
        .filter(function (el) { return !el.hidden && el.offsetParent !== null; });
      if (!focusable.length) return;
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });
    modal = wrapper;
    return modal;
  }

  function closeModal() {
    if (!modal) return;
    modal.hidden = true;
    document.documentElement.classList.remove('yl-photo-modal-open');
    var frame = modal.querySelector('iframe');
    if (frame) frame.src = 'about:blank';
    if (activeTrigger && typeof activeTrigger.focus === 'function') activeTrigger.focus();
  }

  function actionLink(url, label, cls) {
    if (!url) return '';
    return '<a class="yl-button ' + (cls || 'yl-button--ghost') + '" href="' + esc(url) + '" target="_blank" rel="noopener noreferrer">' + esc(label) + '</a>';
  }

  function attributionMarkup(photo) {
    var items = Array.isArray(photo.attributions) ? photo.attributions : [];
    if (!items.length && photo.attribution) {
      items = [{name: photo.attribution, uri: ''}];
    }
    if (!items.length) return '';
    var rendered = items.map(function (author) {
      var name = esc(author.name || 'Người đóng góp');
      return author.uri ? '<a href="' + esc(author.uri) + '" target="_blank" rel="noopener noreferrer">' + name + '</a>' : name;
    }).join(', ');
    return '<small class="yl-photo-attribution">Ảnh: ' + rendered + '</small>';
  }

  function render(data) {
    var photosBox = modal.querySelector('.yl-photo-modal__photos');
    var mapBox = modal.querySelector('.yl-photo-modal__map');
    var actions = modal.querySelector('.yl-photo-modal__actions');
    modal.querySelector('#yl-photo-title').textContent = data.title || 'Địa điểm';
    modal.querySelector('.yl-photo-modal__address').textContent = data.address || '';

    var photos = Array.isArray(data.photos) ? data.photos : [];
    viewerPhotos = photos.slice(0, 12);
    if (photos.length) {
      var html = '<div class="yl-photo-grid yl-photo-grid--count-' + Math.min(photos.length, 6) + '">';
      photos.slice(0, 6).forEach(function (photo, index) {
        var openUrl = photo.open_url || data.maps_url || photo.url;
        html += '<div class="yl-photo-grid__item">' +
          '<button type="button" class="yl-photo-grid__photo-link" data-yl-viewer-index="' + index + '" aria-label="Phóng to ảnh ' + (index + 1) + '">' +
            '<img src="' + esc(photo.url) + '" alt="Ảnh ' + (index + 1) + ' của ' + esc(data.title) + '" loading="lazy">' +
          '</button>' + attributionMarkup(photo) + '</div>';
      });
      html += '</div>';
      if (data.google_used) {
        html += '<p class="yl-photo-source-note">Ảnh từ Google Maps được hiển thị khi bạn mở cửa sổ này; bấm ảnh để xem nguồn.</p>';
      } else {
        html += '<p class="yl-photo-source-note">Ảnh đã được lưu cho địa điểm này trên YangLocal.</p>';
      }
      photosBox.innerHTML = html;
      if (pendingViewerIndex !== null) { var idx=pendingViewerIndex; pendingViewerIndex=null; window.setTimeout(function(){ openViewer(idx); },0); }
    } else {
      photosBox.innerHTML = '<div class="yl-photo-empty"><strong>Chưa có ảnh để hiển thị trực tiếp.</strong><span>Bạn có thể xem ảnh mới nhất trên Google Maps nếu muốn kiểm tra thêm.</span></div>';
    }

    var mapQuery = ((data.title || '') + ' ' + (data.address || '') + ' Hà Nội').trim();
    mapBox.innerHTML = '<iframe title="Google Maps – ' + esc(data.title) + '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q=' + encodeURIComponent(mapQuery) + '&output=embed"></iframe>';

    actions.innerHTML =
      actionLink(data.maps_photos_url, 'Xem thêm trên Google Maps', '') +
      actionLink(data.maps_url, data.maps_photos_url ? 'Mở Google Maps' : 'Xem trên Google Maps', data.maps_photos_url ? 'yl-button--ghost' : '') +
      actionLink(data.tiktok_url, '♪ Xem trên TikTok', 'yl-button--ghost') +
      actionLink(data.verification_url || data.source_url, 'Nguồn ảnh', 'yl-button--ghost');
  }

  function openModal(trigger, startIndex) {
    pendingViewerIndex = (typeof startIndex === 'number') ? startIndex : null;
    buildModal();
    activeTrigger = trigger;
    modal.hidden = false;
    document.documentElement.classList.add('yl-photo-modal-open');
    modal.querySelector('#yl-photo-title').textContent = trigger.getAttribute('data-title') || 'Địa điểm';
    modal.querySelector('.yl-photo-modal__address').textContent = '';
    modal.querySelector('.yl-photo-modal__photos').innerHTML = '<div class="yl-photo-loading">Đang tìm ảnh đúng địa điểm…</div>';
    modal.querySelector('.yl-photo-modal__map').innerHTML = '<div class="yl-photo-map-placeholder">Đang tải vị trí…</div>';
    modal.querySelector('.yl-photo-modal__actions').innerHTML = '';
    var closeButton = modal.querySelector('.yl-photo-modal__close');
    if (closeButton) window.setTimeout(function () { closeButton.focus(); }, 0);

    var body = new URLSearchParams();
    body.set('action', 'yl_place_photos');
    body.set('nonce', config.nonce || '');
    body.set('post_id', trigger.getAttribute('data-post-id') || '0');

    fetch(config.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
      body: body.toString()
    })
      .then(function (response) { return response.json(); })
      .then(function (json) {
        if (!json || !json.success) throw new Error('photo_lookup_failed');
        render(json.data || {});
      })
      .catch(function () {
        var maps = trigger.getAttribute('data-maps-url') || '';
        var title = trigger.getAttribute('data-title') || 'Địa điểm';
        modal.querySelector('.yl-photo-modal__photos').innerHTML = '<div class="yl-photo-empty"><strong>Chưa tải được ảnh lúc này.</strong><span>Bạn vẫn có thể mở Google Maps để xem thêm.</span></div>';
        modal.querySelector('.yl-photo-modal__map').innerHTML = '<div class="yl-photo-map-placeholder">Bạn vẫn có thể mở Google Maps để xem ảnh và chỉ đường.</div>';
        modal.querySelector('.yl-photo-modal__actions').innerHTML = actionLink(maps, 'Mở Google Maps', '');
        modal.querySelector('#yl-photo-title').textContent = title;
      });
  }

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-yl-place-photos]');
    if (!trigger) return;
    event.preventDefault();
    var index = null;
    if (event.target && event.target.matches && event.target.matches('.yl-gallery img')) {
      var imgs = Array.prototype.slice.call(trigger.querySelectorAll('img')); index = Math.max(0, imgs.indexOf(event.target));
    }
    openModal(trigger, index);
  });

  document.addEventListener('click', function (event) {
    var viewerTrigger = event.target.closest('[data-yl-viewer-index]');
    if (!viewerTrigger) return;
    event.preventDefault();
    openViewer(parseInt(viewerTrigger.getAttribute('data-yl-viewer-index') || '0', 10));
  });

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    var trigger = event.target.closest('[data-yl-place-photos]');
    if (!trigger || trigger.tagName === 'BUTTON' || trigger.tagName === 'A') return;
    event.preventDefault();
    var index = null;
    if (event.target && event.target.matches && event.target.matches('.yl-gallery img')) {
      var imgs = Array.prototype.slice.call(trigger.querySelectorAll('img')); index = Math.max(0, imgs.indexOf(event.target));
    }
    openModal(trigger, index);
  });

  document.addEventListener('error', function (event) {
    var img = event.target;
    if (!img || !img.matches) return;
    if (img.matches('.yl-photo-grid img')) {
      var tile = img.closest('.yl-photo-grid__item');
      if (tile) tile.style.display = 'none';
      return;
    }
    if (img.matches('.yl-gallery img')) {
      var galleryFallback = img.getAttribute('data-fallback-src') || '';
      if (galleryFallback && img.getAttribute('src') !== galleryFallback && img.getAttribute('data-fallback-tried') !== '1') {
        img.setAttribute('data-fallback-tried', '1');
        img.setAttribute('src', galleryFallback);
        img.setAttribute('alt', 'Ảnh tham khảo cho ' + ((img.closest('[data-title]') && img.closest('[data-title]').getAttribute('data-title')) || 'địa điểm'));
        var galleryMain = img.closest('.yl-gallery__main');
        if (galleryMain && !galleryMain.querySelector('.yl-image-badge')) {
          var galleryBadge = document.createElement('span');
          galleryBadge.className = 'yl-image-badge yl-image-badge--detail';
          galleryBadge.textContent = 'Ảnh tham khảo';
          galleryMain.appendChild(galleryBadge);
        }
        return;
      }
      img.style.visibility = 'hidden';
      return;
    }
    if (!img.matches('[data-yl-place-photos] img')) return;
    var trigger = img.closest('[data-yl-place-photos]');
    if (!trigger || trigger.classList.contains('yl-map-photo-card') || trigger.classList.contains('yl-place-card__photo-action')) return;
    var fallbackSrc = img.getAttribute('data-fallback-src') || '';
    if (fallbackSrc && img.getAttribute('src') !== fallbackSrc && img.getAttribute('data-fallback-tried') !== '1') {
      img.setAttribute('data-fallback-tried', '1');
      img.setAttribute('src', fallbackSrc);
      img.setAttribute('alt', 'Ảnh tham khảo cho ' + (trigger.getAttribute('data-title') || 'địa điểm'));
      if (!trigger.querySelector('.yl-image-badge')) {
        var badge = document.createElement('span');
        badge.className = 'yl-image-badge';
        badge.textContent = 'Ảnh tham khảo';
        trigger.appendChild(badge);
      }
      return;
    }
    img.style.visibility = 'hidden';
    trigger.classList.add('yl-map-photo-card');
    if (!trigger.querySelector('.yl-map-photo-card__content')) {
      var fallback = document.createElement('span');
      fallback.className = 'yl-map-photo-card__content';
      fallback.innerHTML = '<span class="yl-map-photo-card__pin">⌖</span><strong>Xem tất cả ảnh</strong><span>Mở ảnh và đường đi</span>';
      trigger.appendChild(fallback);
    }
  }, true);
})();
