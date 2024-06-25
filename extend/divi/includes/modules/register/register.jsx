// External Dependencies
import React from "react";
import AjaxComponent from "./../base/AjaxComponent/AjaxComponent";

// Internal Dependencies
import "./style.css";

class Register extends AjaxComponent {
  static slug = "pms_register";

  _shouldReload(oldProps, newProps) {
    return (
      oldProps.toggle_show           !== newProps.toggle_show           ||
      oldProps.toggle_include        !== newProps.toggle_include        ||
      oldProps.include_plans         !== newProps.include_plans         ||
      oldProps.toggle_exclude        !== newProps.toggle_exclude        ||
      oldProps.include_plans         !== newProps.include_plans         ||
      oldProps.exclude_plans         !== newProps.exclude_plans         ||
      oldProps.selected_plan         !== newProps.selected_plan         ||
      oldProps.toggle_plans_position !== newProps.toggle_plans_position
    );
  }

  _reloadFormData(props) {
    var formData = new FormData();

    formData.append("action"                , "pms_divi_extension_ajax");
    formData.append("form_type"             , "rf");
    formData.append("toggle_show"           , props.toggle_show);
    formData.append("toggle_include"        , props.toggle_include);
    formData.append("include_plans"         , props.include_plans);
    formData.append("toggle_exclude"        , props.toggle_exclude);
    formData.append("exclude_plans"         , props.exclude_plans);
    formData.append("selected_plan"         , props.selected_plan);
    formData.append("toggle_plans_position" , props.toggle_plans_position);

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

export default Register;
