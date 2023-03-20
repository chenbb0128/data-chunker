<?php

namespace Cbb\DataChunker;

use Illuminate\Database\Eloquent\Builder;

class QueryChunker extends BaseHandler
{
    public function __construct(Builder $source, $total)
    {
        $this->source = $source;
        $this->totalCount = $total;
    }

    protected function pageChunk(int $chunkCount, callable $callable): bool
    {
        if ($this->whereHandler) {
            $query = call_user_func_array($this->whereHandler, [$this->source]);
        } else {
            $query = $this->source;
        }
        return $query->chunk($chunkCount, $callable);
    }

    protected function fixChunk(int $chunkCount, callable $callable): bool
    {
        // 永远取第一页的数据，因为上一个第一页已经被更新数据了，不满足条件
        $page = 1;
        do {
            if ($this->whereHandler) {
                $query = call_user_func_array($this->whereHandler, [$this->source]);
            } else {
                $query = $this->source;
            }
            $result = $query->forPage($page, $chunkCount)->get();
            $count = $result->count();
            if (!$count) {
                break;
            }

            if ($callable($result, $this->chunkIndex) === false) {
                return false;
            }
            unset($result);
        } while($count === $chunkCount);

        return true;
    }
}