
const overrideDocumentPanel = function () {

    if (!pimcore || !pimcore?.document?.page?.prototype) {
        console.error('Can\'t find document panel')
    }

    if (!pimcore.document.page.prototype.init) {
        console.error('Document panel doesn\'t have init method')
    }

    if (!pimcore.document.page.prototype.getTabPanel) {
        console.error('Document panel doesn\'t have getTabPanel method')
    }

    const originalInit = pimcore.document.page.prototype.init
    const originalGetTabPanel = pimcore.document.page.prototype.getTabPanel

    Ext.override(pimcore.document.page, {

        init: function () {
            originalInit.call(this)

            if (!this.isAllowed("save") && this.isAllowed("publish")) {
                return
            }

            this.editPreview = new pimcore.document.editPreviewTab(this)
        },


        getTabPanel: function() {
            const tabPanel = originalGetTabPanel.call(this)

            if ('page' !== this.type) {
                return tabPanel
            }

            if (this.isAllowed("save") || this.isAllowed("publish")) {
                tabPanel.on('afterrender', () =>  {
                    Ext.defer(() => {
                        tabPanel.insert(0, this.editPreview.getLayout())
                        tabPanel.setActiveTab(0)
                    }, 10)
                })
            }

            return tabPanel
        }
    })
}

overrideDocumentPanel()