document.addEventListener('DOMContentLoaded', () => {

  const colorMap = {
      negro: '#000000',
      blanco: '#ffffff',
      azul: '#1976d2',
      'azul marino': '#0d47a1',
      'azul royal': '#1565c0',
      rojo: '#d32f2f',
      naranja: '#f57c00',
      amarillo: '#fbc02d',
      'amarillo fluor': '#ffeb3b',
      verde: '#388e3c',
      'verde fluor': '#66bb6a',
      gris: '#9e9e9e',
      marron: '#5d4037',
      beige: '#d7ccc8',
      rosa: '#ec407a',
      fucsia: '#c2185b',
      morado: '#7b1fa2',
      plata: '#cfd8dc',
      oro: '#f9a825',
      turquesa: '#0097a7',
      coral: '#ff7043',
      bordeaux: '#6a1b1a',
      cuero: '#8d6e63',
      multicolor: 'linear-gradient(45deg, red, yellow, blue)'
    };


  document.querySelectorAll('.fs-card').forEach(card => {

    const data = JSON.parse(card.dataset.product);

    const colorsContainer = card.querySelector('.fs-card__colors');
    const sizesContainer  = card.querySelector('.fs-card__sizes');
    const priceElement    = card.querySelector('[data-price]');
    const imageElement    = card.querySelector('[data-image]');
    const ctaElement      = card.querySelector('[data-cta]');

    let activeColor = null;

    Object.keys(data.colors).forEach((color, index) => {

      const btn = document.createElement('button');
      btn.className = 'fs-color-btn';

      const normalized = color.toLowerCase();
      btn.style.background = colorMap[normalized] || '#ccc';

      btn.addEventListener('click', () => {

        card.querySelectorAll('.fs-color-btn').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');

        activeColor = color;

        if (data.colors[color].image) {
          imageElement.setAttribute('src', data.colors[color].image);
          imageElement.removeAttribute('srcset');
        }

        sizesContainer.innerHTML = '';
        priceElement.textContent = '';
        ctaElement.href = '#';

        const sizes = data.colors[color].sizes;

        Object.keys(sizes).forEach((size, sizeIndex) => {

          const sizeBtn = document.createElement('button');
          sizeBtn.className = 'fs-size-btn';
          sizeBtn.textContent = size;

          sizeBtn.addEventListener('click', () => {

            card.querySelectorAll('.fs-size-btn').forEach(s => s.classList.remove('is-active'));
            sizeBtn.classList.add('is-active');

            const sizeData = sizes[size];

            priceElement.textContent = sizeData.price.toFixed(2).replace('.', ',') + ' €';
            ctaElement.href = sizeData.url;
          });

          sizesContainer.appendChild(sizeBtn);

          // Auto seleccionar primera talla
          if (sizeIndex === 0) {
            sizeBtn.click();
          }

        });

      });

      colorsContainer.appendChild(btn);

      // Auto seleccionar primer color
      if (index === 0) {
        btn.click();
      }

    });

  });

});
