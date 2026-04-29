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
    'rpt_image_clinical_knowledge',
    'rpt_image_treatment_journey',
  ];

  var WYSIWYG_FIELD_IDS = Array.isArray(cfg.wysiwygFieldIds) ? cfg.wysiwygFieldIds : [];

  function destroyWysiwygs() {
    if (!WYSIWYG_FIELD_IDS.length || !window.wp || !wp.editor || typeof wp.editor.remove !== 'function') {
      return;
    }
    WYSIWYG_FIELD_IDS.forEach(function (id) {
      var ta = document.getElementById(id);
      if (ta) {
        ta.removeAttribute('data-eh-editor-init');
      }
      try {
        wp.editor.remove(id);
      } catch (e) {
        /* ignore */
      }
    });
  }

  function initWysiwygs() {
    if (!WYSIWYG_FIELD_IDS.length || !window.wp || !wp.editor || typeof wp.editor.initialize !== 'function') {
      return;
    }
    WYSIWYG_FIELD_IDS.forEach(function (id) {
      var ta = document.getElementById(id);
      if (!ta || ta.getAttribute('data-eh-editor-init') === '1') {
        return;
      }
      var tinymceOpts = {
        height: 220,
        menubar: false,
        branding: false,
        resize: true,
        wp_autoresize_on: true,
        toolbar1:
          'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink,alignleft,aligncenter,alignright,undo,redo',
      };
      if (typeof wp.editor.getDefaultSettings === 'function') {
        var defs = wp.editor.getDefaultSettings();
        if (defs && defs.tinymce && typeof defs.tinymce === 'object') {
          tinymceOpts = Object.assign({}, defs.tinymce, tinymceOpts);
        }
      }
      wp.editor.initialize(id, {
        tinymce: tinymceOpts,
        quicktags: true,
      });
      ta.setAttribute('data-eh-editor-init', '1');
    });
  }

  function syncWysiwygToTextareas() {
    if (window.tinyMCE) {
      WYSIWYG_FIELD_IDS.forEach(function (id) {
        var ed = tinyMCE.get(id);
        if (ed && !ed.isHidden()) {
          ed.save();
        }
      });
    }
  }

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
    ['report_header_title', 'rpt_report_header_title'],
    ['subtitle', 'rpt_subtitle'],
    ['greeting_description', 'rpt_greeting_description'],
    ['diagnosis_name', 'rpt_diagnosis_name'],
    ['diagnosis_name_detail', 'rpt_diagnosis_name_detail'],
    ['title_condition_explanation', 'rpt_title_condition_explanation'],
    ['description_condition_explanation', 'rpt_description_condition_explanation'],
    ['title_clinical_knowledge', 'rpt_title_clinical_knowledge'],
    ['subtitle_clinical_knowledge', 'rpt_subtitle_clinical_knowledge'],
    ['image_clinical_knowledge', 'rpt_image_clinical_knowledge'],
    ['description_clinical_knowledge', 'rpt_description_clinical_knowledge'],
    ['title_evaluation_urgency', 'rpt_title_evaluation_urgency'],
    ['description_evaluation_urgency', 'rpt_description_evaluation_urgency'],
    ['title_treatment_journey', 'rpt_title_treatment_journey'],
    ['description_treatment_journey', 'rpt_description_treatment_journey'],
    ['image_treatment_journey', 'rpt_image_treatment_journey'],
    ['title_recommendation_approach', 'rpt_title_recommendation_approach'],
    ['description_recommendation_approach', 'rpt_description_recommendation_approach'],
    ['detail_recommendation_approach', 'rpt_detail_recommendation_approach'],
    ['bottom_description_recommendation_approach', 'rpt_bottom_description_recommendation_approach'],
    ['title_next_steps', 'rpt_title_next_steps'],
    ['description_next_steps', 'rpt_description_next_steps'],
    ['title_medical_notes', 'rpt_title_medical_notes'],
    ['body_medical_notes', 'rpt_body_medical_notes'],
    ['description_medical_notes', 'rpt_description_medical_notes'],
  ];

  function setVal(name, value) {
    var v = value == null ? '' : String(value);
    var el = form.querySelector('[name="' + name + '"]');
    if (!el) {
      return;
    }
    if (window.tinyMCE) {
      var ed = tinyMCE.get(name);
      if (ed && !ed.isHidden()) {
        ed.setContent(v);
        return;
      }
    }
    el.value = v;
    if (IMAGE_FIELD_IDS.indexOf(name) !== -1) {
      updateMediaPreview(name, el.value);
    }
  }

  function clearForm() {
    destroyWysiwygs();
    form.reset();
    var idField = document.getElementById('eh-rpt-field-id');
    if (idField) {
      idField.value = '0';
    }
    ROW_MAP.forEach(function (pair) {
      setVal(pair[1], '');
    });
    setVal('rpt_report_header_title', 'HAIR HEALTH');
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
    window.requestAnimationFrame(function () {
      initWysiwygs();
      ROW_MAP.forEach(function (pair) {
        var dbKey = pair[0];
        var formName = pair[1];
        if (WYSIWYG_FIELD_IDS.indexOf(formName) !== -1) {
          setVal(formName, row[dbKey]);
        }
      });
    });
  }

  function openModal() {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    destroyWysiwygs();
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
    window.requestAnimationFrame(function () {
      initWysiwygs();
    });
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

  if (form) {
    form.addEventListener('submit', function () {
      syncWysiwygToTextareas();
    });
  }

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
