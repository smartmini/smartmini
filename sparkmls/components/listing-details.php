<?php



class fmcListingDetails extends fmcWidget {

  function __construct() {
    global $fmc_widgets;

    $widget_info = $fmc_widgets[ get_class($this) ];

    $widget_ops = array( 'description' => $widget_info['description'] );
//    $this->WP_Widget( get_class($this) , $widget_info['title'], $widget_ops);

    // have WP replace instances of [first_argument] with the return from the second_argument function
    add_shortcode($widget_info['shortcode'], array(&$this, 'shortcode'));

    // register where the AJAX calls should be routed when they come in
    add_action('wp_ajax_'.get_class($this).'_shortcode', array(&$this, 'shortcode_form') );
    add_action('wp_ajax_'.get_class($this).'_shortcode_gen', array(&$this, 'shortcode_generate') );

    add_action('wp_ajax_'.get_class($this).'_schedule_showing', array(&$this, 'schedule_showing') );
    add_action('wp_ajax_nopriv_'.get_class($this).'_schedule_showing', array(&$this, 'schedule_showing') );

        add_action('wp_ajax_'.get_class($this).'_contact', array(&$this, 'contact') );
        add_action('wp_ajax_nopriv_'.get_class($this).'_contact', array(&$this, 'contact') );

    }





  function schedule_showing($attr = array()) { 
    global $fmc_api;
        $api_my_account = $fmc_api->GetMyAccount();

        $send_email=($api_my_account['Emails'][0]['Address']);
        $mytest = flexmlsConnect::wp_input_get_post('flexmls_connect__important');

        //This is our bot blocker... if it is set, then pretend like everything went okay
        if (!empty($_POST['flexmls_connect__important'])){
          exit("SUCCESS");
        }

        $action = $api_my_account['UserType'];
        //if ($action=="Mls"){
            if (flexmlsConnect::is_not_blank_or_restricted(flexmlsConnect::wp_input_get_post('flexmls_connect__to_agent'))){
                $send_email=flexmlsConnect::wp_input_get_post('flexmls_connect__to_agent');
            }
            elseif (flexmlsConnect::is_not_blank_or_restricted(flexmlsConnect::wp_input_get_post('flexmls_connect__to_office'))){
                $send_email=flexmlsConnect::wp_input_get_post('flexmls_connect__to_office');
            }
            else{
                $action = "SendToMls";
            }
       // }

        try{
          // if (filter_var(flexmlsConnect::wp_input_get_post('flexmls_connect__from'), FILTER_VALIDATE_EMAIL) === FALSE) {
          //     throw new Exception("From e-mail is invalid");
          // }

         // if ($action=='Mls'){
            $headers = 'From: '. flexmlsConnect::wp_input_get_post('flexmls_connect__from') . "\r\n";
           $message = flexmlsConnect::wp_input_get_post('flexmls_connect__message') . "\r\n\r\n". flexmlsConnect::wp_input_get_post('flexmls_connect__from_name').' <'. flexmlsConnect::wp_input_get_post('flexmls_connect__from') . ">\r\n";
            //wp_mail($send_email, flexmlsConnect::wp_input_get_post('flexmls_connect__subject'), $message, $headers);
           $send_email = "damini.jaiswal@techinfini.com";
           //include (plugins_url("sparkmls/lib/Mandrill.php"));

           require_once("../lib/Mandrill.php"); die('SUCCESS');
            $mandrill = new Mandrill('hMuUQAqpVG9JiVjZ8ObXgQ');
            $sen_name = "Alaska Dream Makers Info";
              $sen_email = "info@alaskaland.info";
              $rec_name = "Larry";
              $rec_email = $send_email;
              $email_type = "to";
              $email_sub = "Schedule Showing";
              $msg_type = "html";         
              $box_msg = "Schedule Showing Details : .<br />";
              $box_msg .= $message;
              $message = array();
              $to = array();
              $to[] = array(
              'email' => $rec_email,
              'name' => $rec_name,
              'type' => $email_type
              );
              $message['subject'] = flexmlsConnect::wp_input_get_post('flexmls_connect__subject');
              $message[$msg_type] = $box_msg;
              $message['from_email'] = $sen_email;
              $message['from_name'] = $sen_name;
              $message['to'] = $to;
              
              $email_result = $mandrill->messages->send($message);
            die('SUCCESS');
         // }

        // if(filter_var(flexmlsConnect::wp_input_get_post('flexmls_connect__from'), FILTER_VALIDATE_EMAIL) === FALSE) {
        //   throw new Exception("From e-mail is invalid");
        // }
        // $headers = 'From: '. flexmlsConnect::wp_input_get_post('flexmls_connect__from') . "\r\n";
        // $message = flexmlsConnect::wp_input_get_post('flexmls_connect__message') . "\r\n\r\n". flexmlsConnect::wp_input_get_post('flexmls_connect__from_name').' <'. flexmlsConnect::wp_input_get_post('flexmls_connect__from') . ">\r\n";
        // $message .= 'Sent From Page: ' . flexmlsConnect::wp_input_get_post('flexmls_connect__page_lead') . "\r\n";
        // wp_mail( flexmlsConnect::wp_input_get_post('flexmls_connect__to'), flexmlsConnect::wp_input_get_post('flexmls_connect__subject'), $message, $headers);
        //   die("SUCCESS");
        } catch(Exception $e) {
          die('There was an error sending the e-mail: ' .$e->getMessage());
        }
  }


