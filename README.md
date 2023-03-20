# data-chunker

分批处理array，db-query的数据，提高db查询速度

## Installation
`composer require cbb/data-chunker`

## Example

### ArrayChunker

```php
    <?php
 
    use Cbb\DataChunker\ArrayChunker;
 
    $arr = [1, 2, 3, 4, 5, 6, 7];
    $chunker = new ArrayChunker($arr, count($arr));
    $chunker->batch(function($batch) {
        // $batch => [1, 2], [3, 4], [5, 6], [7]
    })->each(function($item) {
        // $item => 1, 2, 3, 4, 5, 6, 7
    })->chunk(2);
```

```php
    <?php
 
    use Cbb\DataChunker\QueryChunker;
 
    $query = User::query(); // or Db::table('user');
    $chunker = new QueryChunker($query, $query->count());
    $chunker->batch(function($batch) {
        // // $batch => Collection|User[]
    })->each(function(User $user) {
        // $user => user的一条数据
    })->chunk(2);
```


## Other Example
1. progress-bar(进度条)
```php
    <?php
    // 可以在最后拼接上，用于命令行模式下显示进度条，只能用在命令行中
    $chunker->showProgress()->chunk(3);
```

2. fixed(固定第一页，需要配合where使用)
```php
    <?php
    
    $query = User::query()->where('status', 1);
    $chunker = new QueryChunker($query, $query->count()); 
    // 当查询数据中的数据变化，因为本次不符合条件的数据已经更新跳过，所以第一页就是需要更新的数据
    $chunker->fixed()->each(function (User $user) {
        $user->status = 0;
        $user->save();
    })->chunk(3);

```