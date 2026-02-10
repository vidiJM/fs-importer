document.addEventListener("DOMContentLoaded", () => {

  /* Inicializar Swiper */
  new Swiper(".bfs-swiper-container", {
    slidesPerView: 1.2,
    spaceBetween: 12,
    loop: true,
    autoplay: { delay: 10000000, disableOnInteraction: true },
    navigation: { nextEl: ".bfs-swiper-next", prevEl: ".bfs-swiper-prev" },
    breakpoints: {
      768: { slidesPerView: 2.2 },
      1024: { slidesPerView: 3 },
      1280: { slidesPerView: 4 },
      1600: { slidesPerView: 5 }
    }
  });

  /**
   * Acepta tamaños en formatos:
   * - "[41,42]" / '["41","42"]'
   * - "41" (single)
   * - "41,42,43" (csv)
   * - {"41":true,"42":true}
   * - [41,42]
   * - 41
   */
  const normalizeSizes = (raw) => {
    if (raw == null || raw === "") return [];

    // Si ya es array
    if (Array.isArray(raw)) return raw.map((s) => String(s));

    // Si es número
    if (typeof raw === "number") return [String(raw)];

    // Si es objeto {"41":true}
    if (typeof raw === "object") return Object.keys(raw);

    // A partir de aquí es string
    const str = String(raw).trim();
    if (!str) return [];

    // Intentar JSON si parece JSON
    const looksJson = (str.startsWith("[") && str.endsWith("]")) || (str.startsWith("{") && str.endsWith("}"));
    if (looksJson) {
      try {
        const parsed = JSON.parse(str);

        if (Array.isArray(parsed)) return parsed.map((s) => String(s));
        if (parsed && typeof parsed === "object") return Object.keys(parsed);
        if (typeof parsed === "number") return [String(parsed)];
        if (typeof parsed === "string") return parsed ? [parsed] : [];
      } catch (e) {
        console.warn("bfs: sizes JSON inválido, fallback a CSV/single", e, str);
      }
    }

    // Fallback: "41, 42, 43" o "41"
    return str
      .split(",")
      .map((s) => s.trim())
      .filter(Boolean)
      .map((s) => String(s));
  };

  const renderSizes = (parent, sizesRaw) => {
    const sizesBox = parent.querySelector(".dynamic-sizes");
    if (!sizesBox) return;

    sizesBox.innerHTML = "";

    const sizes = normalizeSizes(sizesRaw)
      .filter(Boolean)
      .sort((a, b) => {
        const na = parseFloat(a);
        const nb = parseFloat(b);
        const aNum = !Number.isNaN(na);
        const bNum = !Number.isNaN(nb);
        if (aNum && bNum) return na - nb;
        if (aNum !== bNum) return aNum ? -1 : 1;
        return a.localeCompare(b, "es", { numeric: true, sensitivity: "base" });
      });

    sizes.forEach(size => {
      const span = document.createElement("span");
      span.classList.add("bfs-size");
      span.textContent = size;
      sizesBox.appendChild(span);
    });
  };

  /* Click en color */
  document.querySelectorAll(".bfs-color-dot").forEach(dot => {
    dot.addEventListener("click", function () {
      const parent = this.closest(".bfs-card");
      if (!parent) return;

      parent.querySelectorAll(".bfs-color-dot").forEach(d => d.classList.remove("active"));
      this.classList.add("active");

      /* Imagen */
      const imgEl = parent.querySelector(".bfs-card-img-main");
      if (imgEl && this.dataset.img) imgEl.src = this.dataset.img;

      /* Precio */
      const priceEl = parent.querySelector(".bfs-price-value");
      const price = parseFloat(this.dataset.price || "0");
      if (priceEl) {
        priceEl.textContent = price > 0 ? `Desde ${price.toFixed(2)} €` : "Consultar";
      }

      /* Tallas */
      renderSizes(parent, this.dataset.sizes);
    });
  });

  /* Seleccionar el primer color por defecto (por card) */
  document.querySelectorAll(".bfs-card").forEach(card => {
    const first = card.querySelector(".bfs-color-dot");
    if (!first) return;

    // Evita doble init si Swiper clona slides
    if (card.dataset.bfsInited === "1") return;
    card.dataset.bfsInited = "1";

    first.dispatchEvent(new MouseEvent("click", { bubbles: true }));
  });

});
