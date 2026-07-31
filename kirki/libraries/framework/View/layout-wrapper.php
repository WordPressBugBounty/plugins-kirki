<?php

namespace {
    /**
     * Layout wrapper for template_include views that use theme header/footer.
     *
     * WordPress includes this file via template_include. The active ViewContext
     * holds the real view path and data; this stub renders content and wraps it.
     *
     * @package    Framework
     * @subpackage View
     * @since      2.1.2
     */
    \defined('ABSPATH') || exit;
    use Kirki\Framework\View\TemplateEngine;
    use Kirki\Framework\View\ViewContext;
    use function Kirki\Framework\app;
    $context = app(ViewContext::class);
    $active = $context->get_active();
    if ($active === null || empty($active['resolved_path'])) {
        return;
    }
    $path = $active['resolved_path'];
    \ob_start();
    require $path;
    $content = (string) \ob_get_clean();
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Assembled layout HTML; dynamic data is escaped in view templates via esc_*.
    echo app(TemplateEngine::class)->wrap_layout($content);
}
