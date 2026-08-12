<?php
/**
 * Playground / preview seed: one Fluent Forms contact form, already annotated.
 *
 * Dual-use: Playground runPHP (loads wp-load) and `studio wp eval-file` (ABSPATH set).
 *
 * @package Siwmfa
 */

if ( ! isset( $siwmfa_playground ) ) {
	$siwmfa_playground = false;
}
if ( ! defined( 'ABSPATH' ) ) {
	$siwmfa_playground = true;
	require_once 'wordpress/wp-load.php';
}

if ( ! class_exists( '\FluentForm\App\Services\Form\FormService' ) ) {
	return;
}

wp_set_current_user( 1 );

$required = array(
	'required' => array(
		'value'          => true,
		'message'        => 'This field is required',
		'global_message' => 'This field is required',
		'global'         => true,
	),
);

$fields = array(
	array(
		'index'          => 0,
		'element'        => 'input_text',
		'attributes'     => array(
			'type'        => 'text',
			'name'        => 'names',
			'value'       => '',
			'class'       => '',
			'placeholder' => '',
		),
		'settings'       => array(
			'container_class'    => '',
			'label'              => 'Name',
			'admin_field_label'  => 'Name',
			'label_placement'    => '',
			'help_message'       => '',
			'validation_rules'   => $required,
			'conditional_logics' => array(),
		),
		'editor_options' => array(
			'title'      => 'Simple Text',
			'icon_class' => 'ff-edit-text',
			'template'   => 'inputText',
		),
		'uniqElKey'      => 'el_siwmfa_name',
	),
	array(
		'index'          => 1,
		'element'        => 'input_email',
		'attributes'     => array(
			'type'        => 'email',
			'name'        => 'email',
			'value'       => '',
			'class'       => '',
			'placeholder' => '',
		),
		'settings'       => array(
			'container_class'    => '',
			'label'              => 'Email',
			'admin_field_label'  => 'Email',
			'label_placement'    => '',
			'help_message'       => '',
			'validation_rules'   => array_merge(
				$required,
				array(
					'email' => array(
						'value'          => true,
						'message'        => 'This field must contain a valid email',
						'global_message' => 'This field must contain a valid email',
						'global'         => true,
					),
				)
			),
			'conditional_logics' => array(),
		),
		'editor_options' => array(
			'title'      => 'Email Address',
			'icon_class' => 'ff-edit-email',
			'template'   => 'inputText',
		),
		'uniqElKey'      => 'el_siwmfa_email',
	),
	array(
		'index'          => 2,
		'element'        => 'textarea',
		'attributes'     => array(
			'name'  => 'message',
			'value' => '',
			'class' => '',
			'rows'  => 5,
			'cols'  => 2,
		),
		'settings'       => array(
			'container_class'    => '',
			'label'              => 'Message',
			'admin_field_label'  => 'Message',
			'label_placement'    => '',
			'help_message'       => '',
			'validation_rules'   => $required,
			'conditional_logics' => array(),
		),
		'editor_options' => array(
			'title'      => 'Text Area',
			'icon_class' => 'ff-edit-textarea',
			'template'   => 'inputTextarea',
		),
		'uniqElKey'      => 'el_siwmfa_message',
	),
);

$form_fields = wp_json_encode(
	array(
		'fields'       => $fields,
		'submitButton' => array(
			'uniqElKey'      => 'el_siwmfa_submit',
			'element'        => 'button',
			'attributes'     => array(
				'type'  => 'submit',
				'class' => '',
			),
			'settings'       => array(
				'align'            => 'left',
				'button_style'     => 'default',
				'container_class'  => '',
				'help_message'     => '',
				'background_color' => '#0b3d4a',
				'button_size'      => 'md',
				'color'            => '#ffffff',
				'button_ui'        => array(
					'type'    => 'default',
					'text'    => 'Send message',
					'img_url' => '',
				),
			),
			'editor_options' => array(
				'title' => 'Submit Button',
			),
		),
	)
);

$existing = (int) get_option( 'siwmfa_preview_fluent_form_id', 0 );
$form_id  = 0;

if ( $existing > 0 && class_exists( '\FluentForm\App\Models\Form' ) ) {
	$found = \FluentForm\App\Models\Form::find( $existing );
	if ( $found ) {
		$form_id = $existing;
		\FluentForm\App\Models\Form::where( 'id', $form_id )->update(
			array(
				'title'       => 'Contact',
				'form_fields' => $form_fields,
				'status'      => 'published',
				'updated_at'  => current_time( 'mysql' ),
			)
		);
	}
}

if ( $form_id <= 0 ) {
	$service = new \FluentForm\App\Services\Form\FormService();
	$form    = $service->store(
		array(
			'predefined' => 'blank_form',
			'title'      => 'Contact',
		)
	);
	$form_id = (int) $form->id;
	\FluentForm\App\Models\Form::where( 'id', $form_id )->update(
		array(
			'title'       => 'Contact',
			'form_fields' => $form_fields,
			'status'      => 'published',
			'updated_at'  => current_time( 'mysql' ),
		)
	);
	update_option( 'siwmfa_preview_fluent_form_id', $form_id, false );
}

$saved = get_option( 'siwmfa_forms', array() );
if ( ! is_array( $saved ) ) {
	$saved = array();
}

$saved[ 'fluent:' . $form_id ] = array(
	'enabled'         => true,
	'toolname'        => 'submit_contact',
	'tooldescription' => 'Fills the contact form on this page. Use for a lead or support request. Do not submit the form — only fill the fields.',
	'params'          => array(
		'names'   => 'Full name of the visitor.',
		'email'   => 'Email address for a reply.',
		'message' => 'Message describing the request.',
	),
);

update_option( 'siwmfa_forms', $saved, false );

$notice  = '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"radius":"4px"}},"backgroundColor":"accent-5","layout":{"type":"constrained"}} -->';
$notice .= '<div class="wp-block-group has-accent-5-background-color has-background" style="border-radius:4px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">';
$notice .= '<!-- wp:paragraph --><p><strong>Demo:</strong> this Fluent Forms contact form is already annotated with WebMCP (<code>toolname</code>, <code>tooldescription</code>, <code>toolparamdescription</code>). It never auto-submits. You are logged in — open <strong>Settings → WebMCP Forms</strong> to edit the annotation, then return here to view the markup.</p><!-- /wp:paragraph -->';
$notice .= '</div><!-- /wp:group -->';

$content  = $notice;
$content .= '<!-- wp:heading --><h2 class="wp-block-heading">Contact</h2><!-- /wp:heading -->';
$content .= '<!-- wp:shortcode -->[fluentform id="' . $form_id . '"]<!-- /wp:shortcode -->';

$page = get_page_by_path( 'contact' );
if ( $page instanceof WP_Post ) {
	$page_id = (int) $page->ID;
	wp_update_post(
		array(
			'ID'           => $page_id,
			'post_title'   => 'Contact',
			'post_content' => $content,
			'post_status'  => 'publish',
		)
	);
} else {
	$page_id = (int) wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_title'   => 'Contact',
			'post_name'    => 'contact',
			'post_status'  => 'publish',
			'post_content' => $content,
			'post_author'  => 1,
		)
	);
}

if ( $siwmfa_playground ) {
	update_option( 'blogname', 'WebMCP Form Annotator (demo)', false );
	update_option( 'blogdescription', 'Fluent Forms contact form with declarative WebMCP attributes — fictional demo', false );
	update_option( 'show_on_front', 'page', false );
	update_option( 'page_on_front', $page_id, false );
	update_option( 'permalink_structure', '/%postname%/', false );
	flush_rewrite_rules( false );
}
