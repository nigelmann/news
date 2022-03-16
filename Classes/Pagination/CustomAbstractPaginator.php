<?php

declare(strict_types=1);

/**
 * This file is part of the "news" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace GeorgRinger\News\Pagination;

use TYPO3\CMS\Core\Pagination\AbstractPaginator;

abstract class CustomAbstractPaginator extends AbstractPaginator
{

    /**
     * @var int
     */
    private $currentPageNumber = 1;

    /**
     * @var int
     */
    private $itemsPerPage = 10;

    protected int $initialOffset = 0;
    protected int $initialLimit = 0;

    /**
     * @param int $initialOffset
     */
    public function setInitialLimitOffset(int $initialLimit = 0, int $initialOffset = 0): void
    {
        $this->initialOffset = $initialOffset;
        $this->initialLimit = $initialLimit;
        if ($initialOffset > 0 || $this->initialLimit > 0) {
            $this->updateInternalState();
        }
    }

    /**
     * This method is the heart of the pagination. It updates all internal params and then calls the
     * {@see updatePaginatedItems} method which must update the set of paginated items.
     */
    protected function updateInternalState(): void
    {
        // offset
        if ($this->currentPageNumber > 1) {
            $offset = (int)($this->itemsPerPage * ($this->currentPageNumber - 1));
            $offset += $this->initialOffset;
        } elseif ($this->initialOffset > 0) {
            $offset = $this->initialOffset;
        } else {
            $offset = 0;
        }

        // limit
        if ($this->currentPageNumber === $this->numberOfPages && $this->initialLimit > 0) {
            $difference = $this->initialLimit - ((integer)($this->itemsPerPage * ($this->currentPageNumber - 1)));
            if ($difference > 0) {
                $this->itemsPerPage = $difference;
            }
        }

        $totalAmountOfItems = $this->getTotalAmountOfItems();

        /*
         * If the total amount of items is zero, then the number of pages is mathematically zero as
         * well. As that looks strange in the frontend, the number of pages is forced to be at least
         * one.
         */
        $this->numberOfPages = max(1, (int)ceil($totalAmountOfItems / $this->itemsPerPage));

        /*
         * To prevent empty results in case the given current page number exceeds the maximum number
         * of pages, we set the current page number to the last page and update the internal state
         * with this value again. Such situation should in the first place be prevented by not allowing
         * those values to be passed, e.g. by using the "max" attribute in the view. However there are
         * valid cases. For example when a user deletes a record while the pagination is already visible
         * to another user with, until then, a valid "max" value. Passing invalid values unintentionally
         * should therefore just silently be resolved.
         */
        if ($this->currentPageNumber > $this->numberOfPages) {
            $this->currentPageNumber = $this->numberOfPages;
            $this->updateInternalState();
            return;
        }

        $this->updatePaginatedItems($this->itemsPerPage, $offset);

        if (!$this->hasItemsOnCurrentPage()) {
            $this->keyOfFirstPaginatedItem = 0;
            $this->keyOfLastPaginatedItem = 0;
            return;
        }

        $indexOfLastPaginatedItem = min($offset + $this->itemsPerPage, $totalAmountOfItems);

        $this->keyOfFirstPaginatedItem = $offset;
        $this->keyOfLastPaginatedItem = $indexOfLastPaginatedItem - 1;
    }
}