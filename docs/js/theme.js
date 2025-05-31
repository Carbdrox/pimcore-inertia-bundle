class ThemeManager {
    constructor() {
        this.currentTheme = this.getStoredTheme() || this.getSystemTheme()
        this.applyTheme(this.currentTheme)
        this.updateThemeIcon()
        this.setupThemeToggle()
    }

    getStoredTheme() {
        return localStorage.getItem('theme')
    }

    getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }

    applyTheme(theme) {
        this.currentTheme = theme
        document.documentElement.setAttribute('data-theme', theme)
        localStorage.setItem('theme', theme)
        this.updateMermaidTheme(theme)
        this.updatePrismTheme(theme);
    }

    updatePrismTheme(theme) {
        const lightTheme = document.getElementById('prism-light');
        const darkTheme = document.getElementById('prism-dark');

        if (!lightTheme || !darkTheme) {
            return
        }

        if (theme === 'dark') {
            lightTheme.disabled = true;
            darkTheme.disabled = false;
        } else {
            lightTheme.disabled = false;
            darkTheme.disabled = true;
        }
    }

    updateMermaidTheme(theme) {

        const mermaidConfig = {
            startOnLoad: true,
            theme: theme === 'dark' ? 'dark' : 'neutral',
            themeVariables: theme === 'dark' ? {
                primaryColor: '#8b5cf6',
                primaryTextColor: '#f8fafc',
                primaryBorderColor: '#334155',
                lineColor: '#cbd5e1',
                secondaryColor: '#1e293b',
                tertiaryColor: '#0f172a'
            } : {
                primaryColor: '#7c3aed',
                primaryTextColor: '#1e293b',
                primaryBorderColor: '#e2e8f0',
                lineColor: '#64748b',
                secondaryColor: '#f8fafc',
                tertiaryColor: '#ffffff'
            }
        }

        if ('undefined' == typeof mermaid || !mermaid) {
            return
        }

        mermaid.initialize(mermaidConfig)
    }

    updateThemeIcon() {
        const themeIcon = document.querySelector('.theme-icon')
        if (!themeIcon) {
            return
        }

        themeIcon.textContent = this.currentTheme === 'dark' ? '☀️' : '🌙'
    }

    setupThemeToggle() {
        const themeToggle = document.querySelector('.theme-toggle')
        if (!themeToggle) {
            return
        }

        themeToggle.addEventListener('click', () => {
            this.toggle()
        })
    }

    toggle() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark'
        this.applyTheme(newTheme)
        this.updateThemeIcon()
    }
}


document.addEventListener('DOMContentLoaded', () => {
    new ThemeManager()


    if ('undefined' !== typeof Prism) {
        console.log('test')
        Prism.plugins.NormalizeWhitespace.setDefaults({
            'remove-trailing': true,
            'remove-indent': true,
            'left-trim': true,
            'right-trim': true
        });
    }

})
