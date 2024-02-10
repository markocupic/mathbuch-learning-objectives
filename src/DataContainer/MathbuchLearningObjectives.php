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

namespace Markocupic\MathbuchLearningObjectives\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

readonly class MathbuchLearningObjectives
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    #[AsCallback(table: 'tl_mathbuch_learning_objectives', target: 'config.onload', priority: 100)]
    public function onload(DataContainer $dc): void
    {
        // no usage
    }
}