  function contact($attr = array()) {
        global $fmc_api; 
        $api_my_account = $fmc_api->GetMyAccount();

        $send_email=($api_my_account['Emails'][0]['Address']);
        $mytest = flexmlsConnect::wp_input_get_post('flexmls_connect__important');

        //This is our bot blocker... if it is set, then pretend like everything went okay
        if (!empty($_POST['flexmls_connect__important'])){
          exit("SUCCESS");
        }

        $action = $api_my_account['UserType'];
        if ($action=="Mls"){
            if (flexmlsConnect::is_not_blank_or_restricted(flexmlsConnect::wp_input_get_post('flexmls_connect__to_agent'))){
                $send_email=flexmlsConnect::wp_input_get_post('flexmls_connect__to_agent');
            }
            elseif (flexmlsConnect::is_not_blank_or_restricted(flexmlsConnect::wp_input_get_post('flexmls_connect__to_office'))){
                $send_email=flexmlsConnect::wp_input_get_post('flexmls_connect__to_office');
            }
            else{
                $action = "SendToMls";
            }
        }




        try{
          // if (filter_var(flexmlsConnect::wp_input_get_post('flexmls_connect__from'), FILTER_VALIDATE_EMAIL) === FALSE) {
          //         throw new Exception("From e-mail is invalid");
          //     }

          if ($action=='Mls'){
            $headers = 'From: '. flexmlsConnect::wp_input_get_post('flexmls_connect__from') . "\r\n";
            $message = flexmlsConnect::wp_input_get_post('flexmls_connect__message') . "\r\n\r\n". flexmlsConnect::wp_input_get_post('flexmls_connect__from_name');
            //wp_mail($send_email, flexmlsConnect::wp_input_get_post('flexmls_connect__subject'), $message, $headers);

            $send_email = "damini.jaiswal@techinfini.com";

            //include (plugins_url("sparkmls/lib/Mandrill.php"));
            require_once("../lib/Mandrill.php");
            $mandrill = new Mandrill('hMuUQAqpVG9JiVjZ8ObXgQ');
            $sen_name = "Alaska Dream Makers Info";
              $sen_email = "info@alaskaland.info";
              $rec_name = "Larry";
              $rec_email = $send_email;
              $email_type = "to";
              $email_sub = "Ask Question";
              $msg_type = "html";         
              $box_msg = "Ask Question Details : .<br />";
              $box_msg .= $message;
              $message = array();
              $to = array();
              $to[] = array(
              'email' => $rec_email,
              'name' => $rec_name,
              'type' => $email_type
              );
              $message['subject'] = flexmlsConnect::wp_input_get_post('flexmls_connect__subject');
              $message[$msg_type] = $box_msg;
              $message['from_email'] = $sen_email;
              $message['from_name'] = $sen_name;
              $message['to'] = $to;
              
              $email_result = $mandrill->messages->send($message);
            die("SUCCESS");
          }
          else{


            $subject = flexmlsConnect::wp_input_get_post('flexmls_connect__subject');

                    $body =  "This message has been auto-generated by your wordpress site.\n\n The following person has attempted to contact you:\n";
            $body .= "This message was sent from this page: " . flexmlsConnect::wp_input_get_post('flexmls_connect__page_lead') . "\n";
            $body .= "Name: " . flexmlsConnect::wp_input_get_post('flexmls_connect__from_name') . "\n";
            $body .= "Email: " . flexmlsConnect::wp_input_get_post('flexmls_connect__from'). "\n\n";
            $body .= "Message:\n";

                    $body .= flexmlsConnect::wp_input_get_post('flexmls_connect__message');
            $Contact = array();
            $Contact['DisplayName'] = flexmlsConnect::wp_input_get_post('flexmls_connect__from_name');
            $Contact['PrimaryEmail'] = flexmlsConnect::wp_input_get_post('flexmls_connect__from');

                flexmlsConnect::add_contact($Contact);

                if (flexmlsConnect::message_me($subject,
                                            $body,
                                            flexmlsConnect::wp_input_get_post('flexmls_connect__from')))
                {
                    die("SUCCESS");
                }
                else
                {
                    throw new Exception("An Error occured while attempting to contact the site.");
                }
      }
    }catch(Exception $e) {
      die('There was an error sending the e-mail: ' .$e->getMessage());
        }
    return;
  }



  function jelly($args, $settings, $type) { 
    global $fmc_api;
    $custom_page = new flexmlsConnectPageListingDetails($fmc_api);
    $custom_page->pre_tasks('-mls_'. trim($settings['listing']) );
    return $custom_page->generate_page(true);
  }


  function widget($args, $instance) { 
    echo $this->jelly($args, $instance, "widget");
  }


  function shortcode($attr = array()) {

    $args = array(
        'before_title' => '<h3>',
        'after_title' => '</h3>',
        'before_widget' => '',
        'after_widget' => ''
        );

    return $this->jelly($args, $attr, "shortcode");

  }


  function settings_form($instance) {
    $listing = array_key_exists('listing', $instance) ? esc_attr($instance['listing']) : null;

    $return  = "<p>\n";
    $return .= "<label for='".$this->get_field_id('title')."'>" . __('MLS#:') . "</label>\n";
    $return .= "<input fmc-field='listing' fmc-type='text' type='text' class='widefat' id='".$this->get_field_id('listing')."' name='".$this->get_field_name('listing')."' value='{$listing}'>\n";
    $return .= "</p>\n";

    $return .= "<input type='hidden' name='shortcode_fields_to_catch' value='listing' />\n";
    $return .= "<input type='hidden' name='widget' value='". get_class($this) ."' />\n";

    return $return;

  }


  function update($new_instance, $old_instance) {
    $instance = $old_instance;

    $instance['listing'] = strip_tags($new_instance['listing']);

    return $instance;
  }

}