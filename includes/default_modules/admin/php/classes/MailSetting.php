<?php
namespace TSJIPPY\ADMIN;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class MailSetting{
    public string $keyword;
    public array $replaceArray;
    public string $subjectKey;
    public string $messageKey;
    public string $headerKey;
    public string $subject;
    public string $message;
    public string $defaultSubject;
    public string $defaultMessage;
    public array $headers;

    /**
     * Initiates the class
     *
     * @param   string  $keyword    The keyword to use in the settings array
     * @param   string  $slug       The slug to use in the option name
     */
    public function __construct($keyword, $slug) {
        $this->replaceArray     = [
            '%site_url%'    => SITEURL,
            '%site_name%'   => SITENAME
        ];

        $this->keyword          = $keyword;
        $this->subjectKey       = $this->keyword."_subject";
        $this->messageKey       = $this->keyword."_message";
        $this->headerKey        = $this->keyword."_header";
        $this->subject          = '';
        $this->message          = '';
        $this->defaultMessage   = '';
        $this->defaultSubject   = '';
        $this->headers          = [];

        $emailSettings          = get_option("tsjippy_{$slug}_emails", []);
        if(isset($emailSettings[$this->subjectKey])){
            $this->subject  = $emailSettings[$this->subjectKey];
        }

        if(isset($emailSettings[$this->messageKey])){
            $this->message  = $emailSettings[$this->messageKey];
        }

        if(isset($emailSettings[$this->headerKey])){
            $this->headers  = $emailSettings[$this->headerKey];
        }
    }

    /**
     * Add replacements for user names
     *
     * @param object    $user   WP_User
     */
    protected function addUser($user){
        if(empty($user)){
            return;
        }
        
        $this->replaceArray['%first_name%']  = $user->first_name;
        $this->replaceArray['%last_name%']   = $user->last_name;
        $this->replaceArray['%full_name%']   = $user->display_name;
    }

    /**
     * Replaces all places holders in subject, message and headers
     */
    public function filterMail(){
        if(empty($this->subject)){
            $this->subject  = $this->defaultSubject;
        }

        if(empty($this->message)){
            $this->message  = $this->defaultMessage;
        }

        // Run the replacement twice as replace values can have placeholders themselves
        $i = 1;
        while ($i <= 2) {
            $i++;
            $this->subject  = str_replace(array_keys($this->replaceArray), array_values($this->replaceArray), $this->subject);
            $this->message  = str_replace(array_keys($this->replaceArray), array_values($this->replaceArray), $this->message);
        }
        
        foreach($this->headers as &$header){
            $header = str_replace(array_keys($this->replaceArray), array_values($this->replaceArray), $header);
        }
    }

    /**
     * Prints the e-mail headers input
     */
    protected function printHeadersInput(){
        if(empty($this->headers)){
            $this->headers    = [''];
        }
        ?>
        Any additional headers (see <a href='https://developer.wordpress.org/reference/functions/wp_mail/#using-headers-to-set-from-cc-and-bcc-parameters'>here</a>):<br>
        <div class="clone-divs-wrapper">
            <?php
            foreach($this->headers as $index=>$header){
                ?>
                <div class="clone-div" data-div-id="<?php echo esc_html($index);?>">
                    <label name="Header" class=" formfield form-label">
                        <h4 class="label-text">Header <?php echo esc_html($index + 1);?></h4>
                    </label>
                    <div class="button-wrapper">
                        <input type="text" name="emails[<?php echo esc_html($this->headerKey);?>][<?php echo esc_html($index);?>]" class="headers formfield formfield-input" value="<?php echo esc_html($header);?>" style="width: 500px;">
                        <?php
                        if(count($this->headers) > 1){
                            ?>
                            <button type="button" class="remove button">-</button>
                            <?php
                        }
                        if(end($this->headers) == $header){
                            ?>
                            <button type="button" class="add button">+</button>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
        <br>
        <?php
    }

    /**
     * Prints the e-mail subject input
     */
    protected function printSubjectInput(){
        $subject  = $this->subject;
        if(empty($subject)){
            $subject  = $this->defaultSubject;
        }

        ?>
        <label>
            E-mail subject:<br>
            <input type='text' name="emails[<?php echo esc_html($this->subjectKey);?>]" value="<?php echo esc_html($subject);?>" style="width: 98%;max-width: 700px;">
        </label>
        <br>
        <?php
    }

    /**
     * Prints the e-mail message input to screen
     */
    protected function printMessageInput(){
        $message  = $this->message;
        if(empty($message)){
            $message  = $this->defaultMessage;
        }

        ?>
        <div style='margin-bottom: -30px;'>E-mail content</div>
        <?php
        $settings = array(
            'wpautop'                   => false,
            'media_buttons'             => false,
            'forced_root_block'         => true,
            'convert_newlines_to_brs'   => true,
            'textarea_name'             => "emails[$this->messageKey]",
            //'textarea_rows'             => 10
        );

        wp_editor(
            $message,
            $this->messageKey,
            $settings
        );
    }

    /**
     * Prints both the subject and the content inputs to screen
     */
    public function printInputs(){
        $this->printSubjectInput();

        echo '<br>';

        $this->printMessageInput();

        $this->printHeadersInput();
    }

    /**
     * Prints all available placeholders to screen
     */
    public function printPlaceholders(){
        if(empty($this->replaceArray)){
            return;
        }

        ?>
        <p>
            You can use placeholders in your inputs.<br>
		    These ones are available (click on any of them to copy):<br>
            <?php
            foreach(array_keys($this->replaceArray) as $placeholder){
                ?>
                <span class='placeholders' title='Click to copy'>
                    <?php echo esc_html($placeholder); ?>
                </span>
                <?php
            }
            ?>
        </p>
        <?php
    }
}