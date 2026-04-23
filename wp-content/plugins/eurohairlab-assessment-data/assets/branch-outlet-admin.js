(function (w) {
  'use strict';

  var inboxCache = {};
  /** @type {Record<string, Record<string, object>>} */
  var templateByInbox = {};

  function waSelectId(prefix) {
    return prefix + '-wa_template_select';
  }

  function waPreviewId(prefix) {
    return prefix + '-template-preview';
  }

  function el(id) {
    return document.getElementById(id);
  }

  function cfg() {
    return w.ehBranchOutletAdmin || {};
  }

  function decodeBo(b64) {
    if (!b64) {
      return null;
    }
    try {
      var bin = atob(b64);
      var i;
      var bytes = new Uint8Array(bin.length);
      for (i = 0; i < bin.length; i++) {
        bytes[i] = bin.charCodeAt(i) & 255;
      }
      return JSON.parse(new TextDecoder('utf-8').decode(bytes));
    } catch (e1) {
      try {
        var json = decodeURIComponent(
          Array.prototype.map
            .call(atob(b64), function (c) {
              return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            })
            .join('')
        );
        return JSON.parse(json);
      } catch (e2) {
        return null;
      }
    }
  }

  function setVal(id, v) {
    var n = el(id);
    if (n) {
      n.value = v == null ? '' : String(v);
    }
  }

  function setDisp(prefix, field, v) {
    var n = el(prefix + '-disp-' + field);
    if (n) {
      n.textContent = v == null || v === '' ? '—' : String(v);
    }
  }

  function escHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function renderButtonsHtml(buttons) {
    if (!buttons || !buttons.length) {
      return '<p style="margin:0;color:#646970;">—</p>';
    }
    var parts = ['<ul style="margin:4px 0 0 18px;">'];
    buttons.forEach(function (b) {
      if (!b || typeof b !== 'object') {
        parts.push('<li><code>' + escHtml(JSON.stringify(b)) + '</code></li>');
        return;
      }
      var t = b.type != null ? String(b.type) : '';
      var txt = b.text != null ? String(b.text) : b.title != null ? String(b.title) : '';
      parts.push('<li><strong>' + escHtml(t) + '</strong>' + (txt ? ': ' + escHtml(txt) : '') + '</li>');
    });
    parts.push('</ul>');
    return parts.join('');
  }

  function htmlForTemplatePreview(tpl, c, leadTitle) {
    if (!tpl || !tpl.id) {
      return '';
    }
    var cat = tpl.category ? String(tpl.category) : '';
    var ht = tpl.header_type ? String(tpl.header_type) : '';
    var headerBlock = '';
    if (ht === 'IMAGE' && tpl.file_url) {
      var u = String(tpl.file_url);
      if (/^https?:\/\//i.test(u)) {
        headerBlock =
          '<p style="margin:0 0 6px;"><img src="' +
          escHtml(u) +
          '" alt="" style="max-width:240px;height:auto;border-radius:4px;border:1px solid #c3c4c7;" loading="lazy" /></p>';
      }
    } else if (tpl.header) {
      headerBlock = '<pre style="white-space:pre-wrap;margin:0;font:inherit;">' + escHtml(String(tpl.header)) + '</pre>';
    } else {
      headerBlock = '<p style="margin:0;color:#646970;">' + escHtml(ht || '—') + '</p>';
    }
    var body = tpl.body != null ? String(tpl.body) : '';
    var lead =
      leadTitle && String(leadTitle).trim()
        ? '<p style="margin:0 0 8px;font-weight:600;">' + escHtml(String(leadTitle)) + '</p>'
        : '';
    return (
      lead +
      (cat
        ? '<h3 style="margin:0 0 10px;font-size:14px;">' +
          escHtml(c.strTemplateCategory || 'Category') +
          ': ' +
          escHtml(cat) +
          '</h3>'
        : '') +
      '<p style="margin:0 0 4px;font-weight:600;">' +
      escHtml(c.strTemplateHeader || 'Header') +
      '</p>' +
      headerBlock +
      '<p style="margin:12px 0 4px;font-weight:600;">' +
      escHtml(c.strTemplateBody || 'Body') +
      '</p>' +
      '<pre style="white-space:pre-wrap;margin:0;font:inherit;max-height:220px;overflow:auto;">' +
      escHtml(body) +
      '</pre>' +
      '<p style="margin:12px 0 4px;font-weight:600;">' +
      escHtml(c.strTemplateButtons || 'Buttons') +
      '</p>' +
      renderButtonsHtml(Array.isArray(tpl.buttons) ? tpl.buttons : [])
    );
  }

  function setPreviewBox(boxId, tpl, showEmptyPlaceholder, emptyText, leadWhenFilled) {
    var box = el(boxId);
    var c = cfg();
    if (!box) {
      return;
    }
    if (!tpl || !tpl.id) {
      if (showEmptyPlaceholder) {
        box.style.display = 'block';
        var msg =
          emptyText ||
          c.strTemplatePreviewNoneCustomer ||
          c.strTemplatePreviewNone ||
          '';
        box.innerHTML = '<p style="margin:0;color:#646970;">' + escHtml(msg) + '</p>';
      } else {
        box.style.display = 'none';
        box.innerHTML = '';
      }
      return;
    }
    box.style.display = 'block';
    box.innerHTML = htmlForTemplatePreview(tpl, c, leadWhenFilled || '');
  }

  function applyWaTemplateSelection(prefix, inboxId, templateId) {
    var store = templateByInbox[inboxId] || {};
    var tpl = templateId ? store[templateId] : null;
    setVal(prefix + '-cekat_wa_template_masking_id', tpl && tpl.id ? tpl.id : '');
    setVal(prefix + '-cekat_wa_template_name', tpl && tpl.name ? tpl.name : '');
    var c = cfg();
    var lead = c.strTemplatePreviewLeadCustomer || '';
    setPreviewBox(waPreviewId(prefix), tpl || null, false, '', lead);
  }

  function fillWaTemplateSelect(prefix, inboxId, rows, selectedId, selectedName) {
    var sel = el(waSelectId(prefix));
    if (!sel) {
      return;
    }
    var c = cfg();
    if (!inboxId) {
      sel.innerHTML =
        '<option value="">' + escHtml(c.strTemplatePickInbox || '— Select inbox first —') + '</option>';
      return;
    }
    var ph = c.strTemplatePlaceholder || '— No template —';
    sel.innerHTML = '<option value="">' + escHtml(ph) + '</option>';
    if (!templateByInbox[inboxId]) {
      templateByInbox[inboxId] = {};
    }
    var store = templateByInbox[inboxId];
    rows.forEach(function (t) {
      if (!t || !t.id) {
        return;
      }
      store[t.id] = t;
      var o = document.createElement('option');
      o.value = t.id;
      var label = (t.name || t.id) + (t.category ? ' (' + t.category + ')' : '');
      o.textContent = label;
      sel.appendChild(o);
    });
    var pick = selectedId || '';
    if (pick && !store[pick] && selectedName) {
      var stub = {
        id: pick,
        name: selectedName,
        category: '',
        header_type: '',
        header: '',
        body: '',
        buttons: [],
        file_url: ''
      };
      store[pick] = stub;
      var o2 = document.createElement('option');
      o2.value = pick;
      o2.textContent = selectedName + ' (saved)';
      sel.appendChild(o2);
    }
    sel.value = '';
    if (pick) {
      var ok = false;
      var j;
      for (j = 0; j < sel.options.length; j++) {
        if (sel.options[j].value === pick) {
          ok = true;
          break;
        }
      }
      if (ok) {
        sel.value = pick;
      }
    }
    applyWaTemplateSelection(prefix, inboxId, sel.value);
  }

  function loadTemplates(prefix, inboxMaskingId, waSelectedId, waSelectedName) {
    var waSel = el(waSelectId(prefix));
    var c = cfg();
    if (!waSel || !c.templatesRestUrl) {
      return;
    }
    if (!inboxMaskingId) {
      fillWaTemplateSelect(prefix, '', [], '', '');
      setPreviewBox(waPreviewId(prefix), null, false, '', '');
      return;
    }
    waSel.innerHTML = '<option value="">' + escHtml(c.strTemplateLoading || 'Loading…') + '</option>';
    var url =
      c.templatesRestUrl +
      (c.templatesRestUrl.indexOf('?') >= 0 ? '&' : '?') +
      'inbox_masking_id=' +
      encodeURIComponent(inboxMaskingId);
    fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': c.nonce || '' }
    })
      .then(function (r) {
        if (!r.ok) {
          throw new Error('bad');
        }
        return r.json();
      })
      .then(function (body) {
        var rows = body && body.data ? body.data : [];
        fillWaTemplateSelect(prefix, inboxMaskingId, rows, waSelectedId, waSelectedName);
      })
      .catch(function () {
        waSel.innerHTML = '<option value="">' + escHtml(c.strTemplateLoadError || 'Error') + '</option>';
        setPreviewBox(waPreviewId(prefix), null, false, '', '');
      });
  }

  function displayNameInputValue(prefix, row) {
    if (!row) {
      return '';
    }
    var dn = row.display_name != null ? String(row.display_name).trim() : '';
    if (dn !== '') {
      return dn;
    }
    if (prefix === 'eh-bo-add') {
      return row.cekat_name != null ? String(row.cekat_name) : '';
    }
    return '';
  }

  function fillFromRow(prefix, row) {
    if (!row) {
      return;
    }
    setDisp(prefix, 'cekat_name', row.cekat_name);
    setDisp(prefix, 'cekat_phone_number', row.cekat_phone_number);
    setDisp(prefix, 'cekat_type', row.cekat_type);
    setDisp(prefix, 'cekat_status', row.cekat_status);
    setDisp(prefix, 'cekat_business_id', row.cekat_business_id);
    setDisp(prefix, 'cekat_created_at', row.cekat_created_at);
    setDisp(prefix, 'cekat_ai_agent_id', row.cekat_ai_agent_id);
    setDisp(prefix, 'cekat_description', row.cekat_description);
    setDisp(prefix, 'cekat_image_url', row.cekat_image_url);
    setDisp(prefix, 'cekat_ai_agent_json', row.cekat_ai_agent_json);
    setVal(prefix + '-cekat_wa_template_masking_id', row.cekat_wa_template_masking_id);
    setVal(prefix + '-cekat_wa_template_name', row.cekat_wa_template_name);
    var hj = el(prefix + '-cekat_row_json');
    if (hj) {
      hj.value = JSON.stringify(row);
    }
    var mid = el(prefix + '-cekat_masking_display');
    if (mid) {
      mid.textContent = row.cekat_masking_id || '';
    }
    setVal(prefix + '-display_name', displayNameInputValue(prefix, row));
  }

  function resetAddTemplateUi() {
    var c = cfg();
    var sel = el(waSelectId('eh-bo-add'));
    if (sel) {
      sel.innerHTML =
        '<option value="">' + escHtml(c.strTemplatePickInbox || '— Select inbox first —') + '</option>';
    }
    setVal('eh-bo-add-cekat_wa_template_masking_id', '');
    setVal('eh-bo-add-cekat_wa_template_name', '');
    setPreviewBox(waPreviewId('eh-bo-add'), null, false, '', '');
  }

  function clearAddForm() {
    var apiSel = el('eh-bo-api-select');
    if (apiSel) {
      apiSel.value = '';
    }
    ['cekat_name', 'cekat_phone_number', 'cekat_type', 'cekat_status'].forEach(function (f) {
      setDisp('eh-bo-add', f, '');
    });
    setVal('eh-bo-add-cekat_row_json', '');
    setVal('eh-bo-add-display_name', '');
    var mid = el('eh-bo-add-cekat_masking_display');
    if (mid) {
      mid.textContent = '';
    }
    resetAddTemplateUi();
  }

  function loadApiSelect() {
    var c = cfg();
    var sel = el('eh-bo-api-select');
    if (!sel || !c.restUrl) {
      return;
    }
    fetch(c.restUrl, {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': c.nonce || '' }
    })
      .then(function (r) {
        if (!r.ok) {
          throw new Error('bad');
        }
        return r.json();
      })
      .then(function (body) {
        var rows = body && body.data ? body.data : [];
        sel.innerHTML = '<option value="">' + (c.strSelectPlaceholder || 'Select from API…') + '</option>';
        rows.forEach(function (row) {
          if (!row || !row.cekat_masking_id) {
            return;
          }
          inboxCache[row.cekat_masking_id] = row;
          var o = document.createElement('option');
          o.value = row.cekat_masking_id;
          o.textContent = (row.cekat_name || '') + ' — ' + (row.cekat_phone_number || '');
          sel.appendChild(o);
        });
      })
      .catch(function () {
        var err = el('eh-bo-api-error');
        if (err) {
          err.style.display = 'block';
        }
      });
  }

  function onApiChange() {
    var sel = el('eh-bo-api-select');
    var row = sel ? inboxCache[sel.value] : null;
    fillFromRow('eh-bo-add', row);
    if (sel && sel.value) {
      loadTemplates('eh-bo-add', sel.value, '', '');
    } else {
      resetAddTemplateUi();
    }
  }

  function openModal(id) {
    var m = el(id);
    if (m) {
      m.style.display = 'flex';
    }
  }

  function closeModal(id) {
    var m = el(id);
    if (m) {
      m.style.display = 'none';
    }
  }

  function wireWaTemplateSelect(prefix) {
    var sel = el(waSelectId(prefix));
    if (!sel) {
      return;
    }
    sel.addEventListener('change', function () {
      var inboxId = '';
      if (prefix === 'eh-bo-add') {
        var apiSel = el('eh-bo-api-select');
        inboxId = apiSel ? apiSel.value : '';
      } else {
        var disp = el('eh-bo-edit-cekat_masking_display');
        inboxId = disp ? disp.textContent.trim() : '';
      }
      applyWaTemplateSelection(prefix, inboxId, sel.value);
    });
  }

  function fieldRows(row) {
    var keys = [
      'cekat_masking_id',
      'cekat_name',
      'display_name',
      'cekat_phone_number',
      'cekat_type',
      'cekat_status',
      'cekat_wa_template_masking_id',
      'cekat_wa_template_name'
    ];
    var parts = ['<table class="widefat striped" style="margin-top:8px;"><tbody>'];
    keys.forEach(function (k) {
      if (!row || !Object.prototype.hasOwnProperty.call(row, k)) {
        return;
      }
      var v = row[k];
      var display = v == null || v === '' ? '—' : String(v);
      if (k === 'cekat_image_url' && display !== '—' && /^https?:\/\//i.test(display)) {
        display =
          '<a href="' +
          escHtml(display) +
          '" target="_blank" rel="noopener noreferrer">' +
          escHtml(display) +
          '</a>';
      } else if (k === 'cekat_description' || k === 'cekat_ai_agent_json') {
        display =
          '<pre style="white-space:pre-wrap;margin:0;max-height:160px;overflow:auto;font-size:12px;">' +
          escHtml(display) +
          '</pre>';
      } else {
        display = '<code style="font-size:12px;">' + escHtml(display) + '</code>';
      }
      parts.push(
        '<tr><th scope="row" style="width:220px;vertical-align:top;">' +
          escHtml(k) +
          '</th><td>' +
          display +
          '</td></tr>'
      );
    });
    parts.push('</tbody></table>');
    return parts.join('');
  }

  function fetchTemplatesForInbox(inboxId) {
    var c = cfg();
    if (!inboxId || !c.templatesRestUrl) {
      return Promise.resolve([]);
    }
    var url =
      c.templatesRestUrl +
      (c.templatesRestUrl.indexOf('?') >= 0 ? '&' : '?') +
      'inbox_masking_id=' +
      encodeURIComponent(inboxId);
    return fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': c.nonce || '' }
    }).then(function (r) {
      if (!r.ok) {
        throw new Error('bad');
      }
      return r.json();
    }).then(function (body) {
      return (body && body.data) || [];
    });
  }

  function loadTemplatePreviewForRow(row) {
    var c = cfg();
    var inboxId = row && row.cekat_masking_id ? String(row.cekat_masking_id).trim() : '';
    var waTid = row && row.cekat_wa_template_masking_id ? String(row.cekat_wa_template_masking_id).trim() : '';

    if (!inboxId || !c.templatesRestUrl) {
      setPreviewBox(
        'eh-bo-view-template-preview',
        null,
        true,
        c.strTemplatePreviewNoneCustomer || c.strTemplatePreviewNone,
        c.strTemplatePreviewLeadCustomer || ''
      );
      return;
    }

    fetchTemplatesForInbox(inboxId)
      .then(function (rows) {
        var foundWa = null;
        rows.forEach(function (t) {
          if (!t || !t.id) {
            return;
          }
          if (waTid && String(t.id) === waTid) {
            foundWa = t;
          }
        });
        setPreviewBox(
          'eh-bo-view-template-preview',
          foundWa,
          true,
          c.strTemplatePreviewNoneCustomer || c.strTemplatePreviewNone,
          c.strTemplatePreviewLeadCustomer || ''
        );
      })
      .catch(function () {
        var errHtml =
          '<p class="notice notice-error" style="margin:0;">' + escHtml(c.strTemplateLoadError || 'Error') + '</p>';
        var boxWa = el('eh-bo-view-template-preview');
        if (boxWa) {
          boxWa.style.display = 'block';
          boxWa.innerHTML = errHtml;
        }
      });
  }

  w.addEventListener('DOMContentLoaded', function () {
    loadApiSelect();
    var apiSel = el('eh-bo-api-select');
    if (apiSel) {
      apiSel.addEventListener('change', onApiChange);
    }

    var openAddBtn = el('eh-bo-open-add');
    if (openAddBtn) {
      openAddBtn.addEventListener('click', function () {
        clearAddForm();
        openModal('eh-bo-modal-add');
      });
    }
    var closeAdd = el('eh-bo-close-add');
    var modalAdd = el('eh-bo-modal-add');
    if (closeAdd && modalAdd) {
      closeAdd.addEventListener('click', function () {
        closeModal('eh-bo-modal-add');
      });
      modalAdd.addEventListener('click', function (e) {
        if (e.target === modalAdd) {
          closeModal('eh-bo-modal-add');
        }
      });
    }

    wireWaTemplateSelect('eh-bo-add');
    wireWaTemplateSelect('eh-bo-edit');

    var editM = el('eh-bo-modal-edit');
    var editClose = el('eh-bo-close-edit');
    if (editClose && editM) {
      editClose.addEventListener('click', function () {
        closeModal('eh-bo-modal-edit');
      });
      editM.addEventListener('click', function (e) {
        if (e.target === editM) {
          closeModal('eh-bo-modal-edit');
        }
      });
    }

    var viewModal = el('eh-bo-modal-view');
    var closeView = el('eh-bo-close-view');
    if (closeView) {
      closeView.addEventListener('click', function () {
        closeModal('eh-bo-modal-view');
      });
    }
    if (viewModal) {
      viewModal.addEventListener('click', function (e) {
        if (e.target === viewModal) {
          closeModal('eh-bo-modal-view');
        }
      });
    }

    document.querySelectorAll('.eh-bo-open-view').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var row = decodeBo(btn.getAttribute('data-bo') || '');
        var wrap = el('eh-bo-view-fields');
        if (!wrap || !row) {
          return;
        }
        wrap.innerHTML = fieldRows(row);
        loadTemplatePreviewForRow(row);
        openModal('eh-bo-modal-view');
      });
    });

    document.querySelectorAll('.eh-bo-open-edit').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var row = decodeBo(btn.getAttribute('data-bo') || '');
        if (!row) {
          return;
        }
        setVal('eh-bo-edit-branch_outlet_id', row.id);
        fillFromRow('eh-bo-edit', row);
        var inboxId = row.cekat_masking_id || '';
        loadTemplates(
          'eh-bo-edit',
          inboxId,
          row.cekat_wa_template_masking_id || '',
          row.cekat_wa_template_name || ''
        );
        openModal('eh-bo-modal-edit');
      });
    });

    w.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closeModal('eh-bo-modal-add');
        closeModal('eh-bo-modal-edit');
        closeModal('eh-bo-modal-view');
      }
    });
  });
})(window);
