# ClickHouse JSON函数

## 参考博文

1. [JSON函数](https://clickhouse.com/docs/en/sql-reference/functions/json-functions)

#### 提取：JSONExtractString

> JSONExtractString(json[, indices_or_keys]…)
> 
> extract是提取的意思

```sql
SELECT JSONExtractString('{"a": "hello", "b": [-100, 200.0, 300]}', 'a') // 'hello'
SELECT JSONExtractString('{"abc":"\\n\\u0000"}', 'abc') // '\n\0'
SELECT JSONExtractString('{"abc":"\\u263a"}', 'abc') // '☺'
SELECT JSONExtractString('{"abc":"\\u263"}', 'abc') // ''
SELECT JSONExtractString('{"abc":"hello}', 'abc') // ''
```

类似：JSONExtractInt/JSONExtractUInt/JSONExtractFloat/JSONExtractBool

通用：JSONExtract，可动态指定需要提取的类型；JSONExtractRaw，提取原字符串

```sql
with
    '{"str":"a","int":8,"arr":[1,2,3]}' as json
select JSONExtract(json, 'str', 'String') as str,
    JSONExtract(json, 'int', 'UInt8') as int,
    JSONExtract(json, 'arr', 'Array(UInt8)') as arr, -- 数组，指定值的类型
    JSONExtractArrayRaw(json, 'arr') as arr2, -- 数组，值字符串
    JSONExtractRaw(json, 'arr') as arr3 -- 字符串
;
```

output

```sql
str|int|arr    |arr2         |arr3   |
---+---+-------+-------------+-------+
a  |  8|[1,2,3]|['1','2','3']|[1,2,3]|
```

#### JSON_QUERY

> JSON_QUERY(json, path)

类似：JSON_VALUE

```sql
SELECT JSON_QUERY('{"hello":"world"}', '$.hello');
SELECT JSON_QUERY('{"array":[[0, 1, 2, 3, 4, 5], [0, -1, -2, -3, -4, -5]]}', '$.array[*][0 to 2, 4]');
SELECT JSON_QUERY('{"hello":2}', '$.hello');
SELECT toTypeName(JSON_QUERY('{"hello":2}', '$.hello'));
```

output：

```sql
["world"]
[0, 1, 4, 0, -1, -4]
[2]
String
```
