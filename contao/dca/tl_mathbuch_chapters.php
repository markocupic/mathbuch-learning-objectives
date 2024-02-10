<?php

declare(strict_types=1);

/*
 * This file is part of mathbuch-learning-objectives.
 *
 * (c) Marko Cupic 2024 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/mathbuch-learning-objectives
 */

use Contao\DataContainer;
use Contao\DC_Table;
use Markocupic\MathbuchLearningObjectives\Config\MathbuchVolume;

/**
 * Table tl_mathbuch_chapters
 */
$GLOBALS['TL_DCA']['tl_mathbuch_chapters'] = [
    'config'      => [
        'dataContainer'    => DC_Table::class,
        'enableVersioning' => true,
        'closed'           => true,
        'notEditable'      => true,
        'notDeletable'     => true,
        'notSortable'      => true,
        'notCopyable'      => true,
        'notCreatable'     => true,
        'sql'              => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list'        => [
        'sorting'           => [
            'mode'        => DataContainer::MODE_SORTABLE,
            'fields'      => ['volume'],
            'flag'        => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label'             => [
            'fields' => ['volume', 'alias', 'title'],
            'format' => '%s | %s | %s',
        ],
        'global_operations' => [
            'all' => [
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations'        => [
            'edit'   => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'copy'   => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
            ],
            'show'   => [
                'href'       => 'act=show',
                'icon'       => 'show.svg',
                'attributes' => 'style="margin-right:3px"',
            ],
        ],
    ],
    'palettes'    => [
        '__selector__' => ['addSubpalette'],
        'default'      => '{basic_legend},alias,title,volume',
    ],
    'subpalettes' => [
        'addSubpalette' => 'textareaField',
    ],
    'fields'      => [
        'id'                       => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp'                   => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'alias'                   => [
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'flag'      => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'      => ['mandatory' => true, 'maxlength' => 13, 'tl_class' => 'w50'],
            'sql'       => "varchar(13) NOT NULL default ''",
        ],
        'title'                  => [
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'eval'      => ['mandatory' => true, 'maxlength' => 512, 'tl_class' => 'w50'],
            'sql'       => "varchar(512) NOT NULL default ''",
        ],
        'volume'              => [
            'inputType' => 'select',
            'exclude'   => true,
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'options'   => MathbuchVolume::ALL,
            'reference' => &$GLOBALS['TL_LANG']['MSC']['mathbuch_volumes'],
            'flag'      => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'      => ['mandatory' => true, 'maxlength' => 6, 'tl_class' => 'w50'],
            'sql'       => "varchar(6) NOT NULL default ''",
        ],
    ],
];
