const applyTheme = (theme, themeClass) => {
    if (! theme || ! themeClass) {
        return;
    }

    document.documentElement.dataset.theme = theme;
    document.body.dataset.theme = theme;

    Array.from(document.body.classList).forEach((className) => {
        if (className.startsWith('theme-')) {
            document.body.classList.remove(className);
        }
    });

    document.body.classList.add(themeClass);
};

document.addEventListener('livewire:init', () => {
    window.Livewire.on('theme-changed', (payload) => {
        const detail = Array.isArray(payload) ? payload[0] : payload;

        applyTheme(detail?.theme, detail?.themeClass);
    });
});
