<?php
namespace TSJIPPY\ADMIN;

use DOMElement;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

class MainAdminMenu{
    public string $tab;
    public \DOMElement|null $tabLinkButtonsWrapper;
    public \DOMElement|null$mainDiv;
    public \DOMDocument|null$dom;
    public array $settings;
    public array $plugins;

    /**
     * Constructor
     */
    public function __construct() {
        $this->tab      = 'settings';
        if(isset($_GET['main-tab'])){
            $this->tab  = sanitize_key( wp_unslash( $_GET['main-tab']));
        }

        $this->dom		= new \DOMDocument();

        // Register a custom menu page.
        add_menu_page("Tsjippy Plugin Settings", "Tsjippy Settings", 'edit_others_posts', "tsjippy", [$this, "mainMenu"]);

        $this->plugins  = [];
        $this->getActivePlugins();

        add_filter("plugin_action_links_".plugin_basename(TSJIPPY\PLUGIN), [$this, 'addExtraPluginLinks'], 10, 3);
        foreach($this->plugins as $slug => $details){
            // Add plugin menu links
            add_filter("plugin_action_links_".plugin_basename($details['file']), [$this, 'addExtraPluginLinks'], 10, 3);
    
            add_submenu_page(
                'tsjippy', 
                $details['name'], 
                $details['name'], 
                "edit_others_posts", 
                'tsjippy-'.$slug, 
                function() use ( $details){
                    $this->buildSubMenu($details['name'], $details['slug']);
                }
            );
        }
    }

    public function getActivePlugins(){
        if(!empty($this->plugins)){
            return $this->plugins;
        }

        foreach(wp_get_active_and_valid_plugins() as $plugin){

            // Fimd tsjippy plugins
            if( strpos($plugin, 'tsjippy-') === false || strpos($plugin, 'tsjippy-shared-functionality') !== false){
                continue;
            }

            $menuSLug   = basename($plugin, '.php');

            $slug = str_replace('tsjippy-', '', $menuSLug);
            $name = ucwords(str_replace('-', ' ', $slug));

            $this->plugins[$slug] = [
                'name'  => $name,
                'slug'  => $slug,
                'file'  => $plugin
            ];
        }
    }
    
