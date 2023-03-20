<?php

namespace Cbb\DataChunker;

use Cbb\DataChunker\Exceptions\TotalInvalidException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class BaseHandler
{
    /** 需要处理的总数据 **/
    protected $source;

    /** @var int 数据总数 */
    protected $totalCount = 0;
    /** @var bool 是否需要固定批次 */
    protected $fixedChunk = false;

    private $progressBar;

    /** 限制处理的item数量，达到limit的限制后，跳过后续的item */
    private $limit = 0;

    /** @var int 当前处理的批次 */
    protected $chunkIndex = 0;

    /** 单次item处理的方法 */
    protected $itemEachHandler;

    /** 每一批次处理的方法 */
    protected $batchEachHandler;

    /** 过滤条件处理方法，每次chunk都会调用该方法 */
    protected $whereHandler;

    abstract protected function pageChunk(int $chunkCount, callable $callable): bool;

    abstract protected function fixChunk(int $chunkCount, callable $callable): bool;

    /**
     * 设置查询数据固定在第一页，避免使用where查询时，出现数据错乱死循环
     * @return BaseHandler
     */
    public function fixed(): BaseHandler
    {
        $this->fixedChunk = true;
        return $this;
    }

    final public function showProgress(): BaseHandler
    {
        if (!$this->totalCount) {
            // 数量不足
            throw new TotalInvalidException();
        }
        $this->createProgressBar();
        return $this;
    }

    private function createProgressBar()
    {
        $output = new ConsoleOutput();
        $progressBar = new ProgressBar($output, $this->totalCount);
        if ('\\' !== DIRECTORY_SEPARATOR) {
            $progressBar->setEmptyBarCharacter('░');
            $progressBar->setProgressCharacter('');
            $progressBar->setBarCharacter('▓');
        }
        $this->progressBar = $progressBar;
    }

    /**
     * 每批次处理的数量
     * @param int $limit
     * @return $this
     */
    final public function limit(int $limit): BaseHandler
    {
        $this->limit = $limit;
        return $this;
    }

    public function each(callable $callable)
    {
        $this->itemEachHandler = $callable;
        return $this;
    }

    public function batch(callable $callable)
    {
        $this->batchEachHandler = $callable;
        return $this;
    }

    public function where(callable $callable)
    {
        $this->whereHandler = $callable;
        return $this;
    }

    public function chunk(int $chunkCount)
    {
        $callable = function (iterable $collection) use ($chunkCount) {
            $batch = $this->runBatch($collection, $chunkCount);
            $this->chunkIndex++;
            return $batch;
        };

        if ($this->fixedChunk) {
            $chunkResult = $this->fixChunk($chunkCount, $callable);
        } else {
            $chunkResult = $this->pageChunk($chunkCount, $callable);
        }

        if ($this->progressBar) {
            $this->progressBar->finish();
            echo "\n";
        }

        return $chunkResult;
    }

    private function runBatch(iterable $collection, int $chunkCount)
    {
        $batchResult = true;
        if ($this->batchEachHandler) {
            $batchResult = call_user_func_array($this->batchEachHandler, [$collection, $this->chunkIndex]);
        }

        foreach ($collection as $key => $item) {
            $eachResult = $this->runEach($item, $key);
            if ($eachResult === false) {
                break;
            }
            if ($this->limit && ($this->chunkIndex * $chunkCount + $key + 1) >= $this->limit) {
                return false;
            }
            unset($collection);
        }
        return $batchResult;
    }

    private function runEach($item, int $batchIndex)
    {
        $eachResult = true;
        if ($this->itemEachHandler) {
            $eachResult = call_user_func_array($this->itemEachHandler, [$item, $this->chunkIndex, $batchIndex]);
        }
        if ($this->progressBar) {
            $this->progressBar->advance();
        }
        unset($item);
        return $eachResult;
    }
}