<?php
/**
 * MAKE SCHOOL - Public Frontend Class
 *
 * Handles all frontend shortcodes, AJAX handlers, and portal rendering
 * for students, parents, and teachers.
 *
 * @package    Make_School
 * @subpackage Make_School/public
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Make_School_Public {

    /**
     * Constructor - Register shortcodes and AJAX handlers
     */
    public function __construct() {
        // Register shortcodes
        add_shortcode( 'make_school_admission_form', array( $this, 'render_admission_form' ) );
        add_shortcode( 'make_school_login_form', array( $this, 'render_login_form' ) );
        add_shortcode( 'make_school_student_portal', array( $this, 'render_student_portal' ) );
        add_shortcode( 'make_school_teacher_portal', array( $this, 'render_teacher_portal' ) );

        // AJAX handlers - Admission form (logged in and not logged in)
        add_action( 'wp_ajax_make_school_submit_admission', array( $this, 'ajax_submit_admission' ) );
        add_action( 'wp_ajax_nopriv_make_school_submit_admission', array( $this, 'ajax_submit_admission' ) );

        // AJAX handlers - Teacher: Get students by class
        add_action( 'wp_ajax_make_school_get_students', array( $this, 'ajax_get_students_by_class' ) );

        // AJAX handlers - Teacher: Save attendance
        add_action( 'wp_ajax_make_school_save_attendance', array( $this, 'ajax_save_attendance' ) );

        // AJAX handlers - Teacher: Save marks
        add_action( 'wp_ajax_make_school_save_marks', array( $this, 'ajax_save_marks' ) );

        // AJAX handlers - Teacher: Get exams by class
        add_action( 'wp_ajax_make_school_get_exams', array( $this, 'ajax_get_exams_by_class' ) );
    }


    // ═══════════════════════════════════════════════════════════════
    // SHORTCODE: ADMISSION FORM
    // ═══════════════════════════════════════════════════════════════

    /**
     * Render the public admission application form
     *
     * @return string HTML output
     */
    public function render_admission_form() {
        global $wpdb;

        $classes_table = Make_School_DB::get_table( 'classes' );
        $classes = $wpdb->get_results(
            "SELECT id, class_name, section_name FROM {$classes_table} WHERE status = 'active' ORDER BY class_name ASC"
        );

        ob_start();
        ?>
        <div class="ms-admission-form-wrapper" style="max-width:700px; margin:0 auto; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
            <div class="ms-form-header" style="background:linear-gradient(135deg,#0073aa,#005177); color:#fff; padding:30px; border-radius:8px 8px 0 0; text-align:center;">
                <h2 style="margin:0; font-size:24px;"><?php esc_html_e( 'Student Admission Application', 'make-school' ); ?></h2>
                <p style="margin:10px 0 0; opacity:0.9;"><?php esc_html_e( 'Fill out the form below to apply for admission.', 'make-school' ); ?></p>
            </div>

            <div id="ms-admission-message" style="display:none; padding:15px; margin:0; border-radius:0;"></div>

            <form id="ms-admission-form" style="background:#fff; padding:30px; border:1px solid #ddd; border-top:none; border-radius:0 0 8px 8px;">

                <!-- Personal Information Section -->
                <h3 style="border-bottom:2px solid #0073aa; padding-bottom:8px; color:#333;"><?php esc_html_e( 'Personal Information', 'make-school' ); ?></h3>

                <div style="margin-bottom:15px;">
                    <label for="ms_full_name" style="display:block; font-weight:600; margin-bottom:5px; color:#333;">
                        <?php esc_html_e( 'Full Name *', 'make-school' ); ?>
                    </label>
                    <input type="text" id="ms_full_name" name="full_name" required
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px;"
                           placeholder="<?php esc_attr_e( 'Enter student full name', 'make-school' ); ?>">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label for="ms_dob" style="display:block; font-weight:600; margin-bottom:5px; color:#333;">
                            <?php esc_html_e( 'Date of Birth *', 'make-school' ); ?>
                        </label>
                        <input type="date" id="ms_dob" name="dob" required
                               style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px;">
                    </div>
                    <div>
                        <label for="ms_gender" style="display:block; font-weight:600; margin-bottom:5px; color:#333;">
                            <?php esc_html_e( 'Gender *', 'make-school' ); ?>
                        </label>
                        <select id="ms_gender" name="gender" required
                                style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px;">
                            <option value=""><?php esc_html_e( '— Select —', 'make-school' ); ?></option>
                            <option value="male"><?php esc_html_e( 'Male', 'make-school' ); ?></option>
                            <option value="female"><?php esc_html_e( 'Female', 'make-school' ); ?></option>
                            <option value="other"><?php esc_html_e( 'Other', 'make-school' ); ?></option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:15px;">
                    <label for="ms_blood_group" style="display:block; font-weight:600; margin-bottom:5px; color:#333;">
                        <?php esc_html_e( 'Blood Group', 'make-school' ); ?>
                    </label>
                    <select id="ms_blood_group" name="blood_group"
                            style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px;">
                        <option value=""><?php esc_html_e( '— Select —', 'make-school' ); ?></option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>


                <!-- Parent/Guardian Information -->
                <h3 style="border-bottom:2px solid #0073aa; padding-bottom:8px; color:#333; margin-top:25px;">
                    <?php esc_html_e( 'Parent / Guardian Information', 'make-school' ); ?>
                </h3>

                <div style="margin-bottom:15px;">
                    <label for="ms_parent_name" style="display:block; font-weight:600; margin-bottom:5px; color:#333;">
                        <?php esc_html_e( 'Parent/Guardian Name *', 'make-school' ); ?>
                    </label>
                    <input type="text" id="ms_parent_name" name="parent_name" required
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px;"
                           placeholder="<?php esc_attr_e( 'Enter parent or guardian name', 'make-school' ); ?>">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label for="ms_email" style="display:block; font-weight:600; margin-bottom:5px; color:#333;">
                            <?php esc_html_e( 'Email Address *', 'make-school' ); ?>
                        </label>
                        <input type="email" id="ms_email" name="email" required
                               style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px;"
                               placeholder="<?php esc_attr_e( 'parent@email.com', 'make-school' ); ?>">
                    </div>
                    <div>
                        <label for="ms_phone" style="display:block; font-weight:600; margin-bottom:5px; color:#333;">
                            <?php esc_html_e( 'Phone Number *', 'make-school' ); ?>
                        </label>
                        <input type="tel" id="ms_phone" name="phone" required
                               style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px;"
                               placeholder="<?php esc_attr_e( '+1-234-567-8900', 'make-school' ); ?>">
                    </div>
                </div>

                <div style="margin-bottom:15px;">
                    <label for="ms_address" style="display:block; font-weight:600; margin-bottom:5px; color:#333;">
                        <?php esc_html_e( 'Residential Address *', 'make-school' ); ?>
                    </label>
                    <textarea id="ms_address" name="address" required rows="3"
                              style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px; resize:vertical;"
                              placeholder="<?php esc_attr_e( 'Enter full residential address', 'make-school' ); ?>"></textarea>
                </div>

                <!-- Class Selection -->
                <h3 style="border-bottom:2px solid #0073aa; padding-bottom:8px; color:#333; margin-top:25px;">
                    <?php esc_html_e( 'Class Selection', 'make-school' ); ?>
                </h3>

                <div style="margin-bottom:20px;">
                    <label for="ms_class_id" style="display:block; font-weight:600; margin-bottom:5px; color:#333;">
                        <?php esc_html_e( 'Applying for Class *', 'make-school' ); ?>
                    </label>
                    <select id="ms_class_id" name="class_id" required
                            style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px;">
                        <option value=""><?php esc_html_e( '— Select Class —', 'make-school' ); ?></option>
                        <?php foreach ( $classes as $class ) : ?>
                            <option value="<?php echo esc_attr( $class->id ); ?>">
                                <?php echo esc_html( $class->class_name . ' - Section ' . $class->section_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php wp_nonce_field( 'make_school_admission_nonce', 'ms_admission_nonce' ); ?>

                <button type="submit" id="ms-admission-submit"
                        style="width:100%; padding:14px; background:linear-gradient(135deg,#0073aa,#005177); color:#fff; border:none; border-radius:4px; font-size:16px; font-weight:600; cursor:pointer; transition:opacity 0.3s;">
                    <?php esc_html_e( 'Submit Application', 'make-school' ); ?>
                </button>
            </form>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#ms-admission-form').on('submit', function(e) {
                e.preventDefault();

                var $form = $(this);
                var $btn = $('#ms-admission-submit');
                var $msg = $('#ms-admission-message');

                $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Submitting...', 'make-school' ) ); ?>');
                $msg.hide();

                var formData = {
                    action: 'make_school_submit_admission',
                    ms_admission_nonce: $('#ms_admission_nonce').val(),
                    full_name: $('#ms_full_name').val(),
                    dob: $('#ms_dob').val(),
                    gender: $('#ms_gender').val(),
                    blood_group: $('#ms_blood_group').val(),
                    parent_name: $('#ms_parent_name').val(),
                    email: $('#ms_email').val(),
                    phone: $('#ms_phone').val(),
                    address: $('#ms_address').val(),
                    class_id: $('#ms_class_id').val()
                };

                $.ajax({
                    url: MakeSchoolAjax.ajax_url,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $msg.css({background:'#d4edda', color:'#155724', border:'1px solid #c3e6cb', display:'block', padding:'15px'})
                                .html('<strong><?php echo esc_js( __( 'Success!', 'make-school' ) ); ?></strong> ' + response.data.message);
                            $form[0].reset();
                        } else {
                            $msg.css({background:'#f8d7da', color:'#721c24', border:'1px solid #f5c6cb', display:'block', padding:'15px'})
                                .html('<strong><?php echo esc_js( __( 'Error:', 'make-school' ) ); ?></strong> ' + response.data.message);
                        }
                        $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Submit Application', 'make-school' ) ); ?>');
                    },
                    error: function() {
                        $msg.css({background:'#f8d7da', color:'#721c24', border:'1px solid #f5c6cb', display:'block', padding:'15px'})
                            .html('<strong><?php echo esc_js( __( 'Error:', 'make-school' ) ); ?></strong> <?php echo esc_js( __( 'A network error occurred. Please try again.', 'make-school' ) ); ?>');
                        $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Submit Application', 'make-school' ) ); ?>');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }


    /**
     * AJAX Callback: Submit admission form data
     */
    public function ajax_submit_admission() {
        // Verify nonce
        if ( ! isset( $_POST['ms_admission_nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ms_admission_nonce'] ) ), 'make_school_admission_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security verification failed. Please refresh and try again.', 'make-school' ) ) );
        }

        // Sanitize all inputs
        $full_name   = sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) );
        $dob         = sanitize_text_field( wp_unslash( $_POST['dob'] ?? '' ) );
        $gender      = sanitize_text_field( wp_unslash( $_POST['gender'] ?? '' ) );
        $blood_group = sanitize_text_field( wp_unslash( $_POST['blood_group'] ?? '' ) );
        $parent_name = sanitize_text_field( wp_unslash( $_POST['parent_name'] ?? '' ) );
        $email       = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $phone       = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
        $address     = sanitize_textarea_field( wp_unslash( $_POST['address'] ?? '' ) );
        $class_id    = intval( $_POST['class_id'] ?? 0 );

        // Validate required fields
        if ( empty( $full_name ) || empty( $dob ) || empty( $gender ) || empty( $parent_name ) ||
             empty( $email ) || empty( $phone ) || empty( $address ) || $class_id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'make-school' ) ) );
        }

        // Validate email format
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'make-school' ) ) );
        }

        // Validate date format
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dob ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a valid date of birth.', 'make-school' ) ) );
        }

        // Validate gender
        if ( ! in_array( $gender, array( 'male', 'female', 'other' ), true ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select a valid gender.', 'make-school' ) ) );
        }

        global $wpdb;
        $table = Make_School_DB::get_table( 'admissions' );

        // Check for duplicate applications
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE email = %s AND class_id = %d AND status = 'pending'",
                $email, $class_id
            )
        );

        if ( $existing > 0 ) {
            wp_send_json_error( array( 'message' => __( 'An application with this email for the selected class is already pending.', 'make-school' ) ) );
        }

        // Insert admission record
        $result = $wpdb->insert(
            $table,
            array(
                'full_name'   => $full_name,
                'dob'         => $dob,
                'gender'      => $gender,
                'blood_group' => $blood_group,
                'parent_name' => $parent_name,
                'email'       => $email,
                'phone'       => $phone,
                'address'     => $address,
                'class_id'    => $class_id,
                'status'      => 'pending',
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
        );

        if ( false !== $result ) {
            wp_send_json_success( array(
                'message' => __( 'Your admission application has been submitted successfully! You will be notified once reviewed.', 'make-school' ),
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to submit application. Please try again later.', 'make-school' ) ) );
        }
    }


    // ═══════════════════════════════════════════════════════════════
    // SHORTCODE: LOGIN FORM
    // ═══════════════════════════════════════════════════════════════

    /**
     * Render a premium responsive login form
     *
     * @return string HTML output
     */
    public function render_login_form() {
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            ob_start();
            ?>
            <div style="max-width:500px; margin:0 auto; text-align:center; padding:40px; background:#fff; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1);">
                <p style="font-size:18px; color:#333;">
                    <?php printf( esc_html__( 'Welcome back, %s!', 'make-school' ), '<strong>' . esc_html( $user->display_name ) . '</strong>' ); ?>
                </p>
                <?php if ( in_array( 'make_school_student', (array) $user->roles, true ) || in_array( 'make_school_parent', (array) $user->roles, true ) ) : ?>
                    <a href="<?php echo esc_url( home_url( '/student-portal/' ) ); ?>" style="display:inline-block; padding:12px 24px; background:#0073aa; color:#fff; text-decoration:none; border-radius:4px; margin:10px 5px;">
                        <?php esc_html_e( 'Go to Student Portal', 'make-school' ); ?>
                    </a>
                <?php endif; ?>
                <?php if ( in_array( 'make_school_teacher', (array) $user->roles, true ) ) : ?>
                    <a href="<?php echo esc_url( home_url( '/teacher-portal/' ) ); ?>" style="display:inline-block; padding:12px 24px; background:#00a32a; color:#fff; text-decoration:none; border-radius:4px; margin:10px 5px;">
                        <?php esc_html_e( 'Go to Teacher Portal', 'make-school' ); ?>
                    </a>
                <?php endif; ?>
                <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" style="display:inline-block; padding:12px 24px; background:#d63638; color:#fff; text-decoration:none; border-radius:4px; margin:10px 5px;">
                    <?php esc_html_e( 'Logout', 'make-school' ); ?>
                </a>
            </div>
            <?php
            return ob_get_clean();
        }

        ob_start();
        ?>
        <div style="max-width:420px; margin:0 auto; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
            <div style="background:linear-gradient(135deg,#0073aa,#005177); color:#fff; padding:30px; border-radius:8px 8px 0 0; text-align:center;">
                <h2 style="margin:0; font-size:22px;"><?php esc_html_e( 'School Portal Login', 'make-school' ); ?></h2>
                <p style="margin:8px 0 0; opacity:0.9; font-size:14px;"><?php esc_html_e( 'Access your dashboard securely', 'make-school' ); ?></p>
            </div>
            <div style="background:#fff; padding:30px; border:1px solid #ddd; border-top:none; border-radius:0 0 8px 8px;">
                <?php
                wp_login_form( array(
                    'redirect'       => home_url(),
                    'form_id'        => 'ms-login-form',
                    'label_username' => __( 'Username or Email', 'make-school' ),
                    'label_password' => __( 'Password', 'make-school' ),
                    'label_remember' => __( 'Keep me signed in', 'make-school' ),
                    'label_log_in'   => __( 'Sign In', 'make-school' ),
                ) );
                ?>
                <p style="text-align:center; margin-top:15px;">
                    <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" style="color:#0073aa; text-decoration:none; font-size:13px;">
                        <?php esc_html_e( 'Forgot your password?', 'make-school' ); ?>
                    </a>
                </p>
            </div>
        </div>
        <style>
            #ms-login-form { margin: 0; }
            #ms-login-form p { margin-bottom: 15px; }
            #ms-login-form label { display: block; font-weight: 600; margin-bottom: 5px; color: #333; font-size: 14px; }
            #ms-login-form input[type="text"],
            #ms-login-form input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
            #ms-login-form input[type="submit"] { width: 100%; padding: 12px; background: linear-gradient(135deg,#0073aa,#005177); color: #fff; border: none; border-radius: 4px; font-size: 16px; font-weight: 600; cursor: pointer; }
            #ms-login-form input[type="submit"]:hover { opacity: 0.9; }
        </style>
        <?php
        return ob_get_clean();
    }


    // ═══════════════════════════════════════════════════════════════
    // SHORTCODE: STUDENT PORTAL
    // ═══════════════════════════════════════════════════════════════

    /**
     * Render the student portal dashboard
     *
     * @return string HTML output
     */
    public function render_student_portal() {
        if ( ! is_user_logged_in() ) {
            return '<div style="text-align:center; padding:40px;"><p>' . esc_html__( 'Please log in to access the Student Portal.', 'make-school' ) . '</p><a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" style="display:inline-block; padding:12px 24px; background:#0073aa; color:#fff; text-decoration:none; border-radius:4px;">' . esc_html__( 'Login', 'make-school' ) . '</a></div>';
        }

        $user = wp_get_current_user();
        $allowed_roles = array( 'make_school_student', 'make_school_parent', 'administrator' );
        $has_access = false;
        foreach ( $allowed_roles as $role ) {
            if ( in_array( $role, (array) $user->roles, true ) ) {
                $has_access = true;
                break;
            }
        }

        if ( ! $has_access ) {
            return '<div style="text-align:center; padding:40px; color:#d63638;"><p>' . esc_html__( 'Access denied. This portal is for students and parents only.', 'make-school' ) . '</p></div>';
        }

        global $wpdb;

        $student_id = $user->ID;
        $class_id   = get_user_meta( $student_id, 'make_school_class_id', true );

        // Get class info
        $classes_table = Make_School_DB::get_table( 'classes' );
        $class_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$classes_table} WHERE id = %d", intval( $class_id ) ) );

        // Get invoices
        $invoices_table = Make_School_DB::get_table( 'invoices' );
        $invoices = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$invoices_table} WHERE student_id = %d ORDER BY due_date DESC", $student_id )
        );

        // Get attendance records
        $attendance_table = Make_School_DB::get_table( 'attendance' );
        $attendance = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$attendance_table} WHERE student_id = %d ORDER BY date DESC", $student_id )
        );

        // Calculate attendance statistics
        $total_days = count( $attendance );
        $present_days = 0;
        $absent_days = 0;
        $late_days = 0;
        foreach ( $attendance as $record ) {
            if ( $record->status === 'present' ) $present_days++;
            elseif ( $record->status === 'absent' ) $absent_days++;
            elseif ( $record->status === 'late' ) $late_days++;
        }
        $attendance_percentage = $total_days > 0 ? round( ( $present_days / $total_days ) * 100, 1 ) : 0;

        // Get marks/results
        $marks_table = Make_School_DB::get_table( 'marks' );
        $exams_table = Make_School_DB::get_table( 'exams' );
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.*, e.exam_name, e.subject_name, e.max_marks AS exam_max_marks, e.pass_marks
                 FROM {$marks_table} m
                 LEFT JOIN {$exams_table} e ON m.exam_id = e.id
                 WHERE m.student_id = %d
                 ORDER BY e.exam_date DESC",
                $student_id
            )
        );

        ob_start();
        ?>
        <div class="ms-student-portal" style="max-width:1000px; margin:0 auto; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">

            <!-- Portal Header -->
            <div style="background:linear-gradient(135deg,#0073aa,#005177); color:#fff; padding:25px 30px; border-radius:8px; margin-bottom:25px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
                <div>
                    <h2 style="margin:0; font-size:22px;"><?php printf( esc_html__( 'Welcome, %s', 'make-school' ), esc_html( $user->display_name ) ); ?></h2>
                    <p style="margin:5px 0 0; opacity:0.9;">
                        <?php
                        if ( $class_info ) {
                            printf( esc_html__( 'Class: %s | Section: %s', 'make-school' ), esc_html( $class_info->class_name ), esc_html( $class_info->section_name ) );
                        }
                        ?>
                    </p>
                </div>
                <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" style="padding:8px 16px; background:rgba(255,255,255,0.2); color:#fff; text-decoration:none; border-radius:4px; font-size:13px;">
                    <?php esc_html_e( 'Logout', 'make-school' ); ?>
                </a>
            </div>


            <!-- Fee Ledger Section -->
            <div style="background:#fff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.08); margin-bottom:25px; overflow:hidden;">
                <div style="padding:20px 25px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; color:#333;"><?php esc_html_e( 'Fee Ledger', 'make-school' ); ?></h3>
                </div>
                <div style="padding:20px 25px;">
                    <?php if ( empty( $invoices ) ) : ?>
                        <p style="color:#666;"><?php esc_html_e( 'No fee records found.', 'make-school' ); ?></p>
                    <?php else : ?>
                        <div style="overflow-x:auto;">
                            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                                <thead>
                                    <tr style="background:#f8f9fa;">
                                        <th style="padding:10px; text-align:left; border-bottom:2px solid #dee2e6;"><?php esc_html_e( 'Tracking ID', 'make-school' ); ?></th>
                                        <th style="padding:10px; text-align:left; border-bottom:2px solid #dee2e6;"><?php esc_html_e( 'Fee Type', 'make-school' ); ?></th>
                                        <th style="padding:10px; text-align:right; border-bottom:2px solid #dee2e6;"><?php esc_html_e( 'Amount', 'make-school' ); ?></th>
                                        <th style="padding:10px; text-align:center; border-bottom:2px solid #dee2e6;"><?php esc_html_e( 'Due Date', 'make-school' ); ?></th>
                                        <th style="padding:10px; text-align:center; border-bottom:2px solid #dee2e6;"><?php esc_html_e( 'Status', 'make-school' ); ?></th>
                                        <th style="padding:10px; text-align:center; border-bottom:2px solid #dee2e6;"><?php esc_html_e( 'Receipt', 'make-school' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $invoices as $inv ) : ?>
                                        <tr style="border-bottom:1px solid #eee;">
                                            <td style="padding:10px;"><code style="font-size:12px;"><?php echo esc_html( $inv->tracking_id ); ?></code></td>
                                            <td style="padding:10px;"><?php echo esc_html( $inv->fee_type ); ?></td>
                                            <td style="padding:10px; text-align:right; font-weight:600;"><?php echo esc_html( number_format( (float) $inv->amount, 2 ) ); ?></td>
                                            <td style="padding:10px; text-align:center;"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $inv->due_date ) ) ); ?></td>
                                            <td style="padding:10px; text-align:center;">
                                                <?php
                                                $s_colors = array( 'paid' => '#00a32a', 'unpaid' => '#d63638', 'partial' => '#dba617' );
                                                $s_color = $s_colors[ $inv->status ] ?? '#666';
                                                ?>
                                                <span style="background:<?php echo esc_attr( $s_color ); ?>; color:#fff; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600;">
                                                    <?php echo esc_html( ucfirst( $inv->status ) ); ?>
                                                </span>
                                            </td>
                                            <td style="padding:10px; text-align:center;">
                                                <?php if ( 'paid' === $inv->status ) : ?>
                                                    <button onclick="msPrintReceipt(<?php echo esc_attr( $inv->id ); ?>)" style="padding:5px 12px; background:#0073aa; color:#fff; border:none; border-radius:3px; cursor:pointer; font-size:12px;">
                                                        <?php esc_html_e( 'Print', 'make-school' ); ?>
                                                    </button>
                                                <?php else : ?>
                                                    <span style="color:#999; font-size:12px;">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Print Receipt Template (Hidden) -->
            <?php foreach ( $invoices as $inv ) : ?>
                <?php if ( 'paid' === $inv->status ) : ?>
                    <div id="ms-receipt-<?php echo esc_attr( $inv->id ); ?>" style="display:none;">
                        <div style="max-width:500px; margin:0 auto; padding:30px; font-family:serif; border:2px solid #333;">
                            <div style="text-align:center; border-bottom:2px solid #333; padding-bottom:15px; margin-bottom:15px;">
                                <h2 style="margin:0;"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h2>
                                <p style="margin:5px 0 0; font-size:14px;"><?php esc_html_e( 'Official Fee Receipt', 'make-school' ); ?></p>
                            </div>
                            <table style="width:100%; margin-bottom:15px; font-size:14px;">
                                <tr><td><strong><?php esc_html_e( 'Receipt No:', 'make-school' ); ?></strong></td><td><?php echo esc_html( $inv->tracking_id ); ?></td></tr>
                                <tr><td><strong><?php esc_html_e( 'Student:', 'make-school' ); ?></strong></td><td><?php echo esc_html( $user->display_name ); ?></td></tr>
                                <tr><td><strong><?php esc_html_e( 'Fee Type:', 'make-school' ); ?></strong></td><td><?php echo esc_html( $inv->fee_type ); ?></td></tr>
                                <tr><td><strong><?php esc_html_e( 'Amount Paid:', 'make-school' ); ?></strong></td><td><strong><?php echo esc_html( number_format( (float) $inv->amount, 2 ) ); ?></strong></td></tr>
                                <tr><td><strong><?php esc_html_e( 'Payment Date:', 'make-school' ); ?></strong></td><td><?php echo esc_html( $inv->paid_date ? date_i18n( 'M j, Y', strtotime( $inv->paid_date ) ) : '—' ); ?></td></tr>
                            </table>
                            <div style="text-align:center; border-top:1px dashed #999; padding-top:10px; font-size:12px; color:#666;">
                                <p><?php esc_html_e( 'This is a computer-generated receipt. No signature required.', 'make-school' ); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>


            <!-- Attendance Log Section -->
            <div style="background:#fff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.08); margin-bottom:25px; overflow:hidden;">
                <div style="padding:20px 25px; border-bottom:1px solid #eee;">
                    <h3 style="margin:0; color:#333;"><?php esc_html_e( 'Attendance Record', 'make-school' ); ?></h3>
                </div>
                <div style="padding:20px 25px;">
                    <!-- Attendance Summary Cards -->
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:12px; margin-bottom:20px;">
                        <div style="background:#e8f5e9; padding:15px; border-radius:6px; text-align:center;">
                            <div style="font-size:24px; font-weight:700; color:#2e7d32;"><?php echo esc_html( $present_days ); ?></div>
                            <div style="font-size:12px; color:#666; margin-top:3px;"><?php esc_html_e( 'Present', 'make-school' ); ?></div>
                        </div>
                        <div style="background:#ffebee; padding:15px; border-radius:6px; text-align:center;">
                            <div style="font-size:24px; font-weight:700; color:#c62828;"><?php echo esc_html( $absent_days ); ?></div>
                            <div style="font-size:12px; color:#666; margin-top:3px;"><?php esc_html_e( 'Absent', 'make-school' ); ?></div>
                        </div>
                        <div style="background:#fff3e0; padding:15px; border-radius:6px; text-align:center;">
                            <div style="font-size:24px; font-weight:700; color:#e65100;"><?php echo esc_html( $late_days ); ?></div>
                            <div style="font-size:12px; color:#666; margin-top:3px;"><?php esc_html_e( 'Late', 'make-school' ); ?></div>
                        </div>
                        <div style="background:#e3f2fd; padding:15px; border-radius:6px; text-align:center;">
                            <div style="font-size:24px; font-weight:700; color:#1565c0;"><?php echo esc_html( $attendance_percentage ); ?>%</div>
                            <div style="font-size:12px; color:#666; margin-top:3px;"><?php esc_html_e( 'Attendance %', 'make-school' ); ?></div>
                        </div>
                    </div>

                    <!-- Attendance Progress Bar -->
                    <div style="background:#eee; border-radius:10px; height:20px; overflow:hidden; margin-bottom:20px;">
                        <div style="background:linear-gradient(90deg,#00a32a,#4caf50); height:100%; width:<?php echo esc_attr( $attendance_percentage ); ?>%; border-radius:10px; transition:width 0.5s;"></div>
                    </div>

                    <!-- Attendance History Table -->
                    <?php if ( ! empty( $attendance ) ) : ?>
                        <div style="overflow-x:auto; max-height:300px; overflow-y:auto;">
                            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                                <thead>
                                    <tr style="background:#f8f9fa; position:sticky; top:0;">
                                        <th style="padding:8px; text-align:left; border-bottom:2px solid #dee2e6;"><?php esc_html_e( 'Date', 'make-school' ); ?></th>
                                        <th style="padding:8px; text-align:center; border-bottom:2px solid #dee2e6;"><?php esc_html_e( 'Status', 'make-school' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( array_slice( $attendance, 0, 50 ) as $att ) : ?>
                                        <tr style="border-bottom:1px solid #eee;">
                                            <td style="padding:8px;"><?php echo esc_html( date_i18n( 'D, M j, Y', strtotime( $att->date ) ) ); ?></td>
                                            <td style="padding:8px; text-align:center;">
                                                <?php
                                                $att_colors = array( 'present' => '#00a32a', 'absent' => '#d63638', 'late' => '#dba617' );
                                                $att_color = $att_colors[ $att->status ] ?? '#666';
                                                ?>
                                                <span style="background:<?php echo esc_attr( $att_color ); ?>; color:#fff; padding:2px 10px; border-radius:10px; font-size:11px;">
                                                    <?php echo esc_html( ucfirst( $att->status ) ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p style="color:#666;"><?php esc_html_e( 'No attendance records found.', 'make-school' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>


            <!-- Report Cards / Exam Results Section -->
            <div style="background:#fff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.08); margin-bottom:25px; overflow:hidden;">
                <div style="padding:20px 25px; border-bottom:1px solid #eee;">
                    <h3 style="margin:0; color:#333;"><?php esc_html_e( 'Report Card — Exam Results', 'make-school' ); ?></h3>
                </div>
                <div style="padding:20px 25px;">
                    <?php if ( empty( $results ) ) : ?>
                        <p style="color:#666;"><?php esc_html_e( 'No exam results available yet.', 'make-school' ); ?></p>
                    <?php else : ?>
                        <?php
                        // Calculate overall stats
                        $total_obtained = 0;
                        $total_max = 0;
                        foreach ( $results as $r ) {
                            $total_obtained += (float) $r->marks_obtained;
                            $total_max += (int) $r->exam_max_marks;
                        }
                        $overall_percentage = $total_max > 0 ? round( ( $total_obtained / $total_max ) * 100, 1 ) : 0;
                        $overall_grade = $this->calculate_grade( $overall_percentage );
                        ?>

                        <!-- Overall Summary -->
                        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:12px; margin-bottom:20px;">
                            <div style="background:#e8f5e9; padding:15px; border-radius:6px; text-align:center;">
                                <div style="font-size:20px; font-weight:700; color:#2e7d32;"><?php echo esc_html( $total_obtained . '/' . $total_max ); ?></div>
                                <div style="font-size:12px; color:#666; margin-top:3px;"><?php esc_html_e( 'Total Marks', 'make-school' ); ?></div>
                            </div>
                            <div style="background:#e3f2fd; padding:15px; border-radius:6px; text-align:center;">
                                <div style="font-size:20px; font-weight:700; color:#1565c0;"><?php echo esc_html( $overall_percentage ); ?>%</div>
                                <div style="font-size:12px; color:#666; margin-top:3px;"><?php esc_html_e( 'Percentage', 'make-school' ); ?></div>
                            </div>
                            <div style="background:#f3e5f5; padding:15px; border-radius:6px; text-align:center;">
                                <div style="font-size:20px; font-weight:700; color:#6a1b9a;"><?php echo esc_html( $overall_grade ); ?></div>
                                <div style="font-size:12px; color:#666; margin-top:3px;"><?php esc_html_e( 'Overall Grade', 'make-school' ); ?></div>
                            </div>
                        </div>

                        <!-- Subject-wise Results Table -->
                        <div style="overflow-x:auto;">
                            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                                <thead>
                                    <tr style="background:#f8f9fa;">
                                        <th style="padding:10px; text-align:left; border-bottom:2px solid #dee2e6;"><?php esc_html_e( 'Exam', 'make-school' ); ?></th>
                                        <th style="padding:10px; text-align:left; border-bottom:2px solid #dee2e6;"><?php esc_html_e( 'Subject', 'make-school' ); ?></th>
                                        <th style="padding:10px; text-align:center; border-bottom:2px solid #dee2e6;"><?php esc_html_e( 'Obtained', 'make-school' ); ?></th>
                                        <th style="padding:10px; text-align:center; border-bottom:2px solid #dee2e6;"><?php esc_html_e( 'Max', 'make-school' ); ?></th>
                                        <th style="padding:10px; text-align:center; border-bottom:2px solid #dee2e6;"><?php esc_html_e( '%', 'make-school' ); ?></th>
                                        <th style="padding:10px; text-align:center; border-bottom:2px solid #dee2e6;"><?php esc_html_e( 'Grade', 'make-school' ); ?></th>
                                        <th style="padding:10px; text-align:center; border-bottom:2px solid #dee2e6;"><?php esc_html_e( 'Result', 'make-school' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $results as $r ) :
                                        $subj_pct = $r->exam_max_marks > 0 ? round( ( (float) $r->marks_obtained / (int) $r->exam_max_marks ) * 100, 1 ) : 0;
                                        $subj_grade = $this->calculate_grade( $subj_pct );
                                        $passed = (float) $r->marks_obtained >= (int) $r->pass_marks;
                                    ?>
                                        <tr style="border-bottom:1px solid #eee;">
                                            <td style="padding:10px;"><?php echo esc_html( $r->exam_name ); ?></td>
                                            <td style="padding:10px; font-weight:600;"><?php echo esc_html( $r->subject_name ); ?></td>
                                            <td style="padding:10px; text-align:center;"><?php echo esc_html( $r->marks_obtained ); ?></td>
                                            <td style="padding:10px; text-align:center;"><?php echo esc_html( $r->exam_max_marks ); ?></td>
                                            <td style="padding:10px; text-align:center;"><?php echo esc_html( $subj_pct ); ?>%</td>
                                            <td style="padding:10px; text-align:center; font-weight:700;"><?php echo esc_html( $subj_grade ); ?></td>
                                            <td style="padding:10px; text-align:center;">
                                                <span style="background:<?php echo $passed ? '#00a32a' : '#d63638'; ?>; color:#fff; padding:2px 10px; border-radius:10px; font-size:11px;">
                                                    <?php echo $passed ? esc_html__( 'Pass', 'make-school' ) : esc_html__( 'Fail', 'make-school' ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.ms-student-portal -->

        <!-- Print Receipt JavaScript -->
        <script type="text/javascript">
        function msPrintReceipt(invoiceId) {
            var receiptHtml = document.getElementById('ms-receipt-' + invoiceId);
            if (!receiptHtml) return;

            var printWindow = window.open('', '_blank', 'width=600,height=500');
            printWindow.document.write('<html><head><title><?php echo esc_js( __( 'Fee Receipt', 'make-school' ) ); ?></title>');
            printWindow.document.write('<style>body{font-family:serif; margin:20px;} table{width:100%;} td{padding:5px 0;}</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(receiptHtml.innerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            setTimeout(function() { printWindow.print(); }, 300);
        }
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Calculate letter grade from percentage
     *
     * @param float $percentage The percentage score.
     * @return string The letter grade.
     */
    private function calculate_grade( $percentage ) {
        if ( $percentage >= 90 ) return 'A+';
        if ( $percentage >= 80 ) return 'A';
        if ( $percentage >= 70 ) return 'B+';
        if ( $percentage >= 60 ) return 'B';
        if ( $percentage >= 50 ) return 'C';
        if ( $percentage >= 40 ) return 'D';
        return 'F';
    }


    // ═══════════════════════════════════════════════════════════════
    // SHORTCODE: TEACHER PORTAL
    // ═══════════════════════════════════════════════════════════════

    /**
     * Render the teacher portal dashboard
     *
     * @return string HTML output
     */
    public function render_teacher_portal() {
        if ( ! is_user_logged_in() ) {
            return '<div style="text-align:center; padding:40px;"><p>' . esc_html__( 'Please log in to access the Teacher Portal.', 'make-school' ) . '</p><a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" style="display:inline-block; padding:12px 24px; background:#00a32a; color:#fff; text-decoration:none; border-radius:4px;">' . esc_html__( 'Login', 'make-school' ) . '</a></div>';
        }

        $user = wp_get_current_user();
        $allowed_roles = array( 'make_school_teacher', 'make_school_admin', 'administrator' );
        $has_access = false;
        foreach ( $allowed_roles as $role ) {
            if ( in_array( $role, (array) $user->roles, true ) ) {
                $has_access = true;
                break;
            }
        }

        if ( ! $has_access ) {
            return '<div style="text-align:center; padding:40px; color:#d63638;"><p>' . esc_html__( 'Access denied. This portal is for teachers only.', 'make-school' ) . '</p></div>';
        }

        global $wpdb;

        $classes_table = Make_School_DB::get_table( 'classes' );
        $classes = $wpdb->get_results( "SELECT id, class_name, section_name FROM {$classes_table} WHERE status = 'active' ORDER BY class_name ASC" );

        $exams_table = Make_School_DB::get_table( 'exams' );
        $exams = $wpdb->get_results( "SELECT id, exam_name, class_id, subject_name FROM {$exams_table} ORDER BY exam_date DESC" );

        ob_start();
        ?>
        <div class="ms-teacher-portal" style="max-width:1100px; margin:0 auto; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">

            <!-- Portal Header -->
            <div style="background:linear-gradient(135deg,#2e7d32,#1b5e20); color:#fff; padding:25px 30px; border-radius:8px; margin-bottom:25px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
                <div>
                    <h2 style="margin:0; font-size:22px;"><?php printf( esc_html__( 'Teacher Portal — %s', 'make-school' ), esc_html( $user->display_name ) ); ?></h2>
                    <p style="margin:5px 0 0; opacity:0.9;"><?php esc_html_e( 'Manage attendance and student marks', 'make-school' ); ?></p>
                </div>
                <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" style="padding:8px 16px; background:rgba(255,255,255,0.2); color:#fff; text-decoration:none; border-radius:4px; font-size:13px;">
                    <?php esc_html_e( 'Logout', 'make-school' ); ?>
                </a>
            </div>

            <!-- Tab Navigation -->
            <div style="margin-bottom:20px;">
                <button id="ms-tab-attendance" class="ms-tab-btn" onclick="msShowTab('attendance')"
                        style="padding:10px 24px; background:#2e7d32; color:#fff; border:none; border-radius:4px 4px 0 0; cursor:pointer; font-size:14px; font-weight:600;">
                    <?php esc_html_e( 'Attendance', 'make-school' ); ?>
                </button>
                <button id="ms-tab-marks" class="ms-tab-btn" onclick="msShowTab('marks')"
                        style="padding:10px 24px; background:#e0e0e0; color:#333; border:none; border-radius:4px 4px 0 0; cursor:pointer; font-size:14px; font-weight:600;">
                    <?php esc_html_e( 'Marks Entry', 'make-school' ); ?>
                </button>
            </div>


            <!-- ATTENDANCE TAB -->
            <div id="ms-panel-attendance" style="background:#fff; border-radius:0 8px 8px 8px; box-shadow:0 2px 8px rgba(0,0,0,0.08); padding:25px;">
                <h3 style="margin:0 0 20px; color:#333;"><?php esc_html_e( 'Mark Attendance', 'make-school' ); ?></h3>

                <div style="display:grid; grid-template-columns:1fr 1fr auto; gap:15px; align-items:end; margin-bottom:20px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#333; font-size:13px;">
                            <?php esc_html_e( 'Select Class', 'make-school' ); ?>
                        </label>
                        <select id="ms-att-class" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                            <option value=""><?php esc_html_e( '— Select Class —', 'make-school' ); ?></option>
                            <?php foreach ( $classes as $class ) : ?>
                                <option value="<?php echo esc_attr( $class->id ); ?>">
                                    <?php echo esc_html( $class->class_name . ' - ' . $class->section_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#333; font-size:13px;">
                            <?php esc_html_e( 'Date', 'make-school' ); ?>
                        </label>
                        <input type="date" id="ms-att-date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>"
                               style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                    </div>
                    <div>
                        <button id="ms-load-students" onclick="msLoadStudents()"
                                style="padding:10px 20px; background:#2e7d32; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:600;">
                            <?php esc_html_e( 'Load Students', 'make-school' ); ?>
                        </button>
                    </div>
                </div>

                <div id="ms-attendance-message" style="display:none; padding:10px; margin-bottom:15px; border-radius:4px;"></div>

                <!-- Student Attendance Table (populated via AJAX) -->
                <div id="ms-students-attendance-list">
                    <p style="color:#666; text-align:center; padding:30px;"><?php esc_html_e( 'Select a class and click "Load Students" to begin.', 'make-school' ); ?></p>
                </div>
            </div>

            <!-- MARKS ENTRY TAB -->
            <div id="ms-panel-marks" style="background:#fff; border-radius:0 8px 8px 8px; box-shadow:0 2px 8px rgba(0,0,0,0.08); padding:25px; display:none;">
                <h3 style="margin:0 0 20px; color:#333;"><?php esc_html_e( 'Enter Student Marks', 'make-school' ); ?></h3>

                <div style="display:grid; grid-template-columns:1fr 1fr auto; gap:15px; align-items:end; margin-bottom:20px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#333; font-size:13px;">
                            <?php esc_html_e( 'Select Class', 'make-school' ); ?>
                        </label>
                        <select id="ms-marks-class" onchange="msLoadExamsForClass()"
                                style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                            <option value=""><?php esc_html_e( '— Select Class —', 'make-school' ); ?></option>
                            <?php foreach ( $classes as $class ) : ?>
                                <option value="<?php echo esc_attr( $class->id ); ?>">
                                    <?php echo esc_html( $class->class_name . ' - ' . $class->section_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#333; font-size:13px;">
                            <?php esc_html_e( 'Select Exam', 'make-school' ); ?>
                        </label>
                        <select id="ms-marks-exam" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                            <option value=""><?php esc_html_e( '— Select Class First —', 'make-school' ); ?></option>
                        </select>
                    </div>
                    <div>
                        <button onclick="msLoadStudentsForMarks()"
                                style="padding:10px 20px; background:#2e7d32; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:600;">
                            <?php esc_html_e( 'Load Students', 'make-school' ); ?>
                        </button>
                    </div>
                </div>

                <div id="ms-marks-message" style="display:none; padding:10px; margin-bottom:15px; border-radius:4px;"></div>

                <!-- Student Marks Table (populated via AJAX) -->
                <div id="ms-students-marks-list">
                    <p style="color:#666; text-align:center; padding:30px;"><?php esc_html_e( 'Select a class and exam, then click "Load Students" to begin.', 'make-school' ); ?></p>
                </div>
            </div>


        </div><!-- /.ms-teacher-portal -->

        <!-- Teacher Portal JavaScript -->
        <script type="text/javascript">
        jQuery(document).ready(function($) {

            // Tab switching
            window.msShowTab = function(tab) {
                $('#ms-panel-attendance, #ms-panel-marks').hide();
                $('#ms-panel-' + tab).show();
                $('.ms-tab-btn').css({background:'#e0e0e0', color:'#333'});
                $('#ms-tab-' + tab).css({background:'#2e7d32', color:'#fff'});
            };

            // Load students for attendance
            window.msLoadStudents = function() {
                var classId = $('#ms-att-class').val();
                if (!classId) {
                    alert('<?php echo esc_js( __( 'Please select a class.', 'make-school' ) ); ?>');
                    return;
                }

                $('#ms-students-attendance-list').html('<p style="text-align:center; padding:20px; color:#666;"><?php echo esc_js( __( 'Loading students...', 'make-school' ) ); ?></p>');

                $.ajax({
                    url: MakeSchoolAjax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'make_school_get_students',
                        nonce: MakeSchoolAjax.nonce,
                        class_id: classId
                    },
                    success: function(response) {
                        if (response.success && response.data.students.length > 0) {
                            var html = '<form id="ms-attendance-form">';
                            html += '<table style="width:100%; border-collapse:collapse; font-size:14px;">';
                            html += '<thead><tr style="background:#f8f9fa;">';
                            html += '<th style="padding:10px; text-align:left; border-bottom:2px solid #dee2e6;">#</th>';
                            html += '<th style="padding:10px; text-align:left; border-bottom:2px solid #dee2e6;"><?php echo esc_js( __( 'Student Name', 'make-school' ) ); ?></th>';
                            html += '<th style="padding:10px; text-align:center; border-bottom:2px solid #dee2e6;"><?php echo esc_js( __( 'Present', 'make-school' ) ); ?></th>';
                            html += '<th style="padding:10px; text-align:center; border-bottom:2px solid #dee2e6;"><?php echo esc_js( __( 'Absent', 'make-school' ) ); ?></th>';
                            html += '<th style="padding:10px; text-align:center; border-bottom:2px solid #dee2e6;"><?php echo esc_js( __( 'Late', 'make-school' ) ); ?></th>';
                            html += '</tr></thead><tbody>';

                            $.each(response.data.students, function(i, student) {
                                html += '<tr style="border-bottom:1px solid #eee;">';
                                html += '<td style="padding:10px;">' + (i+1) + '</td>';
                                html += '<td style="padding:10px; font-weight:600;">' + student.name + '</td>';
                                html += '<td style="padding:10px; text-align:center;"><input type="radio" name="attendance[' + student.id + ']" value="present" checked></td>';
                                html += '<td style="padding:10px; text-align:center;"><input type="radio" name="attendance[' + student.id + ']" value="absent"></td>';
                                html += '<td style="padding:10px; text-align:center;"><input type="radio" name="attendance[' + student.id + ']" value="late"></td>';
                                html += '</tr>';
                            });

                            html += '</tbody></table>';
                            html += '<div style="margin-top:20px; text-align:right;">';
                            html += '<button type="button" onclick="msSaveAttendance()" style="padding:12px 30px; background:#2e7d32; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:15px; font-weight:600;"><?php echo esc_js( __( 'Save Attendance', 'make-school' ) ); ?></button>';
                            html += '</div></form>';

                            $('#ms-students-attendance-list').html(html);
                        } else {
                            $('#ms-students-attendance-list').html('<p style="text-align:center; padding:20px; color:#d63638;"><?php echo esc_js( __( 'No students found in this class.', 'make-school' ) ); ?></p>');
                        }
                    },
                    error: function() {
                        $('#ms-students-attendance-list').html('<p style="text-align:center; padding:20px; color:#d63638;"><?php echo esc_js( __( 'Failed to load students. Please try again.', 'make-school' ) ); ?></p>');
                    }
                });
            };


            // Save attendance via AJAX
            window.msSaveAttendance = function() {
                var classId = $('#ms-att-class').val();
                var date = $('#ms-att-date').val();

                if (!classId || !date) {
                    alert('<?php echo esc_js( __( 'Please select class and date.', 'make-school' ) ); ?>');
                    return;
                }

                var attendanceData = {};
                $('input[name^="attendance["]:checked').each(function() {
                    var name = $(this).attr('name');
                    var studentId = name.match(/\[(\d+)\]/)[1];
                    attendanceData[studentId] = $(this).val();
                });

                var $msg = $('#ms-attendance-message');

                $.ajax({
                    url: MakeSchoolAjax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'make_school_save_attendance',
                        nonce: MakeSchoolAjax.nonce,
                        class_id: classId,
                        date: date,
                        attendance: attendanceData
                    },
                    success: function(response) {
                        if (response.success) {
                            $msg.css({background:'#d4edda', color:'#155724', border:'1px solid #c3e6cb', display:'block'})
                                .html('<strong><?php echo esc_js( __( 'Success!', 'make-school' ) ); ?></strong> ' + response.data.message);
                        } else {
                            $msg.css({background:'#f8d7da', color:'#721c24', border:'1px solid #f5c6cb', display:'block'})
                                .html('<strong><?php echo esc_js( __( 'Error:', 'make-school' ) ); ?></strong> ' + response.data.message);
                        }
                        setTimeout(function() { $msg.fadeOut(); }, 5000);
                    },
                    error: function() {
                        $msg.css({background:'#f8d7da', color:'#721c24', border:'1px solid #f5c6cb', display:'block'})
                            .html('<?php echo esc_js( __( 'Network error. Please try again.', 'make-school' ) ); ?>');
                    }
                });
            };

            // Load exams for selected class
            window.msLoadExamsForClass = function() {
                var classId = $('#ms-marks-class').val();
                var $examSelect = $('#ms-marks-exam');

                $examSelect.html('<option value=""><?php echo esc_js( __( 'Loading...', 'make-school' ) ); ?></option>');

                if (!classId) {
                    $examSelect.html('<option value=""><?php echo esc_js( __( '— Select Class First —', 'make-school' ) ); ?></option>');
                    return;
                }

                $.ajax({
                    url: MakeSchoolAjax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'make_school_get_exams',
                        nonce: MakeSchoolAjax.nonce,
                        class_id: classId
                    },
                    success: function(response) {
                        if (response.success && response.data.exams.length > 0) {
                            var html = '<option value=""><?php echo esc_js( __( '— Select Exam —', 'make-school' ) ); ?></option>';
                            $.each(response.data.exams, function(i, exam) {
                                html += '<option value="' + exam.id + '" data-max="' + exam.max_marks + '">' + exam.exam_name + ' — ' + exam.subject_name + '</option>';
                            });
                            $examSelect.html(html);
                        } else {
                            $examSelect.html('<option value=""><?php echo esc_js( __( 'No exams found for this class', 'make-school' ) ); ?></option>');
                        }
                    }
                });
            };


            // Load students for marks entry
            window.msLoadStudentsForMarks = function() {
                var classId = $('#ms-marks-class').val();
                var examId = $('#ms-marks-exam').val();
                var maxMarks = $('#ms-marks-exam option:selected').data('max') || 100;

                if (!classId || !examId) {
                    alert('<?php echo esc_js( __( 'Please select both class and exam.', 'make-school' ) ); ?>');
                    return;
                }

                $('#ms-students-marks-list').html('<p style="text-align:center; padding:20px; color:#666;"><?php echo esc_js( __( 'Loading students...', 'make-school' ) ); ?></p>');

                $.ajax({
                    url: MakeSchoolAjax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'make_school_get_students',
                        nonce: MakeSchoolAjax.nonce,
                        class_id: classId
                    },
                    success: function(response) {
                        if (response.success && response.data.students.length > 0) {
                            var html = '<form id="ms-marks-form">';
                            html += '<input type="hidden" id="ms-marks-exam-id" value="' + examId + '">';
                            html += '<input type="hidden" id="ms-marks-max" value="' + maxMarks + '">';
                            html += '<table style="width:100%; border-collapse:collapse; font-size:14px;">';
                            html += '<thead><tr style="background:#f8f9fa;">';
                            html += '<th style="padding:10px; text-align:left; border-bottom:2px solid #dee2e6;">#</th>';
                            html += '<th style="padding:10px; text-align:left; border-bottom:2px solid #dee2e6;"><?php echo esc_js( __( 'Student Name', 'make-school' ) ); ?></th>';
                            html += '<th style="padding:10px; text-align:center; border-bottom:2px solid #dee2e6;"><?php echo esc_js( __( 'Marks Obtained', 'make-school' ) ); ?> (Max: ' + maxMarks + ')</th>';
                            html += '<th style="padding:10px; text-align:left; border-bottom:2px solid #dee2e6;"><?php echo esc_js( __( 'Remarks', 'make-school' ) ); ?></th>';
                            html += '</tr></thead><tbody>';

                            $.each(response.data.students, function(i, student) {
                                html += '<tr style="border-bottom:1px solid #eee;">';
                                html += '<td style="padding:10px;">' + (i+1) + '</td>';
                                html += '<td style="padding:10px; font-weight:600;">' + student.name + '</td>';
                                html += '<td style="padding:10px; text-align:center;"><input type="number" name="marks[' + student.id + ']" min="0" max="' + maxMarks + '" step="0.5" style="width:80px; padding:6px; border:1px solid #ddd; border-radius:3px; text-align:center;" required></td>';
                                html += '<td style="padding:10px;"><input type="text" name="remarks[' + student.id + ']" placeholder="<?php echo esc_js( __( 'Optional', 'make-school' ) ); ?>" style="width:100%; padding:6px; border:1px solid #ddd; border-radius:3px;"></td>';
                                html += '</tr>';
                            });

                            html += '</tbody></table>';
                            html += '<div style="margin-top:20px; text-align:right;">';
                            html += '<button type="button" onclick="msSaveMarks()" style="padding:12px 30px; background:#2e7d32; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:15px; font-weight:600;"><?php echo esc_js( __( 'Save Marks', 'make-school' ) ); ?></button>';
                            html += '</div></form>';

                            $('#ms-students-marks-list').html(html);
                        } else {
                            $('#ms-students-marks-list').html('<p style="text-align:center; padding:20px; color:#d63638;"><?php echo esc_js( __( 'No students found in this class.', 'make-school' ) ); ?></p>');
                        }
                    },
                    error: function() {
                        $('#ms-students-marks-list').html('<p style="text-align:center; padding:20px; color:#d63638;"><?php echo esc_js( __( 'Failed to load students.', 'make-school' ) ); ?></p>');
                    }
                });
            };

            // Save marks via AJAX
            window.msSaveMarks = function() {
                var examId = $('#ms-marks-exam-id').val();
                var classId = $('#ms-marks-class').val();
                var maxMarks = $('#ms-marks-max').val();

                if (!examId || !classId) {
                    alert('<?php echo esc_js( __( 'Missing exam or class selection.', 'make-school' ) ); ?>');
                    return;
                }

                var marksData = {};
                var remarksData = {};

                $('input[name^="marks["]').each(function() {
                    var name = $(this).attr('name');
                    var studentId = name.match(/\[(\d+)\]/)[1];
                    marksData[studentId] = $(this).val();
                });

                $('input[name^="remarks["]').each(function() {
                    var name = $(this).attr('name');
                    var studentId = name.match(/\[(\d+)\]/)[1];
                    remarksData[studentId] = $(this).val();
                });

                var $msg = $('#ms-marks-message');

                $.ajax({
                    url: MakeSchoolAjax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'make_school_save_marks',
                        nonce: MakeSchoolAjax.nonce,
                        exam_id: examId,
                        class_id: classId,
                        max_marks: maxMarks,
                        marks: marksData,
                        remarks: remarksData
                    },
                    success: function(response) {
                        if (response.success) {
                            $msg.css({background:'#d4edda', color:'#155724', border:'1px solid #c3e6cb', display:'block'})
                                .html('<strong><?php echo esc_js( __( 'Success!', 'make-school' ) ); ?></strong> ' + response.data.message);
                        } else {
                            $msg.css({background:'#f8d7da', color:'#721c24', border:'1px solid #f5c6cb', display:'block'})
                                .html('<strong><?php echo esc_js( __( 'Error:', 'make-school' ) ); ?></strong> ' + response.data.message);
                        }
                        setTimeout(function() { $msg.fadeOut(); }, 5000);
                    },
                    error: function() {
                        $msg.css({background:'#f8d7da', color:'#721c24', border:'1px solid #f5c6cb', display:'block'})
                            .html('<?php echo esc_js( __( 'Network error. Please try again.', 'make-school' ) ); ?>');
                    }
                });
            };

        });
        </script>
        <?php
        return ob_get_clean();
    }


    // ═══════════════════════════════════════════════════════════════
    // AJAX HANDLERS - TEACHER OPERATIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * AJAX: Get students by class ID
     */
    public function ajax_get_students_by_class() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'make_school_public_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'make-school' ) ) );
        }

        // Verify user capability
        $user = wp_get_current_user();
        $allowed_roles = array( 'make_school_teacher', 'make_school_admin', 'administrator' );
        $has_access = false;
        foreach ( $allowed_roles as $role ) {
            if ( in_array( $role, (array) $user->roles, true ) ) {
                $has_access = true;
                break;
            }
        }

        if ( ! $has_access ) {
            wp_send_json_error( array( 'message' => __( 'Access denied.', 'make-school' ) ) );
        }

        $class_id = intval( $_POST['class_id'] ?? 0 );

        if ( $class_id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid class selection.', 'make-school' ) ) );
        }

        // Get students enrolled in this class
        $students = get_users( array(
            'role'       => 'make_school_student',
            'meta_key'   => 'make_school_class_id',
            'meta_value' => $class_id,
            'orderby'    => 'display_name',
            'order'      => 'ASC',
        ) );

        $student_list = array();
        foreach ( $students as $student ) {
            $student_list[] = array(
                'id'   => $student->ID,
                'name' => $student->display_name,
            );
        }

        wp_send_json_success( array( 'students' => $student_list ) );
    }

    /**
     * AJAX: Get exams by class ID
     */
    public function ajax_get_exams_by_class() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'make_school_public_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'make-school' ) ) );
        }

        $class_id = intval( $_POST['class_id'] ?? 0 );

        if ( $class_id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid class selection.', 'make-school' ) ) );
        }

        global $wpdb;
        $exams_table = Make_School_DB::get_table( 'exams' );

        $exams = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, exam_name, subject_name, max_marks FROM {$exams_table} WHERE class_id = %d ORDER BY exam_date DESC",
                $class_id
            )
        );

        $exam_list = array();
        foreach ( $exams as $exam ) {
            $exam_list[] = array(
                'id'           => $exam->id,
                'exam_name'    => $exam->exam_name,
                'subject_name' => $exam->subject_name,
                'max_marks'    => $exam->max_marks,
            );
        }

        wp_send_json_success( array( 'exams' => $exam_list ) );
    }


    /**
     * AJAX: Save attendance records in bulk
     */
    public function ajax_save_attendance() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'make_school_public_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'make-school' ) ) );
        }

        // Verify user capability
        $user = wp_get_current_user();
        $allowed_roles = array( 'make_school_teacher', 'make_school_admin', 'administrator' );
        $has_access = false;
        foreach ( $allowed_roles as $role ) {
            if ( in_array( $role, (array) $user->roles, true ) ) {
                $has_access = true;
                break;
            }
        }

        if ( ! $has_access ) {
            wp_send_json_error( array( 'message' => __( 'Access denied. Teachers only.', 'make-school' ) ) );
        }

        $class_id   = intval( $_POST['class_id'] ?? 0 );
        $date       = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
        $attendance = isset( $_POST['attendance'] ) && is_array( $_POST['attendance'] ) ? $_POST['attendance'] : array();

        // Validate inputs
        if ( $class_id <= 0 || empty( $date ) || empty( $attendance ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing required data. Please reload and try again.', 'make-school' ) ) );
        }

        // Validate date format
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'make-school' ) ) );
        }

        global $wpdb;
        $attendance_table = Make_School_DB::get_table( 'attendance' );
        $valid_statuses   = array( 'present', 'absent', 'late' );
        $saved_count      = 0;
        $teacher_id       = $user->ID;

        foreach ( $attendance as $student_id => $status ) {
            $student_id = intval( $student_id );
            $status     = sanitize_text_field( $status );

            // Validate status
            if ( ! in_array( $status, $valid_statuses, true ) ) {
                continue;
            }

            // Check if record already exists for this student/class/date
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$attendance_table} WHERE student_id = %d AND class_id = %d AND date = %s",
                    $student_id, $class_id, $date
                )
            );

            if ( $existing ) {
                // Update existing record
                $wpdb->update(
                    $attendance_table,
                    array(
                        'status'    => $status,
                        'marked_by' => $teacher_id,
                    ),
                    array( 'id' => intval( $existing ) ),
                    array( '%s', '%d' ),
                    array( '%d' )
                );
                $saved_count++;
            } else {
                // Insert new record
                $result = $wpdb->insert(
                    $attendance_table,
                    array(
                        'student_id' => $student_id,
                        'class_id'   => $class_id,
                        'date'       => $date,
                        'status'     => $status,
                        'marked_by'  => $teacher_id,
                    ),
                    array( '%d', '%d', '%s', '%s', '%d' )
                );

                if ( false !== $result ) {
                    $saved_count++;
                }
            }
        }

        wp_send_json_success( array(
            'message' => sprintf(
                __( 'Attendance saved successfully for %d students on %s.', 'make-school' ),
                $saved_count,
                date_i18n( 'M j, Y', strtotime( $date ) )
            ),
        ) );
    }


    /**
     * AJAX: Save marks records in bulk
     */
    public function ajax_save_marks() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'make_school_public_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'make-school' ) ) );
        }

        // Verify user capability
        $user = wp_get_current_user();
        $allowed_roles = array( 'make_school_teacher', 'make_school_admin', 'administrator' );
        $has_access = false;
        foreach ( $allowed_roles as $role ) {
            if ( in_array( $role, (array) $user->roles, true ) ) {
                $has_access = true;
                break;
            }
        }

        if ( ! $has_access ) {
            wp_send_json_error( array( 'message' => __( 'Access denied. Teachers only.', 'make-school' ) ) );
        }

        $exam_id   = intval( $_POST['exam_id'] ?? 0 );
        $class_id  = intval( $_POST['class_id'] ?? 0 );
        $max_marks = intval( $_POST['max_marks'] ?? 100 );
        $marks     = isset( $_POST['marks'] ) && is_array( $_POST['marks'] ) ? $_POST['marks'] : array();
        $remarks   = isset( $_POST['remarks'] ) && is_array( $_POST['remarks'] ) ? $_POST['remarks'] : array();

        // Validate inputs
        if ( $exam_id <= 0 || $class_id <= 0 || empty( $marks ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing required data.', 'make-school' ) ) );
        }

        global $wpdb;
        $marks_table = Make_School_DB::get_table( 'marks' );
        $saved_count = 0;
        $teacher_id  = $user->ID;

        foreach ( $marks as $student_id => $marks_obtained ) {
            $student_id     = intval( $student_id );
            $marks_obtained = floatval( $marks_obtained );
            $student_remark = isset( $remarks[ $student_id ] ) ? sanitize_text_field( wp_unslash( $remarks[ $student_id ] ) ) : '';

            // Validate marks range
            if ( $marks_obtained < 0 || $marks_obtained > $max_marks ) {
                continue;
            }

            // Calculate grade
            $percentage = $max_marks > 0 ? ( $marks_obtained / $max_marks ) * 100 : 0;
            $grade = $this->calculate_grade( $percentage );

            // Check if record already exists
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$marks_table} WHERE student_id = %d AND exam_id = %d",
                    $student_id, $exam_id
                )
            );

            if ( $existing ) {
                // Update existing record
                $wpdb->update(
                    $marks_table,
                    array(
                        'marks_obtained'  => $marks_obtained,
                        'max_marks'       => $max_marks,
                        'grade'           => $grade,
                        'teacher_remarks' => $student_remark,
                        'entered_by'      => $teacher_id,
                    ),
                    array( 'id' => intval( $existing ) ),
                    array( '%f', '%d', '%s', '%s', '%d' ),
                    array( '%d' )
                );
                $saved_count++;
            } else {
                // Insert new record
                $result = $wpdb->insert(
                    $marks_table,
                    array(
                        'student_id'      => $student_id,
                        'exam_id'         => $exam_id,
                        'class_id'        => $class_id,
                        'marks_obtained'  => $marks_obtained,
                        'max_marks'       => $max_marks,
                        'grade'           => $grade,
                        'teacher_remarks' => $student_remark,
                        'entered_by'      => $teacher_id,
                    ),
                    array( '%d', '%d', '%d', '%f', '%d', '%s', '%s', '%d' )
                );

                if ( false !== $result ) {
                    $saved_count++;
                }
            }
        }

        wp_send_json_success( array(
            'message' => sprintf(
                __( 'Marks saved successfully for %d students.', 'make-school' ),
                $saved_count
            ),
        ) );
    }

} // End class Make_School_Public
