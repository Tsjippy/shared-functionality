// webpack.aliases.js
const path = require('path');

module.exports = {
    '@tsjippy/alert': path.resolve(__dirname, 'modules/alert.js'),
    '@tsjippy/display_message': path.resolve(__dirname, 'modules/display_message.js'),
    '@tsjippy/internet_connection': path.resolve(__dirname, 'modules/internet_connection.js'),
    '@tsjippy/load_assets': path.resolve(__dirname, 'modules/load_assets.js'),
    '@tsjippy/mobile': path.resolve(__dirname, 'modules/mobile.js'),
    '@tsjippy/modals': path.resolve(__dirname, 'modules/modals.js'),
    '@tsjippy/nice_select': path.resolve(__dirname, 'modules/nice_select.js'),
    '@tsjippy/show_loader': path.resolve(__dirname, 'modules/show_loader.js'),
    '@tsjippy/tabs': path.resolve(__dirname, 'modules/tabs.js'),
    '@tsjippy/field_value': path.resolve(__dirname, '../../tsjippy-forms/js/modules/field_value.js'),
    '@tsjippy/form_exports': path.resolve(__dirname, '../../tsjippy-forms/js/modules/form_exports.js'),
    '@tsjippy/form_submit_functions': path.resolve(__dirname, '../../tsjippy-forms/js/modules/form_submit_functions.js'),
    '@tsjippy/qr_login': path.resolve(__dirname, '../../tsjippy-login/js/modules/qr_login.js'),
    '@tsjippy/register_webauth': path.resolve(__dirname, '../../tsjippy-login/js/modules/register_webauth.js'),
    '@tsjippy/shared': path.resolve(__dirname, '../../tsjippy-login/js/modules/shared.js'),
    '@tsjippy/webauth': path.resolve(__dirname, '../../tsjippy-login/js/modules/webauth.js'),
    '@tsjippy/schedules_shared': path.resolve(__dirname, '../../tsjippy-schedules/js/modules/shared.js'),
    '@tsjippy/file_upload_exports': path.resolve(__dirname, '../modules/fileUpload/js/modules/file-upload-exports.js'),
    '@tsjippy/image_edit': path.resolve(__dirname, '../modules/fileUpload/js/modules/image-edit.js'),
};
