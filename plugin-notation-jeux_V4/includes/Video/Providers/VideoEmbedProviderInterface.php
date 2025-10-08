<?php

namespace JLG\Notation\Video\Providers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface VideoEmbedProviderInterface {

    public function get_slug(): string;

    public function build_embed_src( string $url ): string;
}
