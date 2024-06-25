// External Dependencies
import React from "react";
import AjaxComponent from "./../base/AjaxComponent/AjaxComponent";

// Internal Dependencies
import "./style.css";

class RecoverPassword extends AjaxComponent {
  static slug = "pms_recover_password";

  _shouldReload(oldProps, newProps) {
    return (
        oldProps.redirect_url !== newProps.redirect_url
    );
  }

  _reloadFormData(props) {
    var formData = new FormData();

    formData.append("action", "pms_divi_extension_ajax");
    formData.append("form_type", "rp");
    formData.append("redirect_url", props.redirect_url);

    return formData;
  }

  render() {
    return super.render();
  }

  _render() {
    return (
      <div
        className="pms-form-container"
        dangerouslySetInnerHTML={{ __html: this.state.result }}
      />
    );
  }
}

export default RecoverPassword;
