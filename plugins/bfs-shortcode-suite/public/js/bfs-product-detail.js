(() => {
  const root = document.querySelector('.bfs-pd[data-bfs-pd]');
  if (!root) return;

  const data = JSON.parse(root.getAttribute('data-bfs-pd') || '{}');

  const elImg = root.querySelector('.bfs-pd__img');
  const elThumbs = root.querySelector('.bfs-pd__thumbs');
  const elColors = root.querySelector('.bfs-pd__colors');
  const elSizes = root.querySelector('.bfs-pd__sizes');
  const elMerchants = root.querySelector('.bfs-pd__merchants');
  const elPrice = root.querySelector('.bfs-pd__priceValue');
  const elDesc = root.querySelector('.bfs-pd__desc');

  const colors = Array.isArray(data.colors) ? data.colors : [];

  const state = {
    color: null,
    size: null,
    image: null,
  };

  const fmt = (n) => {
    if (typeof n !== 'number' || Number.isNaN(n)) return '';
    try {
      return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n);
    } catch (e) {
      return `${n.toFixed(2)} €`;
    }
  };

  const getColorObj = (colorKey) => colors.find(c => c.color === colorKey) || null;

  const pickInitialColor = () => data.active_color || (colors[0] ? colors[0].color : null);

  const pickInitialImage = (c) => {
    const imgs = (c && Array.isArray(c.images)) ? c.images : [];
    return imgs[0] || '';
  };

  const renderDescription = () => {
    if (!elDesc) return;
    const map = (data && typeof data.descriptions_by_color === 'object' && data.descriptions_by_color) ? data.descriptions_by_color : {};
    const html = (state.color && map && map[state.color]) ? map[state.color] : (data.description_default || data.description || '');
    elDesc.innerHTML = html;
  };

  const renderPrice = () => {
    const c = getColorObj(state.color);
    if (!c) { elPrice.textContent = ''; return; }

    const price = (state.size && c.sizes && c.sizes[state.size] && c.sizes[state.size].min_price != null)
      ? c.sizes[state.size].min_price
      : c.min_price;

    elPrice.textContent = price != null ? `Desde ${fmt(price)}` : '';
  };
  
  const normalizeColorTokens = (raw) => {
    if (!raw) return [];
    const up = String(raw).trim().toUpperCase();
    if (!up) return [];

    // Separadores típicos en feeds: "-", "/", "+", "|", ","
    const parts = up.split(/[\s]*(?:-|\/|–|—|\+|\||,)[\s]*/).map(s => s.trim()).filter(Boolean);

    // Modificadores que suelen pertenecer al color anterior.
    const modifiers = new Set(['MARINO','ROYAL','OSCURO','CLARO','FLUOR','NEON','NEÓN','PASTEL','METAL','METALICO','METÁLICO','PLATA','DORADO']);

    const out = [];
    for (const p of parts) {
      if (!p) continue;
      if (modifiers.has(p) && out.length) {
        out[out.length - 1] = `${out[out.length - 1]} ${p}`;
        continue;
      }
      out.push(p);
    }

    // dedup preservando orden
    const unique = [];
    for (const c of out) {
      if (!unique.includes(c)) unique.push(c);
    }
    return unique;
  };

  const mapColorToHex = (token) => {
    const t = String(token || '').toUpperCase().trim();
    const colorMap = {
      BLANCO: '#ffffff',
      NEGRO: '#000000',
      ROJO: '#e60023',
      VERDE: '#2e7d32',
      AZUL: '#1565c0',
      AMARILLO: '#fdd835',
      MORADO: '#8e24aa',
      GRIS: '#9e9e9e',
      NARANJA: '#ff8000',
      ROSA: '#ec4899',
      TURQUESA: '#14b8a6',
      MULTICOLOR: '#CBD5E1',
    };

    if (colorMap[t]) return colorMap[t];
    if (/^#([0-9A-F]{3}){1,2}$/i.test(t)) return t;
    return '#CBD5E1';
  };

  const swatchStyle = (raw) => {
    const tokens = normalizeColorTokens(raw);
    if (!tokens.length) return 'background-color:#CBD5E1;';

    const mapped = [...new Set(tokens.map(mapColorToHex))];

    if (mapped.length === 1) {
      return `background-color:${mapped[0]};`;
    }
    if (mapped.length === 2) {
      return `background: linear-gradient(135deg, ${mapped[0]} 0 50%, ${mapped[1]} 50% 100%);`;
    }

    // 3+ colores: conic gradient
    const n = mapped.length;
    const step = 100 / n;
    const stops = mapped.map((c, i) => {
      const from = (i * step).toFixed(4);
      const to = ((i + 1) * step).toFixed(4);
      return `${c} ${from}% ${to}%`;
    }).join(', ');

    return `background: conic-gradient(from 90deg, ${stops});`;
  };

  const renderColors = () => {
    elColors.innerHTML = '';
    if (!colors.length) return;

    colors.forEach(c => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'bfs-pd__color' + (c.color === state.color ? ' is-active' : '');
      btn.setAttribute('data-color', c.color);
      btn.setAttribute('aria-label', `Color ${c.color}`);
      btn.title = c.color;

      const sw = document.createElement('span');
      sw.className = 'bfs-pd__swatch';
      sw.setAttribute('aria-hidden', 'true');
      sw.setAttribute('style', swatchStyle(c.color));

      btn.appendChild(sw);

      btn.addEventListener('click', () => {
        state.color = c.color;
        state.size = null;
        state.image = pickInitialImage(c);
        renderAll();
      });

      elColors.appendChild(btn);
    });
  };

  const renderThumbs = () => {
    const c = getColorObj(state.color);
    const imgs = (c && Array.isArray(c.images)) ? c.images : [];

    elThumbs.innerHTML = '';

    if (!imgs.length) return;

    imgs.slice(0, 10).forEach((src) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'bfs-pd__thumb' + (src === state.image ? ' is-active' : '');
      btn.setAttribute('data-img', src);

      const img = document.createElement('img');
      img.loading = 'lazy';
      img.decoding = 'async';
      img.alt = '';
      img.src = src;

      btn.appendChild(img);
      elThumbs.appendChild(btn);
    });
  };

  const renderSizes = () => {
    const c = getColorObj(state.color);
    elSizes.innerHTML = '';
    if (!c || !c.sizes) return;

    Object.keys(c.sizes).forEach(size => {
      const s = c.sizes[size];
      const inStock = !!s.in_stock;

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'bfs-pd__size' + (size === state.size ? ' is-active' : '');
      btn.disabled = !inStock;
      btn.setAttribute('data-size', size);
      btn.textContent = `EU ${size}`;
      elSizes.appendChild(btn);
    });
  };

  const renderMerchants = () => {
    const c = getColorObj(state.color);
    elMerchants.innerHTML = '';

    if (!c || !state.size || !c.sizes || !c.sizes[state.size]) {
      elMerchants.innerHTML = '<div class="bfs-pd__hint">Selecciona una talla para ver tiendas.</div>';
      return;
    }

    const offers = Array.isArray(c.sizes[state.size].offers) ? c.sizes[state.size].offers : [];
    const inStockOffers = offers.filter(o => o && o.in_stock && o.url);

    if (!inStockOffers.length) {
      elMerchants.innerHTML = '<div class="bfs-pd__hint">No hay tiendas con stock para esta talla.</div>';
      return;
    }

    inStockOffers.sort((a, b) => (a.price ?? 999999) - (b.price ?? 999999));

    inStockOffers.forEach(o => {
      const a = document.createElement('a');
      a.className = 'bfs-pd__merchantBtn';
      a.href = o.url;
      a.target = '_blank';
      a.rel = 'nofollow noopener';

      const price = (o.price != null) ? fmt(o.price) : '';
      a.innerHTML = `<span class="bfs-pd__merchantName">${o.merchant}</span><span class="bfs-pd__merchantPrice">${price}</span>`;
      elMerchants.appendChild(a);
    });
  };

  const applyImage = (src) => {
    state.image = src;
    if (elImg && src) elImg.src = src;
    renderThumbs();
  };

  const applyColor = (colorKey) => {
    state.color = colorKey;
    state.size = null;

    const c = getColorObj(colorKey);
    const img = pickInitialImage(c);

    renderColors();
    renderSizes();
    renderMerchants();
    renderPrice();

    applyImage(img);
  };

  const applySize = (size) => {
    state.size = size;
    renderSizes();
    renderMerchants();
    renderPrice();
  };

  root.addEventListener('click', (e) => {
    const thumbBtn = e.target.closest('.bfs-pd__thumb');
    if (thumbBtn) {
      const src = thumbBtn.getAttribute('data-img');
      if (src) applyImage(src);
      return;
    }

    const colorBtn = e.target.closest('.bfs-pd__color');
    if (colorBtn) {
      applyColor(colorBtn.getAttribute('data-color'));
      return;
    }

    const sizeBtn = e.target.closest('.bfs-pd__size');
    if (sizeBtn && !sizeBtn.disabled) {
      applySize(sizeBtn.getAttribute('data-size'));
    }
  });

  // init
  const initialColor = pickInitialColor();
  if (initialColor) {
    applyColor(initialColor);
  }
})();