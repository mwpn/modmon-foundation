import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        theme: 'light',

        init() {
            const saved = localStorage.getItem('theme');
            const system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            this.theme = saved === 'dark' || saved === 'light' ? saved : system;
            this.apply();
        },

        set(value) {
            this.theme = value === 'dark' ? 'dark' : 'light';
            localStorage.setItem('theme', this.theme);
            this.apply();
        },

        toggle() {
            this.set(this.theme === 'dark' ? 'light' : 'dark');
        },

        apply() {
            const root = document.documentElement;
            const isDark = this.theme === 'dark';
            root.classList.toggle('dark', isDark);
            root.setAttribute('data-color-scheme', this.theme);
            root.style.colorScheme = this.theme;
        },
    });

    Alpine.store('sidebar', {
        isExpanded: true,
        isMobileOpen: false,
        isHovered: false,

        init() {
            const saved = localStorage.getItem('sidebarExpanded');
            if (window.innerWidth >= 1280) {
                this.isExpanded = saved === null ? true : saved === 'true';
            } else {
                this.isExpanded = false;
            }
            this.isMobileOpen = false;
            this.isHovered = false;
            window.addEventListener('resize', () => this.handleResize());
        },

        handleResize() {
            if (window.innerWidth < 1280) {
                this.isMobileOpen = false;
                this.isHovered = false;
            } else {
                this.isMobileOpen = false;
                const saved = localStorage.getItem('sidebarExpanded');
                this.isExpanded = saved === null ? true : saved === 'true';
                this.isHovered = false;
            }
        },

        toggleExpanded() {
            this.isExpanded = !this.isExpanded;
            this.isMobileOpen = false;
            this.isHovered = false;
            if (window.innerWidth >= 1280) {
                localStorage.setItem('sidebarExpanded', String(this.isExpanded));
            }
        },

        toggleMobileOpen() {
            this.isMobileOpen = !this.isMobileOpen;
        },

        setMobileOpen(value) {
            this.isMobileOpen = value;
        },

        setHovered(value) {
            if (window.innerWidth >= 1280 && !this.isExpanded) {
                this.isHovered = value;
            }
        },

        get isWide() {
            return this.isExpanded || this.isHovered || this.isMobileOpen;
        },
    });
});

window.Alpine = Alpine;
Alpine.start();
