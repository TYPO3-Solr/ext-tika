/**
 * Module: TYPO3/CMS/Tika/ContextMenuActions
 *
 * JavaScript to handle import/export actions from context menu
 * @exports TYPO3/CMS/Tika/ContextMenuActions
 */
define(function () {
    'use strict';

    /**
     * @exports TYPO3/CMS/Tika/ContextMenuActions
     */
    var ContextMenuActions = {};

    ContextMenuActions.tikaPreview = function (table, uid) {
        if (table === 'sys_file') {

            require([
                'jquery',
                'TYPO3/CMS/Backend/Modal'
            ], function ($, Modal) {

                var configuration = {
                    title: 'Tika Preview',
                    content: top.TYPO3.settings.TikaPreview.moduleUrl + '&identifier=' + top.rawurlencode(uid),
                    size: Modal.sizes.large,
                    type: Modal.types.ajax
                }
                Modal.advanced(configuration);
            });
        }

    };

    return ContextMenuActions;
});