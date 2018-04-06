<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
        <title>Send Email via  Mandrill API Using PHP</title>
        <link rel="stylesheet" type="text/css" href="css/style.css"/>
        <script src="js/jquery.js"></script>
    </head>
    <body>
        <?php
        include ("lib/Mandrill.php");
        $mandrill = new Mandrill('<-- Mandrill API-Key -->');
        $sen_name = "";
        $sen_email = "";
        $rec_name = "";
        $rec_email = "";
        $email_type = "";
        $email_sub = "";
        $msg_type = "";
        $box_msg = "";
        $message = array();
        $to = array();
        if (isset($_POST['sen_name'])) {
            $sen_name = $_POST['sen_name'];
        }
        if (isset($_POST['sen_email'])) {
            $sen_email = $_POST['sen_email'];
        }
        if (isset($_POST['rec_name'])) {
            $rec_name = $_POST['rec_name'];
        }
        if (isset($_POST['rec_email'])) {
            $rec_email = $_POST['rec_email'];
        }
        if (isset($_POST['email_type'])) {
            $email_type = $_POST['email_type'];
        }
        if (isset($_POST['email_sub'])) {
            $email_sub = $_POST['email_sub'];
        }
        if (isset($_POST['msg_type'])) {
            $msg_type = $_POST['msg_type'];
        }
        if (isset($_POST['box_msg'])) {
            $box_msg = $_POST['box_msg'];
        }
        $to[] = array(
            'email' => $rec_email,
            'name' => $rec_name, 
            'type' => $email_type 
        );
        $message['subject'] = $email_sub;
        $message[$msg_type] = $box_msg;
        $message['from_email'] = $sen_email;
        $message['from_name'] = $sen_name;
        $message['to'] = $to;
        if(isset($to[0]['email']) && $to[0]['email'] !== ""){
        $result = $mandrill->messages->send($message);
        $status = $result[0]['status'];
        }       
        ?>

        <div id="main">
            <h1>Send Email via  Mandrill API Using PHP</h1>
            <div id="login">
                <h2>Message Box</h2>
                <hr>
                <form action="" method="POST">
                    <h3>From : </h3>
                    <label>Sender's Name (Optional) : </label> <input type="text" name="" class="" placeholder="Enter Sender's Name"/>
                    <label>Sender's Email Address : </label> <input type="email" name="sen_email" class="sen_email" placeholder="Enter Sender's Email Address"/>
                    <h3>To : </h3>
                    <label>Receiver's Name (Optional) : </label> <input type="text" name="rec_name" class="" placeholder="Enter Receiver's Name"/>
                    <label>Receiver's Email Address : </label> <input type="email" name="rec_email" class="rec_email" placeholder="Enter Reciever's Email Address"/>
                    <label>Email Type : </label> 
                    <input type="radio" name="email_type" value="to" checked="checked" /><label> Default </label>
                    <input type="radio" name="email_type" value="cc"/><label> cc </label>
                    <input type="radio" name="email_type" value="bcc"/><label> bcc </label>
                    <label>Subject : </label>
                    <input type="text" name="email_sub" class="" placeholder="Subject"/>
                    <label>Message : </label> 
                    <input type="radio" name="msg_type" value="text" checked="checked"/><label>text</label>
                    <input type="radio" name="msg_type" value="html"/><label>html</label>
                    <textarea name="box_msg" rows="10" cols="30">Write your message here...</textarea>
                    <input type="submit" value="Check" id="submit"/>
                </form>
            </div>

            <div id="note">
                <?php
                if (isset($status)){
                    if($status == "sent") {
                    echo "<script>alert('Congratulations!!! Your Email has been sent successfully!!!')</script>";
                } elseif($status == "rejected") {
                    echo "<script>alert('Sorry!!! Some error occurs, Please try again.')</script>";
                }
                }
                ?>
            </div>
        </div>
        <script>
  jQuery(document).ready(function() {
                jQuery("#submit").click(function() {
                    var sen_email = jQuery('.sen_email').val();
                    var rec_email = jQuery('.rec_email').val();
                    if (sen_email == "") {
                        alert('Sender\'s Email Address cannot be empty.');
                    }
                     if (rec_email == "") {
                        alert('Receiver\'s Email Address cannot be empty.');
                    }
                });
            });
        </script>
    </body>
</html>


