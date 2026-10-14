document.documentElement.classList.add('js-ready');

if (window.jQuery && jQuery.fn.select2) {
  jQuery('.js-select2').each(function () {
    const select = jQuery(this);
    select.select2({
      allowClear: true,
      placeholder: select.data('placeholder') || 'Search',
      width: '100%'
    });
  });
}
