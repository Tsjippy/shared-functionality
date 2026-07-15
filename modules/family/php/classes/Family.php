<?php

namespace TSJIPPY\FAMILY;

use TSJIPPY;
use WP_Error;

if (! defined('ABSPATH')) exit;

class Family
{
    public string $tableName;
    public string $metaTableName;
    public array $siblings;
    public array $children;
    public object $partner;
    public array $parents;
    public int $userId;

    /**
     * Initiates the class
     */
    public function __construct()
    {
        global $wpdb;

        $this->tableName        = $wpdb->prefix . 'tsjippy_family';
        $this->metaTableName    = $wpdb->prefix . 'tsjippy_family_meta';
    }

    /**
     * Creates the tables for this plugin
     */
    public function createDbTables()
    {
        if (!function_exists('maybe_create_table')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        //only create db if it does not exist
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        //Main table
        $sql = "CREATE TABLE {$this->tableName} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            family_id mediumint(9) NOT NULL,
            user_id_1 mediumint(9) NOT NULL,
            user_id_2 mediumint(9) NOT NULL,
            relationship tinytext NOT NULL,
            start_date date,
            PRIMARY KEY  (id)
       ) $charsetCollate;";

        maybe_create_table($this->tableName, $sql);

        // Family Meta table
        $sql = "CREATE TABLE {$this->metaTableName} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            family_id mediumint(9) NOT NULL,
            meta_key text NOT NULL,
            meta_value text NOT NULL,
            PRIMARY KEY  (id)
       ) $charsetCollate;";

