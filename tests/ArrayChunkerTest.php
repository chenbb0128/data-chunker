<?php

namespace Cbb\Tests;

use Cbb\DataChunker\ArrayChunker;
use Cbb\DataChunker\ItemValue;
use PHPUnit\Framework\TestCase;

class ArrayChunkerTest extends TestCase
{
    public function testEach()
    {
        $array = range(1, 20);
        $chunker = new ArrayChunker($array, count($array));

        $chunkCount = 2;
        $chunker->each(function (ItemValue $item, $chunkIndex, $batchIndex) use ($chunkCount, $array) {
            $currentIndex = $chunkIndex * $chunkCount + $batchIndex;
            $this->assertEquals($array[$currentIndex], $item->getValue());
        })->chunk($chunkCount);
    }

    public function testBatch()
    {
        $array = range(1, 20);
        $chunker = new ArrayChunker($array, count($array));

        $chunkCount = 2;
        $chunker->batch(function ($batch, $chunkIndex) use ($array, $chunkCount) {
            $slice = array_slice($array, $chunkIndex * $chunkCount, $chunkCount);
            $res = [];
            foreach ($batch as $item) {
                $res[] = $item->getValue();
            }
            $this->assertEquals($slice, $res);
        })->chunk($chunkCount);
    }

    public function testLimit()
    {
        $array = range(1, 20);
        $chunker = new ArrayChunker($array, count($array));

        $chunkCount = 2;
        $limit = 5;

        $res = [];
        $chunker->limit($limit)->each(function (ItemValue $item) use (&$res) {
            $res[] = $item->getValue();
        })->chunk($chunkCount);

        $except = array_slice($array, 0, $limit);
        $this->assertEquals($except, $res);
    }
}