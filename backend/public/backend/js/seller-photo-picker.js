(function () {
  function assignFiles(mainInput, files, multiple) {
    if (!mainInput || !files || !files.length) return;
    try {
      var dt = new DataTransfer();
      if (multiple) {
        Array.prototype.forEach.call(files, function (f) { dt.items.add(f); });
      } else {
        dt.items.add(files[0]);
      }
      mainInput.files = dt.files;
      mainInput.dispatchEvent(new Event('change', { bubbles: true }));
    } catch (e) {
      // Fallback: some older browsers — trigger main click instead
      mainInput.click();
    }
  }

  function renderPreviews(picker, files) {
    var box = picker.querySelector('.seller-photo-previews');
    var previewId = picker.getAttribute('data-preview');
    if (!box) return;
    box.innerHTML = '';

    if (!files || !files.length) return;

    if (previewId && files[0]) {
      var img = document.getElementById(previewId);
      if (img) {
        var reader = new FileReader();
        reader.onload = function () { img.src = reader.result; };
        reader.readAsDataURL(files[0]);
      }
    }

    Array.prototype.forEach.call(files, function (file) {
      if (!file.type || file.type.indexOf('image/') !== 0) return;
      var col = document.createElement('div');
      col.className = files.length === 1 ? 'col-6 col-md-3' : 'col-4 col-md-2';
      var thumb = document.createElement('img');
      thumb.alt = file.name;
      col.appendChild(thumb);
      box.appendChild(col);
      var r = new FileReader();
      r.onload = function () { thumb.src = r.result; };
      r.readAsDataURL(file);
    });
  }

  function bindPicker(picker) {
    var main = picker.querySelector('.seller-photo-main');
    var cameraBtn = picker.querySelector('.seller-photo-camera');
    var galleryBtn = picker.querySelector('.seller-photo-gallery');
    var cameraInput = picker.querySelector('.seller-photo-camera-input');
    var galleryInput = picker.querySelector('.seller-photo-gallery-input');
    var multiple = picker.getAttribute('data-multiple') === '1';

    if (!main) return;

    if (cameraBtn && cameraInput) {
      cameraBtn.addEventListener('click', function () { cameraInput.click(); });
      cameraInput.addEventListener('change', function () {
        assignFiles(main, cameraInput.files, multiple);
        renderPreviews(picker, main.files);
      });
    }

    if (galleryBtn && galleryInput) {
      galleryBtn.addEventListener('click', function () { galleryInput.click(); });
      galleryInput.addEventListener('change', function () {
        assignFiles(main, galleryInput.files, multiple);
        renderPreviews(picker, main.files);
      });
    }

    main.addEventListener('change', function () {
      renderPreviews(picker, main.files);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.seller-photo-picker').forEach(bindPicker);
  });
})();
