<?php

use Kirki\Database\Migrations\CreateCollaborationsConnectedTable;
use Kirki\Database\Migrations\CreateCollaborationsSentTable;
use Kirki\Database\Migrations\CreateCollaborationsTable;
use Kirki\Database\Migrations\CreateCmReferenceTable;
use Kirki\Database\Migrations\CreateCommentsSeenTable;
use Kirki\Database\Migrations\CreateCommentsTable;
use Kirki\Database\Migrations\CreateFormsDataTable;
use Kirki\Database\Migrations\CreateFormsTable;

return [
    CreateFormsTable::class,
    CreateFormsDataTable::class,
    CreateCollaborationsTable::class,
    CreateCollaborationsConnectedTable::class,
    CreateCollaborationsSentTable::class,
    CreateCmReferenceTable::class,
    CreateCommentsTable::class,
    CreateCommentsSeenTable::class,
];
