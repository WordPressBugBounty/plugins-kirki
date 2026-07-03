<?php

namespace Kirki\App\DTO\Apps;

use Kirki\Framework\DTO;

defined('ABSPATH') || exit;

class AppDTO extends DTO
{
    /** @var int */
    public $id;

    /** @var string */
    public $version;

    /** @var bool */
    public $isPro;

    /** @var string */
    public $slug;

    /** @var string */
    public $title;

    /** @var string */
    public $shortDescription;

    /** @var string */
    public $author;

    /** @var string */
    public $src;

    /** @var bool */
    public $isNew;

    /** @var bool */
    public $isFeatured;

    /** @var array<array{
     *     key:int,
     *     title:string,
     *     action:string,
     *     value:array{
     *         name:string,
     *         options:array{
     *             menuTabCurrentCategory:string,
     *             searchQuery:string
     *         }
     *     }
     * }> $actions
     */
    public $actions;

    /** @var array<array{
     *     key: string,
     *     label: string,
     *     setting: array{
     *         type: string,
     *         placeholder: string
     *     }
     * }> $settings
     */
    public $settings;
}