import NiceSelect from '../node_modules/nice-select2/src/js/nice-select2.js';

export function attachNiceSelect(element, options = { searchable: true }) {
  if (element._niceSelect == undefined) {
    new NiceSelect(element, options);
  }
}