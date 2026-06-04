Base plugin to add capabilities to wordpress with the use of modules.

== Hooks ==

# FILTERS

## GENERIC

- apply_filters('sim-template-filter', $templateFile);
- apply_filters('sim_role_description', '', $role);
- apply_filters('sim-moduledirs', $moduleDirs);

## Admin module

- apply_filters('sim_submenu_description', '', $moduleSlug, $moduleName);
- apply_filters('sim_submenu_options', '', $moduleSlug, $settings, $moduleName);
- apply_filters('sim_email_settings', '', $moduleSlug, $settings, $moduleName);
- apply_filters('sim_module_data', '', $moduleSlug, $settings, $moduleName);
- apply_filters('sim_module_functions', '', $moduleSlug, $settings, $moduleName);
- apply_filters('sim_module_updated', $options, $moduleSlug, $Modules[$moduleSlug]);
- apply_filters('sim_module_updated', $options, $slug, $Modules[$slug]);

# Actions

## Generic

- do_action('sim_roles_changed', $user, $newRoles);
- do_action( 'sim_approved_user', $userId);
- do_action('sim_plugin_update', $oldVersion);
- do_action('sim_module_deactivated', $moduleSlug, $options);
- do_action('sim_module_activated', $slug, $options);

## Admin

- do_action('sim_module_actions');
- do_action('sim-admin-settings-post');

Download available other plugins to add functionality

=== Available Plugins ===

- [Bookings](https://github.com/Tsjippy/bookings) AirBNB like booking reservation system
- [Captcha](https://github.com/Tsjippy/captcha) Add captcha or equivalent to forms
- [Comments](https://github.com/Tsjippy/comments) Comment e-mails and permissions
- [Contentfilter](https://github.com/Tsjippy/content-filter) Make uploads and pages for logged-in users only or per role
- [Default pictures](https://github.com/Tsjippy/default-pictures) Define default pictures for new content if not set
- [Embed page](https://github.com/Tsjippy/embed-page) Embed a post in another post
- [Events](https://github.com/Tsjippy/events) Display (recurring) events on a calendar and feed
- [Html email](https://github.com/Tsjippy/html-email) Auto create nice looking e-mails
- [Forms](https://github.com/Tsjippy/forms) Advanced formbuilder
- [Frontend posting](https://github.com/Tsjippy/frontend-posting) Create content from the frontend
- [Heic to jpeg](https://github.com/Tsjippy/heic-to-jpeg) Auto convert heic to jpeg
- [Locations](https://github.com/Tsjippy/locations) Show locations on a map
- [Login](https://github.com/Tsjippy/login) Adds AJAX and 2fa (authenticator, webauth) login
- [Mailchimp](https://github.com/Tsjippy/mailchimp) Integration with mailchimp
- [Maintenance](https://github.com/Tsjippy/maintenance) Easy way to put your site in maintenance mode
- [Mandatory](https://github.com/Tsjippy/mandatory) Make certain content mandatory to read based on given criteria
- [Media gallery](https://github.com/Tsjippy/media-gallery) Show a gallery of images/audio/video
- [Page gallery](https://github.com/Tsjippy/page-gallery) Show a gallery of pages
- [Pdf](https://github.com/Tsjippy/pdf) Show pdf's full screen, export content as pdf
- [Pdf to excel](https://github.com/Tsjippy/pdf-to-excel) Convert PDF to excel
- [Positional accounts](https://github.com/Tsjippy/positional-accounts) Link a positional account to a personal one with one click switching
- [Prayer](https://github.com/Tsjippy/prayer) Sends automated prayer requests
- [Projects](https://github.com/Tsjippy/projects) Shows Project info
- [Querier](https://github.com/Tsjippy/querier) Add a very limited user role
- [Recipes](https://github.com/Tsjippy/recipes) Share recipes
- [Signal](https://github.com/Tsjippy/signal) Link with Signal messenger
- [Statistics](https://github.com/Tsjippy/statistics) Posts statistics
- [User management](https://github.com/Tsjippy/user-management) Central user management
- [User pages](https://github.com/Tsjippy/user-pages) Display info of users on a page per user
- [Vimeo](https://github.com/Tsjippy/vimeo) Auto upload video's to vimeo, auto import vimeo's to the media library
- [Welcome message](https://github.com/Tsjippy/welcome-message) Show one time only message to new users
