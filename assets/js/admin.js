jQuery(document).ready(function () {
  /** mapping input accordion **/ jQuery(".VONTMNT_mdm_mappings").accordion({
    header: ".VONTMNT_mdm_mapping_header",
    collapsible: true,
    active: false,
    animate: 500,
    heightStyle: "content",
  });
  jQuery(".VONTMNT_mdm_input_wrap input").on("click", function (e) {
    e.stopPropagation();
  });
  /** mapping input delete icon **/ jQuery(".VONTMNT_mdm_delete_mapping a").on(
    "click",
    function (e) {
      e.preventDefault();
      jQuery(this).parents(".VONTMNT_mdm_mapping").remove();
      jQuery(
        '<div id="setting-error-VONTMNT_mdm_message" class="notice notice-info is-dismissible"><p><strong>' +
          localizedObj.removedMessage +
          '</strong>. <a class="VONTMNT_mdm_reload" href="#" title="' +
          localizedObj.undoMessage +
          '">' +
          localizedObj.undoMessage +
          '</a>.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">' +
          localizedObj.dismissMessage +
          "</span></button></div>"
      ).insertBefore(".VONTMNT_mdm_wrap p.submit");
    }
  );
  /** mapping input delete icon - undo helper **/ jQuery("body").on(
    "click",
    ".VONTMNT_mdm_reload",
    function (e) {
      e.preventDefault();
      location.reload();
    }
  );
});
