<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

use \Tsugi\Core\LTIX;

// Retrieve the launch data if present
$LTI = LTIX::requireData();
$p = $CFG->dbprefix;
$displayname = $USER->displayname;

if ( isset($_POST['grade']) )  {
    $gradetosend = $_POST['grade'] + 0.0;
    if ( $gradetosend < 0.0 || $gradetosend > 1.0 ) {
        $_SESSION['error'] = "Grade out of range";
        header('Location: '.addSession('index.php'));
        return;
    }

    // TODO: Look in the $LINK Variable to find the previous grade
    // to make it so the grade never goes down
    $prevgrade = 0.5;

    if ( $gradetosend < $prevgrade ) {
        $_SESSION['error'] = "Grade lower than $prevgrade - not sent";
    } else {
        // Use LTIX to send the grade back to the LMS.
        $debug_log = array();
        $retval = LTIX::gradeSend($gradetosend, false, $debug_log);
        $_SESSION['debug_log'] = $debug_log;

        if ( $retval === true ) {
            $_SESSION['success'] = "Grade $gradetosend sent to server.";
        } else if ( is_string($retval) ) {
            $_SESSION['error'] = "Grade not sent: ".$retval;
        } else {
            echo("<pre>\n");
            var_dump($retval);
            echo("</pre>\n");
            die();
        }
    }

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

// Start of the output
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
echo("<h1>Grade Test Harness</h1>\n");
$OUTPUT->welcomeUserCourse();

?>
<form method="post">
Enter grade:
<input type="number" name="grade" step="0.01" min="0" max="1.0"><br/>
<input type="submit" name="send" value="Send grade">
</form>
<?php

if ( isset($_SESSION['debug_log']) ) {
    echo("<p>Debug output from grade send:</p>\n");
    $OUTPUT->dumpDebugArray($_SESSION['debug_log']);
    unset($_SESSION['debug_log']);
}

echo("<pre>Global Tsugi Objects:\n\n");
var_dump($USER);
var_dump($CONTEXT);
var_dump($LINK);

echo("\n<hr/>\n");
echo("Session data (low level):\n");
echo(safe_var_dump($_SESSION));

$OUTPUT->footer();

