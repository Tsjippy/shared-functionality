<?php

namespace TSJIPPY\GITHUB;

use TSJIPPY;
use Github\Exception\ApiLimitExceedException;
use Github\Api\Repository\Releases;
use Github\Api\Repository\Contents;
use \Github\Client;
use WP_Error;

if (! defined('ABSPATH')) exit;

require(__DIR__  . '/../../lib/vendor/autoload.php');

class Github
{
    public object   $client;
    public string   $token;
    public bool     $authenticated;
    public object   $repo;
    public object   $releases;
    public object   $contents;

    /**
     * Constructor
     * 
     * @param string $token Optional GitHub token for authentication
     */
    public function __construct($token = '')
    {
        $this->client             = new \Github\Client();
        $this->token            = $token;
        $this->authenticated    = false;
        /** @var \Github\Api\Repository $repo **/
        $this->repo             = $this->client->api('repo');
        $this->releases         = new Releases($this->client);
        $this->contents         = new Contents($this->client);
    }

    /**
     * Handle the case when the GitHub API rate limit is exceeded.
     * If not authenticated, it will attempt to authenticate using a token.
     */
    public function handleRateLimitExceeded()
    {
        if (!$this->authenticated) {
            $this->authenticate();
        }
    }

    /**
     * Authenticate using a token
     * Create a token here: https://github.com/settings/tokens/new
     */
    private function authenticate()
    {
        if ($this->authenticated) {
            // Already authenticated
            return true;
        }

        if (empty($this->token)) {
            $this->token    = SETTINGS['token'] ?? false;

            if (!$this->token) {
                return new WP_Error('Github', 'Please set a Github token');
            }
        }
        $this->client->authenticate($this->token, null, \Github\AuthMethod::ACCESS_TOKEN);

        $this->authenticated    = true;
    }

    /**
     * Retrieves the latest github release information from cache or github
     *
     * @param    string    $author     The github author. Default 'tsjippy'
     * @param    string    $repo       The github repo name
     * @param    bool      $force      Whether to skip the cached result. Default false
     *
     * @return   array|WP_Error        Array containing information about the latest release or an WP_Error object
     */
    public function getLatestRelease($author = 'tsjippy', $repo = '', $force = false)
    {
        if ($force) {
            $release    = false;
        } else {
            //check db version
            $release    = get_transient("tsjippy_$author-$repo");
        }

        // if not in transient
        if ($release === false) {
            $release    = '';

            try {
                $release         = $this->releases->latest($author, $repo);
            } catch (ApiLimitExceedException $e) {
                $this->handleRateLimitExceeded();

                if ($this->authenticated) {
                    return $this->getLatestRelease($author, $repo, $force);
                }
            } catch (\Exception $e) {
                if ($e->getMessage() == 'Not Found') {
                    if (!$this->authenticated) {
                        // authenticate
                        $this->authenticate();

                        // rerun
                        return $this->getLatestRelease($author, $repo, $force);
                    }
                }
            }

            //printArray($release);
            $this->client->removeCache();

            // Store for 1 hours
            set_transient("tsjippy_$author-$repo", $release, HOUR_IN_SECONDS);

            if (isset($e)) {
                if ($e->getCode() != 404) {
                    TSJIPPY\printArray($e);
                }
                return new \WP_Error('update', $e->getMessage());
            }
        }
        return $release;
    }

