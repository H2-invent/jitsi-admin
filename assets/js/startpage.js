import '../css/startpage.scss';
import $ from 'jquery';
global.$ = global.jQuery = $;

import('bootstrap');

/* Mobile Nav Toggle */
$('#navToggle').on('click', function () {
  $('.hp-nav-right').toggleClass('hp-nav-open');
});
