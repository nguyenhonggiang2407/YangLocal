(function () {
  'use strict';
  if (typeof YangLocalFavorites === 'undefined') return;

  var cfg = YangLocalFavorites;
  function guestIds() {
    try {
      var parsed = JSON.parse(localStorage.getItem(cfg.storageKey) || '[]');
      return Array.isArray(parsed) ? parsed.map(Number).filter(Boolean) : [];
    } catch (e) { return []; }
  }
  function saveGuestIds(ids) {
    try {
      localStorage.setItem(cfg.storageKey, JSON.stringify(Array.from(new Set(ids))));
      return true;
    } catch (e) { return false; }
  }
  function currentIds() { return cfg.loggedIn ? cfg.savedIds.map(Number) : guestIds(); }
  function setButtonState(button, saved) {
    button.classList.toggle('is-saved', saved);
    button.setAttribute('aria-pressed', saved ? 'true' : 'false');
    button.setAttribute('aria-label', saved ? cfg.labels.saved : cfg.labels.save);
  }
  function syncButtons() {
    var ids = currentIds();
    document.querySelectorAll('[data-yl-favorite]').forEach(function (button) {
      setButtonState(button, ids.indexOf(Number(button.dataset.postId)) !== -1);
    });
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-yl-favorite]');
    if (!button) return;
    event.preventDefault();
    var id = Number(button.dataset.postId);
    if (!id) return;

    if (!cfg.loggedIn) {
      var ids = guestIds();
      var index = ids.indexOf(id);
      if (index === -1) ids.push(id); else ids.splice(index, 1);
      if (!saveGuestIds(ids)) {
        window.alert(cfg.labels.error);
        return;
      }
      syncButtons();
      return;
    }

    if (button.disabled) return;
    button.disabled = true;
    var data = new URLSearchParams();
    data.set('action', 'yl_toggle_favorite');
    data.set('nonce', cfg.nonce);
    data.set('post_id', String(id));
    fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: data.toString() })
      .then(function (res) { return res.json(); })
      .then(function (json) {
        if (!json.success) throw new Error('request_failed');
        cfg.savedIds = json.data.ids || [];
        syncButtons();
      })
      .catch(function () { window.alert(cfg.labels.error); })
      .finally(function () { button.disabled = false; });
  });

  function renderGuestSaved() {
    var holder = document.querySelector('[data-yl-guest-saved-loading]');
    if (!holder || cfg.loggedIn) return;
    var ids = guestIds();
    if (!ids.length) {
      holder.innerHTML = '<h2>Bạn chưa lưu địa điểm nào.</h2><p>Danh sách của khách được lưu ngay trên trình duyệt này.</p><a class="yl-button" href="' + ((document.querySelector('[data-yl-discover-url]') && document.querySelector('[data-yl-discover-url]').href) || '/') + '">Khám phá địa điểm</a>';
      return;
    }
    holder.innerHTML = '<p>Đang tải ' + ids.length + ' địa điểm đã lưu…</p>';
    var data = new URLSearchParams();
    data.set('action', 'yl_guest_saved_cards');
    data.set('nonce', cfg.nonce);
    ids.forEach(function (id) { data.append('ids[]', String(id)); });
    fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: data.toString() })
      .then(function (res) { return res.json(); })
      .then(function (json) {
        var grid = document.querySelector('[data-yl-saved-grid]');
        if (!json.success || !grid) throw new Error('load_failed');
        holder.remove();
        grid.insertAdjacentHTML('beforeend', json.data.html || '<div class="yl-empty-state"><h2>Không còn địa điểm hợp lệ trong danh sách.</h2></div>');
        syncButtons();
      })
      .catch(function () { holder.innerHTML = '<h2>Chưa tải được danh sách.</h2><p>Danh sách vẫn được giữ trên trình duyệt; hãy thử tải lại trang.</p>'; });
  }

  syncButtons();
  renderGuestSaved();
})();
