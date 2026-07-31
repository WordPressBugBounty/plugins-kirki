<?php

namespace Kirki\App\DTO\Form;

use Kirki\Framework\Sanitizer;

defined('ABSPATH') || exit;

use Kirki\Framework\DTO;

class FormConfigDTO extends DTO
{
    /** @var string The form element's builder id. */
    public $id;

    /** @var string Whether the form posts to this site ('default') or an external URL ('external'). Not used server-side. */
    public $type;

    /** @var string */
    public $name;

    /** @var array */
    public $fields = [];

    /** @var array */
    public $actions = [];

    /** @var array Client-side "what to show after submit" config. Not used server-side. */
    public $onSubmit = [];

    /** @var array */
    public $maxEntry = [];

    /** @var array */
    public $responseLimit = [];

    /** @var bool */
    public $saveData = false;

    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->id = (string) $this->id;
        $this->type = (string) $this->type;
        $this->name = (string) $this->name;
        $this->fields = is_array($this->fields) ? $this->fields : [];
        $this->actions = is_array($this->actions) ? $this->actions : [];
        $this->onSubmit = is_array($this->onSubmit) ? $this->onSubmit : [];
        $this->maxEntry = is_array($this->maxEntry) ? $this->maxEntry : [];
        $this->responseLimit = is_array($this->responseLimit) ? $this->responseLimit : [];
        $this->saveData = Sanitizer::apply_rule($this->saveData, Sanitizer::BOOL);
    }
}
