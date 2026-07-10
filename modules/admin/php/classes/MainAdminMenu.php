<?php

namespace TSJIPPY\ADMIN;

use DOMElement;
use TSJIPPY;

if (!defined('ABSPATH')) {
    exit;
}

class MainAdminMenu
{
    public string $tab;
    public \DOMElement|null $tabLinkButtonsWrapper;
    public \DOMElement|null $mainDiv;
    public array $settings;
    public array $plugins;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tab      = 'settings';

        // phpcs:disable
        if (isset($_GET['main-tab'])) {
            $this->tab  = TSJIPPY\sanitize($_GET['main-tab'], 'key');
        }
        // phpcs:enable

        // Register a custom menu page.
        add_menu_page("Tsjippy Plugin Settings", "Tsjippy Settings", 'edit_others_posts', "tsjippy", [$this, "mainMenu"]);

        $this->plugins  = [];
        $this->getActivePlugins();

        foreach ($this->plugins as $slug => $details) {
            // Add plugin menu links
            add_filter("plugin_action_links_" . plugin_basename($details['file']), [$this, 'addExtraPluginLinks'], 10, 3);

            add_submenu_page(
                'tsjippy',
                $details['name'],
                $details['name'],
                "edit_others_posts",
                'tsjippy-' . $slug,
                function () use ($details) {
                    $this->buildSubMenu($details['name'], $details['slug']);
                }
            );
        }
    }

    public function getActivePlugins()
    {
        if (!empty($this->plugins)) {
            return $this->plugins;
        }

        foreach (wp_get_active_and_valid_plugins() as $plugin) {
            // Fimd tsjippy plugins
            if (strpos($plugin, 'tsjippy-') === false || strpos($plugin, 'tsjippy-shared-functionality') !== false) {
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

    public function mainMenu()
    {
        $inActivePlugins        = array_diff_key(PLUGINSLUGS, $this->plugins);
        $notInstalledPlugins    = [];

        /**
         * Runs before the admin menu is printed
         */
        do_action('tsjippy-plugin-actions');

        $nonce  = wp_create_nonce('tsjippy-plugin-actions');

        ?>
        <div class="wrap">
            <h1>Tsjippy Plugin Settings</h1>

            <h2>Active Plugins</h2>
            <table class='tsjippy table'>
                <?php
                foreach ($this->plugins as $slug => $details) {
                ?>
                    <tr>
                        <td>
                            <?php
                            echo esc_html($details['name']);
                            ?>
                        </td>
                        <td>
                            <a href='<?php echo esc_url(admin_url("admin.php?page=tsjippy-$slug")); ?>'>
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
                foreach ($inActivePlugins as $plugin) {
                    if (!is_file(WP_PLUGIN_DIR . "/tsjippy-$plugin/tsjippy-$plugin.php")) {
                        $notInstalledPlugins[] = $plugin;
                        continue;
                    }

                    $none   = false;
                ?>
                    <tr>
                        <td>
                            <?php
                            echo esc_attr(ucfirst(str_replace('-', ' ', $plugin)));
                            ?>
                        </td>
                        <td>
                            <a href='<?php echo esc_url(network_admin_url("plugin-install.php?tab=plugin-information&plugin=tsjippy-$plugin&TB_iframe=true&width=600&height=550")); ?>'>
                                Activate
                            </a>
                        </td>
                    </tr>
                <?php
                }
                if ($none) {
                    echo "No inactive plugins. ";
                }
                ?>
            </table>

            <h2>Available Plugins</h2>
            <table class='tsjippy table'>
                <?php
                foreach ($notInstalledPlugins as $plugin) {
                ?>
                    <tr>
                        <td>
                            <?php
                            echo esc_attr(ucfirst(str_replace('-', ' ', $plugin)));
                            ?>
                        </td>
                        <td>
                            <a href='<?php echo esc_url(network_admin_url("plugin-install.php?tab=plugin-information&plugin=tsjippy-$plugin&TB_iframe=true&width=600&height=550")); ?>'>
                                Install
                            </a>
                        </td>
                    </tr>
                <?php
                }
                if (empty($notInstalledPlugins)) {
                    echo "No other available plugins. ";
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
    public function tabLinkButton($slug)
    {
        $classString        = 'tablink';

        if ($this->tab == $slug) {
            $classString    .= ' active';
        }

        $attributes                = [
            'class'         => $classString,
            'id'             => "show-$slug",
            'data-target'    => $slug
        ];

        if ($slug == 'settings') {
            $position   = 'afterBegin';
        } else {
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
    public function mainNode($slug, $name)
    {
        /**
         * Main container for the submenu
         */
        $attributes                = [
            'id'    => $slug,
            'class' => 'tabcontent'
        ];
        if ($this->tab != $slug) {
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
     * @return  void echoes the submenu HTML
     */
    public function buildSubMenu($name, $slug)
    {
        // phpcs:ignore
        if (empty($_GET['page'])) {
            return '';
        }

        $this->settings = get_option("tsjippy_{$slug}_settings", []);

        $this->mainDiv  = TSJIPPY\addElement('div', '', ['class' => 'plugin-settings']);
        TSJIPPY\addElement('h1', $this->mainDiv, [], "$name plugin settings");

        $className      = "TSJIPPY\\" . str_replace('-', '', strtoupper($slug)) . "\\AdminMenu";
        $exists         = false;
        if (class_exists($className)) {
            $exists     = true;
        } 
        
        // Class in the shared functionality code
        elseif($slug == 'logs' ){
            $className  = "TSJIPPY\\AdminMenu";

            if (class_exists($className)) {
                $exists = true;
            }
        }

        if ($exists) {
            $this->tabLinkButtonsWrapper = TSJIPPY\addElement('div', $this->mainDiv, ['class' => 'tablink-wrapper']);

            $subMenu          = new $className($this->settings, $name);

            $message          = $subMenu->handlePost();

            $settingsTab      = $this->settingsTab($subMenu, $slug, $name);
            $emailSettingsTab = $this->emailSettingsTab($subMenu, $slug, $name);
            $dataTab          = $this->dataTab($subMenu, $slug, $name);
            $functionsTab     = $this->functionsTab($subMenu, $slug, $name);

            if (!$settingsTab) {
                if ($emailSettingsTab) {
                    $this->tab = 'emails';
                } else if ($dataTab) {
                    $this->tab = 'data';
                } else if ($functionsTab) {
                    $this->tab = 'functions';
                } else {
                    $this->tab = '';
                }
            }

            // Only add a tablink button for the settings if there is at least on other tab
            if (
                $settingsTab &&
                (
                    $emailSettingsTab || $dataTab || $functionsTab
                )
            ) {
                $this->tabLinkButton('settings');
            }

            $parent = null;

            if ($this->tab == 'settings') {
                $parent = $settingsTab;
            } elseif ($this->tab == 'emails') {
                $parent = $emailSettingsTab;
            } elseif ($this->tab == 'data') {
                $parent = $dataTab;
            } elseif ($this->tab == 'functions') {
                $parent = $functionsTab;
            }

            // Make sure the content is visible
            if ($parent != null) {
                $parent->className = str_replace(' hidden', '', $parent->className);
            }

            if (!empty($message)) {
                TSJIPPY\addRawHtml($message, $parent, 'afterBegin');
            }
        } else {
            TSJIPPY\addElement('div', $this->mainDiv, [], 'No special settings needed for this plugin');
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->mainDiv->ownerDocument->saveHtml();
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
    public function settingsTab($subMenu, $slug, $name)
    {
        $node   = $this->mainNode('settings', 'Settings');

        $form   = TSJIPPY\addElement('form', $node, ['method' => "post"]);
        TSJIPPY\addElement('input', $form, ['type' => "hidden", 'name' => "plugin", 'value' => $slug,  'class' => 'no-reset']);
        TSJIPPY\addElement('input', $form, ['type' => "hidden", 'class' => 'no-reset', 'name' => "nonce", 'value' => wp_create_nonce('plugin-settings')]);

        $wrapper    = TSJIPPY\addElement('div', $form, ['class' => 'options']);

        $hasSettings    = $subMenu->settings($wrapper);

        if ($hasSettings) {
            TSJIPPY\addElement('br', $form);
            TSJIPPY\addElement('input', $form, ['type' => "submit", 'value' => "Save $name settings"]);
        } else {
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
    public function emailSettingsTab($subMenu, $slug, $name)
    {
        $node    = $this->mainNode('emails', 'E-mail Settings');

        $form   = TSJIPPY\addElement('form', $node, ['method' => "post"]);
        TSJIPPY\addElement('input', $form, ['type' => "hidden", 'name' => "plugin", 'value' => $slug,  'class' => 'no-reset']);
        TSJIPPY\addElement('input', $form, ['type' => "hidden", 'name' => "nonce", 'value' => wp_create_nonce('plugin-settings'), 'class' => 'no-reset']);

        $hasEmails  = $subMenu->emails($form);

        if ($hasEmails) {
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
    public function dataTab($subMenu, $slug, $name)
    {
        $node    = $this->mainNode('data', 'Data Settings');

        if (!$subMenu->data($node)) {
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
    public function functionsTab($subMenu, $slug, $name)
    {
        $node    = $this->mainNode('functions', 'Functions');

        if (!$subMenu->functions($node)) {
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
    public function addExtraPluginLinks($links, $plugin, $data)
    {
        //http://plugin-prepare.local/wp-admin/admin.php?page=tsjippy
        //http://plugin-prepare.local/wp-admin/admin.php?page=tsjippy_bookings

        // Settings Link
        $slug       = basename($plugin, '.php');

        if ($slug == 'tsjippy-shared-functionality') {
            $page   = 'tsjippy';
        } else {
            $page   = basename($plugin, '.php');
        }

        $url               = admin_url("admin.php?page=$page");
        $link              = "<a href='$url'>Settings</a>";
        $links['settings'] = $link;

        // Details link
        $url              = admin_url("plugin-install.php?tab=plugin-information&plugin=$slug&section=changelog");
        $link             = "<a href='$url'>Details</a>";
        $links['details'] = $link;

        /**
         * Filters the links shown in the plugin screen
         * 
         * @param   array   $links   The current links
         * @param   string  $plugin  The plugin file path
         * @param   array   $data    The plugin data
         */
        $links            = apply_filters('tsjippy-menu-links', $links, $plugin, $data);

        ksort($links);

        return $links;
    }
}
