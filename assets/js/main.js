/* BRAYVEN — front-end behaviour (vanilla JS; progressive enhancement only). */
(function () {
  'use strict';

  /* Auto-dismiss flash messages */
  document.querySelectorAll('.flash-item').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .4s ease';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 400);
    }, 4200);
  });

  /* Product detail: thumbnail → main image swap */
  var pdMain = document.getElementById('pdMainImage');
  if (pdMain) {
    document.querySelectorAll('.pd-thumb').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var src = btn.getAttribute('data-image');
        if (!src) return;
        pdMain.src = src;
        document.querySelectorAll('.pd-thumb').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
      });
    });
  }

  /* Product detail: size / colour pill pickers write into hidden inputs */
  document.querySelectorAll('[data-picker]').forEach(function (group) {
    var target = document.getElementById(group.getAttribute('data-picker'));
    if (!target) return;
    group.addEventListener('click', function (e) {
      var pill = e.target.closest('[data-value]');
      if (!pill) return;
      group.querySelectorAll('.active').forEach(function (a) { a.classList.remove('active'); });
      pill.classList.add('active');
      target.value = pill.getAttribute('data-value');
      var err = document.getElementById(group.getAttribute('data-picker') + 'Error');
      if (err) { err.textContent = ''; }
    });
  });

  /* Quantity steppers */
  document.querySelectorAll('[data-qty]').forEach(function (wrap) {
    var input = wrap.querySelector('input');
    wrap.querySelector('[data-step="-1"]').addEventListener('click', function () {
      input.value = Math.max(1, parseInt(input.value || '1', 10) - 1);
      input.dispatchEvent(new Event('change'));
    });
    wrap.querySelector('[data-step="1"]').addEventListener('click', function () {
      var max = parseInt(input.getAttribute('max') || '99', 10);
      input.value = Math.min(max, parseInt(input.value || '1', 10) + 1);
      input.dispatchEvent(new Event('change'));
    });
  });

  /* Add-to-cart validation: require explicit size + colour selection */
  var addToCartForm = document.getElementById('addToCartForm');
  if (addToCartForm) {
    addToCartForm.addEventListener('submit', function (e) {
      var ok = true;
      ['size', 'color'].forEach(function (name) {
        var input = addToCartForm.elements[name];
        var err = document.getElementById(name + 'Error');
        if (input && input.value === '') {
          ok = false;
          if (err) err.textContent = 'Please choose a ' + name + '.';
        } else if (err) {
          err.textContent = '';
        }
      });
      if (!ok) e.preventDefault();
    });
  }

  /* Newsletter forms: visual confirmation is server-side; just prevent empty submits */
  document.querySelectorAll('form[data-validate-email]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var email = form.querySelector('input[type="email"]');
      if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        e.preventDefault();
        email.focus();
        email.classList.add('is-invalid');
      }
    });
  });
})();
