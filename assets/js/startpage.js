import '../css/startpage.scss';
import('bootstrap');

// Mobile Nav Toggle
document.getElementById('navToggle').addEventListener('click', function() {
  document.querySelector('.hp-nav-right').classList.toggle('hp-nav-open');
});

/* Language Dropdown */
$('#langToggle').on('click', function (e) {
  e.stopPropagation();
  $('#langMenu').toggleClass('hp-lang-open');
});
$(document).on('click', function () {
  $('#langMenu').removeClass('hp-lang-open');
});
