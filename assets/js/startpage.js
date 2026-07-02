import '../css/startpage.scss';
import $ from 'jquery';
global.$ = global.jQuery = $;

import('bootstrap');

/* Mobile Nav Toggle */
$('#navToggle').on('click', function () {
  $('.hp-nav-right').toggleClass('hp-nav-open');
});

/* Language Dropdown */
$('#langToggle').on('click', function (e) {
  e.stopPropagation();
  $('#langMenu').toggleClass('hp-lang-open');
});
$(document).on('click', function () {
  $('#langMenu').removeClass('hp-lang-open');
});
