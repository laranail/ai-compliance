<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\View;

use Illuminate\Contracts\View\Factory as ViewFactory;

/**
 * Server-side hydration for the <ai-c> elements the shortcode compiler
 * emits: each element whose component has a matching island view
 * (laranail-ai-compliance::islands.{name}) is replaced with that view's output; the
 * rest keep their fallback text, so blade/livewire surfaces never ship an
 * inert custom element. The js core does the same job in the browser for
 * react/vue surfaces.
 */
final readonly class IslandRenderer
{
    private const string PATTERN = '/<ai-c data-component="([a-z][a-z0-9-]*)" data-props="([^"]*)">(.*?)<\/ai-c>/s';

    public function __construct(
        private ViewFactory $views,
    ) {}

    public function render(string $html): string
    {
        return (string) preg_replace_callback(
            self::PATTERN,
            function (array $matches): string {
                $name = $matches[1];
                $props = $this->decodeProps($matches[2]);
                $fallback = $matches[3];

                $view = 'laranail-ai-compliance::islands.' . $name;

                if (! $this->views->exists($view)) {
                    return $fallback;
                }

                return $this->views->make($view, [
                    'props'    => $props,
                    'fallback' => $fallback,
                ])->render();
            },
            $html,
        );
    }

    /**
     * @return array<string, string>
     */
    private function decodeProps(string $encoded): array
    {
        $decoded = json_decode(html_entity_decode($encoded, ENT_QUOTES | ENT_HTML5), true);

        if (! is_array($decoded)) {
            return [];
        }

        $props = [];

        foreach ($decoded as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $props[$key] = $value;
            }
        }

        return $props;
    }
}