    /**
     * Updates or installs the plugin based on the slug in the $_GET parameter
     */
    public function installPlugins(){
        if(empty($_GET['activate']) && empty($_GET['install'])){
            return;
        }

        if(!empty($_GET['activate'])){
            $key    = 'activate';
        }else{
            $key    = 'install';
        }

        $slug   = sanitize_text_field( wp_unslash( $_GET[$key]));

        if(!empty($_GET['install'])){
            updateOrDownloadPlugin($slug);
        }

        // Check dependencies
        $result = validate_plugin_requirements("tsjippy-$slug/tsjippy-$slug.php");
        if(is_wp_error($result)){
            if(!empty($result->error_data['plugin_missing_dependencies'])){

                // Activate plugins
                foreach($result->error_data['plugin_missing_dependencies']['inactive'] ?? [] as $depSlug => $pluginName){
                    activate_plugin("$depSlug/$depSlug.php");
                    wp_clean_plugins_cache();
                }

                // Download and activate plugins
                foreach($result->error_data['plugin_missing_dependencies']['not_installed'] ?? [] as $depSlug => $pluginName){
                    if(!updateOrDownloadPlugin($depSlug)){
                        continue;
                    }
                    $result = activate_plugin("$depSlug/$depSlug.php");
                    wp_clean_plugins_cache();
                }
            }else{
                TSJIPPY\printArray($result);
            }
        }


        wp_clean_plugins_cache();

        $result = activate_plugin("tsjippy-$slug/tsjippy-$slug.php");
        wp_clean_plugins_cache();
        if(is_wp_error($result)){
            ?>
            <div class='error'>
                Failed to activate the plugin
            </div>
            <?php
        }else{
            ?>
            <div class='success'>
                Plugin activated successfully
            </div>
            <?php
        }

    }
    public function mainMenu(){
        $this->installPlugins();

        $plugins = [
            'bookings',
            'captcha',
            'comments',
            'content-filter',
            'default-pictures',
            'embed-page',
            'events',
            'html-email',
            'forms',
            'frontend-posting',
            'heic-to-jpeg',
            'library',
            'locations',
            'login',
            'mailchimp',
            'maintenance',
            'mandatory',
            'media-gallery',
            'page-gallery',
            'pdf',
            'pdf-to-excel',
            'prayer',
            'projects',
            'positional-accounts',
            'querier',
            'statistics',
            'user-management',
            'user-pages',
            'welcome-message',
            'signal',
            'vimeo',
        ];

        $inActivePlugins        = array_diff($plugins, array_keys($this->plugins));
        $notInstalledPlugins    = [];
        $curUrl                 = remove_query_arg( ['activate', 'install'],);

        do_action('tsjippy_plugin_actions');
    
        ?>
        <div class="wrap">
            <h1>Tsjippy Plugin Settings</h1>
            
            <h2>Active Plugins</h2>
            <table class='tsjippy table'>
                <?php
                foreach($this->plugins as $slug => $details){
                    ?>
                    <tr>
                        <td>
                            <?php
                            echo esc_html($details['name']);
                            ?>
                        </td>
                        <td>
                            <a href='<?php echo esc_url(admin_url( "admin.php?page=tsjippy-$slug" ));?>'>
                                Settings
                            </a>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            
            <h2>Inactive Plugins</h2>
            <table class='tsjippy table'>
                <?php
                $none = true;
                foreach($inActivePlugins as $plugin){
                    if(!is_file(WP_PLUGIN_DIR."/tsjippy-$plugin/tsjippy-$plugin.php")){
                        $notInstalledPlugins[] = $plugin;
                        continue;
                    }

                    $none   = false;
                    ?>
                    <tr>
                        <td>
                            <?php
                            echo esc_attr(ucfirst(str_replace('-', ' ', $plugin))) ;
                            ?>
                        </td>
                        <td>
                            <a href='<?php echo esc_url($curUrl);?>&activate=<?php echo esc_attr($plugin);?>'>
                                Activate
                            </a>
                        </td>
                    </tr>
                    <?php
                }
                if($none){
                    echo "No inactive plugins.";
                }
                ?>
            </table>

            <h2>Available Plugins</h2>
            <table class='tsjippy table'>
                <?php
                foreach($notInstalledPlugins as $plugin){
                    ?>
                    <tr>
                        <td>
                            <?php
                            echo esc_attr(ucfirst(str_replace('-', ' ', $plugin))) ;
                            ?>
                        </td>
                        <td>
                            <a href='<?php echo esc_url($curUrl);?>&install=<?php echo esc_attr($plugin);?>'>
                                Install
                            </a>
                        </td>
                    </tr>
                    <?php
                }
                if(empty($notInstalledPlugins)){
                    echo "No other available plugins.";
                }
                ?>
            </table>
        </div>
        <?php
    }
    
    /**
     * Tablink button for the submenu
     * 
     * @param   string  $slug   The slug one of settings, emails, data or functions
     * 
     * @return DOMElement       The DOm Document node
     */
    public function tabLinkButton($slug){
        $classString		= 'tablink';
        
        if($this->tab == $slug){
            $classString	.= ' active';
        }
        
        $attributes				= [
            'class' 		=> $classString, 
            'id' 			=> "show-$slug", 
            'data-target'	=> $slug
        ];

        if($slug == 'settings'){
            $position   = 'afterBegin';
        }else{
            $position   = 'beforeEnd';   
        }
        return TSJIPPY\addElement('button', $this->tabLinkButtonsWrapper, $attributes, ucfirst($slug), $position);
    }

    /**
    * Build the submenu container and tablink button
    * 
    * @param    string $slug    The slug of the submenu, used for the id and data-target of the button
    * @param    string $name    The name of the submenu
    *
    * @return   DOMElement      The domcontent node
    */
    public function mainNode($slug, $name){
        /**
         * Main container for the submenu
         */
        $attributes				= [
            'id'	=> $slug, 
            'class' => 'tabcontent'
        ];
        if($this->tab != $slug){
            $attributes['class'] .= ' hidden';
        }

        $node    = TSJIPPY\addElement('div', $this->mainDiv, $attributes);
        TSJIPPY\addElement('h2', $node, [], $name);

        return $node;
    }

    /**
     * Builds the submenu for each plugin
     * 
     * @param   string  $name    The name of the plugin
     * @param   string  $slug    The slug of the plugin, used for getting the settings and for the submenu slug
     * 
     * @return  void               Echoes the submenu HTML
     */
    public function buildSubMenu($name, $slug){
        if(empty($_GET['page'])){
            return '';
        }

        $this->settings	= get_option("tsjippy_{$slug}_settings", []);

        $this->mainDiv	= TSJIPPY\addElement('div', $this->dom, ['class' => 'plugin-settings']);
        TSJIPPY\addElement('h1', $this->mainDiv, [], "$name plugin settings");
        
        $className          = "TSJIPPY\\" . str_replace('-', '', strtoupper($slug)) . "\\AdminMenu";
        if(class_exists($className)){
            $exists  = true;
        }else{
            $className          = "TSJIPPY\\AdminMenu";

            if(class_exists($className)){
                $exists  = true;
            }else{
                $exists  = false;
            }
        }

        if($exists){
            $this->tabLinkButtonsWrapper	= TSJIPPY\addElement('div', $this->mainDiv, ['class' => 'tablink-wrapper']);

            $subMenu            = new $className($this->settings, $name);

            $message	        = $subMenu->handlePost();
                
            $settingsTab        = $this->settingsTab($subMenu, $slug, $name);
            $emailSettingsTab   = $this->emailSettingsTab($subMenu, $slug, $name);
            $dataTab            = $this->dataTab($subMenu, $slug, $name);
            $functionsTab       = $this->functionsTab($subMenu, $slug, $name);

            if(!$settingsTab){
                if($emailSettingsTab){
                    $this->tab      = 'emails';
                }else if($dataTab){
                    $this->tab      = 'data';
                }else if($functionsTab){
                    $this->tab      = 'functions';
                }else{
                    $this->tab      = '';
                }
            }

            // Only add a tablink button for the settings if there is at least on other tab
            if(
                $settingsTab &&
                (
                    $emailSettingsTab || $dataTab || $functionsTab
                )
            ){
                $this->tabLinkButton('settings');
            }

            $parent = null;

            if($this->tab == 'settings'){
                $parent = $settingsTab;
            }elseif($this->tab == 'emails'){
                $parent = $emailSettingsTab;
            }elseif($this->tab == 'data'){
                $parent = $dataTab;
            }elseif($this->tab == 'functions'){
                $parent = $functionsTab;
            }

            // Make sure the content is visible
            if($parent != null){
                $parent->className = str_replace(' hidden', '', $parent->className);
            }

            if(!empty($message)){
                TSJIPPY\addRawHtml($message, $parent, 'afterBegin');
            }
        }else{
            TSJIPPY\addElement('div', $this->mainDiv, [], 'No special settings needed for this plugin');
        }

        echo $this->dom->saveHtml();
    }

    /**
     * Builds the settings tab for the submenu
     * 
     * @param   object  $subMenu The submenu class instance
     * @param   string  $slug    The slug of the plugin
     * @param   string  $name    The name of the plugin
     * 
     * @return  \DOMElement|null       The DOM element for the settings tab
     */
    public function settingsTab($subMenu, $slug, $name){
        $node   = $this->mainNode('settings', 'Settings');

        $form   = TSJIPPY\addElement('form', $node, ['method' => "post"]);
        TSJIPPY\addElement('input', $form, ['type' => "hidden", 'name' => "plugin", 'value' => $slug,  'class' => 'no-reset']);
        TSJIPPY\addElement('input', $form, ['type' => "hidden", 'class' => 'no-reset', 'name' => "nonce", 'value' => wp_create_nonce('plugin-settings')]);

        $wrapper    = TSJIPPY\addElement('div', $form, ['class' => 'options']);

        $hasSettings    = $subMenu->settings($wrapper);

        if($hasSettings){
            TSJIPPY\addElement('br', $form);
            TSJIPPY\addElement('input', $form, ['type' => "submit", 'value' => "Save $name settings"]);
        }else{
            $node->remove();

            return false;
        }

        return $node;
    }

    /**
     * Builds the e-mail settings tab for the submenu
     * 
     * @param   object  $subMenu The submenu class instance
     * @param   string  $slug    The slug of the plugin
     * @param   string  $name    The name of the plugin
     * 
     * @return  \DOMElement|null       The DOM element for the e-mail settings tab
     */
    public function emailSettingsTab($subMenu, $slug, $name){
        $node    = $this->mainNode('emails', 'E-mail Settings');

        $form   = TSJIPPY\addElement('form', $node, ['method' => "post"]);
        TSJIPPY\addElement('input', $form, ['type' => "hidden", 'name' => "plugin", 'value' => $slug,  'class' => 'no-reset']);
        TSJIPPY\addElement('input', $form, ['type' => "hidden", 'name' => "nonce", 'value' => wp_create_nonce('plugin-settings'), 'class' => 'no-reset']);

        $hasEmails  = $subMenu->emails($form);

        if($hasEmails){
            TSJIPPY\addElement('br', $form);
            
            TSJIPPY\addElement('input', $form, ['type' => "submit", 'value' => "Save $name e-mail settings"]);

            $this->tabLinkButton('emails');

            return $node;
        }
        
        $node->remove();

        return false;
    }

    /**
     * Builds the data settings tab for the submenu
     * 
     * @param   object  $subMenu The submenu class instance
     * @param   string  $slug    The slug of the plugin
     * @param   string  $name    The name of the plugin
     * 
     * @return  \DOMElement|null       The DOM element for the data settings tab
     */
    public function dataTab($subMenu, $slug, $name){
        $node    = $this->mainNode('data', 'Data Settings');

        if(!$subMenu->data($node)){
            $node->remove();

            return false;
        }

        $this->tabLinkButton('data');

        return $node;
    }

    /**
     * Builds the functions settings tab for the submenu
     * 
     * @param   object  $subMenu The submenu class instance
     * @param   string  $slug    The slug of the plugin
     * @param   string  $name    The name of the plugin
     * 
     * @return  \DOMElement|null       The DOM element for the functions settings tab
     */
    public function functionsTab($subMenu, $slug, $name){
        $node    = $this->mainNode('functions', 'Functions');

        if(!$subMenu->functions($node)){
            $node->remove();

            return false;
        }

        $this->tabLinkButton('functions');

        return $node;
    }

    /**
     * Adds extra links to the plugin page
     * 
     * @param   array   $links   The existing links
     * @param   string  $plugin  The plugin file path
     * @param   array   $data    The plugin data
     * 
     * @return  array               The modified links
     */
    public function addExtraPluginLinks($links, $plugin, $data) {
        //http://plugin-prepare.local/wp-admin/admin.php?page=tsjippy
        //http://plugin-prepare.local/wp-admin/admin.php?page=tsjippy_bookings
        
        // Settings Link
        $slug           = basename($plugin, '.php');

        if($slug == 'tsjippy-shared-functionality'){
            $page   = 'tsjippy';
        }else{
            $page   = basename($plugin, '.php');
        }

        $url            = admin_url( "admin.php?page=$page" );
        $link           = "<a href='$url'>Settings</a>";
        $links['settings'] = $link;

        // Details link
        $url            = admin_url( "plugin-install.php?tab=plugin-information&plugin=$slug&section=changelog" );
        $link           = "<a href='$url'>Details</a>";
        $links['details'] = $link;

        //TO DO: implement Pro
        $pro = true;
        if($pro){

            // Update links
            if(isset($_GET['update']) && $_GET['update'] == $slug){
                // Reset updates cache
                delete_site_transient( 'update_plugins' );
                delete_transient('tsjippy-git-release');

                wp_update_plugins();

                $updates    = get_site_transient( 'update_plugins' );
                if(is_wp_error($updates)){
                    $link = "<div class='error'>".$updates->get_error_message()."</div>";
                }elseif(isset($updates->response[$plugin])){
                    $url    = self_admin_url( 'update.php?action=update-selected&amp;plugin=' . urlencode( $plugin ) );
                    $url    = wp_nonce_url( $url, 'bulk-update-plugins' );
                    $link   = "<a href='$url' class='update-link'>Update to ".$updates->response[$plugin]->new_version."</a>";
                }else{
                    $url   = admin_url( "plugins.php?update=$slug"  );
                    $link  = "Up to date <a href='$url'>Check again</a>";
                }
            }else{
                $url   = admin_url( "plugins.php?update=$slug" );
                $link  = "<a href='$url'>Check for update</a>";
            }
            $links['update'] = $link;
        }

        ksort($links);

        return $links;
    }
}