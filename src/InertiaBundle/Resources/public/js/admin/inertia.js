
document.addEventListener(pimcore.events.postOpenDocument, function(e) {
    if (e.detail && e.detail.document && e.detail.document.type === 'page') {

        setTimeout(function() {
            new pimcore.document.editmodePreviewTab(e.detail.document);
        }, 500);
    }
});