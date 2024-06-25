// Like the built-in multiple checkboxes field, but preserves the IDs of the checkboxes in the value.

// External Dependencies
import React, { Component } from 'react';
// eslint-disable-next-line
import $ from 'jquery';

class MultipleCheckboxesWithIDs extends Component {

  static slug = 'pms_multiple_checkboxes_with_ids';

  state = {
    checkedBoxes: [],
  };
  constructor(props) {
    super(props);
    this.state = {
      checkedBoxes: this.props.value ? this.props.value.split(',') : [],
    };
  }
  _onCheckboxChange = (event) => {
    const { value, checked } = event.target;
    this.setState(prevState => {
      if (checked) {
        return { checkedBoxes: [...prevState.checkedBoxes, value] };
      } else {
        return { checkedBoxes: prevState.checkedBoxes.filter(box => box !== value) };
      }
    }, () => {
      this.props._onChange(this.props.name, this.state.checkedBoxes.join(','))
    });
  }
  render() {
    let checkboxes_data = this.props.fieldDefinition.options;
    const checkboxes = Object.keys(checkboxes_data).map((id, index) => {
      return (
          <p className="et-fb-multiple-checkbox" key={index}>
            <label htmlFor={`${this.constructor.slug}-${this.props.name}-checkbox-${id}`}>
              <input
                  type="checkbox"
                  id={`${this.constructor.slug}-${this.props.name}-checkbox-${id}`}
                  name={`${this.constructor.slug}-${this.props.name}-checkbox`}
                  value={id}
                  data-text={checkboxes_data[id]}
                  onChange={this._onCheckboxChange}
                  checked={this.state.checkedBoxes.includes(id.toString())}
              />{checkboxes_data[id]}
            </label>
          </p>
      );
    });
    return (
        <div className={`${this.constructor.slug}-wrap et-fb-multiple-checkboxes-wrap`}>
          {checkboxes}
          <input
              id={`${this.constructor.slug}-${this.props.name}`}
              name={this.props.name}
              value={this.props.value}
              type='hidden'
          />
        </div>
    );
  }
}
export default MultipleCheckboxesWithIDs;
