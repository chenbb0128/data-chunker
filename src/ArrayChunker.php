<?php

namespace Cbb\DataChunker;

class ArrayChunker extends BaseHandler
{
    public function __construct(array $source, int $total)
    {
        foreach ($source as $item) {
            if (!is_object($item)) {
                $this->source[] = new ItemValue($item);
            } else {
                $this->source[] = $item;
            }
        }
        $this->totalCount = $total;
    }

    protected function pageChunk(int $chunkCount, callable $callable): bool
    {
        return $this->chunkInterval($chunkCount, $callable, false);
    }

    protected function fixChunk(int $chunkCount, callable $callable): bool
    {
        return $this->chunkInterval($chunkCount, $callable, true);
    }

    private function chunkInterval(int $chunkCount, callable $callable, bool $fixed): bool
    {
        $filter  = function () {
            if ($this->whereHandler) {
                $filteredSource = array_filter($this->source, $this->whereHandler);
            } else {
                $filteredSource = $this->source;
            }
            return $filteredSource;
        };

        while(($slice = array_slice($filter(), $fixed ? 0 : ($this->chunkIndex * $chunkCount), $chunkCount))) {
            $eachResult = $callable($slice, $this->chunkIndex);
            if ($eachResult === false) {
                return false;
            }
        }

        return true;
    }
}