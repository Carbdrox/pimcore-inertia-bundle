pimcore.registerNS("pimcore.document.editmodePreviewTab")
pimcore.document.editmodePreviewTab = Class.create({

    initialize: function(document) {
        this.document = document
        this.boundOnTabChange = this.onTabChange.bind(this);
        this.boundDocumentEventListener = this.documentEventListener.bind(this);

        if (this.document.edit && this.document.edit.layout) {
            this.addEditModePreviewTab()
        }
        else {
            document.addEventListener(pimcore.events.postOpenDocument, (e) => {
                if (e.detail && e.detail.document && e.detail.document.id === this.document.id) {
                    this.addEditModePreviewTab()
                }
            })
        }

        this.setupTabRemovalListener()
        this.setupEventListeners()
    },

    findEditorTabPanel: function(obj = undefined) {

        if (obj === null) {
            return null
        }

        if (!!obj) {

            if (obj.getActiveTab && typeof obj.getActiveTab === 'function' && obj.add && typeof obj.add === 'function') {
                return obj
            }

            const possibleProperties = [
                'container', 'component'
            ]

            for (let i = 0; i < possibleProperties.length; i++) {
                const prop = possibleProperties[i]
                if (obj[prop]) {
                    const result = this.findEditorTabPanel(obj[prop])
                    if (result) {
                        return result
                    }
                }
            }

            return null
        }

        if (!this.document.edit || !this.document.edit.layout || !this.document.edit.layout.tab) {
            return null
        }

        if (this.document.edit.layout.tab.getActiveTab) {
            return this.document.edit.layout.tab
        }

        return this.findEditorTabPanel(this.document.edit.layout.tab)
    },

    addEditModePreviewTab: function() {
        if (!this.document.edit || !this.document.edit.layout) {
            console.error("Document edit layout not found")
            return
        }

        if (!this?.editorTabPanel) {
            this.editorTabPanel = this.findEditorTabPanel()
        }

        if (!this.editorTabPanel) {
            console.error("Could not find TabPanel")
            return
        }

        this.findAndStoreTabReferences(this.editorTabPanel);

        this.layout = new Ext.Panel({
            title: t('Edit & Preview'),
            iconCls: 'pimcore_icon_preview',
            border: false,
            layout: 'border',
            items: [
                {
                    region: 'west',
                    xtype: 'panel',
                    width: '30%',
                    split: true,
                    autoScroll: true,
                    html: '<iframe id="editmode-frame-' + this.document.id + '" src="' + this.getEditmodeUrl() + '" width="100%" height="100%" frameborder="0"></iframe>',
                    listeners: {
                        afterrender: this.initEditModeFrame.bind(this)
                    }
                },
                {
                    region: 'center',
                    xtype: 'panel',
                    autoScroll: true,
                    bodyStyle: 'padding: 0 10px;',
                    html: '<iframe id="preview-frame-' + this.document.id + '" src="' + this.getPreviewUrl() + '" width="100%" height="100%" frameborder="0"></iframe>',
                    listeners: {
                        afterrender: this.initPreviewFrame.bind(this)
                    },
                    tbar: [
                        {
                            text: t('refresh'),
                            iconCls: 'pimcore_icon_reload',
                            handler: this.refreshPreview.bind(this)
                        }
                    ]
                }
            ],
            listeners: {}
        })

        try {
            this.editorTabPanel.insert(0, this.layout)
            this.editorTabPanel.setActiveTab(this.layout)

            if (!this?.tabChanged){
                this.editorTabPanel.on('tabchange', this.boundOnTabChange);
            }

            this.tabChanged = false
        } catch (e) {
            console.error("Error adding tab to editor:", e)
        }
    },

    findAndStoreTabReferences: function(tabPanel) {
        this.otherTabs = {};

        if (!tabPanel || !tabPanel.items || !tabPanel.items.items) {
            console.error('TabPanel doesn\'t contaim any items')
            return
        }

        tabPanel.items.items.forEach((tab) => {
            if (tab.title === 'Edit' || tab.iconCls === 'pimcore_icon_edit') {
                this.otherTabs.editTab = tab;
            }
        })
    },

    onTabChange: function(tabPanel, newTab) {
        if (this.otherTabs.editTab && newTab === this.otherTabs.editTab) {
            this.reloadEditTab();
        }

        if (newTab === this.layout) {

            if (this?.tabChanged) {
                return;
            }

            this.tabChanged = true
            this.recreateEditAndPreviewTab()
        }
    },

    reloadEditTab: function() {
        if (!this.otherTabs.editTab) {
            console.error("Edit tab reference not found")
            return;
        }

        try {
            const editTabPanel = pimcore.globalmanager.get("document_" + this.document.id)

            if (!editTabPanel?.edit?.reload) {
                console.error("Edit tab reload method not found")
                return
            }

            editTabPanel.edit.reload()
        } catch (e) {
            console.error("Error reloading edit tab:", e);
            return false;
        }
    },

    recreateEditAndPreviewTab: function() {

        if (!this?.editorTabPanel) {
            this.editorTabPanel = this.findEditorTabPanel()
        }

        if (!this.editorTabPanel) {
            console.error("Could not find TabPanel")
            return
        }

        try {
            this.editorTabPanel.remove(this.layout);
        } catch (e) {
            console.error("Error removing old tab:", e);
        }

        setTimeout(() => {
            this.addEditModePreviewTab();
        }, 100);
    },

    getBaseUrl: function () {
        let path = ''

        if (this.document.data && this.document.data.path) {
            path = this.document.data.path
            if (this.document.data.key) {
                path += this.document.data.key
            }
        }
        else if (this.document.path) {
            path = this.document.path
        }

        return '/' + path.replace(/^\/+|\/+$/g, '')
    },

    getEditmodeUrl: function() {
        return this.getBaseUrl() + '?pimcore_editmode=true&_dc=' + Date.now()
    },

    getPreviewUrl: function() {
        return this.getBaseUrl() + '?pimcore_preview=true&_dc=' + Date.now()
    },

    initEditModeFrame: function(panel) {
        const frameEl = Ext.get('editmode-frame-' + this.document.id)

        if (!frameEl) {
            return
        }

        this.updateFrameHeight(frameEl, panel)

        panel.on('resize', () => {
            this.updateFrameHeight(frameEl, panel)
        })
    },

    initPreviewFrame: function(panel) {
        const frameEl = Ext.get('preview-frame-' + this.document.id)

        if (!frameEl) {
            return
        }

        this.updateFrameHeight(frameEl, panel)

        panel.on('resize', () => {
            this.updateFrameHeight(frameEl, panel)
        })
    },

    updateFrameHeight: function(frameEl, panel) {
        if (!frameEl || !panel) {
            return
        }

        let height = panel.getHeight()

        if (panel.getDockedItems && panel.getDockedItems().length > 0) {
            height -= panel.getDockedItems()[0].getHeight()
        }

        frameEl.setHeight(height - 10)
    },

    refreshPreview: function() {
        this.saveToSession(() => {
            const frameId = 'preview-frame-' + this.document.id
            const previewFrame = Ext.get(frameId)

            if (!previewFrame) {
                console.error("Preview frame not found:", frameId)
                return
            }

            previewFrame.dom.src = this.getPreviewUrl()
        })
    },

    saveToSession: function(callback) {
        if (typeof callback !== 'function') {
            console.error('Callback is not a function')
            return
        }


        if (!this.document || !this.document.edit) {
            console.error("Document or edit object not available")
            callback()
            return
        }

        let data = {}

        try {
            if (typeof this.document.edit.getValues === 'function') {
                data = this.document.edit.getValues()
            }
            else if (this.document.edit.page && typeof this.document.edit.page.getValues === 'function') {
                data = this.document.edit.page.getValues()
            }
        }
        catch (e) {
            console.error("Error getting document values:", e)
            callback()
            return
        }

        Ext.Ajax.request({
            url: '/admin/page/save-to-session',
            method: 'POST',
            params: {
                id: this.document.id,
                data: Ext.encode(data)
            },
            success: function(response) {
                callback()
            },
            failure: function(response) {
                console.error("Request failed:", response)
                callback()
            }
        })

        Ext.Ajax.request({
            url: '/admin/page/save',
            method: 'POST',
            params: {
                id: this.document.id,
                data: Ext.encode(data),
                task: 'autoSave'
            },
            failure: function(response) {
                console.error("Auto-save request failed:", response);
            }
        });
    },

    documentEventListener: function(event) {

        const updateDocId = event?.detail?.document?.id ?? event?.id

        if (updateDocId !== this.document.id) {
            return
        }

        this.refreshPreview()
    },

    setupEventListeners: function() {

        window.pimcoreEditmodePreviewUpdate = (documentId) => {
            if (documentId === this.document.id) {
                this.refreshPreview()
            }
        }


        const documentEvents = [
            pimcore.events.postSaveDocument
        ];

        documentEvents.forEach((eventName) => {
            document.addEventListener(eventName, this.boundDocumentEventListener)
        })

    },

    setupTabRemovalListener: function() {

        const checkInterval = setInterval(() => {
            const documentTab = pimcore.globalmanager.get("document_" + this.document.id)

            if (!documentTab || !documentTab.tab) {
                return
            }

            clearInterval(checkInterval);
            documentTab.tab.on('beforedestroy', () => {
                this.destroy()
            })

        }, 500)

        //backup exit after 30sec
        setTimeout(() => {
            clearInterval(checkInterval)
        }, 30000)

    },

    destroy: function() {

        if (this.editorTabPanel) {
            try {
                this.editorTabPanel.un('tabchange', this.boundOnTabChange, this);
            } catch (e) {
                console.error("Error removing tabchange listener:", e);
            }
        }

        const documentEvents = [
            pimcore.events.postSaveDocument
        ];

        documentEvents.forEach((eventName) => {
            try {
                document.removeEventListener(eventName, this.boundDocumentEventListener);
            } catch (e) {
                console.error("Error removing document event listener:", e);
            }
        });

        if (window.pimcoreEditmodePreviewUpdate) {
            window.pimcoreEditmodePreviewUpdate = null;
        }

        this.document = null;
        this.editorTabPanel = null;
        this.layout = null;
        this.otherTabs = null;
    }

})