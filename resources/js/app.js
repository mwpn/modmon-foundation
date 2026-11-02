import './bootstrap';
import Alpine from 'alpinejs';

// Alpine stores consumed by the Foundation Experience layouts
// (app shell sidebar + theme toggle, guest shell theme toggle).
const DESKTOP_MIN_WIDTH = 1280;

Alpine.store('theme', {
    theme: 'light',
    init() {
        // A page may pin its theme: <html data-theme-lock="light">.
        const locked = document.documentElement.dataset.themeLock;
        if (locked === 'light' || locked === 'dark') {
            this.theme = locked;
            this.apply();
            return;
        }
        const saved = localStorage.getItem('theme');
        const system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        this.theme = saved === 'dark' || saved === 'light' ? saved : system;
        this.apply();
    },
    toggle() {
        this.theme = this.theme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', this.theme);
        this.apply();
    },
    apply() {
        const root = document.documentElement;
        root.classList.toggle('dark', this.theme === 'dark');
        root.setAttribute('data-color-scheme', this.theme);
        root.style.colorScheme = this.theme;
    },
});

Alpine.store('sidebar', {
    isExpanded: window.innerWidth >= DESKTOP_MIN_WIDTH,
    isMobileOpen: false,
    isHovered: false,
    toggleExpanded() {
        this.isExpanded = !this.isExpanded;
        this.isMobileOpen = false;
    },
    toggleMobileOpen() {
        this.isMobileOpen = !this.isMobileOpen;
    },
    setMobileOpen(value) {
        this.isMobileOpen = value;
    },
    setHovered(value) {
        if (window.innerWidth >= DESKTOP_MIN_WIDTH && !this.isExpanded) {
            this.isHovered = value;
        }
    },
});

window.addEventListener('resize', () => {
    const sidebar = Alpine.store('sidebar');
    if (window.innerWidth < DESKTOP_MIN_WIDTH) {
        sidebar.isExpanded = false;
    } else {
        sidebar.isMobileOpen = false;
    }
});

// A tap anywhere on a date/time field opens its picker (on phones the field
// otherwise may take focus without showing one, and on desktop only the
// small calendar icon opened it).
document.addEventListener('click', (event) => {
    const field = event.target;
    if (!(field instanceof HTMLInputElement) || !['date', 'time', 'month', 'week', 'datetime-local'].includes(field.type)) {
        return;
    }
    if (field.disabled || field.readOnly || typeof field.showPicker !== 'function') {
        return;
    }
    try {
        field.showPicker();
    } catch {
        // Not allowed here (e.g. cross-origin frame): the browser's own behaviour stays.
    }
});

window.Alpine = Alpine;

Alpine.start();
