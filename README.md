Base plugin to add capabilities to wordpress with the use of modules.

# Description

This plugin adds the following funcionality among other things.

## Family

You can define relationships like mother, father, sibling, child between users and define shared meta values like wedding date last name etc.

## File upload

A file uploader for uploading files to both the media gallery or with bypassing it

## Logger

A filterable page in the admin menu to show errors, warnings, and or info messages.
The page loads more messages over AJAX as they come

## Other

- Admin menu for this plugin and all installed tsjippy plugins
- List of available tsjippy plugins
- Lots of functions, js files and css that is needed by several tsjippy plugins to reduce code redundancy

# Hooks

## Filters

### tsjippy-family-meta-keys

Filters the available family meta keys

#### Parameters   
- array $metaKeys  The available keys array. Default ['family_name', 'family_picture']

### tsjippy-file-upload-path

Filters the destination path for a file upload

#### Parameters 
- string $destination   The targetfile path

### tsjippy-post-type-creation-args

Filters the posttype arguments

#### Parameters 
- array   $args   The arguments
-  string $single The single name of the posttype

### tsjippy-template-filter

Filters the template file path

#### Parameters 
- string $templateFile  The path to the template file

### tsjippy-role-description

Filters the role description

#### Parameters 
- string $roleDescription  The description of a user role
- string $role             The role slug

### tsjippy-user-page-url

Filters the url to an userpage

#### Parameters 
- false|string $url    The url or false if not found
- int          $userId The user id for which to get the url

## Actions

### tsjippy-plugin-actions

Runs before the admin menu is printed
#### Parameters 
None

### tsjippy-roles-changed

Runs after the roles of an user got changed

#### Parameters 
- WP_User $user The WP user object
- array $newRoles The updated roles for the user

### tsjippy-after-user-register

Fires after an user got registered

#### Parameters 
- int $userId the new users id
- array $roles Array of roles of the new user

### tsjippy_approved_user

Fires when an user account is approved an becomes active

#### Parameters 
- int $userId the new users id

# Available Plugins

Download available other plugins to add functionality '

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
- [Prayer](https://github.com/Tsjippy/daily-message) Sends and displays automated daily messages
- [Projects](https://github.com/Tsjippy/projects) Shows Project info
- [Querier](https://github.com/Tsjippy/querier) Add a very limited user role
- [Recipes](https://github.com/Tsjippy/recipes) Share recipes
- [Signal](https://github.com/Tsjippy/signal) Link with Signal messenger
- [Statistics](https://github.com/Tsjippy/statistics) Posts statistics
- [User management](https://github.com/Tsjippy/user-management) Central user management
- [User pages](https://github.com/Tsjippy/user-pages) Display info of users on a page per user
- [Vimeo](https://github.com/Tsjippy/vimeo) Auto upload video's to vimeo, auto import vimeo's to the media library
- [Welcome message](https://github.com/Tsjippy/welcome-message) Show one time only message to new users
