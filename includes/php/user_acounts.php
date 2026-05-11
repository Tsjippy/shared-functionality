<?php
namespace TSJIPPY;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Returns a unique username
 * @param	string 		$firstName		First name of a new user
 * @param	string 		$lastName		Last name of a new user
 *
 * @return	string|\WP_Error			An unique username or WP_Error
*/
function getAvailableUsername($firstName, $lastName){
	//Check if a user with this username already exists
	$i =1;
	while (true){
		//Create a username
		$userName = sanitize_user(str_replace(' ', '', $firstName.substr($lastName, 0, $i)));
		//Check for availability
		if (get_user_by("login", $userName) == ""){
			//available, return the username
			break;
		}
		$i += 1;
	}

	$errors = new \WP_Error();

	// Check the username.
	if ( '' === $userName ) {
		$errors->add( 'empty_username', __( '<strong>Error:</strong> Please enter a username.' ) );
	} elseif ( ! validate_username( $userName ) ) {
		$errors->add( 'invalid_username', __( '<strong>Error:</strong> This username is invalid because it uses illegal characters. Please enter a valid username.' ) );
		$sanitized_user_login = '';
	} elseif ( username_exists( $userName ) ) {
		$errors->add( 'username_exists', __( '<strong>Error:</strong> This username is already registered. Please choose another one.' ) );
	} else {
		/** This filter is documented in wp-includes/user.php */
		$illegal_user_logins = (array) apply_filters( 'illegal_user_logins', array() );
		if ( in_array( strtolower( $userName ), array_map( 'strtolower', $illegal_user_logins ), true ) ) {
			$errors->add( 'invalid_username', __( '<strong>Error:</strong> Sorry, that username is not allowed.' ) );
		}
	}

	if ( $errors->has_errors() ) {
		return $errors;
	}

	return $userName;
}

/**
 * Creates a new useraccount from POST values
 * 
 * @param	bool	$self		Whether the useraccount is created by the user itself or by an admin, default false
 * 
 * @return	string|\WP_Error	Message on success or WP_Error on failure
 */
function createUserAccount($self=false){
    $errors = new WP_Error();

    /**
     * First Name
     */
    if (empty($_POST["first-name"])){
        return new \WP_Error('Input error', "First name is required.");
    }	
    $firstName	= ucfirst(sanitize_text_field($_POST["first-name"]));

    /**
     * Last Name
     */
    if (empty($_POST["last-name"])){	
        return new \WP_Error('Input error', "Last name is required.");
    }
    $lastName	= ucfirst(sanitize_text_field($_POST["last-name"]));

    /**
     * E-mail
     */
    if (empty($_POST["email"])){		
		//Make up a non-existing emailaddress
		$email = sanitize_email("$firstName@$lastName.empty");
	}else{
        $email = sanitize_email($_POST["email"]);
    }

    /**
	 * Filters the email address of a user being registered.
	 *
	 * @since 2.1.0
	 *
	 * @param string $user_email The email address of the new user.
	 */
	$email = apply_filters( 'user_registration_email', $email );

    // Check the email address.
	if ( '' === $email ) {
		$errors->add( 'empty_email', __( '<strong>Error:</strong> Please type your email address.' ) );
	} elseif ( ! is_email( $email ) ) {
		$errors->add( 'invalid_email', __( '<strong>Error:</strong> The email address is not correct.' ) );
		$email = '';
	} elseif ( email_exists( $email ) ) {
		$errors->add(
			'email_exists',
			sprintf(
				/* translators: %s: Link to the login page. */
				__( '<strong>Error:</strong> This email address is already registered. <a href="%s">Log in</a> with this address or choose another one.' ),
				wp_login_url()
			)
		);
	}

    /**
     * Password
     */
    $pass1	    = $_POST['pass1'];
	$pass2	    = $_POST['pass2'];

	if($pass1 != $pass2){
        $errors->add(
			'password_no_match',
			sprintf(
				/* translators: %s: Link to the login page. */
				__( '<strong>Error:</strong> The passwords you entered do not match.' ),
				wp_login_url()
			)
		);
    }

    $passWord   = sanitize_user_field('user_pass', $pass1, null, 'edit');

    if ( $errors->has_errors() ) {
		return $errors;
	}

    /**
     * Approved and roles
     */
    $approved   = false;
    $roles      = ['revisor'];

    // Check if the current user has the right to create approved user accounts
    if(current_user_can('create_users')){
        $approved   = true;
	
        if(empty($_POST["validity"])){
            $validity = "unlimited";
        }else{
            $validity = $_POST["validity"];
        }

        if(!empty($_POST["roles"])){
            $roles = ["revisor"];
        }
    }

	//Create the account
	$userId = addUserAccount($firstName, $lastName, $email, $approved, $validity, $roles, $passWord);

	if(is_wp_error($userId)){
		return $userId;
	}

    if ( ! empty( $_COOKIE['wp_lang'] ) ) {
		$wp_lang = sanitize_text_field( $_COOKIE['wp_lang'] );
		if ( in_array( $wp_lang, get_available_languages(), true ) ) {
			update_user_meta( $userId, 'locale', $wp_lang ); // Set user locale if defined on registration.
		}
	}

    /**
	 * Fires after a new user registration has been recorded.
	 *
	 * @since 4.4.0
	 *
	 * @param int $userId ID of the newly registered user.
	 */
	do_action( 'register_new_user', $userId );
	
    if(current_user_can('create_users')){
        $url		= get_permalink(SETTINGS['user-edit-page'] ?? '');
		if(!$url){
			$url	= '';
		}
		$url= "?user-id=$userId";
        $message = "Succesfully created an useraccount for $firstName<br>You can edit the deails <a href='$url'>here</a>";
    }elseif($self){
        $message = "Succesfully created your useraccount, you will receive an e-mail as soon as it gets approved.<br>You can edit your details in your profile page.";
    }else{
        $message = "Succesfully created useraccount for $firstName<br>You can now select $firstName in the dropdowns";
    }
		
	return [
        'message'	=> $message,
        'user_id'	=> $userId
    ];
}

