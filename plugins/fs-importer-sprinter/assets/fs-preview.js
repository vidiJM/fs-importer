document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".fs-color-dot").forEach(dot => {
        const colorText = dot.dataset.color || "";
        const parts = colorText.split(/[\s\-]+/).filter(Boolean);

        const c1 = colorToCss(parts[0] || "");
        const c2 = colorToCss(parts[1] || parts[0] || "");

        if (parts.length > 1) {
            dot.style.background = `linear-gradient(90deg, ${c1} 50%, ${c2} 50%)`;
        } else {
            dot.style.background = c1;
        }

        dot.addEventListener("click", () => {
            const card = dot.closest(".fs-card");

            card.querySelectorAll(".fs-color-dot").forEach(d => d.classList.remove("active"));
            dot.classList.add("active");

            const imgEl = card.querySelector(".fs-img");
            if (imgEl && dot.dataset.img) {
                imgEl.src = dot.dataset.img;
            }

            card.querySelectorAll(".fs-sizes-group")
                .forEach(g => g.classList.remove("active"));

            const sizesGroup = card.querySelector(`.fs-sizes-group[data-color="${dot.dataset.color}"]`);
            if (sizesGroup) {
                sizesGroup.classList.add("active");
            }

            if (dot.dataset.price) {
                card.querySelector(".fs-price").innerText =
                    "Desde " + dot.dataset.price + " €";
            }
        });
    });
});

function colorToCss(color) {
    color = color.toUpperCase();

    if (color.includes("BLANCO")) return "#ffffff";
    if (color.includes("NEGRO")) return "#000000";
    if (color.includes("AZUL") && color.includes("MARINO")) return "#1e293b";
    if (color.includes("AZUL") && color.includes("ROYAL")) return "#1d4ed8";
    if (color.includes("AZUL")) return "#1e3a8a";
    if (color.includes("ROJO")) return "#dc2626";
    if (color.includes("VERDE")) return "#16a34a";
    if (color.includes("AMARILLO")) return "#facc15";
    if (color.includes("NARANJA")) return "#fb923c";
    if (color.includes("ROSA")) return "#f472b6";
    if (color.includes("GRIS")) return "#9ca3af";
    if (color.includes("MARRON")) return "#92400e";
    if (color.includes("LIMA")) return "#65a30d";
    if (color.includes("FLUOR")) return "#facc15";

    return "#ccc";
}
