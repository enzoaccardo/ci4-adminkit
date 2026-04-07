<?php

namespace AdminKit\Traits;

trait HasPagination
{
    protected function getPage(): int
    {
        return max(1, (int) $this->request->getGet('page'));
    }

    protected function getPerPage(int $default = 20, int $max = 200): int
    {
        $perPage = (int) $this->request->getGet('per_page');

        if ($perPage <= 0) {
            return $default;
        }

        return min($perPage, $max);
    }

    protected function paginationOffset(int $page, int $perPage): int
    {
        return ($page - 1) * $perPage;
    }

    protected function assignPagination(int $total, int $perPage, int $page): void
    {
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
        $offset     = $this->paginationOffset($page, $perPage);

        $this->assign('pagination', (object) [
            'total'      => $total,
            'perPage'    => $perPage,
            'page'       => $page,
            'totalPages' => $totalPages,
            'hasPrev'    => $page > 1,
            'hasNext'    => $page < $totalPages,
            'from'       => $total > 0 ? $offset + 1 : 0,
            'to'         => min($page * $perPage, $total),
            'rangeStart' => max(1, $page - 2),
            'rangeEnd'   => min($totalPages, $page + 2),
        ]);
    }
}