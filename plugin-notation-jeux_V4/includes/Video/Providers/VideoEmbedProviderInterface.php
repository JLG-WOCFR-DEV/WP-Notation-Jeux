<?php

namespace JLG\Notation\Video\Providers;

interface VideoEmbedProviderInterface {

    public function get_slug(): string;

    public function build_embed_src( string $url ): string;
}
