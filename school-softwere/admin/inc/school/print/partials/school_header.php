<?php
/**
 * Print partial - School header.
 *
 * Expects: $school (object) and optionally $title.
 */
defined( 'ABSPATH' ) || die();
$school = isset( $school ) ? $school : null;
$title  = isset( $title ) ? $title : '';
?>
<div class="ss-print-header">
    <?php if ( ! empty( $school->logo ) ) : ?>
        <img src="<?php echo esc_url( $school->logo ); ?>" alt="">
    <?php else : ?>
        <div style="width:64px;height:64px;border-radius:12px;background:linear-gradient(135deg,#4F46E5,#0EA5E9);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:24px;font-family:Nunito,sans-serif;">SS</div>
    <?php endif; ?>
    <div>
        <h2 class="ss-print-school-name"><?php echo esc_html( $school ? $school->label : SS_Config::get( 'school_name' ) ); ?></h2>
        <p class="ss-print-school-meta">
            <?php if ( $school ) : ?>
                <?php echo esc_html( trim( $school->address . ' | ' . $school->phone . ' | ' . $school->email, ' |' ) ); ?>
            <?php endif; ?>
        </p>
    </div>
</div>
<?php if ( $title ) : ?>
    <div class="ss-print-title"><?php echo esc_html( $title ); ?></div>
<?php endif; ?>
<?php
