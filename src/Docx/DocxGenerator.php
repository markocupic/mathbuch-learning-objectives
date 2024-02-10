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

namespace Markocupic\MathbuchLearningObjectives\Docx;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\MathbuchLearningObjectives\Config\MathbuchAhLevel;
use Markocupic\MathbuchLearningObjectives\Model\MathbuchChaptersModel;
use Markocupic\PhpOffice\PhpWord\MsWordTemplateProcessor;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class DocxGenerator
{
    public function __construct(
        private Connection $connection,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function generate(string $volume, string $level, \SplFileObject $template, string $targetPath): \SplFileObject|null
    {
        // Create phpword instance
        $objPhpWord = new MsWordTemplateProcessor($template->getRealPath(), $targetPath);

        $arrChapters = $this->getChapters($volume, $level);

        $countChapters = \count($arrChapters);

        // Do not generate a file, if there are no objectives
        if (!$countChapters) {
            return null;
        }

        // Clone the main block (a chapter per page)
        $objPhpWord->cloneBlock('BLOCK_CHAPTER', $countChapters, true, true);

        $objPhpWord->setValue('volume', $this->translator->trans('MSC.mathbuch_volumes.'.$volume, [], 'contao_default'), 1);
        $objPhpWord->setValue('level_ah', $this->translator->trans('MSC.ah_level.'.$level, [], 'contao_default'), 1);

        foreach ($arrChapters as $i => $chapter) {
            $index_outer = $i + 1;

            $chapterAlias = sprintf('mb%s_LU%s', $volume, $chapter);
            $chapterTitle = $this->connection->fetchOne('SELECT title FROM tl_mathbuch_chapters WHERE alias = ?',[$chapterAlias]);

            $objPhpWord->setValue('volume_#'.$index_outer, $this->translator->trans('MSC.mathbuch_volumes.'.$volume, [], 'contao_default'), 1);
            $objPhpWord->setValue('chapter_#'.$index_outer, $chapter, 1);
            $objPhpWord->setValue('chapter_title_#'.$index_outer, $chapterTitle, 1);
            $objPhpWord->setValue('level_ah_#'.$index_outer, $this->translator->trans('MSC.ah_level.'.$level, [], 'contao_default'), 1);

            // Load objectives of the current chapter from database
            $arrObjectives = $this->getObjectives($volume, $chapter, $level);

            if (empty($arrObjectives)) {
                throw new \LogicException(sprintf('This point should never be reached. We do not clone a page, if a chapter has no objectives. Volume: %s Chapter: %s', $volume, $chapter));
            }

            $arrBasicObjectives = [];
            $arrExtendedObjectives = [];

            foreach ($arrObjectives as $rowObjective) {
                if (MathbuchAhLevel::AH_BASIC === $level) {
                    if ($rowObjective['level_basic']) {
                        $arrBasicObjectives[] = $rowObjective['objective_text'];
                    }

                    if ($rowObjective['extended_objective_basic']) {
                        $arrExtendedObjectives[] = $rowObjective['objective_text'];
                    }
                }

                if (MathbuchAhLevel::AH_PLUS === $level) {
                    if ($rowObjective['level_plus']) {
                        $arrBasicObjectives[] = $rowObjective['objective_text'];
                    }

                    if ($rowObjective['extended_objective_plus']) {
                        $arrExtendedObjectives[] = $rowObjective['objective_text'];
                    }
                }
            }

            if (empty($arrBasicObjectives)) {
                // $objPhpWord->deleteBlock() doesn't work!!!
                $objPhpWord->cloneBlock('BLOCK_OBJECTIVES_#'.$index_outer, 0, true, true);
            } else {
                $objPhpWord->cloneBlock('BLOCK_OBJECTIVES_#'.$index_outer, \count($arrBasicObjectives), true, true);

                foreach ($arrBasicObjectives as $ii => $objective) {
                    $index_inner = $ii + 1;
                    $objPhpWord->setValue('objective_text_#'.$index_outer.'#'.$index_inner, $this->formatMultilineText($objective), 1);
                }
            }

            if (empty($arrExtendedObjectives)) {
                // $objPhpWord->deleteBlock() doesn't work!!!
                $objPhpWord->cloneBlock('BLOCK_EXTENDED_OBJECTIVES_#'.$index_outer, 0, true, true);
            } else {
                $objPhpWord->cloneBlock('BLOCK_EXTENDED_OBJECTIVES_#'.$index_outer, \count($arrExtendedObjectives), true, true);

                foreach ($arrExtendedObjectives as $ii => $objective) {
                    $index_inner = $ii + 1;
                    $objPhpWord->setValue('objective_text_extended_#'.$index_outer.'#'.$index_inner, $this->formatMultilineText($objective), 1);
                }
            }
        }

        return $objPhpWord->generate();
    }

    /**
     * @throws Exception
     */
    private function getChapters(string $volume, string $level): array
    {
        $qb = $this->connection->createQueryBuilder();

        $qb->select('chapter')
            ->from('tl_mathbuch_learning_objectives', 't')
            ->where('t.volume = :volume')
            ->setParameter('volume', $volume)
        ;

        if (MathbuchAhLevel::AH_BASIC === $level) {
            $qb->andWhere('t.level_basic = "1" OR t.extended_objective_basic = "1"');
        } elseif (MathbuchAhLevel::AH_PLUS === $level) {
            $qb->andWhere('t.level_plus = "1" OR t.extended_objective_plus = "1"');
        }

        $qb->groupBy('t.chapter');
        $qb->orderBy('t.chapter');

        return $qb->fetchFirstColumn();
    }

    /**
     * @throws Exception
     */
    private function getObjectives(string $volume, int $chapter, string $level): array
    {
        $qb = $this->connection->createQueryBuilder();

        $qb->select('*')
            ->from('tl_mathbuch_learning_objectives', 't')
            ->where('t.volume = :volume')
            ->andWhere('t.chapter = :chapter')
            ->setParameter('volume', $volume)
            ->setParameter('chapter', $chapter)
    ;

        if (MathbuchAhLevel::AH_BASIC === $level) {
            $qb->andWhere('t.level_basic = "1" OR t.extended_objective_basic = "1"');
        } elseif (MathbuchAhLevel::AH_PLUS === $level) {
            $qb->andWhere('t.level_plus = "1" OR t.extended_objective_plus = "1"');
        }

        $qb->orderBy('t.volume', 'ASC');
        $qb->orderBy('t.chapter', 'ASC');
        $qb->addOrderBy('t.id', 'ASC');
        $qb->addOrderBy('t.level_basic', 'ASC');
        $qb->addOrderBy('t.level_plus', 'ASC');
        $qb->addOrderBy('t.extended_objective_basic', 'ASC');
        $qb->addOrderBy('t.extended_objective_plus', 'ASC');

        return $qb->fetchAllAssociative();
    }

    private function formatMultilineText(string $text): string
    {
        return preg_replace('~\R~u', '</w:t><w:br/><w:t>', $text);
    }
}
