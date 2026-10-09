(function () {
    'use strict';
    const form = document.getElementById('expediente-form');
    if (!form) return;
    const panels = [...form.querySelectorAll('.wizard-panel')];
    const steps = [...form.querySelectorAll('[data-go-step]')];
    const next = document.getElementById('wizard-next');
    const back = document.getElementById('wizard-back');
    const save = document.getElementById('guardar-expediente');
    const status = document.getElementById('wizard-save-status');
    let active = 0, dirty = false, submitting = false;
    form.querySelector('.wizard-steps').hidden = false;
    function syncDetails() {
        form.querySelectorAll('[data-detail-for]').forEach(detail => {
            const yes = form.querySelector('input[name="datos[' + detail.dataset.detailFor + ']"]:checked');
            const visible = yes && yes.value === '1';
            detail.hidden = !visible;
            detail.querySelectorAll('textarea').forEach(input => { input.disabled = !visible; input.required = !!visible; });
        });
        const choices = [...form.querySelectorAll('input[name="datos[q11_necesita_apoyo][]"]')];
        if (choices.length) {
            choices[0].setCustomValidity(choices.some(input => input.checked) ? '' : 'Selecciona al menos una opción de apoyo.');
            const detail = form.querySelector('[data-support-detail]');
            const visible = choices.some(input => input.value === 'Otro' && input.checked);
            detail.hidden = !visible;
            detail.querySelector('textarea').disabled = !visible;
            detail.querySelector('textarea').required = visible;
        }
    }
    function review() {
        const container = document.getElementById('wizard-review-content');
        container.replaceChildren();
        panels.slice(0, -1).forEach((panel, index) => {
            const section = document.createElement('section'); section.className = 'review-section';
            const header = document.createElement('header');
            const title = document.createElement('h3'); title.textContent = panel.dataset.stepTitle;
            const edit = document.createElement('button'); edit.type = 'button'; edit.textContent = 'Editar'; edit.addEventListener('click', () => show(index, true));
            header.append(title, edit); section.append(header);
            const list = document.createElement('dl');
            panel.querySelectorAll('[data-review-field]').forEach(field => {
                const row = document.createElement('div'), label = document.createElement('dt'), value = document.createElement('dd');
                label.textContent = field.dataset.reviewLabel;
                const answers = [...field.querySelectorAll('input[name], select[name], textarea[name]')].filter(input => !input.disabled && (!['radio', 'checkbox'].includes(input.type) || input.checked)).map(input => {
                    if (input.tagName === 'SELECT') return input.value ? input.selectedOptions[0].textContent.trim() : '';
                    if (input.type === 'radio' && ['0', '1'].includes(input.value)) return input.value === '1' ? 'Sí' : 'No';
                    if (input.type === 'date' && input.value) return input.value.split('-').reverse().join('/');
                    return input.value.trim();
                }).filter(Boolean);
                value.textContent = answers.join('\n') || 'Sin información adicional';
                row.append(label, value); list.append(row);
            });
            section.append(list); container.append(section);
        });
    }
    function show(index, focus) {
        active = Math.max(0, Math.min(index, panels.length - 1));
        panels.forEach((panel, i) => { panel.hidden = i !== active; });
        steps.forEach((step, i) => { step.classList.toggle('completed', i < active); if (i === active) step.setAttribute('aria-current', 'step'); else step.removeAttribute('aria-current'); });
        back.hidden = active === 0; next.hidden = active === panels.length - 1; save.hidden = active !== panels.length - 1;
        next.textContent = active === panels.length - 2 ? 'Revisar respuestas' : 'Continuar';
        document.getElementById('wizard-step-label').textContent = 'Paso ' + (active + 1) + ' de ' + panels.length + ' · ' + panels[active].dataset.stepTitle;
        if (active === panels.length - 1) review();
        if (focus) {
            const heading = panels[active].querySelector('legend, h2');
            heading.tabIndex = -1; heading.focus({ preventScroll: true });
            panels[active].scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
        }
    }
    function validate(index) {
        for (const control of panels[index].querySelectorAll('input, select, textarea')) {
            if (!control.disabled && !control.checkValidity()) { show(index, false); control.reportValidity(); control.focus(); return false; }
        }
        return true;
    }
    steps.forEach(step => step.addEventListener('click', () => {
        const index = Number(step.dataset.goStep);
        if (index > active) for (let i = 0; i < index; i++) if (!validate(i)) return;
        show(index, true);
    }));
    next.addEventListener('click', () => { if (validate(active)) show(active + 1, true); });
    back.addEventListener('click', () => show(active - 1, true));
    form.addEventListener('change', event => {
        if (event.target.name === 'datos[q11_necesita_apoyo][]' && event.target.checked) {
            form.querySelectorAll('input[name="datos[q11_necesita_apoyo][]"]').forEach(input => { if (input !== event.target && (event.target.value === 'Ninguno' || input.value === 'Ninguno')) input.checked = false; });
        }
        syncDetails();
    });
    form.addEventListener('input', () => { dirty = true; status.textContent = 'Tienes cambios sin guardar'; });
    form.addEventListener('invalid', event => { const index = panels.findIndex(panel => panel.contains(event.target)); if (index >= 0) show(index, false); }, true);
    form.addEventListener('submit', event => {
        syncDetails();
        for (let i = 0; i < panels.length - 1; i++) if (!validate(i)) { event.preventDefault(); return; }
        if (active !== panels.length - 1) { event.preventDefault(); show(panels.length - 1, true); return; }
        submitting = true; save.disabled = true; save.textContent = 'Guardando…';
    });
    window.addEventListener('beforeunload', event => { if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; } });
    syncDetails(); show(0, false);
    const error = form.querySelector('.field-error');
    if (error) { const index = panels.findIndex(panel => panel.contains(error)); if (index >= 0) show(index, false); }
}());
