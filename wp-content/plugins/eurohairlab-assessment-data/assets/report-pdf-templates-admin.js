(function () {
  'use strict';

  var cfg = window.ehReportPdfTemplatesAdmin;
  if (!cfg) {
    return;
  }

  var modal = document.getElementById('eh-rpt-modal');
  var form = document.getElementById('eh-rpt-form');
  var loading = document.getElementById('eh-rpt-modal-loading');
  var titleEl = document.getElementById('eh-rpt-modal-title');
  var maskingInput = document.getElementById('rpt_masking_id');
  var maskingHelp = document.getElementById('eh-rpt-masking-help');
  var addBtn = document.getElementById('eh-rpt-open-add');

  if (!modal || !form || !loading || !titleEl || !maskingInput) {
    return;
  }

  var IMAGE_FIELD_IDS = [
    'rpt_treatment_rec_1_image',
    'rpt_phase_of_hair_growth_male_image',
    'rpt_phase_of_hair_growth_female_image',
    'rpt_risk_untreated_image',
    'rpt_treatment_rec_2_image',
    'rpt_treatment_rec_3_image',
  ];

  function normalizeImageValue(value) {
    if (value == null) {
      return '';
    }

    var v = String(value).trim();
    if (!v) {
      return '';
    }

    if (/^\d+$/.test(v)) {
      var attachment = window.wp && window.wp.media && window.wp.media.attachment ? window.wp.media.attachment(v) : null;
      if (attachment && attachment.get) {
        var cached = attachment.get('url');
        if (cached) {
          return String(cached);
        }
        if (attachment.fetch) {
          attachment.fetch().then(function () {
            var fetched = attachment.get('url');
            if (fetched) {
              refreshAllImagePreviews();
            }
          });
        }
      }
    }

    return v;
  }

  function updateMediaPreview(inputId, url) {
    var input = document.getElementById(inputId);
    var img = document.getElementById(inputId + '_preview');
    if (!input) {
      return;
    }
    var v = normalizeImageValue(url);
    input.value = v;
    if (img) {
      if (v) {
        img.src = v;
        img.style.display = 'block';
      } else {
        img.removeAttribute('src');
        img.style.display = 'none';
      }
    }
  }

  function refreshAllImagePreviews() {
    IMAGE_FIELD_IDS.forEach(function (id) {
      var input = document.getElementById(id);
      updateMediaPreview(id, input ? input.value : '');
    });
    window.requestAnimationFrame(function () {
      IMAGE_FIELD_IDS.forEach(function (id) {
        var input = document.getElementById(id);
        updateMediaPreview(id, input ? input.value : '');
      });
    });
  }

  function bindMediaButtons() {
    if (!window.wp || !window.wp.media) {
      return;
    }

    document.querySelectorAll('.eh-rpt-media-select').forEach(function (btn) {
      if (btn.getAttribute('data-eh-bound') === '1') {
        return;
      }
      btn.setAttribute('data-eh-bound', '1');
      btn.addEventListener('click', function () {
        var target = btn.getAttribute('data-target');
        if (!target) {
          return;
        }
        var frame = window.wp.media({
          title: 'Choose image',
          button: { text: 'Use this image' },
          multiple: false,
          library: { type: 'image' },
        });
        frame.on('select', function () {
          var att = frame.state().get('selection').first().toJSON();
          var url = att.url || '';
          if (att.sizes && att.sizes.large && att.sizes.large.url) {
            url = att.sizes.large.url;
          }
          updateMediaPreview(target, url);
        });
        frame.open();
      });
    });

    document.querySelectorAll('.eh-rpt-media-clear').forEach(function (btn) {
      if (btn.getAttribute('data-eh-bound') === '1') {
        return;
      }
      btn.setAttribute('data-eh-bound', '1');
      btn.addEventListener('click', function () {
        var target = btn.getAttribute('data-target');
        if (target) {
          updateMediaPreview(target, '');
        }
      });
    });
  }

  var ROW_MAP = [
    ['masking_id', 'rpt_masking_id'],
    ['report_title', 'rpt_report_title'],
    ['diagnosis_name', 'rpt_diagnosis_name'],
    ['diagnosis_description', 'rpt_diagnosis_description'],
    ['clinical_desc_1', 'rpt_clinical_desc_1'],
    ['clinical_desc_2', 'rpt_clinical_desc_2'],
    ['clinical_desc_3', 'rpt_clinical_desc_3'],
    ['risk_delayed_description', 'rpt_risk_delayed_description'],
    ['risk_untreated_image', 'rpt_risk_untreated_image'],
    ['treatment_rec_1_title', 'rpt_treatment_rec_1_title'],
    ['treatment_rec_1_description', 'rpt_treatment_rec_1_description'],
    ['treatment_rec_1_image', 'rpt_treatment_rec_1_image'],
    ['phase_of_hair_growth_male_image', 'rpt_phase_of_hair_growth_male_image'],
    ['phase_of_hair_growth_female_image', 'rpt_phase_of_hair_growth_female_image'],
    ['treatment_rec_2_title', 'rpt_treatment_rec_2_title'],
    ['treatment_rec_2_description', 'rpt_treatment_rec_2_description'],
    ['treatment_rec_2_image', 'rpt_treatment_rec_2_image'],
    ['treatment_rec_3_title', 'rpt_treatment_rec_3_title'],
    ['treatment_rec_3_description', 'rpt_treatment_rec_3_description'],
    ['treatment_rec_3_image', 'rpt_treatment_rec_3_image'],
  ];

  function setVal(name, value) {
    var el = form.querySelector('[name="' + name + '"]');
    if (!el) {
      return;
    }
    el.value = value == null ? '' : String(value);
    if (IMAGE_FIELD_IDS.indexOf(name) !== -1) {
      updateMediaPreview(name, el.value);
    }
  }

  function clearForm() {
    form.reset();
    var idField = document.getElementById('eh-rpt-field-id');
    if (idField) {
      idField.value = '0';
    }
    ROW_MAP.forEach(function (pair) {
      setVal(pair[1], '');
    });
    refreshAllImagePreviews();
    maskingInput.removeAttribute('readonly');
    maskingInput.value = '';
    if (maskingHelp) {
      maskingHelp.style.display = '';
    }
  }

  function applyRow(row) {
    var idField = document.getElementById('eh-rpt-field-id');
    if (idField) {
      idField.value = String(row.id != null ? row.id : 0);
    }
    ROW_MAP.forEach(function (pair) {
      var dbKey = pair[0];
      var formName = pair[1];
      setVal(formName, row[dbKey]);
    });
    refreshAllImagePreviews();
  }

  function openModal() {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.style.display = 'none';
    document.body.style.overflow = '';
    loading.style.display = 'none';
    form.style.display = 'block';
  }

  function openAdd() {
    clearForm();
    titleEl.textContent = cfg.strAddTitle;
    form.style.display = 'block';
    loading.style.display = 'none';
    openModal();
    maskingInput.focus();
  }

  function openEdit(id) {
    openModal();
    titleEl.textContent = cfg.strEditTitle;
    loading.style.display = 'block';
    form.style.display = 'none';
    clearForm();

    var fd = new URLSearchParams();
    fd.append('action', 'eh_assessment_get_report_pdf_template');
    fd.append('nonce', cfg.nonce);
    fd.append('id', String(id));

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: fd.toString(),
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (j) {
        if (!j || !j.success || !j.data || !j.data.row) {
          throw new Error('bad');
        }
        applyRow(j.data.row);
        maskingInput.setAttribute('readonly', 'readonly');
        if (maskingHelp) {
          maskingHelp.style.display = 'none';
        }
        loading.style.display = 'none';
        form.style.display = 'block';
      })
      .catch(function () {
        alert(cfg.strLoadError);
        closeModal();
      });
  }

  if (addBtn) {
    addBtn.addEventListener('click', openAdd);
  }

  document.querySelectorAll('.eh-rpt-open-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var raw = btn.getAttribute('data-template-id') || '0';
      var id = parseInt(raw, 10);
      if (!id) {
        return;
      }
      openEdit(id);
    });
  });

  var closeBtn = document.getElementById('eh-rpt-modal-close');
  var cancelBtn = document.getElementById('eh-rpt-modal-cancel');
  if (closeBtn) {
    closeBtn.addEventListener('click', closeModal);
  }
  if (cancelBtn) {
    cancelBtn.addEventListener('click', closeModal);
  }

  modal.addEventListener('click', function (e) {
    if (e.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.style.display === 'flex') {
      closeModal();
    }
  });

  bindMediaButtons();

  IMAGE_FIELD_IDS.forEach(function (id) {
    var input = document.getElementById(id);
    if (!input) {
      return;
    }
    input.addEventListener('input', function () {
      updateMediaPreview(id, input.value);
    });
    input.addEventListener('change', function () {
      updateMediaPreview(id, input.value);
    });
  });

  window.setTimeout(refreshAllImagePreviews, 0);
})();
