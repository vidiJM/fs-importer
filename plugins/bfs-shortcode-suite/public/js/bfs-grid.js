(() => {
  "use strict";

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const parseJsonSafe = (str) => {
    try {
      const v = JSON.parse(String(str || ""));
      return v && typeof v === "object" ? v : null;
    } catch (_) {
      return null;
    }
  };

  const normalizeUrl = (u) => String(u || "").trim();

  const setImage = (imgEl, url) => {
    const next = normalizeUrl(url);
    if (!imgEl || !next) return;

    const current = normalizeUrl(imgEl.getAttribute("src"));
    if (current === next) return;

    imgEl.onload = null;
    imgEl.style.opacity = "0.02";
    imgEl.onload = () => { imgEl.style.opacity = "1"; };

    imgEl.setAttribute("src", next);
  };

  const preload = (url) => {
    const u = normalizeUrl(url);
    if (!u) return;
    const i = new Image();
    i.decoding = "async";
    i.loading = "eager";
    i.src = u;
  };

  const findVariantByImage = (variantMap, imgUrl) => {
    if (!variantMap || typeof variantMap !== "object") return null;
    const target = normalizeUrl(imgUrl);
    if (!target) return null;

    for (const key of Object.keys(variantMap)) {
      const v = variantMap[key];
      if (!v) continue;
      const vImg = normalizeUrl(v.image);
      if (vImg && vImg === target) return v;
    }
    return null;
  };

  const setPrice = (card, priceNumber) => {
    const priceEl = card.querySelector(".bfs-grid-price, .bfs-price-value");
    if (!priceEl) return;

    const p = Number(priceNumber || 0);
    priceEl.textContent = p > 0 ? `€ ${p.toFixed(2).replace(".", ",")}` : "";
  };

  const applyVariantFromDot = (card, dot) => {
    if (!card || !dot) return;

    card.querySelectorAll(".bfs-color-dot").forEach((b) => b.classList.remove("active"));
    dot.classList.add("active");

    const imgEl = card.querySelector(".bfs-grid-img");
    if (!imgEl) return;

    const imgUrl = normalizeUrl(dot.dataset.img);
    const hoverUrl = normalizeUrl(dot.dataset.hover);

    if (imgUrl) {
      card.dataset.baseImg = imgUrl;
      setImage(imgEl, imgUrl);
    }

    if (hoverUrl) {
      card.dataset.hoverImg = hoverUrl;
      preload(hoverUrl);
    } else {
      const fromCard = normalizeUrl(card.dataset.hoverImg);
      if (fromCard) preload(fromCard);
    }

    const dotPrice = normalizeUrl(dot.dataset.price);
    if (dotPrice) {
      const p = parseFloat(dotPrice);
      setPrice(card, Number.isFinite(p) ? p : 0);
      return;
    }

    const variantMap = parseJsonSafe(card.getAttribute("data-variants"));
    const v = findVariantByImage(variantMap, imgUrl);
    if (v && typeof v.price !== "undefined") {
      setPrice(card, parseFloat(String(v.price)));
    }
  };

  const initCard = (card) => {
    if (!card || card.__bfsGridInited) return;
    card.__bfsGridInited = true;

    const imgEl = card.querySelector(".bfs-grid-img");
    if (!imgEl) return;

    const baseFromCard = normalizeUrl(card.dataset.baseImg);
    const hoverFromCard = normalizeUrl(card.dataset.hoverImg);

    const imgMain = normalizeUrl(imgEl.dataset.imgMain) || normalizeUrl(imgEl.getAttribute("src"));
    if (!baseFromCard && imgMain) card.dataset.baseImg = imgMain;

    if (hoverFromCard) {
      preload(hoverFromCard);
    } else {
      const imgHover = normalizeUrl(imgEl.dataset.imgHover);
      if (imgHover) {
        card.dataset.hoverImg = imgHover;
        preload(imgHover);
      }
    }

    const firstDot = card.querySelector(".bfs-color-dot");
    if (firstDot) applyVariantFromDot(card, firstDot);

    card.addEventListener("pointerenter", () => {
      const h = normalizeUrl(card.dataset.hoverImg);
      if (h) setImage(imgEl, h);
    });

    card.addEventListener("pointerleave", () => {
      const base = normalizeUrl(card.dataset.baseImg);
      if (base) setImage(imgEl, base);
    });
  };

  const initCardsIn = (root) => {
    $$(".bfs-grid-card", root).forEach(initCard);
  };

  const isMobile = () =>
    window.matchMedia && window.matchMedia("(max-width: 768px)").matches;

  const getConfig = () => {
    const cfg = window.bfsGrid || null;
    return cfg && typeof cfg === "object" ? cfg : null;
  };

  const buildUrlWithOffset = (cfg, offset, perPage) => {
    const u = new URL(cfg.restUrl, window.location.origin);
    u.searchParams.set("offset", String(offset));
    u.searchParams.set("per_page", String(perPage));
    if (cfg.genero) u.searchParams.set("genero", String(cfg.genero));
    if (cfg.ui) u.searchParams.set("ui", String(cfg.ui));
    if (cfg.ageGroup) u.searchParams.set("age_group", String(cfg.ageGroup));
    if (typeof cfg.strict !== "undefined") u.searchParams.set("strict", String(cfg.strict));
    if (cfg.order) u.searchParams.set("order", String(cfg.order));
    if (cfg.seed) u.searchParams.set("seed", String(cfg.seed));
    return u.toString();
  };

  const fetchOffset = async (cfg, offset, perPage) => {
    const url = buildUrlWithOffset(cfg, offset, perPage);
    const res = await fetch(url, { credentials: "same-origin" });
    if (!res.ok) throw new Error("http_" + res.status);
    const json = await res.json();
    if (!json || !json.success || !json.data) throw new Error("bad_response");
    return { html: String(json.data.html || ""), hasMore: !!json.data.has_more };
  };

  document.addEventListener("DOMContentLoaded", () => {
    const cfg = getConfig();
    if (!cfg || !cfg.restUrl) return;

    const gridRoot =
      (cfg.gridId && document.getElementById(cfg.gridId)) ||
      $("[data-bfs-grid]");

    if (!gridRoot) return;

    initCardsIn(gridRoot);

    const itemsEl = $("[data-bfs-grid-items]", gridRoot) || gridRoot;
    const sentinel = $("[data-bfs-grid-sentinel]", gridRoot);
    const cta = $("[data-bfs-grid-cta]", gridRoot);

    if (!sentinel || cfg.infinite === 0) return;

    const batches = isMobile()
      ? (cfg.batches && cfg.batches.mobile ? cfg.batches.mobile : [3])
      : (cfg.batches && cfg.batches.desktop ? cfg.batches.desktop : [4, 3]);

    let loading = false;
    let done = false;

    // ✅ IMPORTANTE: offset real según SSR renderizado
    let offset = $$(".bfs-grid-card", itemsEl).length;
    let batchIndex = 0;

    const showCta = () => { if (cta) cta.hidden = false; };

    const stop = (observer) => {
      done = true;
      if (observer) observer.disconnect();
      showCta();
    };

    const appendHtml = (html) => {
      if (!html) return;
      const wrap = document.createElement("div");
      wrap.innerHTML = html;
      while (wrap.firstChild) itemsEl.appendChild(wrap.firstChild);
      initCardsIn(itemsEl);
    };

    const loadNext = async (observer) => {
      if (loading || done) return;

      if (batchIndex >= batches.length) {
        stop(observer);
        return;
      }

      const want = Number(batches[batchIndex] || 0);
      if (want <= 0) {
        batchIndex++;
        return;
      }

      loading = true;

      try {
        const { html, hasMore } = await fetchOffset(cfg, offset, want);

        // Si el backend devuelve vacío, cortamos ya (evita loop)
        if (!html) {
          stop(observer);
          return;
        }

        appendHtml(html);

        offset += want;
        batchIndex++;

        if (!hasMore || batchIndex >= batches.length) stop(observer);
      } catch (_) {
        stop(observer);
      } finally {
        loading = false;
      }
    };

    const obs = new IntersectionObserver(
      (entries) => {
        if (entries.some((e) => e.isIntersecting)) loadNext(obs);
      },
      { root: null, rootMargin: "600px 0px", threshold: 0.01 }
    );

    obs.observe(sentinel);
  });

  // Delegación global (dots)
  document.addEventListener(
    "click",
    (ev) => {
      const dot = ev.target.closest(".bfs-color-dot");
      if (!dot) return;

      ev.preventDefault();
      ev.stopPropagation();

      const card = dot.closest(".bfs-grid-card");
      if (!card) return;

      applyVariantFromDot(card, dot);
    },
    true
  );

  document.addEventListener(
    "pointerover",
    (ev) => {
      const dot = ev.target.closest(".bfs-color-dot");
      if (!dot) return;

      const card = dot.closest(".bfs-grid-card");
      if (!card) return;

      applyVariantFromDot(card, dot);
    },
    true
  );
})();
