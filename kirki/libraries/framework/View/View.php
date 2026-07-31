<?php

/**
 * Renderable view value object returned by the view() helper.
 *
 * @package    Framework
 * @subpackage View
 * @since      1.0.0
 */
namespace Kirki\Framework\View;

\defined('ABSPATH') || exit;
use function Kirki\Framework\app;
class View
{
    /**
     * The template name in dot notation.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $template;
    /**
     * Data passed to the template.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $data = [];
    /**
     * Whether to wrap the view in the theme layout.
     *
     * @var bool
     *
     * @since 1.0.0
     */
    protected $with_layout = \true;
    /**
     * Create a new View instance.
     *
     * @param string $template The template name.
     * @param array $data The template data.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct(string $template, array $data = [])
    {
        $this->template = $template;
        $this->data = $data;
    }
    /**
     * Disable layout wrapping for this view.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function partial()
    {
        $this->with_layout = \false;
        return $this;
    }
    /**
     * Enable or set layout wrapping for this view.
     *
     * @param bool $enabled Whether layout wrapping is enabled.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function layout($enabled = \true)
    {
        $this->with_layout = (bool) $enabled;
        return $this;
    }
    /**
     * Merge additional data into the view.
     *
     * @param array $data The data to merge.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function with(array $data)
    {
        $this->data = \array_merge($this->data, $data);
        return $this;
    }
    /**
     * Get the template name.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_template()
    {
        return $this->template;
    }
    /**
     * Get the view data.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function get_data()
    {
        return $this->data;
    }
    /**
     * Whether the view uses layout wrapping.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function uses_layout()
    {
        return $this->with_layout;
    }
    /**
     * Render the view to a string.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function render()
    {
        return app(TemplateEngine::class)->render($this->template, $this->data, $this->with_layout);
    }
    /**
     * Render the view when cast to string.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function __toString()
    {
        return $this->render();
    }
}
