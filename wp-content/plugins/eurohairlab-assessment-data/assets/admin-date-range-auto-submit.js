(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var watched = { created_from: true, created_to: true, overview_from: true, overview_to: true };

    document.querySelectorAll('input[type="date"]').forEach(function (input) {
      var name = input.getAttribute('name');
      if (!name || !Object.prototype.hasOwnProperty.call(watched, name)) {
        return;
      }

      input.addEventListener('change', function () {
        var form = input.closest('form');
        if (form) {
          form.submit();
        }
      });
    });
  });
})();
