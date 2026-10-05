(() => {
    'use strict';

    const nav = document.querySelector('[data-responsive-nav]');
    if (!nav) return;

    const toggle = nav.querySelector('.modelo-menu-toggle');
    const menu = nav.querySelector('.modelo-navigation');
    const label = toggle?.querySelector('.modelo-menu-label');
    if (!toggle || !menu || !label) return;

    const setExpanded = (expanded, restoreFocus = false) => {
        nav.classList.toggle('navigation-open', expanded);
        toggle.setAttribute('aria-expanded', String(expanded));
        toggle.setAttribute('aria-label', expanded ? 'Ocultar menú principal' : 'Mostrar menú principal');
        label.textContent = expanded ? 'Cerrar' : 'Menú';
        if (restoreFocus) toggle.focus();
    };
    toggle.hidden = false;
    setExpanded(false);
    nav.classList.add('navigation-ready');
    toggle.addEventListener('click', () => setExpanded(toggle.getAttribute('aria-expanded') !== 'true'));
    menu.addEventListener('click', event => {
        if (event.target.closest('a')) setExpanded(false);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            setExpanded(false, true);
        }
    });
})();