/**
 * Creates an useraccount
 * @param	string 		$firstName		First name of a new user
 * @param	string 		$lastName		Last name of a new user
 * @param	string		$email			E-mail adres
 * @param	bool		$approved		Whether the user is already approved or not. Default false
 * @param	string		$validity		How long the account will be valid, default 'unlimited'
 * @param   string      $passWord       The password for the new user account
 *
 * @return	int|\WP_Error				The new user id or WP_Error on error
*/
function addUserAccount($firstName, $lastName, $email, $approved = false, $validity = 'unlimited', $roles=[], $passWord = null){
    $errors = new WP_Error();

	//Get the username based on the first and lastname
	$username = TSJIPPY\getAvailableUsername($firstName, $lastName);
	
	//Build the user
	$userData = array(
		'user_login'    => $username,
		'last_name'     => $lastName,
		'first_name'    => $firstName,
		'user_email'    => $email,
		'display_name'  => "$firstName $lastName",
		'nickname'  	=> "$firstName $lastName",
		'user_pass'     => $passWord
	);
	
	//Give it the guest user role
	if($validity != "unlimited"){
		$userData['role'] = 'subscriber';
	}

    /**
	 * Fires when submitting registration form data, before the user is created.
	 *
	 * @since 2.1.0
	 *
	 * @param string   $username The submitted username after being sanitized.
	 * @param string   $email           The submitted email.
	 * @param WP_Error $errors               Contains any errors with submitted username and email,
	 *                                       e.g., an empty field, an invalid username or email,
	 *                                       or an existing username or email.
	 */
	do_action( 'register_post', $username, $email, $errors );

	/**
	 * Filters the errors encountered when a new user is being registered.
	 *
	 * The filtered WP_Error object may, for example, contain errors for an invalid
	 * or existing username or email address. A WP_Error object should always be returned,
	 * but may or may not contain errors.
	 *
	 * If any errors are present in $errors, this will abort the user's registration.
	 *
	 * @since 2.1.0
	 *
	 * @param WP_Error $errors               A WP_Error object containing any errors encountered
	 *                                       during registration.
	 * @param string   $username User's username after it has been sanitized.
	 * @param string   $email           User's email.
	 */
	$errors = apply_filters( 'registration_errors', $errors, $username, $email );

	if ( $errors->has_errors() ) {
		return $errors;
	}

	//Insert the user
	$userId = wp_insert_user( $userData ) ;

	// User creation failed
	if(is_wp_error($userId)){
		TSJIPPY\printArray($userId->get_error_message());
		return new \WP_Error('User creation', $userId->get_error_message());
	}

	if(!empty($roles) && function_exists('TSJIPPY\USERMANAGEMENT\updateRoles')){
		USERMANAGEMENT\updateRoles($userId, $roles);
	}
	
	if($approved){
		delete_user_meta( $userId, 'disabled');
		wp_send_new_user_notifications($userId, 'user');

		//Force an account update
		do_action( 'tsjippy_approved_user', $userId);
	}else{
		//Make the useraccount inactive
		update_user_meta( $userId, 'disabled', 'pending');
	}

	//Store the validity
	update_user_meta( $userId, 'account_validity', $validity);
	
	// Return the user id
	return $userId;
}

