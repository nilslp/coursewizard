<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/tracker
}


// Date formatting - can be customized if necessary
$certificatedate = certificate_get_date($certificate, $certrecord, $course);
//Grade formatting
$grade =  certificate_get_grade($certificate, $course);
//Print the outcome
$outcome = certificate_get_outcome($certificate, $course);

// Print the code number
$code = '';
if ($certificate->printnumber) {
    $code = $certrecord->code;
}

//Print the student name
$studentname = fullname($USER);

//Print the credit hours
if ($certificate->printhours) {
    $credithours = get_string('credithours','certificate') . ': ' . $certificate->printhours;
} else {
    $credithours = '';
}
$customtext = $certificate->customtext;
$orientation = $certificate->orientation;
$pdf = new TCPDF($orientation, 'mm', 'A4', true, 'UTF-8', false);
// $pdf->SetProtection(array('print'));
$pdf->SetTitle($certificate->name);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

//Define variables
//Landscape
if ($orientation == 'L') {
    $x = 45.5;
    $y = 50;
    $sealx = 10;
    $sealy = 10;
    $sealw = 100;
    $sealh = 38.3333;
    $sigx = 45.5;
    $sigy = 190;
    $custx = 45.5;
    $custy = 150;
    $wmarkx = 40;
    $wmarky = 31;
    $wmarkw = 212;
    $wmarkh = 148;
    $brdrx = 0;
    $brdry = 0;
    $brdrw = 297;
    $brdrh = 210;
    $codey = 175;
} else {
//Portrait
    $x = 45.5;
    $y = 70;
    $sealx = 10;
    $sealy = 10;
    $sealw = 100;
    $sealh = 38.3333;
    $sigx = 45.5;
    $sigy = 277;
    $custx = 45.5;
    $custy = 170;
    $wmarkx = 26;
    $wmarky = 58;
    $wmarkw = 158;
    $wmarkh = 170;
    $brdrx = 0;
    $brdry = 0;
    $brdrw = 210;
    $brdrh = 297;
    $codey = 250;
}

// Add images and lines
certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
certificate_draw_frame($pdf, $certificate);

// Set alpha to semi-transparency
$pdf->SetAlpha(0.2);
certificate_print_image($pdf, $certificate, CERT_IMAGE_WATERMARK, $wmarkx, $wmarky, $wmarkw, $wmarkh);
$pdf->SetAlpha(1);

certificate_print_image($pdf, $certificate, CERT_IMAGE_SEAL, $sealx, $sealy, $sealw, $sealh);
certificate_print_image($pdf, $certificate, CERT_IMAGE_SIGNATURE, $sigx, $sigy, '', '');

// Add text
$certificatetitle = (!empty($certificate->txttitle)) ? $certificate->txttitle : get_string('titledefault', 'certificate');
$classname = (!empty($certificate->txtcoursename)) ? $certificate->txtcoursename : $course->fullname;
$txtcertify = (!empty($certificate->txtcertify)) ?   $certificate->txtcertify : get_string('certify', 'certificate');
$txthascompleted = (!empty($certificate->txthascompleted)) ?  $certificate->txthascompleted : get_string('statement', 'certificate');

$pdf->SetTextColor(0, 0, 0);
certificate_print_text($pdf, $x, $y, 'L', 'Helvetica', 'B', 24, $certificatetitle);
certificate_print_text($pdf, $x, $y + 20, 'L', 'Helvetica', '', 13, $txtcertify);
certificate_print_text($pdf, $x, $y + 28, 'L', 'Helvetica', 'B', 20, $studentname);
certificate_print_text($pdf, $x, $y + 45, 'L', 'Helvetica', '', 13, $txthascompleted);
certificate_print_text($pdf, $x, $y + 53, 'L', 'Helvetica', 'B', 20, $classname);

if( !empty($certificatedate) ){
    certificate_print_text($pdf, $x, $y + 70, 'L', 'Helvetica', '', 13, get_string('onthisdate', 'certificate'));
    certificate_print_text($pdf, $x, $y + 78, 'L', 'Helvetica', 'B', 16, $certificatedate);
}

certificate_print_text($pdf, $x, $y + 97, 'L', 'Helvetica', '', 12, $grade);
certificate_print_text($pdf, $x, $y + 102, 'L', 'Helvetica', '', 12, $credithours);
certificate_print_text($pdf, $x, $y + 117, 'L', 'Helvetica', '', 12, $outcome);
certificate_print_text($pdf, $x, $codey, 'L', 'Helvetica', '', 12, $code);
$i = 0;
if ($certificate->printteacher) {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    if ($teachers = get_users_by_capability($context, 'mod/certificate:printteacher', '', $sort = 'u.lastname ASC', '', '', '', '', false)) {
        foreach ($teachers as $teacher) {
            $i++;
            certificate_print_text($pdf, $sigx, ($sigy + 10) + ($i * 53), 'L', 'Helvetica', '', 12, fullname($teacher));
        }
    }
}

certificate_print_text($pdf, $custx, $custy, 'L', '', '', 12, $customtext);

