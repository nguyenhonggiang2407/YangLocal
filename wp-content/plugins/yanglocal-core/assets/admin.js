(function ($) {
  'use strict';
  $(document).on('click', '[data-yl-gallery-pick]', function (event) {
    event.preventDefault();
    var field = $('#yl_gallery_ids');
    var preview = $('[data-yl-gallery-preview]');
    var frame = wp.media({ title: 'Chọn ảnh cho gallery', button: { text: 'Dùng các ảnh này' }, multiple: true, library: { type: 'image' } });
    frame.on('select', function () {
      var selection = frame.state().get('selection');
      var ids = [];
      preview.empty();
      selection.each(function (attachment) {
        var data = attachment.toJSON();
        ids.push(data.id);
        var src = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;
        preview.append($('<img>', { src: src, alt: '', css: { width: '86px', height: '64px', objectFit: 'cover', margin: '0 6px 6px 0', borderRadius: '4px' } }));
      });
      field.val(ids.join(','));
    });
    frame.open();
  });
  $(document).on('click', '[data-yl-gallery-clear]', function (event) {
    event.preventDefault();
    $('#yl_gallery_ids').val('');
    $('[data-yl-gallery-preview]').empty();
  });
})(jQuery);
