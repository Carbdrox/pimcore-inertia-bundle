pimcore.registerNS("pimcore.document.editPreviewTab")

pimcore.document.editPreviewTab = Class.create({

    initialize: function (document) {
        this.document = document
        this.boundDocumentEventListener = this.documentEventListener.bind(this)
        this.isActive = false
        this.eventListenersSetup = false
        this.originalEditParent = null
    },

    getUrl: function (type) {
        let path = ''

        if (this.document.data && this.document.data.path) {
            path = this.document.data.path
            if (this.document.data.key) {
                path += this.document.data.key
            }
        } else if (this.document.path) {
            path = this.document.path
        }

        path = '/' + path.replace(/^\/+|\/+$/g, '')
        return `${path}?${type}=true&systemLocale=${pimcore.settings.language}&_dc=${Date.now()}`
    },

    getPreviewUrl: function () {
        return this.getUrl('pimcore_preview')
    },

    getLayout: function () {
        if (this.layout == null) {
            this.layout = this.createLayout()
        }
        return this.layout
    },

    createLayout: function () {
        this.editContainerName = 'edit-container-' + this.document.id
        this.previewIframeName = 'preview-frame-' + this.document.id

        const editHtml = `<div id="${this.editContainerName}" style="overflow:hidden;width:100%;height:100%;"></div>`
        const previewHtml = `<iframe id="${this.previewIframeName}" name="${this.previewIframeName}" src="${this.getPreviewUrl()}" width="100%" height="100%" frameborder="0"></iframe>`

        return new Ext.Panel({
            id: 'document_edit_preview_' + this.document.id,
            title: t('Edit & Preview'),
            iconCls: 'pimcore_icon_preview pimcore_material_icon',
            border: false,
            layout: 'border',
            closable: false,
            items: [
                {
                    region: 'west',
                    xtype: 'panel',
                    width: '35%',
                    split: true,
                    autoScroll: true,
                    html: editHtml,
                    listeners: {
                        resize: (panel) => {
                            this.updateFrameHeight(this.editContainerName, panel)
                        }
                    }
                },
                {
                    region: 'center',
                    xtype: 'panel',
                    autoScroll: true,
                    bodyStyle: 'padding: 0 10px;',
                    html: previewHtml,
                    listeners: {
                        resize: (panel) => {
                            this.updateFrameHeight(this.previewIframeName, panel)
                        }
                    },
                    tbar: [
                        {
                            text: t('refresh'),
                            iconCls: 'pimcore_icon_reload pimcore_material_icon',
                            handler: this.refreshPreview.bind(this)
                        }
                    ]
                }
            ],
            listeners: {
                activate: this.onActivate.bind(this),
                deactivate: this.onDeactivate.bind(this)
            }
        })
    },

    onActivate: function () {
        if (this.isActive) {
            return
        }

        this.isActive = true
        this.showLoadMask()
        this.setupEventListeners()
        this.moveEditIframeToPreviewTab()

        Ext.defer(() => {
            this.hideLoadMask()
        }, 100)
    },

    onDeactivate: function () {
        if (!this.isActive) {
            return
        }

        this.showLoadMask()
        this.moveEditIframeBackToOriginal()

        Ext.defer(() => {
            this.hideLoadMask()
            this.isActive = false
        }, 100)
    },

    moveEditIframeToPreviewTab: function() {
        try {
            const originalEditIframe = Ext.get(this.document.edit.iframeName ?? 'document_iframe_' + this.document.id)

            if (!originalEditIframe) {
                console.error('Original edit iframe not found')
                return false
            }

            const editContainer = Ext.get(this.editContainerName)
            if (!editContainer) {
                console.error('Edit container not found')
                return false
            }

            this.originalEditParent = originalEditIframe.dom.parentNode

            editContainer.dom.appendChild(originalEditIframe.dom)

            originalEditIframe.dom.style.width = '100%'
            originalEditIframe.dom.style.height = '100%'

            return true
        } catch (e) {
            console.error('Error moving edit iframe:', e)
            return false
        }
    },

    moveEditIframeBackToOriginal: function() {
        try {
            const editIframe = Ext.get(this.document.edit.iframeName ?? 'document_iframe_' + this.document.id)

            if (!editIframe || !this.originalEditParent) {
                return false
            }

            this.originalEditParent.appendChild(editIframe.dom)
            return true
        } catch (e) {
            console.error('Error moving edit iframe back:', e)
            return false
        }
    },

    showLoadMask: function () {

        if (!this.layout || !this.layout.rendered) {
            return
        }

        if (!this.loadMask) {
            this.loadMask = new Ext.LoadMask({
                target: this.layout,
                msg: t("please_wait")
            })
        }

        if (!this.loadMask) {
            return
        }

        this.loadMask.show()
    },

    hideLoadMask: function () {
        if (!this.loadMask) {
            return
        }
        this.loadMask.hide()
    },

    updateFrameHeight: function (frameId, panel) {
        const frameEl = Ext.get(frameId)
        if (!frameEl || !panel) {
            return
        }

        let height = panel.getHeight()
        if (panel.getDockedItems && panel.getDockedItems().length > 0) {
            height -= panel.getDockedItems()[0].getHeight()
        }

        frameEl.setHeight(height - 10)
    },

    refreshPreview: function () {
        this.saveToSession(() => {
            const previewFrame = Ext.get(this.previewIframeName)
            if (!previewFrame) {
                return
            }

            previewFrame.dom.src = this.getPreviewUrl()
        })
    },

    saveToSession: function (callback) {
        if (typeof callback !== 'function') {
            callback = function () {}
        }

        if (!this.document || !this.document.edit) {
            callback()
            return
        }

        let values = {}
        try {
            values = this.document.edit.getValues()
        } catch (e) {
            console.error("Error getting document values:", e)
            callback()
            return
        }

        Ext.Ajax.request({
            url: '/admin/page/save-to-session',
            method: 'POST',
            params: {
                id: this.document.id,
                data: Ext.encode(values)
            },
            success: callback,
            failure: function (response) {
                console.error("Request failed:", response)
                callback()
            }
        })
    },

    documentEventListener: function(event) {
        const updateDocId = event?.detail?.document?.id ?? event?.id

        if (updateDocId !== this.document.id) {
            return
        }

        this.refreshPreview()
    },

    setupEventListeners: function () {
        if (this.eventListenersSetup) {
            return
        }

        if (!window.pimcoreEditmodePreviewUpdate) {
            window.pimcoreEditmodePreviewUpdate = {}
        }

        window.pimcoreEditmodePreviewUpdate[this.document.id] = () => {
            this.refreshPreview()
        }

        const documentEvents = [
            pimcore.events.postSaveDocument
        ]

        documentEvents.forEach((eventName) => {
            document.addEventListener(eventName, this.boundDocumentEventListener)
        })

        this.eventListenersSetup = true
    },

    destroy: function () {

        this.moveEditIframeBackToOriginal()

        if (this.eventListenersSetup) {
            const documentEvents = [pimcore.events.postSaveDocument]
            documentEvents.forEach((eventName) => {
                document.removeEventListener(eventName, this.boundDocumentEventListener)
            })
        }

        if (window.pimcoreEditmodePreviewUpdate && window.pimcoreEditmodePreviewUpdate[this.document.id]) {
            delete window.pimcoreEditmodePreviewUpdate[this.document.id]
        }

        if (this.loadMask) {
            this.loadMask.destroy()
            this.loadMask = null
        }

        this.layout = null
        this.document = null
        this.isActive = false
        this.eventListenersSetup = false
        this.originalEditParent = null
    }
})