    /**
     * Downloads and unzips the latest release from a given github location to a given path
     *
     * @param    string  $author  The github author. Default 'tsjippy'
     * @param    string  $repo    The github repo name
     * @param    string  $path    The destination path
     * @param    bool    $force   Whether to skip the cached result version info. Default false
     * @param    bool    $skipZip Whether to unzip the package default false to unzip
     *
     * @return    true|string|WP_Error    True on success, the filepath is $skipZip or WP_Error object on failure
     */
    public function downloadRelease($author = 'tsjippy', $repo = '', $path = '', $force = false, $skipZip = false)
    {
        if (empty($path) && !$skipZip) {
            return new WP_Error('Github', 'Path canot be empty');
        }

        $slug   = basename($path);
        if (!str_starts_with($slug, 'tsjippy-')) {
            $path    = str_replace($slug, "tsjippy-$slug", $path);
        }

        $wpFileSystem   = TSJIPPY\loadWpFileSystem();

        $oldVersion    = -1;
        $nameSpace  = strtoupper(str_replace('tsjippy-', '', $repo));
        if (defined("TSJIPPY\\$nameSpace\\PLUGINVERSION")) {
            $oldVersion    = constant("TSJIPPY\\$nameSpace\\PLUGINVERSION");
        }

        // Get latest release info
        $release    = $this->getLatestRelease($author, $repo, $force);

        if (is_wp_error($release) || empty($release)) {
            return $release;
        }

        // download latest release
        $zipContent = '';
        try {
            $zipContent = $this->releases->assets()->show($author, $repo, $release['assets'][0]['id'], true);
        } catch (ApiLimitExceedException $e) {
            $this->handleRateLimitExceeded();

            try {
                $zipContent = $this->releases->assets()->show($author, $repo, $release['assets'][0]['id'], true);
            } catch (\Exception $e) {
                TSJIPPY\printArray("Could not find asset with id {$release['assets'][0]['id']} for $author-$repo");
                TSJIPPY\printArray($release['assets']);
            }
        } catch (\Exception $e) {
            if ($e->getCode() == 404) {
                // Get a new download link, bypass transient
                $release    = $this->getLatestRelease($author, $repo, true);
                if (is_wp_error($release)) {
                    return $release;
                }

                try {
                    $zipContent = $this->releases->assets()->show($author, $repo, $release['assets'][0]['id'], true);
                } catch (\Exception $e) {
                    TSJIPPY\printArray("Could not find asset with id {$release['assets'][0]['id']} for $author-$repo");
                    TSJIPPY\printArray($release['assets']);
                }
            } else {
                TSJIPPY\printArray($e);
            }

            if (!$zipContent) {
                return new WP_Error('Github', "Failed to download the latest release for $author-$repo<br><br>" . $e->getMessage() . "<br><br>Does the zip file exist in the release?");
            }
        }

        if ($this->contents->exists($author, $repo, "preupdate/pre_update.php")) {
            $fileContent    = $this->contents->download($author, $repo, "preupdate/pre_update.php");

            $tempFilePath = wp_tempnam();
            file_put_contents($tempFilePath, $fileContent);
            require_once($tempFilePath);

            // Remove the file
            wp_delete_file($tempFilePath);
        }

        // Create a temporary file in that directory
        $tmpZipFile   = wp_tempnam();

        if ($skipZip && !empty($path)) {
            $tmpZipFile = get_temp_dir() . basename($path);

            if (file_exists($tmpZipFile)) {
                wp_delete_file($$tmpZipFile);
            }
        }

        $wpFileSystem->put_contents($tmpZipFile, $zipContent);

        if ($skipZip) {
            return $tmpZipFile;
        }

        $zip            = new \ZipArchive();
        $zip->open($tmpZipFile);

        // if the folder already exists, remove it, to accomodate file deletions
        if (is_dir($path)) {
            $result                = $wpFileSystem->rmdir($path, true);
        }

        // recreate the folder
        wp_mkdir_p($path);

        // Extract the zipfile
        $result = $zip->extractTo($path);

        // close the archive and delete the file
        $zip->close();

        if (!$result) {
            TSJIPPY\printArray("Unzip failed to $path");

            return new WP_Error('Github', "Unzip failed for $repo");
        }

        wp_delete_file($tmpZipFile);

        // Run potential pre-update functions
        if (file_exists("$path/php/pre_update.php")) {
            // Load the file
            require_once("$path/php/pre_update.php");

            // Delete file so that we can suply a new one the next time
            wp_delete_file("$path/php/pre_update.php");
        }

        return true;
    }

