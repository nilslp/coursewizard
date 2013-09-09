<?php

require('../../../config.php');
require($CFG->dirroot.'/course/lib.php');

$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_url("$CFG->httpswwwroot/404");
$PAGE->set_context($context);
$PAGE->set_pagelayout('server-error');

$site = get_site();
#$PAGE->navbar->add("Content Not Found");
$PAGE->set_title("$site->fullname: Content Not Found");
$PAGE->set_heading("$site->fullname");

echo $OUTPUT->header();
?>

<h2 class="main">Oops!</h2>

<div class="copy">
    <p>We&rsquo;re sorry, but the page you&rsquo;re looking for can&rsquo;t be found, probably because it&rsquo;s been moved or it doesn&rsquo;t exist anymore.</p>
    <p>Click the home button to start again or if you&rsquo;re stuck try our <a href="http://www.learningpool.com/support">support page</a> for some helpful advice. </p>
</div>

<?php

print_course_search();

echo $OUTPUT->footer();
