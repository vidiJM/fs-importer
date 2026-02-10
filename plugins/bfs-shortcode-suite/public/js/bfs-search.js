/* global BFS_SEARCH */
(() => {
  'use strict';

  const cfg = window.BFS_SEARCH || {};
  const root = document.querySelector('.bfs-search');
  if (!root) return;

  const trigger = root.querySelector('.bfs-search__trigger');
  const overlay = root.querySelector('.bfs-search__overlay');
  const panel = root.querySelector('.bfs-search__panel');
  const input = root.querySelector('.bfs-search__input');
  const clearBtn = root.querySelector('.bfs-search__clear');
  const closeEls = root.querySelectorAll('[data-bfs-close]');
  const filtersBar = root.querySelector('.bfs-search__filters');
  const hint = root.querySelector('[data-bfs-hint]');
  const resultsEl = root.querySelector('[data-bfs-results]');
  const moreBtn = root.querySelector('[data-bfs-more]');
  const historyEl = root.querySelector('[data-bfs-history]');
  const clearHistoryBtn = root.querySelector('[data-bfs-clear-history]');
  const suggestionsEl = root.querySelector('[data-bfs-suggestions]');
  const popover = root.querySelector('[data-bfs-popover]');
  const stockCheck = root.querySelector('.bfs-stock__check');

  const state = {
    q: '',
    page: 1,
    hasMore: false,
    loading: false,
    filters: {
      price_min: '',
      price_max: '',
      in_stock: true,
      marca: [],
      categoria: [],
      superficie: [],
      color: [],
      genero: [],
      talla: [],
    },
    filterOptions: null,
  };

  const HISTORY_KEY = 'bfs_search_history_v1';
  const debounce = (fn, ms) => {
    let t = null;
    return (...args) => {
      window.clearTimeout(t);
      t = window.setTimeout(() => fn(...args), ms);
    };
  };

  const lockBody = (lock) => {
    document.documentElement.classList.toggle('bfs-noscroll', lock);
    document.body.style.overflow = lock ? 'hidden' : '';
  };

  const open = async () => {
    overlay.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
    lockBody(true);
    window.requestAnimationFrame(() => input.focus());
    renderHistory();
    maybeLoadFilterOptions();
    setHint(cfg.i18n?.minCharsHint || '');
  };

  const close = () => {
    overlay.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    lockBody(false);
    hidePopover();
  };

  closeEls.forEach((el) => el.addEventListener('click', close));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && overlay && overlay.hidden === false) close();
  });

  trigger.addEventListener('click', open);

  const setHint = (text) => {
    if (!hint) return;
    hint.textContent = text || '';
    hint.style.display = text ? 'block' : 'none';
  };

  const renderResults = (items, append = false) => {
    if (!resultsEl) return;
    if (!append) resultsEl.innerHTML = '';
    const frag = document.createDocumentFragment();

    items.forEach((it) => {
      const a = document.createElement('a');
      a.className = 'bfs-card';
      a.href = it.permalink;

      const img = document.createElement('img');
      img.className = 'bfs-card__img';
      img.loading = 'lazy';
      img.alt = it.title || '';
      img.src = it.image || '';
      a.appendChild(img);

      const body = document.createElement('div');
      body.className = 'bfs-card__body';

      const h = document.createElement('p');
      h.className = 'bfs-card__title';
      h.textContent = it.title || '';
      body.appendChild(h);

      const meta = document.createElement('p');
      meta.className = 'bfs-card__meta';
      meta.textContent = (it.min_price !== null && it.min_price !== undefined) ? `${it.min_price} €` : '';
      body.appendChild(meta);

      a.appendChild(body);
      frag.appendChild(a);
    });

    resultsEl.appendChild(frag);
  };

  const apiGet = async (path, params = {}) => {
    const url = new URL(cfg.restUrl + path, window.location.origin);
    Object.entries(params).forEach(([k, v]) => {
      if (v === null || v === undefined || v === '') return;
      if (Array.isArray(v)) {
        v.forEach((val) => url.searchParams.append(`${k}[]`, String(val)));
      } else {
        url.searchParams.set(k, String(v));
      }
    });

    const headers = {};
    if (cfg.nonce) headers['X-WP-Nonce'] = cfg.nonce;

    const res = await fetch(url.toString(), { headers, credentials: 'same-origin' });
    const json = await res.json();
    if (!json || json.success !== true) throw new Error('API error');
    return json.data;
  };

  const doSearch = async ({ append = false } = {}) => {
    if (state.loading) return;

    const q = state.q.trim();
    if (q.length < (cfg.minChars || 2)) {
      filtersBar.hidden = true;
      moreBtn.hidden = true;
      renderResults([], false);
      setHint(cfg.i18n?.minCharsHint || '');
      return;
    }

    filtersBar.hidden = false;
    setHint('');
    state.loading = true;

    const params = {
      q,
      page: state.page,
      per_page: cfg.maxResults || 12,
      price_min: state.filters.price_min,
      price_max: state.filters.price_max,
      in_stock: state.filters.in_stock ? 1 : 0,
      marca: state.filters.marca,
      categoria: state.filters.categoria,
      superficie: state.filters.superficie,
      color: state.filters.color,
      genero: state.filters.genero,
      talla: state.filters.talla,
    };

    try {
      const data = await apiGet('/search', params);
      state.hasMore = !!data.hasMore;
      renderResults(data.items || [], append);
      moreBtn.hidden = !state.hasMore;
    } catch (e) {
      // fail silently (UX)
      moreBtn.hidden = true;
      if (!append) renderResults([], false);
      setHint(cfg.i18n?.noResults || 'Sin resultados');
    } finally {
      state.loading = false;
    }
  };

  const doSearchDebounced = debounce(() => {
    state.page = 1;
    doSearch({ append: false });
  }, 320);

  const saveHistory = (term) => {
    const t = term.trim();
    if (!t) return;

    const limit = cfg.historyLimit || 8;
    const cur = loadHistory();
    const next = [t, ...cur.filter((x) => x !== t)].slice(0, limit);
    window.localStorage.setItem(HISTORY_KEY, JSON.stringify(next));
    renderHistory();
  };

  const loadHistory = () => {
    try {
      const raw = window.localStorage.getItem(HISTORY_KEY);
      const arr = JSON.parse(raw || '[]');
      return Array.isArray(arr) ? arr.filter(Boolean) : [];
    } catch {
      return [];
    }
  };

  const removeHistoryItem = (term) => {
    const cur = loadHistory().filter((x) => x !== term);
    window.localStorage.setItem(HISTORY_KEY, JSON.stringify(cur));
    renderHistory();
  };

  const clearHistory = () => {
    window.localStorage.removeItem(HISTORY_KEY);
    renderHistory();
  };

  const renderHistory = () => {
    const items = loadHistory();
    historyEl.innerHTML = '';
    clearHistoryBtn.hidden = items.length === 0;

    items.forEach((t) => {
      const li = document.createElement('li');
      li.className = 'bfs-list__item';

      const row = document.createElement('div');
      row.className = 'bfs-history';

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'bfs-history__btn';
      btn.textContent = t;
      btn.addEventListener('click', () => {
        input.value = t;
        onInputChange();
      });

      const x = document.createElement('button');
      x.type = 'button';
      x.className = 'bfs-history__x';
      x.setAttribute('aria-label', 'Eliminar');
      x.textContent = '×';
      x.addEventListener('click', (e) => {
        e.stopPropagation();
        removeHistoryItem(t);
      });

      row.appendChild(btn);
      row.appendChild(x);
      li.appendChild(row);
      historyEl.appendChild(li);
    });
  };

  clearHistoryBtn.addEventListener('click', clearHistory);

  const onInputChange = () => {
    const v = (input.value || '').trim();
    state.q = v;
    clearBtn.hidden = v.length === 0;
    doSearchDebounced();
  };

  input.addEventListener('input', onInputChange);
  clearBtn.addEventListener('click', () => {
    input.value = '';
    clearBtn.hidden = true;
    state.q = '';
    state.page = 1;
    filtersBar.hidden = true;
    moreBtn.hidden = true;
    hidePopover();
    renderResults([], false);
    renderHistory();
    setHint(cfg.i18n?.minCharsHint || '');
    input.focus();
  });

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      saveHistory(state.q);
    }
  });

  suggestionsEl.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-bfs-suggest]');
    if (!btn) return;
    const term = btn.getAttribute('data-bfs-suggest') || '';
    input.value = term;
    onInputChange();
    saveHistory(term);
  });

  moreBtn.addEventListener('click', () => {
    if (!state.hasMore) return;
    state.page += 1;
    doSearch({ append: true });
  });

  stockCheck.addEventListener('change', () => {
    state.filters.in_stock = !!stockCheck.checked;
    state.page = 1;
    doSearch({ append: false });
  });

  // Filters popover (minimal MVP)
  const showPopover = (html) => {
    popover.innerHTML = html;
    popover.hidden = false;
  };
  const hidePopover = () => {
    popover.hidden = true;
    popover.innerHTML = '';
  };

  panel.addEventListener('click', (e) => {
    const chip = e.target.closest('[data-bfs-chip]');
    if (!chip) return;

    const kind = chip.getAttribute('data-bfs-chip');
    if (!kind) return;

    if (!state.filterOptions) {
      maybeLoadFilterOptions().then(() => openPopover(kind));
    } else {
      openPopover(kind);
    }
  });

  document.addEventListener('click', (e) => {
    if (popover.hidden) return;
    const inside = e.target.closest('[data-bfs-popover]') || e.target.closest('[data-bfs-chip]');
    if (!inside) hidePopover();
  });

  const maybeLoadFilterOptions = async () => {
    if (state.filterOptions) return state.filterOptions;
    try {
      state.filterOptions = await apiGet('/filters', {});
    } catch {
      state.filterOptions = {};
    }
    return state.filterOptions;
  };

  const openPopover = (kind) => {
    if (!kind || kind === 'more') {
      // placeholder: could open a full panel later
      showPopover(`<div style="font-size:13px;opacity:.7">Próximamente: panel avanzado de filtros.</div>`);
      return;
    }

    if (kind === 'price') {
      showPopover(`
        <div class="bfs-popover__row">
          <input type="number" step="0.01" inputmode="decimal" placeholder="${cfg.i18n?.min || 'Min'}" value="${state.filters.price_min || ''}" data-bfs-price="min">
          <input type="number" step="0.01" inputmode="decimal" placeholder="${cfg.i18n?.max || 'Max'}" value="${state.filters.price_max || ''}" data-bfs-price="max">
        </div>
        <div class="bfs-popover__actions">
          <button type="button" class="bfs-btn" data-bfs-pop-reset>${cfg.i18n?.reset || 'Reset'}</button>
          <button type="button" class="bfs-btn bfs-btn--primary" data-bfs-pop-apply>${cfg.i18n?.apply || 'Aplicar'}</button>
        </div>
      `);

      popover.querySelector('[data-bfs-pop-apply]').addEventListener('click', () => {
        const min = popover.querySelector('[data-bfs-price="min"]').value;
        const max = popover.querySelector('[data-bfs-price="max"]').value;
        state.filters.price_min = min;
        state.filters.price_max = max;
        state.page = 1;
        hidePopover();
        doSearch({ append: false });
      });

      popover.querySelector('[data-bfs-pop-reset]').addEventListener('click', () => {
        state.filters.price_min = '';
        state.filters.price_max = '';
        state.page = 1;
        hidePopover();
        doSearch({ append: false });
      });

      return;
    }

    const list = (state.filterOptions && state.filterOptions[kind]) ? state.filterOptions[kind] : [];
    const selected = new Set(state.filters[kind] || []);

    const rows = list.map((t) => {
      const key = (kind === 'talla') ? String(t.id) : Number(t.id);
      const checked = selected.has(key) ? 'checked' : '';
      return `
        <label style="display:flex;align-items:center;gap:10px;padding:8px 2px;">
          <input type="checkbox" value="${t.id}" ${checked} data-bfs-opt="${kind}">
          <span style="font-size:13px">${escapeHtml(t.name)}</span>
        </label>
      `;
    }).join('');

    showPopover(`
      <div style="max-height:280px;overflow:auto;padding-right:6px">${rows || '<div style="font-size:13px;opacity:.7">Sin opciones</div>'}</div>
      <div class="bfs-popover__actions">
        <button type="button" class="bfs-btn" data-bfs-pop-reset>${cfg.i18n?.reset || 'Reset'}</button>
        <button type="button" class="bfs-btn bfs-btn--primary" data-bfs-pop-apply>${cfg.i18n?.apply || 'Aplicar'}</button>
      </div>
    `);

    popover.querySelector('[data-bfs-pop-apply]').addEventListener('click', () => {
      const checks = [...popover.querySelectorAll('input[type="checkbox"][data-bfs-opt]')];
      const vals = checks.filter((c) => c.checked).map((c) => (kind === 'talla' ? String(c.value) : Number(c.value)));
      state.filters[kind] = (kind === 'talla') ? vals.filter((v) => v) : vals.filter((v) => Number.isFinite(v) && v > 0);
      state.page = 1;
      hidePopover();
      doSearch({ append: false });
    });

    popover.querySelector('[data-bfs-pop-reset]').addEventListener('click', () => {
      state.filters[kind] = [];
      state.page = 1;
      hidePopover();
      doSearch({ append: false });
    });
  };

  const escapeHtml = (s) => String(s || '').replace(/[&<>"']/g, (m) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  }[m]));

})();
