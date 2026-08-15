/**
 * Keep delete form pack_id in sync with the library select.
 */
(function () {
  'use strict';
  var select = document.getElementById('cmsStylePackSelect');
  var deleteId = document.getElementById('cmsStylePackDeleteId');
  if (!select || !deleteId) return;
  select.addEventListener('change', function () {
    deleteId.value = select.value;
  });
})();
