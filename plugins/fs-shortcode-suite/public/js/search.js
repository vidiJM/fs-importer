(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {

        if (typeof FSSearchConfig === 'undefined') return;

        const wrapper  = document.querySelector('[data-fs-search]');
        const overlay  = document.querySelector('[data-fs-search-overlay]');
        const input    = overlay?.querySelector('.fs-search-input');
        const results  = overlay?.querySelector('[data-fs-search-results]');
        const closeBtn = overlay?.querySelector('.fs-search-close');

        if (!wrapper || !overlay || !input || !results) return;

        let controller = null;

        /* =========================
           OPEN / CLOSE
        ========================= */

        const openOverlay = () => {
            overlay.classList.add('is-active');
            overlay.setAttribute('aria-hidden', 'false');
            wrapper.querySelector('.fs-search-trigger')
                   ?.setAttribute('aria-expanded', 'true');

            document.body.classList.add('fs-search-open');

            setTimeout(() => {
                input.focus();
            }, 50);
        };

        const closeOverlay = () => {
            overlay.classList.remove('is-active');
            overlay.setAttribute('aria-hidden', 'true');
            wrapper.querySelector('.fs-search-trigger')
                   ?.setAttribute('aria-expanded', 'false');

            document.body.classList.remove('fs-search-open');

            input.value = '';
            results.innerHTML = '';
        };

        wrapper.addEventListener('click', openOverlay);

        closeBtn?.addEventListener('click', closeOverlay);

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeOverlay();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && overlay.classList.contains('is-active')) {
                closeOverlay();
            }
        });

        /* =========================
           SEARCH LOGIC
        ========================= */

        input.addEventListener('input', async (e) => {

            const query = e.target.value.trim();

            if (query.length < FSSearchConfig.minLength) {
                results.innerHTML = '';
                return;
            }

            if (controller) controller.abort();
            controller = new AbortController();

            try {

                const response = await fetch(
                    `${FSSearchConfig.restUrl}?q=${encodeURIComponent(query)}`,
                    { signal: controller.signal }
                );

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                renderResults(data);

            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Search error:', error);
                }
            }
        });

        /* =========================
           RENDER RESULTS
        ========================= */

        const renderResults = (items) => {

            if (!Array.isArray(items) || !items.length) {
                results.innerHTML = `
                    <div class="fs-search-empty">
                        No se encontraron resultados
                    </div>
                `;
                return;
            }

            results.innerHTML = items.map(item => `
                <a href="${item.permalink}" class="fs-search-result-item">
                    ${item.image 
                        ? `<img src="${item.image}" alt="${item.name}" loading="lazy" decoding="async">`
                        : ''
                    }
                    <div>
                        <div class="fs-search-result-title">${item.name}</div>
                        ${
                            item.price
                            ? `<div class="fs-search-result-price">
                                ${item.price.toFixed(2).replace('.', ',')} €
                               </div>`
                            : ''
                        }
                    </div>
                </a>
            `).join('');
        };

    });

})();
