(function () {
    'use strict';
    function ageAt(value, today) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return null;
        const [year, month, day] = value.split('-').map(Number);
        const birth = new Date(0);
        birth.setFullYear(year, month - 1, day);
        birth.setHours(0, 0, 0, 0);
        if (birth.getFullYear() !== year || birth.getMonth() !== month - 1 || birth.getDate() !== day || birth > today) return null;
        let age = today.getFullYear() - year;
        if (today.getMonth() < month - 1 || (today.getMonth() === month - 1 && today.getDate() < day)) age--;
        return age;
    }
    if (typeof module !== 'undefined' && module.exports) module.exports = { ageAt };
    if (typeof document === 'undefined') return;
    const birth = document.getElementById('fecha_nacimiento');
    const age = document.getElementById('edad-calculada');
    if (!birth || !age) return;
    function updateAge() {
        const result = ageAt(birth.value, new Date());
        age.value = result === null ? '' : String(result);
    }
    birth.addEventListener('input', updateAge);
    birth.addEventListener('change', updateAge);
    updateAge();
}());
