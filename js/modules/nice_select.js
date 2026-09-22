import NiceSelect from 'nice-select2';

export function attachNiceSelect(element, options = { searchable: true }) {
  if (element._niceSelect == undefined) {
    new NiceSelect(element, options);
  }
}