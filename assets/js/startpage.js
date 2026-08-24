import '../css/startpage.scss';
import('bootstrap');

// Mobile Nav Toggle
document.getElementById('navToggle').addEventListener('click', function() {
  document.querySelector('.hp-nav-right').classList.toggle('hp-nav-open');
});

// Language Dropdown
document.getElementById('langToggle').addEventListener('click', function(e) {
  e.stopPropagation();
  document.getElementById('langMenu').classList.toggle('hp-lang-open');
});
document.addEventListener('click', function() {
  document.getElementById('langMenu').classList.remove('hp-lang-open');
});
