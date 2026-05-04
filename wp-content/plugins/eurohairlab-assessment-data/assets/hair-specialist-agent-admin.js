(function (w) {
  'use strict';

  function el(id) {
    return document.getElementById(id);
  }

  function decodeRow(b64) {
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

  function setText(id, v) {
    var n = el(id);
    if (n) {
      n.textContent = v == null ? '' : String(v);
    }
  }

  function setChecked(id, on) {
    var n = el(id);
    if (n && n.type === 'checkbox') {
      n.checked = !!on;
    }
  }

  function showModal(node) {
    if (node) {
      node.style.display = 'flex';
    }
  }

  function hideModal(node) {
    if (node) {
      node.style.display = 'none';
    }
  }

  function loadAgentsInto(selectId, onPick) {
    var cfg = w.ehHairSpecialistAgentAdmin || {};
    var sel = el(selectId);
    if (!sel || !cfg.restUrl) {
      return;
    }
    fetch(cfg.restUrl, {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': cfg.nonce || '' },
    })
      .then(function (r) {
        if (!r.ok) {
          throw new Error('bad');
        }
        return r.json();
      })
      .then(function (body) {
        var rows = (body && body.data) || [];
        sel.innerHTML = '';
        var opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = 'Select agent…';
        sel.appendChild(opt0);
        rows.forEach(function (row) {
          if (!row || !row.masking_id) {
            return;
          }
          var o = document.createElement('option');
          o.value = row.masking_id;
          o.textContent = (row.name || row.masking_id) + (row.email ? ' — ' + row.email : '');
          o.setAttribute('data-name', row.name || '');
          o.setAttribute('data-email', row.email || '');
          sel.appendChild(o);
        });
        sel.onchange = onPick;
      })
      .catch(function () {
        var err = el('eh-hsa-api-error');
        if (err) {
          err.style.display = 'block';
        }
        sel.innerHTML = '';
        var ox = document.createElement('option');
        ox.value = '';
        ox.textContent = 'Could not load agents';
        sel.appendChild(ox);
      });
  }

  function fillAddFromSelect() {
    var sel = el('eh-hsa-add-api-select');
    if (!sel) {
      return;
    }
    var opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) {
      setVal('eh-hsa-add-masking-id', '');
      setText('eh-hsa-add-masking-display', '');
      setVal('eh-hsa-add-agent-name', '');
      setVal('eh-hsa-add-agent-email', '');
      return;
    }
    setVal('eh-hsa-add-masking-id', opt.value);
    setText('eh-hsa-add-masking-display', opt.value);
    setVal('eh-hsa-add-agent-name', opt.getAttribute('data-name') || '');
    setVal('eh-hsa-add-agent-email', opt.getAttribute('data-email') || '');
  }

  var AGENT_CODE_RE = /^[A-Za-z0-9_-]+$/;

  function hsaCfg() {
    return w.ehHairSpecialistAgentAdmin || {};
  }

  function validateAgentCodeRaw(raw) {
    var c = hsaCfg();
    var maxLen = Number(c.agentCodeMaxLen) || 64;
    var s = String(raw == null ? '' : raw).trim();
    if (s === '') {
      return { ok: false, message: c.strAgentCodeEmpty || 'Agent code is required.' };
    }
    if (s.length > maxLen || !AGENT_CODE_RE.test(s)) {
      return { ok: false, message: c.strAgentCodeInvalid || 'Invalid agent code.' };
    }
    return { ok: true, message: '' };
  }

  function setAgentCodeAriaInvalid(inputId, invalid) {
    var inp = el(inputId);
    if (!inp) {
      return;
    }
    inp.setAttribute('aria-invalid', invalid ? 'true' : 'false');
  }

  function showHsaFormError(which, message) {
    var id = which === 'edit' ? 'eh-hsa-edit-form-error' : 'eh-hsa-add-form-error';
    var box = el(id);
    if (!box) {
      return;
    }
    box.textContent = message;
    box.style.display = 'block';
  }

  function clearHsaFormError(which) {
    var id = which === 'edit' ? 'eh-hsa-edit-form-error' : 'eh-hsa-add-form-error';
    var box = el(id);
    if (box) {
      box.textContent = '';
      box.style.display = 'none';
    }
    var inputId = which === 'edit' ? 'eh-hsa-edit-agent-code' : 'eh-hsa-add-agent-code';
    setAgentCodeAriaInvalid(inputId, false);
  }

  function wireAgentCodeFormValidation(form, which) {
    if (!form) {
      return;
    }
    var inputId = which === 'edit' ? 'eh-hsa-edit-agent-code' : 'eh-hsa-add-agent-code';
    var codeInput = el(inputId);
    if (codeInput) {
      codeInput.addEventListener('input', function () {
        clearHsaFormError(which);
      });
    }
    form.addEventListener(
      'submit',
      function (e) {
        var inp = el(inputId);
        var raw = inp ? inp.value : '';
        var res = validateAgentCodeRaw(raw);
        if (!res.ok) {
          e.preventDefault();
          e.stopPropagation();
          setAgentCodeAriaInvalid(inputId, true);
          showHsaFormError(which, res.message);
          if (inp && typeof inp.focus === 'function') {
            inp.focus();
          }
          if (inp && typeof inp.scrollIntoView === 'function') {
            inp.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
          }
          return false;
        }
        clearHsaFormError(which);
      },
      true
    );
  }

  function copyUrl(url) {
    var toast = el('eh-hsa-copy-toast');
    function showToast() {
      if (!toast) {
        return;
      }
      toast.style.display = 'block';
      w.setTimeout(function () {
        toast.style.display = 'none';
      }, 2000);
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(showToast).catch(function () {
        w.prompt('Copy this URL:', url);
      });
    } else {
      w.prompt('Copy this URL:', url);
    }
  }

  w.addEventListener('DOMContentLoaded', function () {
    var modalAdd = el('eh-hsa-modal-add');
    var modalEdit = el('eh-hsa-modal-edit');

    loadAgentsInto('eh-hsa-add-api-select', fillAddFromSelect);

    wireAgentCodeFormValidation(el('eh-hsa-form-add'), 'add');
    wireAgentCodeFormValidation(el('eh-hsa-form-edit'), 'edit');

    var openAdd = el('eh-hsa-open-add');
    if (openAdd) {
      openAdd.addEventListener('click', function () {
        clearHsaFormError('add');
        var sel = el('eh-hsa-add-api-select');
        if (sel) {
          sel.selectedIndex = 0;
        }
        fillAddFromSelect();
        setVal('eh-hsa-add-branch', '');
        setVal('eh-hsa-add-agent-code', '');
        setChecked('eh-hsa-add-exclude-rr', false);
        showModal(modalAdd);
      });
    }

    var closeAdd = el('eh-hsa-close-add');
    if (closeAdd) {
      closeAdd.addEventListener('click', function () {
        hideModal(modalAdd);
      });
    }
    if (modalAdd) {
      modalAdd.addEventListener('click', function (e) {
        if (e.target === modalAdd) {
          hideModal(modalAdd);
        }
      });
    }

    var closeEdit = el('eh-hsa-close-edit');
    if (closeEdit) {
      closeEdit.addEventListener('click', function () {
        hideModal(modalEdit);
      });
    }
    if (modalEdit) {
      modalEdit.addEventListener('click', function (e) {
        if (e.target === modalEdit) {
          hideModal(modalEdit);
        }
      });
    }

    document.querySelectorAll('.eh-hsa-open-edit').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var row = decodeRow(btn.getAttribute('data-row'));
        if (!row) {
          return;
        }
        setVal('eh-hsa-edit-row-id', row.id || '');
        setVal('eh-hsa-edit-masking-id', row.masking_id || '');
        setText('eh-hsa-edit-masking-display', row.masking_id || '');
        setVal('eh-hsa-edit-agent-name', row.name || '');
        setVal('eh-hsa-edit-agent-email', row.email || '');
        setVal('eh-hsa-edit-branch', row.branch_outlet_id ? String(row.branch_outlet_id) : '');
        setVal('eh-hsa-edit-agent-code', row.agent_code || '');
        var ex = row.exclude_from_round_robin;
        setChecked(
          'eh-hsa-edit-exclude-rr',
          ex === 1 || ex === '1' || ex === true
        );
        clearHsaFormError('edit');
        showModal(modalEdit);
      });
    });

    document.querySelectorAll('.eh-hsa-copy-link').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var url = btn.getAttribute('data-url') || '';
        if (url) {
          copyUrl(url);
        }
      });
    });
  });
})(window);
