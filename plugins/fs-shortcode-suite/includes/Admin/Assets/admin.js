document.addEventListener('DOMContentLoaded', () => {

    const generateBtn = document.getElementById('fs-generate');
    const outputBox = document.getElementById('fs-output');
    const copyBtn = document.getElementById('fs-copy');

    generateBtn.addEventListener('click', () => {

        const brand = document.getElementById('fs-brand').value.trim();
        const color = document.getElementById('fs-color').value.trim();
        const gender = document.getElementById('fs-gender').value.trim();
        const age = document.getElementById('fs-age').value.trim();
        const size = document.getElementById('fs-size').value.trim();
        const perpage = document.getElementById('fs-perpage').value.trim();

        let shortcode = '[fs_grid';

        if (brand) shortcode += ` brand="${brand}"`;
        if (color) shortcode += ` color="${color}"`;
        if (gender) shortcode += ` gender="${gender}"`;
        if (age) shortcode += ` age_group="${age}"`;
        if (size) shortcode += ` size="${size}"`;
        if (perpage) shortcode += ` per_page="${perpage}"`;

        shortcode += ']';

        outputBox.textContent = shortcode;
    });

    copyBtn.addEventListener('click', () => {
        navigator.clipboard.writeText(outputBox.textContent);
        copyBtn.textContent = "Copiado ✓";

        setTimeout(() => {
            copyBtn.textContent = "Copiar";
        }, 1500);
    });

});
