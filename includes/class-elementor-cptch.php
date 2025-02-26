<?php

/**
 * INEOSQ captcha for Elementor Pro
 *
 * @since 1.65
 */
namespace ElementorPro\Modules\Forms;

use Elementor\Core\Experiments\Manager;
use Elementor\Core\Common\Modules\Ajax\Module as Ajax;
use ElementorPro\Base\Module_Base;
use ElementorPro\Modules\Forms\Classes;
use ElementorPro\Modules\Forms\Controls\Fields_Map;
use ElementorPro\Modules\Forms\Registrars\Form_Actions_Registrar;
use ElementorPro\Modules\Forms\Registrars\Form_Fields_Registrar;
use ElementorPro\Modules\Forms\Controls\Fields_Repeater;
use ElementorPro\Plugin;

class Elementor_Cptch {

    protected function get_control_id( $control_id ) {
        return $control_id;
    }

    public static function is_enabled() {
        $is_user_logged_in = is_user_logged_in();
        if ( cptch_is_captcha_required( 'elementor_contact_form', $is_user_logged_in ) ) {
            return true;
        }
        return false;
    }

    /**
     * @param Form_Record  $record
     * @param Ajax_Handler $ajax_handler
     */
    public function validation( $record, $ajax_handler ) {
       global $cptch_options;

        if ( empty( $cptch_options ) ) {
            register_cptch_settings();
        }
        $is_user_logged_in = is_user_logged_in();

        $fields = $record->get_field( [
            'type' => 'cptch',
        ] );

        if ( empty( $fields ) ) {
            return;
        }

        $field = current( $fields ); 
        unset( $ajax_handler->errors[ $field['id'] ] );

        if ( Elementor_Cptch::is_enabled() && $is_user_logged_in && isset( $cptch_options['forms']['elementor_contact_form']['hide_from_registered'] ) && $cptch_options['forms']['elementor_contact_form']['hide_from_registered'] == 1 ) {
            return;
        }
      
        // validation
        $cptch = cptch_check_custom_form( true, 'elementor_contact_form', 'elementor_contact_form' );

        if ( ! empty( $cptch->errors ) ) {        
            $message = '';          
            foreach ($cptch->errors as $arrors) {
                    $message = implode('.', $arrors);
            }
            $ajax_handler->add_error( $field['id'], $message );
            $ajax_handler->messages['error'][] = $message;
        } 

        // If success - remove the field form list (don't send it in emails and etc )
        $record->remove_field( $field['id'] );

    }

    public function render_field() {
        echo cptch_display_captcha_custom( 'elementor_contact_form' );
    }

    public function add_field_type( $field_types ) {
        $field_types['cptch'] = 'INEOSQ captcha';

        return $field_types;
    }

    public function filter_field_item( $item ) {
        if ( 'cptch' == $item['field_type'] ) {
            $item['field_label'] = false;
        }
        return $item;
    }

    public function __construct() {
        if ( static::is_enabled() ) {
            add_filter( 'elementor_pro/forms/field_types', [ $this, 'add_field_type' ] );
            add_action( 'elementor_pro/forms/render_field/cptch', [ $this, 'render_field' ] );
            add_filter( 'elementor_pro/forms/render/item', [ $this, 'filter_field_item' ] );  
            add_action( 'elementor_pro/forms/validation', [ $this, 'validation' ], 10, 2 );
        }
    }
}

if ( Elementor_Cptch::is_enabled() ) {
    $all_plugins = get_plugins();
    if ( version_compare( '3.5.0', $all_plugins['elementor-pro/elementor-pro.php']['Version'], '<=' ) ) {
        class Module extends Module_Base {
            /**
             * @var Form_Actions_Registrar
             */
            public $actions_registrar;

            /**
             * @var Form_Fields_Registrar
             */
            public $fields_registrar;

            public function get_name() {
                return 'forms';
            }

            public function get_widgets() {
                return [
                    'Form',
                    'Login',
                ];
            }

            public function localize_settings( $settings ) {
                $settings = array_replace_recursive( $settings, [
                    'i18n' => [
                        'x_field' => esc_html__( '%s Field', 'ineosq-captcha-pro' ),
                    ],
                ] );

                return $settings;
            }

            public static function find_element_recursive( $elements, $form_id ) {
                foreach ( $elements as $element ) {
                    if ( $form_id === $element['id'] ) {
                        return $element;
                    }

                    if ( ! empty( $element['elements'] ) ) {
                        $element = self::find_element_recursive( $element['elements'], $form_id );

                        if ( $element ) {
                            return $element;
                        }
                    }
                }

                return false;
            }

            public function register_controls() {
                $controls_manager = Plugin::elementor()->controls_manager;

                $controls_manager->register_control( Fields_Repeater::CONTROL_TYPE, new Fields_Repeater() );
                $controls_manager->register_control( Fields_Map::CONTROL_TYPE, new Fields_Map() );
            }


            /**
             * Module constructor.
             */
            public function __construct() {
                parent::__construct();

                add_filter( 'elementor_pro/editor/localize_settings', [ $this, 'localize_settings' ] );
                add_action( 'elementor/controls/controls_registered', [ $this, 'register_controls' ] );
                add_action( 'elementor/ajax/register_actions', [ $this, 'register_ajax_actions' ] );

                $this->add_component( 'cptch', new Elementor_Cptch() );

            
                // Initialize registrars.
                $this->actions_registrar = new Form_Actions_Registrar();
                $this->fields_registrar = new Form_Fields_Registrar();

                // Ajax Handler
                if ( Classes\Ajax_Handler::is_form_submitted() ) {
                    $this->add_component( 'ajax_handler', new Classes\Ajax_Handler() );

                    /**
                     * Elementor form submitted.
                     *
                     * @param Module $this An instance of the form module.
                     */
                    do_action( 'elementor_pro/forms/form_submitted', $this );
                }
                
            }
        }
    } else {
        add_action( 'elementor_pro/forms/register_action', function( Module $module ) {
            $module->add_component( 'cptch', new Elementor_Cptch() );
        } );
    }
}
