class NavigationManager {

    navigationData = [
        {
            title: "Documentation",
            items: [
                {
                    href: "index.html",
                    text: "1. Introduction and overview",
                    chapter: "introduction",
                    sublinks: [
                        { anchor: "#what-is", text: "1.1 What is the Inertia Bundle?" },
                        { anchor: "#challenge", text: "1.2 The Challenge" },
                        { anchor: "#solution", text: "1.3 The Solution" },
                        { anchor: "#architecture", text: "1.4 Architecture Overview" },
                        { anchor: "#features", text: "1.5 Key Features" },
                        { anchor: "#technologies", text: "1.6 Supported Technologies" },
                        { anchor: "#requirements", text: "1.7 System Requirements" },
                        { anchor: "#license", text: "1.8 License and Community" }
                    ]
                },
                {
                    href: "setup.html",
                    text: "2. Installation and Setup",
                    chapter: "setup",
                    sublinks: [
                        { anchor: "#bundle-installation", text: "2.1 Bundle Installation" },
                        { anchor: "#bundle-configuration", text: "2.2 Bundle Configuration" },
                        { anchor: "#frontend-setup", text: "2.3 Frontend Setup" },
                        { anchor: "#root-template", text: "2.4 Root Template Setup" },
                        { anchor: "#javascript-setup", text: "2.5 JavaScript Application Setup" },
                        { anchor: "#package-scripts", text: "2.6 Package Scripts" },
                        { anchor: "#directory-structure", text: "2.7 Directory Structure" },
                        { anchor: "#development-workflow", text: "2.8 Development Workflow" },
                        { anchor: "#verification", text: "2.9 Verification" }
                    ]
                },
                {
                    href: "basic_usage.html",
                    text: "3. Basic Usage",
                    chapter: "basic_usage",
                    sublinks: [
                        { anchor: "#understanding-flow", text: "3.1 Understanding the Flow" },
                        { anchor: "#controller-methods", text: "3.2 Controller Implementation Methods" },
                        { anchor: "#method-autowired", text: "3.3 Method 1: Autowired Service" },
                        { anchor: "#method-attribute", text: "3.4 Method 2: InertiaResponse Attribute" },
                        { anchor: "#method-abstract", text: "3.5 Method 3: AbstractInertiaController" },
                        { anchor: "#areabricks", text: "3.6 Working with Areabricks" }
                    ]
                },
                {
                    href: "chapter3.html",
                    text: "3. Konfiguration",
                    chapter: "chapter3"
                },
                {
                    href: "chapter4.html",
                    text: "4. Entwicklung",
                    chapter: "chapter4"
                },
                {
                    href: "chapter5.html",
                    text: "5. Best Practices",
                    chapter: "chapter5"
                }
            ]
        },
        {
            title: "Erweitert",
            items: [
                {
                    href: "api-reference.html",
                    text: "API Referenz",
                    chapter: "api"
                },
                {
                    href: "examples.html",
                    text: "Beispiele",
                    chapter: "examples"
                },
                {
                    href: "troubleshooting.html",
                    text: "Fehlerbehebung",
                    chapter: "issues"
                }
            ]
        }
    ]

    pageMap = {}

    constructor() {
        this.navigationData.forEach((section) => {
            section.items.forEach((item) => {

                this.pageMap[item.href] = item.chapter
            })
        })

        this.currentPage = this.getCurrentPage()
        this.loadNavigation()
    }

    getCurrentPage() {
        const path = window.location.pathname
        const filename = path.split('/').pop() || 'index.html'

        return this.pageMap[filename] || 'chapter1'
    }

    renderNavigation() {

        const navMenu = document.querySelector('.nav-menu')

        console.log(navMenu)

        if (!navMenu) {
            return
        }

        navMenu.innerHTML = ''

        this.navigationData.forEach(section => {
            const navSection = document.createElement('div')
            navSection.className = 'nav-section'

            const sectionTitle = document.createElement('div')
            sectionTitle.className = 'nav-section-title'
            sectionTitle.textContent = section.title
            navSection.appendChild(sectionTitle)

            section.items.forEach(item => {
                const mainLink = document.createElement('a')
                mainLink.href = item.href
                mainLink.textContent = item.text
                mainLink.className = 'nav-link nav-entry'

                if (item.chapter) {
                    mainLink.setAttribute('data-chapter', item.chapter)
                }

                navSection.appendChild(mainLink)
                if (item.sublinks && item.sublinks.length > 0) {
                    item.sublinks.forEach(sublink => {
                        const sublinkElement = document.createElement('a')
                        sublinkElement.href = item.href + sublink.anchor
                        sublinkElement.textContent = sublink.text
                        sublinkElement.className = 'nav-link nav-sublink'

                        if (item.chapter) {
                            sublinkElement.setAttribute('data-chapter', item.chapter)
                        }

                        navSection.appendChild(sublinkElement)
                    })
                }
            })

            navMenu.appendChild(navSection);
        })
    }

    async loadNavigation() {
        try {
            this.renderNavigation()
            this.initializeNavigation()

        } catch (error) {
            console.error('Error while loading navigation:', error)
            this.showNavigationError()
        }
    }

    initializeNavigation() {
        this.setActiveNavigation()
        this.setupMobileNavigation()
        this.setupSmoothScrolling()
        this.setupScrollNavigation()
    }

    setActiveNavigation() {
        const navLinks = document.querySelectorAll('.nav-link')

        navLinks.forEach(link => {
            link.classList.remove('active')

            if (link.classList.contains('nav-entry') && link.getAttribute('data-chapter') === this.currentPage) {
                link.classList.add('active')
            }

            const href = link.getAttribute('href')
            if (href && href.includes('#') && href.startsWith(window.location.pathname.split('/').pop())) {
                link.classList.add('current-page-link')
            }
        })
    }

    setupMobileNavigation() {
        const mobileToggle = document.querySelector('.mobile-toggle')
        const sidebar = document.getElementById('sidebar')

        if (mobileToggle && sidebar) {
            mobileToggle.addEventListener('click', () => {
                sidebar.classList.toggle('open')
            })
        }

        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('open')
                }
            })
        })
    }

    setupSmoothScrolling() {
        document.querySelectorAll('.current-page-link').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault()
                const targetId = this.getAttribute('href').split('#')
                if (targetId.length < 2) {
                    return
                }

                const target = document.getElementById(targetId[1])
                if (!target) {
                    return;
                }

                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                })
            })
        })
    }

    setupScrollNavigation() {
        window.addEventListener('scroll', () => {
            const sections = document.querySelectorAll('h1[id], h2[id]')
            const currentPageLinks = document.querySelectorAll('.current-page-link')

            let current = ''
            sections.forEach(section => {
                const rect = section.getBoundingClientRect()
                if (rect.top <= 100) {
                    current = section.getAttribute('id')
                }
            })

            currentPageLinks.forEach(link => {
                const href = link.getAttribute('href')
                const anchorId = href.includes('#') ? href.split('#')[1] : ''

                if (anchorId === current) {
                    link.classList.add('active')
                } else {
                    link.classList.remove('active')
                }
            })
        })
    }

    showNavigationError() {
        const sidebar = document.getElementById('sidebar')

        if (!sidebar) {
            return
        }

        sidebar.innerHTML = `
            <div style="padding: 2rem; text-align: center; color: #ef4444;">
                <p>Could not load navigation.</p>
                <p><a href="index.html">Home</a></p>
            </div>
        `
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new NavigationManager()
})

