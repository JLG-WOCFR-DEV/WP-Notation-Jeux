<?php

namespace JLG\Notation\Admin;

use JLG\Notation\Admin\Ajax;
use JLG\Notation\Admin\Menu;
use JLG\Notation\Admin\Metaboxes;
use JLG\Notation\Admin\Platforms;
use JLG\Notation\Admin\Settings;
use JLG\Notation\Admin\Diagnostics;
use JLG\Notation\Admin\Onboarding\OnboardingController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Core {
    private static $instance = null;
    private $menu;
    private $metaboxes;
    private $settings;
    private $platforms;
    private $diagnostics;
    private $onboarding;

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_admin_components();
    }

    private function init_admin_components() {
        if ( class_exists( Menu::class ) ) {
            $this->menu = new Menu();
        }

        if ( class_exists( Metaboxes::class ) ) {
            $this->metaboxes = new Metaboxes();
        }

        if ( class_exists( Settings::class ) ) {
            $this->settings = new Settings();
        }

        if ( class_exists( Ajax::class ) ) {
            new Ajax();
        }

        // Initialiser la classe Platforms en mode singleton
        if ( class_exists( Platforms::class ) ) {
            $this->platforms = Platforms::get_instance();
        }

        if ( class_exists( Diagnostics::class ) ) {
            $this->diagnostics = new Diagnostics();
        }

        if ( class_exists( OnboardingController::class ) ) {
            $this->onboarding = new OnboardingController( defined( 'JLG_NOTATION_PLUGIN_BASENAME' ) ? JLG_NOTATION_PLUGIN_BASENAME : '' );
        }
    }

    public function get_component( $component_name ) {
        switch ( $component_name ) {
            case 'menu':
                return $this->menu;
            case 'metaboxes':
                return $this->metaboxes;
            case 'settings':
                return $this->settings;
            case 'platforms':
                return $this->platforms;
            case 'diagnostics':
                return $this->diagnostics;
            case 'onboarding':
                return $this->onboarding;
            default:
                return null;
        }
    }
}
