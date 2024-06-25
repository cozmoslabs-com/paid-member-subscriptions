// External Dependencies
import React from "react";
import AjaxComponent from "./../base/AjaxComponent/AjaxComponent";

// Internal Dependencies
import "./style.css";

class Account extends AjaxComponent {
  static slug = "pms_edit_profile";

  _shouldReload(oldProps, newProps) {
    return (
      oldProps.hide_tabs    !== newProps.hide_tabs    ||
      oldProps.redirect_url !== newProps.redirect_url
    );
  }

  _reloadFormData(props) {
    var formData = new FormData();

    formData.append("action"       , "pms_divi_extension_ajax");
    formData.append("form_type"    , "af");
    formData.append("hide_tabs"    , props.hide_tabs);
    formData.append("redirect_url" , props.redirect_url);

    return formData;
  }

  render() {
    return super.render();
  }

  _render() {
    // console.log("_render");
    return (
      <div
        className="pms-form-container"
        dangerouslySetInnerHTML={{ __html: this.state.result }}
      />
    );
  }
}

export default Account;
