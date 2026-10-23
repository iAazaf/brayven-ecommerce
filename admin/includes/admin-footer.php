  </div><!-- /.adm-content -->
</div><!-- /.adm-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  /* Auto-dismiss admin flashes */
  document.querySelectorAll('.flash-item').forEach(function (el) {
    setTimeout(function () { el.style.opacity = '0'; el.style.transition = 'opacity .4s'; setTimeout(function () { el.remove(); }, 400); }, 4200);
  });
  /* Confirm destructive actions */
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
    });
  });
</script>
</body>
</html>
