<?php
/*
Plugin Name: Ay Update mailer
Plugin URI:  http://ayctor.com
Description: Send an email each time a plugin or WordPress core is updated
Version:     0.1
Author:      Erwan Guillon
Author URI:  http://ayctor.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

include('php-mailjet-v3-simple.class.php');

class AyUpdateMailer{

  public static $default_email_template = "Hello {recipient_names},\r\n\r\nThe following elements have been updated :\r\n\r\n{update_sentence}";
  public static $default_wordpress_sentence = "Your WordPress site was just updated to version {wp_version}";
  public static $default_plugin_sentence = "The plugin &quot;{plugin_name}&quot; was just updated to version {plugin_version}";
  public static $default_subject = "Your website has been updated";

  public function __construct(){
    add_action('admin_menu', array($this, 'menu'));
    add_action('upgrader_process_complete', array($this, 'upgrade_action'), 10, 2);
  }

  public function menu(){
    add_options_page('Update Mailer', 'Update Mailer', 'manage_options', 'updatemailer', array($this, 'settings_page'));
  }

  public function settings_page(){
    if(isset($_POST['submit'])){
      if(isset($_POST['um_emails'])){
        update_option('um_emails', filter_input(INPUT_POST, 'um_emails', FILTER_SANITIZE_SPECIAL_CHARS));
      }
      if(isset($_POST['um_recipientnames'])){
        update_option('um_recipientnames', filter_input(INPUT_POST, 'um_recipientnames', FILTER_SANITIZE_SPECIAL_CHARS));
      }
      if(isset($_POST['um_subject'])){
        update_option('um_subject', filter_input(INPUT_POST, 'um_subject', FILTER_SANITIZE_SPECIAL_CHARS));
      }
      if(isset($_POST['um_emailtemplate'])){
        update_option('um_emailtemplate', filter_input(INPUT_POST, 'um_emailtemplate', FILTER_SANITIZE_SPECIAL_CHARS));
      }
      if(isset($_POST['um_wordpress'])){
        update_option('um_wordpress', filter_input(INPUT_POST, 'um_wordpress', FILTER_SANITIZE_SPECIAL_CHARS));
      }
      if(isset($_POST['um_plugin'])){
        update_option('um_plugin', filter_input(INPUT_POST, 'um_plugin', FILTER_SANITIZE_SPECIAL_CHARS));
      }
      if(isset($_POST['um_api'])){
        update_option('um_api', filter_input(INPUT_POST, 'um_api', FILTER_SANITIZE_SPECIAL_CHARS));
      }
      if(isset($_POST['um_from'])){
        update_option('um_from', filter_input(INPUT_POST, 'um_from', FILTER_SANITIZE_SPECIAL_CHARS));
      }
      if(isset($_POST['um_secret']) AND $_POST['um_secret'] != 'secret'){
        update_option('um_secret', filter_input(INPUT_POST, 'um_secret', FILTER_SANITIZE_SPECIAL_CHARS));
      }
    }
    ?>
    <div class="wrap">
      <h2>Update Mailer</h2>
      <form method="post" action="options-general.php?page=updatemailer">
        <table class="form-table">
          <tr>
            <th scope="row"><label for="um_emails">Emails</label></th>
            <td>
              <input name="um_emails" type="text" id="um_emails" value="<?php echo get_option('um_emails', ''); ?>" class="regular-text">
              <p class="description">Enter the emails (comma separated) to receive every update report.</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="um_recipientnames">Recipient names</label></th>
            <td>
              <input name="um_recipientnames" type="text" id="um_recipientnames" value="<?php echo get_option('um_recipientnames', ''); ?>" class="regular-text">
              <p class="description">It will be displayed at the beginning of the mail as : Hello {recipient_names}, ...</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="um_subject">Recipient names</label></th>
            <td>
              <input name="um_subject" type="text" id="um_subject" value="<?php echo get_option('um_subject', self::$default_subject); ?>" class="regular-text">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="um_emailtemplate">Email template</label></th>
            <td>
              <textarea name="um_emailtemplate" id="um_emailtemplate" class="regular-text" rows="10" style="width: 25em;"><?php echo get_option('um_emailtemplate', self::$default_email_template); ?></textarea>
              <p class="description">Variables are {recipient_names}, {update_sentence}</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="um_wordpress">WordPress update sentence</label></th>
            <td>
              <input name="um_wordpress" type="text" id="um_wordpress" value="<?php echo get_option('um_wordpress', self::$default_wordpress_sentence); ?>" class="regular-text">
              <p class="description">Variable is {wp_version}</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="um_plugin">Plugin update sentence</label></th>
            <td>
              <input name="um_plugin" type="text" id="um_plugin" value="<?php echo get_option('um_plugin', self::$default_plugin_sentence); ?>" class="regular-text">
              <p class="description">Variables are {plugin_name}, {plugin_version}</p>
            </td>
          </tr>
        </table>
        <h2 class="title">Mailjet configuration</h2>
        <table class="form-table">
          <tr>
            <th scope="row"><label for="um_from">From email</label></th>
            <td>
              <input name="um_from" type="text" id="um_from" value="<?php echo get_option('um_from', ''); ?>" class="regular-text">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="um_api">API key</label></th>
            <td>
              <input name="um_api" type="text" id="um_api" value="<?php echo get_option('um_api', ''); ?>" class="regular-text">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="um_secret">Secret key</label></th>
            <td>
              <input name="um_secret" type="password" id="um_secret" value="secret" class="regular-text">
            </td>
          </tr>
        </table>
        <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
      </form>
    </div>
    <?php
  }

  public function upgrade_action($upgrader_subject, $options){
    global $wp_version;

    $subject = get_option('um_subject', self::$default_subject);

    if($options['action'] == 'update' AND $options['type'] == 'plugin'){

      foreach($options['plugins'] as $plugin){
        $plugin_data = get_plugin_data(dirname(__FILE__) . '/../' . $plugin);
        $title = 'Plugin mis à jour';
        $variables = array(
          '{recipient_names}' => get_option('um_recipientnames', ''),
          '{update_sentence}' => str_replace(array('{plugin_name}', '{plugin_version}'), array($plugin_data['Name'], $plugin_data['Version']), get_option('um_plugin', self::$default_plugin_sentence))
        );
        $message = str_replace(array_keys($variables), array_values($variables), nl2br(html_entity_decode(get_option('um_emailtemplate', self::$default_email_template))));
        $this->send_mail($subject, $title, $message);
      }

    } elseif($options['action'] == 'update' AND $options['type'] == 'core'){

      $title = 'Mise à jour de WordPress';
      $variables = array(
        '{recipient_names}' => get_option('um_recipientnames', ''),
        '{update_sentence}' => str_replace('{wp_version}', $wp_version, get_option('um_wordpress', self::$default_wordpress_sentence))
      );
      $message = str_replace(array_keys($variables), array_values($variables), nl2br(html_entity_decode(get_option('um_emailtemplate', self::$default_email_template))));
      $this->send_mail($subject, $title, $message);

    }

  }

  public function send_mail($subject, $title, $message){
    $emails = get_option('um_emails', '');
    if($emails != ''){
      $email = '';

      ob_start();
      include(dirname(__FILE__) . '/mail.php');
      $email = ob_get_contents();
      ob_end_clean();

      $api = get_option('um_api', '');
      $secret = get_option('um_secret', '');
      $from = get_option('um_from', '');

      if($api != '' AND $secret != '' AND $from != ''){

        $mj = new Mailjet($api, $secret);
        $params = array(
          'method' => 'POST',
          'from' => $from,
          'to' => explode(',', $emails),
          'subject' => $subject,
          'html' => $email
        );

        $mj->sendEmail($params);

      } else {

        add_filter( 'wp_mail_content_type', function( $content_type ) {
          return 'text/html';
        });

        wp_mail($emails, $subject, $email);
        
        add_filter( 'wp_mail_content_type', function( $content_type ) {
          return 'text/plain';
        });

      }
    }
  }

}

$ayupdatemailer = new AyUpdateMailer();