    /**
     * Read the data of a file on github
     *
     * @param   string  $author     The github author
     * @param   string  $repo       The github repository
     * @param   string  $fileName   The filename
     *
     * @return  string|false        The content or false on failure
     */
    public function getFileContents($author, $repo, $fileName)
    {
        $content    = false;
        try {
            $file   = $this->contents->show($author, $repo, $fileName);

            if (!empty($file)) {
                $content    = base64_decode($file['content']);
                //convert to html
                $parser     = new \Michelf\MarkdownExtra;
                $content    = $parser->transform($content);
            }
        } catch (ApiLimitExceedException $e) {
            $this->handleRateLimitExceeded();
        } catch (\Exception $e) {
            // 404 is not found
            if ($e->getCode() != 404) {
                TSJIPPY\printArray($e);
            }

            $content    = false;
        }

        return $content;
    }

    /**
     * Parses plugin info from github
     *
     * @param   string  $pluginFilePath     The main file of the plugin you want to have info of
     * @param   string  $author             The github author
     * @param   string  $repo               The github repository, default empty
     * @param   array   $extraData          Extra data to include an array of active_installs, donate_link, rating, ratings banners, tested
     *
     * @return  object                      The details object
     */
    public function pluginData($pluginFilePath, $author, $repo = '', $extraData = [])
    {
        if (! function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $pluginData  = get_plugin_data($pluginFilePath, false, true);

        $res         = (object)$pluginData;

        $release     = $this->getLatestRelease($author, $repo);
        if (is_wp_error($release) || empty($release)) {
            return $res;
        }

        // Add available Sections
        $res->sections = [];
        foreach (['README', 'INSTALLATION', 'FAQ', 'CHANGELOG', 'screenshots', 'reviews', 'hooks'] as $item) {
            $content    = get_transient("tsjippy-git-$item");
            // if not in transient
            if ($content === false) {
                $content    = $this->getFileContents($author, $repo, $item . '.md');

                // Store for 24 hours
                set_transient("tsjippy-git-$item", $content, DAY_IN_SECONDS);
            }

            if (empty($content) && file_exists(dirname($pluginFilePath) . "/$item.md")) {
                $content    = file_get_contents(dirname($pluginFilePath) . "/$item.md");
            }

            if (!empty($content)) {
                // do not use h2 for layout purposes
                $content    = str_replace('h4', 'h5', trim($content));
                $content    = str_replace('h3', 'h4', trim($content));
                $content    = str_replace('h2', 'h3', trim($content));

                //convert to html
                $parser     = new \Michelf\MarkdownExtra;
                $content    = $parser->transform($content);

                $res->sections[strtolower(ucfirst($item))]    = str_replace('h2', 'h3', trim($content));
            }
        }

        // Add meta's
        $res->version           = $release['tag_name'];
        $res->last_updated      = gmdate(TSJIPPY\DATEFORMAT, strtotime($release['published_at']));
        $res->author            = $res->Author;
        $res->requires          = $res->RequiresWP;
        //$res->requires_php    = $res->RequiresPhp;
        $res->homepage          = $res->PluginURI;
        $res->slug              = 'tsjippy';

        foreach ($extraData  as $key => $data) {
            $res->$key  = $data;

            if ($key == 'ratings') {
                $res->num_ratings       = count($data);
            }
        }

        return $res;
    }

    /**
     * Checks for update from github
     *
     * @param   string  $path     The fullpath to the plugin or themes main file
     *
     * @return  object            Version information
     */
    public function getVersionInfo($path, $author = '%TEXTDOMAIN%', $repo = 'shared-functionality')
    {
        $slug       = pathinfo($path, PATHINFO_FILENAME);
        if (str_contains($path, 'themes')) {
            $oldVersion = wp_get_theme($slug)->get('Version');
        } else {
            if (!function_exists('get_plugin_data')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            $oldVersion = get_plugin_data($path)['Version'];
        }

        $release    = $this->getLatestRelease($author, $repo);

        if (is_wp_error($release) || empty($release)) {
            return $release;
        }

        $gitVersion     = $release['tag_name'];

        $item            = (object) array(
            'slug'          => $slug,
            'url'           => "https://api.github.com/repos/$author/$repo",
            'package'       => '',
            'plugin'        => $path
        );

        if (version_compare($gitVersion, $oldVersion) === 1 && !empty($release['assets'][0]['browser_download_url'])) {
            $item->new_version    = $gitVersion;
            $item->package        = $release['assets'][0]['browser_download_url'];
        }

        return $item;
    }
}
