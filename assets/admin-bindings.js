/**
 * OIDC 绑定管理页面脚本
 *
 * 独立于 Twig 模板，通过 <script type="application/json" id="oidcAdminConfig">
 * 注入的配置块读取接口地址、当前分页大小与前端文案（i18n）。
 *
 * 加载方式：bootstrap.php 中 Hook::addScriptFileToPage(..., ['admin/oidc'])
 * 该脚本在页面底部（jQuery/Bootstrap 之后）加载，但为稳妥起见仍做 DOM 就绪检查。
 */
(function () {
  'use strict';

  // ── 读取页面注入的配置 ──
  var configEl = document.getElementById('oidcAdminConfig');
  if (!configEl) {
    // 非目标页面或配置缺失，静默退出（脚本虽只在 admin/oidc 加载，仍做防御）
    return;
  }

  var config;
  try {
    config = JSON.parse(configEl.textContent);
  } catch (e) {
    console.error('OIDC Admin: 页面配置解析失败', e);
    return;
  }

  var URLS = config.urls || {};
  var L = config.i18n || {};
  var PER_PAGE = config.perPage;

  // ── 前端校验规则（与后端控制器保持一致） ──
  var SUB_REGEX = /^[a-zA-Z0-9\-_.\/:@+=~]+$/;
  var ISSUER_REGEX = /^[a-zA-Z0-9\-_.\/:@+=~?#%&]*$/;
  var DANGER_PATTERNS = [
    /\b(DROP|DELETE|INSERT|UPDATE|ALTER|CREATE|TRUNCATE|EXEC|EXECUTE)\b/i,
    /\b(UNION\s+SELECT|OR\s+1\s*=\s*1|AND\s+1\s*=\s*1)\b/i,
    /(--|\/\*|\*\/|;)/
  ];

  /**
   * 校验字段值，返回错误信息或 null
   * @param {string} field 字段名（oidc_sub / oidc_issuer）
   * @param {string} value 输入值
   * @returns {?string} 错误消息，无错误返回 null
   */
  function validateField(field, value) {
    if (field === 'oidc_sub') {
      if (value.trim() === '') return L.err_empty;
      if (value.length > 255) return L.err_too_long;
      if (!SUB_REGEX.test(value)) return L.err_illegal;
    } else if (field === 'oidc_issuer') {
      if (value.trim() !== '' && value.length > 512) return L.err_too_long;
      if (value.trim() !== '' && !ISSUER_REGEX.test(value)) return L.err_illegal;
    }
    for (var i = 0; i < DANGER_PATTERNS.length; i++) {
      if (DANGER_PATTERNS[i].test(value)) return L.err_danger;
    }
    return null;
  }

  /** 从 meta 标签读取 CSRF token */
  function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  /**
   * POST JSON 请求
   * @param {string} url 接口地址
   * @param {object} data 请求体（自动附加 _token）
   * @param {function} onSuccess code === 0 时回调
   * @param {function} onError 失败时回调，参数为错误消息
   */
  function postAjax(url, data, onSuccess, onError) {
    var payload = Object.assign({ _token: getCsrfToken() }, data);

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    })
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (resp.code === 0) {
        onSuccess(resp);
      } else {
        onError(resp.message || 'Error');
      }
    })
    .catch(function (err) {
      onError(err.message || 'Network error');
    });
  }

  // ── 编辑弹窗逻辑 ──
  function initEditModal() {
    var editBtn = document.getElementById('editConfirmBtn');
    var editInput = document.getElementById('editFieldValue');
    var editError = document.getElementById('editFieldError');
    if (!editBtn || !editInput || !editError) return;

    document.querySelectorAll('.btn-edit-field').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var field = this.getAttribute('data-field');
        var value = this.getAttribute('data-value') || '';
        var id = this.getAttribute('data-id');

        document.getElementById('editBindingId').value = id;
        document.getElementById('editFieldName').value = field;
        editInput.value = value;
        editInput.classList.remove('is-invalid', 'is-valid');
        editError.style.display = 'none';
        editError.textContent = '';
        editBtn.disabled = false;
        editBtn.textContent = L.confirm;

        if (field === 'oidc_sub') {
          document.getElementById('editModalTitle').textContent = L.edit_sub;
          document.getElementById('editFieldLabel').textContent = 'OIDC Subject (sub)';
          document.getElementById('editFieldHint').textContent = L.hint_sub;
        } else {
          document.getElementById('editModalTitle').textContent = L.edit_issuer;
          document.getElementById('editFieldLabel').textContent = 'OIDC Issuer';
          document.getElementById('editFieldHint').textContent = L.hint_issuer;
        }
      });
    });

    editInput.addEventListener('input', function () {
      var field = document.getElementById('editFieldName').value;
      var err = validateField(field, this.value);
      if (err) {
        editError.textContent = err;
        editError.style.display = 'block';
        this.classList.add('is-invalid');
        this.classList.remove('is-valid');
      } else {
        editError.style.display = 'none';
        this.classList.remove('is-invalid');
        if (this.value.trim() !== '') this.classList.add('is-valid');
      }
    });

    editBtn.addEventListener('click', function () {
      var field = document.getElementById('editFieldName').value;
      var value = editInput.value;
      var id = document.getElementById('editBindingId').value;

      var err = validateField(field, value);
      if (err) {
        editError.textContent = err;
        editError.style.display = 'block';
        editInput.classList.add('is-invalid');
        return;
      }

      editBtn.disabled = true;
      editBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>' + L.saving;

      postAjax(URLS.updateField, {
        id: id,
        field: field,
        value: value
      }, function () {
        window.location.reload();
      }, function (msg) {
        editBtn.disabled = false;
        editBtn.textContent = L.confirm;
        editError.textContent = msg;
        editError.style.display = 'block';
      });
    });
  }

  // ── 删除弹窗逻辑 ──
  function initDeleteModal() {
    var deleteBtn = document.getElementById('deleteConfirmBtn');
    if (!deleteBtn) return;

    document.querySelectorAll('.btn-delete').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.getElementById('deleteBindingId').value = this.getAttribute('data-id');
        document.getElementById('delInfoId').textContent = this.getAttribute('data-id');
        document.getElementById('delInfoUid').textContent = this.getAttribute('data-uid');
        document.getElementById('delInfoEmail').textContent = this.getAttribute('data-email');
        document.getElementById('delInfoNickname').textContent = this.getAttribute('data-nickname');
        document.getElementById('delInfoSub').textContent = this.getAttribute('data-sub');
        document.getElementById('delInfoIssuer').textContent = this.getAttribute('data-issuer');
        document.getElementById('delInfoUpdatedAt').textContent = this.getAttribute('data-updated-at');
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = '<i class="fas fa-trash mr-1"></i>' + L.delete_btn;
      });
    });

    deleteBtn.addEventListener('click', function () {
      var id = document.getElementById('deleteBindingId').value;

      deleteBtn.disabled = true;
      deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>' + L.deleting;

      postAjax(URLS.delete, { id: id }, function () {
        window.location.reload();
      }, function (msg) {
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = '<i class="fas fa-trash mr-1"></i>' + L.delete_btn;
        alert(msg);
      });
    });
  }

  // ── 分页选择器逻辑 ──
  function initPerPageSelect() {
    var perPageSelect = document.getElementById('perPageSelect');
    var perPageForm = document.getElementById('perPageForm');
    if (!perPageSelect || !perPageForm) return;

    perPageSelect.addEventListener('change', function () {
      var val = this.value;
      var numVal = parseInt(val, 10);
      // 选择"全部"或超过 50 条时提示可能的数据库并发压力
      if (val === 'all' || numVal > 50) {
        if (!confirm(L.large_page_warn)) {
          this.value = PER_PAGE;
          return;
        }
      }
      perPageForm.submit();
    });
  }

  /** DOM 就绪后初始化（与 link-redirect.js 保持一致的时序保险） */
  function onReady(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
    } else {
      callback();
    }
  }

  onReady(function () {
    initEditModal();
    initDeleteModal();
    initPerPageSelect();
  });
})();
