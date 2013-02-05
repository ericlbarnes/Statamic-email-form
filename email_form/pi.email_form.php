<?php
class Plugin_email_form extends Plugin {

  public $meta = array(
    'name'       => 'Email',
    'version'    => '1.0',
    'author'     => 'Eric Barnes',
    'author_url' => 'http://ericlbarnes.com'
  );

  /**
   * Holds the validation errors
   * @param array
   */
  protected $validation = array();

  /**
   * Email Form
   *
   * Allows you to create an email form and parses the posted data.
   */
  public function index() {
    // Setup the gazillion options
    $options['to'] = $this->fetch_param('to');
    $options['cc'] = $this->fetch_param('cc', '');
    $options['bcc'] = $this->fetch_param('bcc', '');
    $options['from'] = $this->fetch_param('from', '');
    $options['msg_header'] = $this->fetch_param('msg_header', 'New Message', false, false, false);
    $options['msg_footer'] = $this->fetch_param('msg_footer', '', false, false, false);
    $options['subject'] = $this->fetch_param('subject', 'Email Form', false, false, false);
    $options['class'] = $this->fetch_param('class', '');
    $options['id'] = $this->fetch_param('id', '');
    
    $required = $this->fetch_param('required');
    $honeypot = $this->fetch_param('honeypot', false, false, true); #boolen param

    // Set up some default vars.
    $output = '';
    $vars = array(array());

    // If the page has post data process it.
    if (isset($_POST) and ! empty($_POST)) {
      if ( ! $this->validate($_POST, $required)) {
        $vars = array(array('error' => true, 'errors' => $this->validation));
      } elseif ($this->send($_POST, $options)) {
          $vars = array(array('success' => true));
      } else {
          $vars = array(array('error' => true, 'errors' => 'Could not send email'));
      }
    }

    // Display the form on the page.
    $output .= '<form method="post"';
    
    if( $options['class'] != '') {
      $output .= ' class="' . $options['class'] . '"';
    }

    if( $options['id'] != '') {
      $output .= ' id="' . $options['id'] . '"';
    }

    $output .= '>';
    
    $output .= $this->parse_loop($this->content, $vars);

    //inject the honeypot if true
    if ($honeypot) {
      $output .= '<input type="text" name="username" value="" style="display:none" />';
    }

    $output .= '</form>';

    return $output;
  }

  /**
   * Validate the submitted form data
   *
   * @param array input
   * @param string required
   * @return bool
   */
  protected function validate($input, $required) {
    $required = explode('|', str_replace('from', '', $required));

    // From is always required
    if ( ! isset($input['from']) or ! filter_var($input['from'], FILTER_VALIDATE_EMAIL)) {
      $this->validation[0]['error'] = 'From is required';
    }

    // Username is never required
    if (isset($input['username']) && $input['username'] !== '' ) {
      $this->validation[]['error'] = 'Username is never required';
    }

    foreach ($required as $key => $value) {
      if ($value != '' and $input[$value] == '') {
        $this->validation[]['error'] = ucfirst($value).' is required';
      }
    }

    return empty($this->validation) ? true : false;
  }

  /**
   * Send the email
   *
   * @param array $input
   * @param array $options
   * @return bool
   */
  protected function send($input, $options) {

    $to      = $options['to'];
    $subject = $options['subject'];
    $name    = isset($input['name']) ? $input['name'] : 'Email Form';

    // message
    $message = $options['msg_header']."\r\n";
    $message .= "-------------\r\n";
    foreach ($input as $key => $value) {
      $message .= $key.": ".$value."\r\n";
    }
    $message .= "-------------\r\n";
    $message .= $options['msg_footer']."\r\n";

    // Additional headers
    $headers   = array();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/plain; charset=iso-8859-1";
    $headers[] = "From: ".$name." <".$input['from'].">";
    $headers[] = "Reply-To: ".$name." <".$input['from'].">";
    if ($options['cc'] !== '') {
      $headers[] = "Cc: ".$options['cc'];
    }
    if ($options['bcc'] !== '') {
      $headers[] = "Bcc: ".$options['bcc'];
    }
    $headers[] = "Subject: ".$options['subject'];
    $headers[] = "X-Mailer: PHP/".phpversion();

    // Mail it
    return mail($options['to'], $options['subject'], $message, implode("\r\n", $headers));
  }
}