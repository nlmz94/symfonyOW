/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import 'bootstrap';

// any CSS you import will output into a single css file (app.scss in this case)
import './styles/app.scss';

document.addEventListener('DOMContentLoaded', () => {
    const htmlEl = document.documentElement;
    const toggleBtn = document.getElementById('theme-toggle');
    const icon = document.getElementById('theme-icon');

    if (!toggleBtn || !icon) return;

    const savedTheme = localStorage.getItem('theme') || 'light';
    htmlEl.setAttribute('data-bs-theme', savedTheme);
    icon.className = savedTheme === 'light' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';

    toggleBtn.addEventListener('click', () => {
        const currentTheme = htmlEl.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        htmlEl.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        icon.className = newTheme === 'light' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
    });
});