        maybe_create_table($this->metaTableName, $sql);
    }

    /**
     * Gets the family id
     *
     * @param   int|object  $userId     The wp user or user id
     *
     * @return  int|false               The family id or false on not found
     */
    protected function getFamilyId($userId)
    {
        if (is_object($userId)) {
            $userId = $userId->ID;
        }

        $familyId = TSJIPPY\getFromDb("familyId-$userId", 'family', "select family_id from %i where user_id_1=%d OR user_id_2=%d LIMIT 1", $this->tableName, $userId, $userId);

        return $familyId;
    }

    /**
     * Checks if an user has family
     *
     * @param   int|object  $userId     The wp user or user id
     *
     * @return  bool                    True if user has family
     */
    public function hasFamily($userId)
    {

        return !empty($this->getFamilyId($userId));
    }

    /**
     * Gets all family members of a user
     *
     * @param   int|object  $userId     The wp user or user id
     * @param   bool        $flat       Wheter to return a flast arary of user ids or indexed by relation type. Default false for indexed
     *
     * @return array|\WP_Error           The requested array
     */
    public function getFamily($userId, $flat = false)
    {
        global $wpdb;

        if (is_object($userId)) {
            $userId = $userId->ID;
        }

        $results = TSJIPPY\getFromDb("family-$userId", 'family', "select * from %i where user_id_1=%d or user_id_2=%d", $this->tableName, $userId, $userId);

        if (is_wp_error($results)) {
            return $results;
        }

        $family = [];

        if ($flat) {
            foreach ($results as $result) {
                if ($result->user_id_1 == $userId) {
                    $family[]   = $result->user_id_2;
                } else {
                    $family[]   = $result->user_id_1;
                }
            }

            return $family;
        }

        foreach ($results as $result) {
            // We add the relation ship as is
            if ($result->user_id_1 == $userId) {
                if (!is_array($family[$result->relationship])) {
                    $family[$result->relationship]  = [];
                }

                $family[$result->relationship][]   = $result->user_id_2;
            }

            // We add the opposite as the user id is the second one
            else {
                $type   = $result->relationship;

                if ($result->relationship == 'child') {
                    $type   = 'parent';
                }
                $family[$type]   = $result->user_id_1;
            }
        }

        return $family;
    }

    /**
     * Gets all the children of an user
     *
     * @param   int|object  $userId     The wp user or user id
     *
     * @return  array|\WP_Error          An array of children user ids or wp error
     */
    public function getChildren($userId)
    {
        if (is_object($userId)) {
            $userId = $userId->ID;
        }

        $results = TSJIPPY\getFromDb("children-$userId", 'family', "select user_id_2 from %i where user_id_1=%d AND relationship='child'", $this->tableName, $userId);

        return $results;
    }

    /**
     * Gets all the siblings of an user
     *
     * @param   int|object  $userId     The wp user or user id
     *
     * @return  array|\WP_Error          An array of sibling user ids
     */
    public function getSiblings($userId)
    {
        global $wpdb;

        if (is_object($userId)) {
            $userId = $userId->ID;
        }

        $siblings   = [];

        // Query all relations marked as siblings
        $results    = TSJIPPY\getFromDb("siblings-$userId", 'family', "select * from %i where (user_id_1=%d OR user_id_2=%d) AND relationship='sibling'", $this->tableName, $userId, $userId);

        if (is_wp_error($results)) {
            return $results;
        }

        foreach ($results as $result) {
            if ($result->user_id_1 == $userId) {
                $siblings[$result->user_id_2] = $result->user_id_2;
            } else {
                $siblings[$result->user_id_1] = $result->user_id_1;
            }
        }

        // Get all the users with the same parent
        $subQuery   = $wpdb->prepare("select user_id_1 from %i where user_id_2=%d AND relationship='child' LIMIT 1", $this->tableName, $userId);
        $results    = TSJIPPY\getFromDb("siblings-$userId", 'family', "select user_id_2 from %i where user_id_1=(%s) AND relationship='child'", $this->tableName, $subQuery);

        if (is_wp_error($results)) {
            return $results;
        }

        foreach ($results as $result) {
            if ($result->user_id_1 != $userId) {
                $siblings[$result->user_id_1] = $result->user_id_1;
            }
        }

        return $siblings;
    }

    /**
     * Gets all the parents of an user
     *
     * @param   int|object  $userId     The wp user or user id
     *
     * @return  array|\WP_Error          An array of parent user ids
     */
    public function getParents($userId)
    {
        if (is_object($userId)) {
            $userId = $userId->ID;
        }

        $results    = TSJIPPY\getFromDb("parents-$userId", 'family', "select user_id_1 from %i where user_id_2=%d AND relationship='child'", $this->tableName, $userId);

        if (is_wp_error($results) || empty($results)) {
            return $results;
        }

        $parents    = [];

        if ($results[0] == $userId) {
            $parents[$results[1]]  = $results[1];
        } else {
            $parents[$results[0]]  = $results[0];
        }

        return $parents;
    }

    /**
     * Get the partner of a user
     *
     * @param   int|object  $userId                 The wp user or user id
     * @param    bool        $returnUser                Whether to return the partners user id or the full user object default false for just the id
     * @param   bool        $returnDate             Wheter to return the wedding date, default false
     *
     * @return  int|object|string|false|\WP_Error   The partner user id or user object or wedding date or false if no partner or wp error on error
     */
    public function getPartner($userId, $returnUser = false, $returnDate = false)
    {
        if (is_object($userId)) {
            $userId = $userId->ID;
        }

        $results    = TSJIPPY\getFromDb("partner-$userId", 'family', "select * from %i where (user_id_1=%d OR user_id_2=%d) AND relationship='partner'", $this->tableName, $userId, $userId);

        if (is_wp_error($results)) {
            return $results;
        }

        if (empty($results)) {
            return false;
        }

        if ($returnDate) {
            return $results[0]->start_date;
        }

        if ($results[0]->user_id_1 == $userId) {
            $partner    = $results[0]->user_id_2;
        } else {
            $partner    = $results[0]->user_id_1;
        }

        if ($returnUser) {
            return get_userdata($partner);
        }

        return $partner;
    }

    /**
     * Get the wedding date of a user
     *
     * @param   int|object  $userId     The wp user or user id
     *
     * @return  string|false|WP_Error   The wedding date or false if no partner or wp error on error
     */
    public function getWeddingDate($userId)
    {
        return $this->getPartner($userId, false, true);
    }

    /**
     * Get a value from the family meta db
     *
     * @param   int|object  $userId     The wp user or user id
     * @param   string      $key        The key to get the value for, default empty for all
     * @param   bool        $single     Return only the first value
     *
     * @return  mixed                   The value or an array of key values values or null if not found
     */
    public function getFamilyMeta($userId, $key = '', $single = false)
    {
        if (is_object($userId)) {
            $userId = $userId->ID;
        }

        $familyId   = $this->getFamilyId($userId);
        if (empty($familyId)) {
            return $familyId;
        }

        // Get value for a specific key
        if (!empty($key)) {
            // These are not stored in the meta table but as relationships
            if(isset(['children' => 1, 'parents' => 1, 'siblings' => 1, 'partner' => 1][$key])){
                $functionName   = "get".ucfirst($key);
                $value          = $this->$functionName($userId);
            }else{
                $value    = TSJIPPY\getFromDb("$userId-$key", 'family', "select meta_value from %i where family_id=%d AND `meta_key`=%s", $this->metaTableName, $familyId, $key);
            }

            if (is_wp_error($value)) {
                return $value;
            }

            if (empty($value)) {
                return null;
            }

            if($single){
                return $value[0];
            }

            return $value;
        }

        /**
         * Get all meta's
         */
        $results    = TSJIPPY\getFromDb("$userId-familymetas", 'family', "select * from %i where family_id=%d", $this->metaTableName, $familyId);

        if (is_wp_error($results)) {
            return $results;
        }

        if (empty($results)) {
            return null;
        }

        $metas  = [];
        foreach($results as $result){
            if(!is_array($result->meta_value)){
                $result->meta_value = [$result->meta_value];
            }
            $metas[$result->meta_key]   = maybe_unserialize($result->meta_value);
        }

        /**
         * Add relational metas
         */
        foreach(['children', 'parents', 'siblings', 'partner'] as $k){
            $functionName   = "get".ucfirst($k);
            $value          = $this->$functionName($userId);

            // Metas area lways returns as arrays
            if(!is_array($value)){
                $value  = [$value];
            }
            $metas[$k]      = $value;
        }

        if($single){
            return $metas[0];
        }

        return $metas;
    }

    /**
     * Function to get proper family name
     * @param     object|int        $user            WP User_ID or WP_User object
     * @param    bool            $lastNameFirst    Whether we should return the names as Lastname, Firstname. Default false
     * @param    mixed            $partnerId        Variable passed by reference to hold the partner id
     *
     * @return    string|false                    Family name string or last name when a single or false when not a valid user
     */
    public function getFamilyName($user, $lastNameFirst = false, &$partnerId = false)
    {
        if (is_numeric($user)) {
            $user    = get_userdata($user);

            if (!$user) {
                return false;
            }
        }

        $familyName    = $this->getFamilyMeta($user, 'family_name', true);

        if (!empty($familyName)) {
            $familyName = $familyName;

            if(!str_contains($familyName, 'family')){
                $familyName .= ' family';
            }

            return $familyName;
        }

        // user has no family
        if (!$this->hasFamily($user)) {
            if ($lastNameFirst) {
                return "$user->last_name, $user->first_name";
            }

            if ($user->user_login == $user->display_name) {
                return "$user->first_name $user->last_name";
            }

            return $user->display_name;
        }

        $name         = $user->last_name;
        $partner    = $this->getPartner($user, true);

        // user has a partner
        if ($partner) {

            if ($partner->last_name != $user->last_name) {
                // Male name first
                if (get_user_meta($user->ID, 'tsjippy_gender', true)[0] == 'Male') {
                    $name    = $user->last_name . ' - ' . $partner->last_name;
                } else {
                    $name    = $partner->last_name . ' - ' . $user->last_name;
                }
            }
        }

        $this->updateFamilyMeta($user, 'family_name', $name . ' family');

        return $name . ' family';
    }

    /**
     * Function to check if a certain user is a child
     * @param     int        $userId         WP User_ID
     *
     * @return    bool                True if a child, false if not
     */
    public function isChild($userId)
    {
        return !empty($this->getParents($userId));
    }

    /**
     * Stores a relationship in the db
     *
     * @param   int     $userId     The main user this relationship applies to
     * @param   int     $userId2    The other user this relationship applies to
     * @param   string  $type       The relationship type (parent, partner, child, sibling)
     * @param   string  $start      The start of relatioship, i.e. wedding date
     *
     * @return  WP_Error|int        The id or an wp error object
     */
    public function storeRelationship($userId, $userId2, $type, $start = '')
    {
        global $wpdb;

        if (is_object($userId)) {
            $userId = $userId->ID;
        }

        if (is_object($userId2)) {
            $userId2 = $userId2->ID;
        }

        if (
            empty($userId) || 
            empty($userId2) || 
            empty($type) ||
            !get_userdata($userId) ||
            !get_userdata($userId2)
        ) {
            return new \WP_Error('family', 'Please supply valid values');
        }

        // Check if this relationship is already in the db
        switch ($type) {
            case 'siblings':
                if (isset($this->getSiblings($userId)[$userId2])) {
                    return true;
                }
                break;
            case 'child':
                if (in_array($userId2, $this->getChildren($userId))) {
                    return true;
                }
                break;
            case 'partner':
                $prevPartner    = $this->getPartner($userId);

                // Nothing to change
                if ($prevPartner == $userId2) {
                    return true;
                }

                // there is already a different partner set, remove it
                $this->removeRelationShip($userId, $prevPartner);
                break;
        }

        // Check if this user is already in the db
        $familyId   = $this->getFamilyId($userId);

        // Create family id if needed
        if (empty($familyId)) {
            // phpcs:disable
            $familyId   = TSJIPPY\getFromDb(
                "get_family_total",
                "family",
                "SELECT MAX(family_id) FROM %i", 
                $this->tableName
            ) + 1;
            // phpcs:enable
        }

        // phpcs:disable
        return TSJIPPY\insertInDb(
            $this->tableName,
            [
                'family_id'     => $familyId,
                'user_id_1'     => $userId,
                'user_id_2'     => $userId2,
                'relationship'  => $type,
                'start_date'    => $start
            ],
            [
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
            ],
            'family'
        );
    }

    /**
     * Updates the date of a relationship
     *
     * @param   int     $userId         The main user this relationship applies to
     * @param   string  $weddingdate    The start of relatioship, i.e. wedding date
     */
    public function updateWeddingDate($userId,  $weddingdate)
    {
        global $wpdb;

        if (is_object($userId)) {
            $userId = $userId->ID;
        }

        if (empty($userId) || empty($weddingdate)) {
            return new \WP_Error('family', 'Please supply valid values');
        }

        // Update weddingdate
        // phpcs:disable
        $wpdb->query(
            $wpdb->prepare("UPDATE %i SET start_date=%s WHERE (user_id_1=%d OR user_id_2=%d) and `relationship`='partner'", $this->tableName, $weddingdate, $userId, $userId)
        );
        // phpcs:enable

        /**
         * Flush db cache
         */
        if(wp_cache_supports( 'flush_group' )){
            wp_cache_flush_group('family');
        }else{
            wp_cache_flush();
        }


        if (!empty($wpdb->last_error)) {
            return new \WP_Error('family', $wpdb->last_error);
        }

        return true;
    }

    /**
     * Stores a family meta value
     *
     * @param   int     $userId     The user this relationship applies to
     * @param   string  $key        The key
     * @param   string  $value      The value
     *
     * @return  WP_Error|int        The id or an wp error object
     */
    public function updateFamilyMeta($userId, $key, $value)
    {
        global $wpdb;

        if (is_object($userId)) {
            $userId = $userId->ID;
        }

        // Check if already there
        $v   = $this->getFamilyMeta($userId, $key);
        if ($value == $v) {
            return true;
        } elseif (!empty($v)) {
            // remove the old one
            $this->removeFamilyMeta($userId, $key);
        }

        // Fetch the family Id
        $familyId   = $this->getFamilyId($userId);

        if (empty($familyId)) {
            return new \WP_Error('family', 'No family found!');
        }

        // phpcs:disable
        return TSJIPPY\insertInDb(
            $this->metaTableName,
            [
                'family_id'  => $familyId,
                'meta_key'   => $key,
                'meta_value' => $value
            ],
            [   
                '%d',
                '%s',
                '%s'
            ],
            'family'
        );
        // phpcs:enable
    }

    /**
     * Remove relationship
     *
     * @param     object|int        $userId1            WP User_ID or WP_User object
     * @param     object|int        $userId2            WP User_ID or WP_User object
     */
    public function removeRelationShip($userId1, $userId2)
    {
        global $wpdb;

        if (is_object($userId1)) {
            $userId1 = $userId1->ID;
        }

        if (is_object($userId2)) {
            $userId2 = $userId2->ID;
        }

        if (empty($userId1) || empty($userId2)) {
            return new \WP_Error('family', 'Please supply valid values');
        }

        $familyId   = $this->getFamilyId($userId1);

        // Delete relationship
        TSJIPPY\removeFromDb(
            $this->tableName,
            [
                "DELETE FROM %i WHERE (`user_id_1` = %d AND `user_id_2` = %d) OR (`user_id_1` = %d AND `user_id_2` = %d)", 
                $this->tableName, 
                $userId1, 
                $userId2, 
                $userId2, 
                $userId1
            ],
            [],
            'family'
        );

        /**
         * Flush db cache
         */
        if(wp_cache_supports( 'flush_group' )){
            wp_cache_flush_group('family');
        }else{
            wp_cache_flush();
        }

        // Check if this was the last family relationship
        $results    = TSJIPPY\getFromDb(
            "get_family_$familyId",
            "family",
            "SELECT * FROM %i WHERE family_id=%d", 
            $this->tableName, 
            $familyId
        );
        // phpcs:enable

        if (empty($results)) {
            // Delete any meta's

            TSJIPPY\removeFromDb(
                $this->metaTableName,
                [
                    'family_id' => $familyId
                ],
                [
                    '%d'
                ],
                'family'
            );
        }
    }

    /**
     * Remove family meta
     *
     * @param     object|int        $userId            WP User_ID or WP_User object
     * @param     string          $key            The meta key
     *
     * @return  WP_Error|int|null               The amount of rows deleted or an wp error object or null if nothing happened
     */
    public function removeFamilyMeta($userId, $key)
    {
        global $wpdb;

        if (is_object($userId)) {
            $userId = $userId->ID;
        }

        $familyId   = $this->getFamilyId($userId);

        if (!$familyId) {
            return null;
        }

        // delete meta
        // phpcs:disable
        TSJIPPY\removeFromDb(
            $this->metaTableName,
            [
                'family_id' => $familyId,
                'meta_key'  => $key,
            ],
            [
                '%d',
                '%s'
            ],
            'family'
        );
        // phpcs:enable

        if (!empty($wpdb->last_error)) {
            return new \WP_Error('family', $wpdb->last_error);
        }

        if ($wpdb->rows_affected === 0) {
            return null;
        }

        return $wpdb->rows_affected;
    }

    /**
     * Remove user from family
     *
     * @param     object|int        $userId            WP User_ID or WP_User object
     */
    function removeUser($userId)
    {
        global $wpdb;

        if (is_object($userId)) {
            $userId = $userId->ID;
        }

        // delete entries where the first user id is this user
        TSJIPPY\removeFromDb(
            $this->tableName,
            [
                'user_id_1' => $userId
            ],
            [
                '%d'
            ],
            'family'
        );

        // delete entries where the second user id is this user
        TSJIPPY\removeFromDb(
            $this->tableName,
            [
                'user_id_2' => $userId
            ],
            [
                '%d'
            ],
            'family'
        );
    }
}
