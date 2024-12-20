import Modal from "@typo3/backend/modal.js";
import $ from 'jquery';

class ContextMenuActions {


  tikaPreview = function (table, uid, dataAttributes) {
    if (table === 'sys_file') {

      const configuration = {
        title: 'Tika Preview',
        content:  dataAttributes.actionUrl + '&identifier=' + encodeURIComponent(uid).replace(/\*/g, '%2A'),
        size: Modal.sizes.large,
        type: Modal.types.ajax
      };
      Modal.advanced(configuration);
    }

  };

}

export default new ContextMenuActions();
