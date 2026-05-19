<?php
/**
 * Print: Certificate.
 */
defined( 'ABSPATH' ) || die();
require_once __DIR__ . '/_print_shell.php';

global $wpdb;
$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
$cs = $wpdb->get_row( $wpdb->prepare(
    'SELECT cs.*, c.label cert_label, c.content_template, sr.first_name, sr.last_name, sr.admission_number, sr.school_id, cl.label class_label
     FROM ' . SS_Helper::table( 'certificate_student' ) . ' cs
     LEFT JOIN ' . SS_Helper::table( 'certificates' ) . ' c ON c.ID = cs.certificate_id
     LEFT JOIN ' . SS_Helper::table( 'student_records' ) . ' sr ON sr.ID = cs.student_record_id
     LEFT JOIN ' . SS_Helper::table( 'class_school' ) . ' csch ON csch.ID = sr.class_school_id
     LEFT JOIN ' . SS_Helper::table( 'classes' ) . ' cl ON cl.ID = csch.class_id
     WHERE cs.ID = %d', $id
) );
if ( ! $cs ) { wp_die( esc_html__( 'Certificate not found', 'school-softwere' ) ); }
$school = ss_print_school( $cs->school_id );
$body   = (string) $cs->content_template;
$body   = str_replace(
    array( '{student_name}', '{class}', '{admission_number}', '{date}', '{school_name}' ),
    array( trim( $cs->first_name . ' ' . $cs->last_name ), (string) $cs->class_label, (string) $cs->admission_number, SS_Helper::format_date( $cs->issued_date ), $school ? $school->label : '' ),
    $body
);
ss_print_open( __( 'Certificate', 'school-softwere' ) );
$title = $cs->cert_label;
include __DIR__ . '/partials/school_header.php';
?>
<div style="border:8px double #4F46E5; padding:40px; margin-top:24px; min-height:400px; text-align:center; font-family:Nunito,serif;">
    <h2 style="font-size:32px; color:#1E1B4B; margin-top:0;">Certificate of Achievement</h2>
    <p style="font-size:16px; line-height:1.7; color:#1E293B; max-width:600px; margin:24px auto;">
        <?php echo wp_kses_post( $body ?: 'This is to certify that ' . esc_html( trim( $cs->first_name . ' ' . $cs->last_name ) ) . ' of ' . esc_html( $cs->class_label ) . ' has successfully demonstrated outstanding performance.' ); ?>
    </p>
    <p style="margin-top:36px; font-size:13px; color:#64748B;">Issued: <?php echo esc_html( SS_Helper::format_date( $cs->issued_date ) ); ?></p>
    <div style="display:flex; justify-content:space-around; margin-top:60px;">
        <div style="border-top:1px solid #1E1B4B; width:180px; padding-top:8px; font-size:12px;">Class Teacher</div>
        <div style="border-top:1px solid #1E1B4B; width:180px; padding-top:8px; font-size:12px;">Principal</div>
    </div>
</div>
<?php ss_print_close();
