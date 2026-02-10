(() => {
  "use strict";

  const $ = (sel, root = document) => root.querySelector(sel);

  const escapeHtml = (s) => String(s)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");

  // --------------------------------------------
  // P0: Sanitize remote HTML before injecting
  // --------------------------------------------
  const sanitizeResultsHtml = (html) => {
    const tpl = document.createElement("template");
    tpl.innerHTML = String(html || "");

    // Drop dangerous tags entirely
    const dropTags = new Set([
      "script", "style", "iframe", "object", "embed", "link", "meta", "base",
      "form", "input", "textarea", "select", "button", "svg", "math"
    ]);

    // Allowed tags (keep it practical for your partials)
    const allowedTags = new Set([
      "div", "span", "p", "a", "img",
      "article", "section", "header", "footer",
      "h1", "h2", "h3", "h4", "h5", "h6",
      "ul", "ol", "li",
      "strong", "em", "b", "i", "small",
      "br"
    ]);

    const isSafeUrl = (value) => {
      const v = String(value || "").trim();
      if (!v) return true;
      // Allow relative URLs
      if (v.startsWith("/") || v.startsWith("./") || v.startsWith("../") || v.startsWith("#")) return true;
      try {
        const u = new URL(v, window.location.origin);
        return u.protocol === "http:" || u.protocol === "https:";
      } catch {
        return false;
      }
    };

    const walk = (node) => {
      // Process element children first (live list can change)
      const children = Array.from(node.children || []);
      for (const el of children) {
        const tag = el.tagName ? el.tagName.toLowerCase() : "";

        if (dropTags.has(tag) || !allowedTags.has(tag)) {
          el.remove();
          continue;
        }

        // Strip dangerous attributes
        for (const attr of Array.from(el.attributes)) {
          const name = attr.name.toLowerCase();
          const value = attr.value;

          // remove inline handlers and srcdoc
          if (name.startsWith("on") || name === "srcdoc") {
            el.removeAttribute(attr.name);
            continue;
          }

          // Allow data-*, aria-*, class, id, role, alt, title, loading, decoding
          const allowedAttr =
            name === "class" ||
            name === "id" ||
            name === "role" ||
            name === "alt" ||
            name === "title" ||
            name === "loading" ||
            name === "decoding" ||
            name.startsWith("data-") ||
            name.startsWith("aria-");

          // Allow href/src but validate scheme
          if (name === "href" || name === "src") {
            if (!isSafeUrl(value)) {
              el.removeAttribute(attr.name);
            }
            continue;
          }

          // Drop everything else not in allowlist
          if (!allowedAttr) {
            el.removeAttribute(attr.name);
          }
        }

        // Additional hardening for links
        if (tag === "a") {
          // Avoid reverse tabnabbing if target ever appears
          if (el.getAttribute("target") === "_blank") {
            el.setAttribute("rel", "noopener noreferrer");
          }
        }

        walk(el);
      }
    };

    walk(tpl.content);
    return tpl.innerHTML;
  };

  const buildChip = (label, id) => {
    return `
      <span class="bfs-finder__chip" data-chip="${escapeHtml(id)}">
        <span>${escapeHtml(label)}</span>
        <button type="button" aria-label="Eliminar">×</button>
      </span>
    `;
  };

  const renderStep = (root, cfg, stepIndex, answers) => {
    const step = cfg.steps[stepIndex];
    const stepEl = $('[data-bfs-step]', root);
    const kickerEl = $('[data-bfs-kicker]', root);
    const backBtn = $('[data-bfs-back]', root);

    kickerEl.textContent = step.kicker || "";
    backBtn.disabled = stepIndex === 0;

    const optionsHtml = step.options.map((o) => {
      const selected = answers[step.id] === o.value ? " is-selected" : "";
      return `
        <button type="button" class="bfs-finder__opt${selected}" data-opt="${escapeHtml(o.value)}">
          <span class="bfs-finder__opt-label">${escapeHtml(o.label)}</span>
          <span class="bfs-finder__opt-check">✓</span>
        </button>
      `;
    }).join("");

    stepEl.innerHTML = `
      <div>
        <div class="bfs-finder__qtitle">${escapeHtml(step.title)}</div>
        <div class="bfs-finder__qsubtitle">${escapeHtml(step.subtitle || "")}</div>
      </div>
      <div class="bfs-finder__options" data-bfs-options>${optionsHtml}</div>
    `;

    return step;
  };

  const updateProgress = (root, cfg, answers) => {
    const done = cfg.steps.filter(s => !!answers[s.id]).length;
    const count = $('.bfs-finder__progress-count', root);
    const fill = $('.bfs-finder__progress-fill', root);
    const bar = $('.bfs-finder__progress-bar', root);

    if (count) count.textContent = `${done}/4`;
    if (fill) fill.style.width = `${Math.round((done / 4) * 100)}%`;
    if (bar) bar.setAttribute("aria-valuenow", String(done));
  };

  const renderChips = (root, cfg, answers) => {
    const chipsWrap = $('[data-bfs-chips]', root);
    if (!chipsWrap) return;

    const chips = [];
    cfg.steps.forEach((s) => {
      const v = answers[s.id];
      if (!v) return;
      const opt = s.options.find(o => o.value === v);
      const label = `${s.subtitle}: ${opt ? opt.label : v}`;
      chips.push(buildChip(label, s.id));
    });

    chipsWrap.innerHTML = chips.join("");
    chipsWrap.hidden = chips.length === 0;
  };

  const syncHidden = (el, hidden) => {
    if (!el) return;
    el.hidden = !!hidden;
    el.classList.toggle("bfs-is-hidden", !!hidden);
  };

  const setLoading = (root, on) => {
    const l = $('[data-bfs-loading]', root);
    const r = $('[data-bfs-results]', root);
    const e = $('[data-bfs-error]', root);
    syncHidden(l, !on);
    if (on) {
      syncHidden(r, true);
      syncHidden(e, true);
    }
  };

  const setError = (root, msg) => {
    const e = $('[data-bfs-error]', root);
    if (!e) return;
    e.textContent = msg;
    syncHidden(e, false);
  };

  const clearResults = (root) => {
    const r = $('[data-bfs-results]', root);
    if (!r) return;
    r.innerHTML = "";
    syncHidden(r, true);
  };

  const setResults = (root, html) => {
    const r = $('[data-bfs-results]', root);
    if (!r) return;
    r.innerHTML = sanitizeResultsHtml(html);
    syncHidden(r, false);
  };

  const resetUiState = (root) => {
    const l = root.querySelector("[data-bfs-loading]");
    const r = root.querySelector("[data-bfs-results]");
    const e = root.querySelector("[data-bfs-error]");
    if (l) { syncHidden(l, true); }
    if (r) { r.innerHTML = ""; syncHidden(r, true); }
    if (e) { e.textContent = ""; syncHidden(e, true); }
  };

  const buildResultsUrl = (cfg, answers) => {
    const params = new URLSearchParams();
    if (cfg.genero) params.set("genero", cfg.genero);
    params.set("limit", String(cfg.limit || 3));
    params.set("pool", String(cfg.pool || 60));
    params.set("surface", answers.surface || "");
    params.set("style", answers.style || "");
    params.set("priority", answers.priority || "");
    params.set("budget", answers.budget || "");
    if (cfg.guideUrl) params.set("guide_url", cfg.guideUrl);

    const base = cfg.resultsUrl || window.location.href;
    const url = new URL(base, window.location.origin);
    params.forEach((v, k) => url.searchParams.set(k, v));
    url.searchParams.set("bfs_results", "1");
    return url.toString();
  };

  const init = (root) => {
    const cfg = window.bfsFinder;
    if (!cfg || !cfg.steps || !cfg.restUrl) return;

    resetUiState(root);

    let stepIndex = 0;
    let answers = {};

    const backBtn = $('[data-bfs-back]', root);

    const go = (nextIndex) => {
      stepIndex = Math.max(0, Math.min(cfg.steps.length - 1, nextIndex));
      renderStep(root, cfg, stepIndex, answers);
      updateProgress(root, cfg, answers);
      renderChips(root, cfg, answers);
    };

    const finishIfReady = () => {
      const finishBtn = root.querySelector("[data-bfs-finish]");
      const complete = cfg.steps.every(s => !!answers[s.id]);
      if (finishBtn) finishBtn.disabled = !complete;
    };

    go(0);
    finishIfReady();

    root.addEventListener("click", async (ev) => {
      const optBtn = ev.target.closest("[data-opt]");
      if (optBtn) {
        ev.preventDefault();
        const step = cfg.steps[stepIndex];
        const val = optBtn.getAttribute("data-opt") || "";
        answers = { ...answers, [step.id]: val };

        if (stepIndex < cfg.steps.length - 1) {
          go(stepIndex + 1);
          finishIfReady();
        } else {
          updateProgress(root, cfg, answers);
          renderChips(root, cfg, answers);
          finishIfReady();
        }
        return;
      }

      const finish = ev.target.closest("[data-bfs-finish]");
      if (finish) {
        ev.preventDefault();
        const complete = cfg.steps.every(s => !!answers[s.id]);
        if (!complete) return;
        const url = buildResultsUrl(cfg, answers);
        window.location.assign(url);
        return;
      }

      const chip = ev.target.closest("[data-chip]");
      if (chip) {
        ev.preventDefault();
        const id = chip.getAttribute("data-chip");
        if (!id) return;

        const idx = cfg.steps.findIndex(s => s.id === id);
        if (idx >= 0) {
          const next = { ...answers };
          delete next[id];
          answers = next;
          clearResults(root);
          go(idx);
        }
        return;
      }

      if (backBtn && ev.target.closest("[data-bfs-back]")) {
        ev.preventDefault();
        clearResults(root);
        go(stepIndex - 1);
        finishIfReady();
      }
    });
  };

  document.addEventListener("DOMContentLoaded", () => {
    // Modal launchers
    document.querySelectorAll("[data-bfs-finder-launch]").forEach((launch) => {
      const openBtn = launch.querySelector("[data-bfs-open]");
      const modal = launch.querySelector("[data-bfs-modal]");
      const finderRoot = modal ? modal.querySelector("[data-bfs-finder]") : null;

      const close = () => {
        if (!modal) return;
        modal.hidden = true;
        document.documentElement.classList.remove("bfs-finder-modal-open");
      };

      const open = () => {
        if (!modal) return;
        modal.hidden = false;
        document.documentElement.classList.add("bfs-finder-modal-open");
        if (finderRoot) {
          resetUiState(finderRoot);
        }
        if (finderRoot && !finderRoot.__bfsInited) {
          finderRoot.__bfsInited = true;
          init(finderRoot);
        }
        const focusable = modal.querySelector("button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])");
        if (focusable) focusable.focus();
      };

      if (openBtn) openBtn.addEventListener("click", (e) => { e.preventDefault(); open(); });
      if (modal) {
        modal.addEventListener("click", (e) => {
          if (e.target && e.target.closest("[data-bfs-close]")) close();
        });
      }
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && modal && !modal.hidden) close();
      });
    });

    // Inline finder (legacy)
    document.querySelectorAll("[data-bfs-finder]").forEach((node) => {
      if (node.closest("[data-bfs-finder-launch]")) return;
      init(node);
    });

    // Results page container
    document.querySelectorAll("[data-bfs-finder-results]").forEach((root) => {
      const cfg = window.bfsFinder;
      if (!cfg || !cfg.restUrl) return;

      const params = new URLSearchParams(window.location.search);

      const answers = {
        surface: params.get("surface") || "",
        style: params.get("style") || "",
        priority: params.get("priority") || "",
        budget: params.get("budget") || "",
      };

      if (params.get("genero")) cfg.genero = params.get("genero");
      if (params.get("limit")) cfg.limit = parseInt(params.get("limit"), 10) || cfg.limit;
      if (params.get("pool")) cfg.pool = parseInt(params.get("pool"), 10) || cfg.pool;
      if (params.get("guide_url")) cfg.guideUrl = params.get("guide_url");

      const setLoadingSimple = (on) => {
        const l = root.querySelector("[data-bfs-loading]");
        const r = root.querySelector("[data-bfs-results]");
        const e = root.querySelector("[data-bfs-error]");
        syncHidden(l, !on);
        if (on) {
          if (r) { r.innerHTML = ""; syncHidden(r, true); }
          if (e) { e.textContent = ""; syncHidden(e, true); }
        }
      };

      const fetchResults = async () => {
        const q = new URLSearchParams();
        if (cfg.genero) q.set("genero", cfg.genero);
        q.set("limit", String(cfg.limit || 3));
        q.set("pool", String(cfg.pool || 60));
        q.set("surface", answers.surface);
        q.set("style", answers.style);
        q.set("priority", answers.priority);
        q.set("budget", answers.budget);
        if (cfg.guideUrl) q.set("guide_url", cfg.guideUrl);

        // Robust URL build
        const urlObj = new URL(String(cfg.restUrl), window.location.origin);
        q.forEach((v, k) => urlObj.searchParams.set(k, v));
        const res = await fetch(urlObj.toString(), { credentials: "same-origin" });
        const json = await res.json();
        if (!json || !json.success) throw new Error("bad_response");
        return (json.data && json.data.html) ? String(json.data.html) : "";
      };

      setLoadingSimple(true);
      (async () => {
        try {
          const html = await fetchResults();
          setResults(root, html);
        } catch (err) {
          const e = root.querySelector("[data-bfs-error]");
          if (e) {
            e.textContent = (cfg.strings && cfg.strings.error) ? cfg.strings.error : "Error";
            syncHidden(e, false);
          }
        } finally {
          setLoadingSimple(false);
        }
      })();
    });
  });
})();
