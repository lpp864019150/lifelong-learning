# ClickHouse类型转换函数

## 参考博文

1. [类型转换函数](https://clickhouse.com/docs/en/sql-reference/functions/type-conversion-functions)

#### toUInt8

> toUInt8(expr)

```sql
SELECT toUInt64(nan), toUInt32(-32), toUInt16('16'), toUInt8(8.8);
```

类似：toInt8/toString/toFloat32/toDate

通用：CAST(x,t)/CAST(x AS t)/x::t，低版本的使用CAST(x AS t)，其他两个不生效

```sql
SELECT
    CAST(toInt8(-1), 'UInt8') AS cast_int_to_uint,
    CAST(1.5 AS Decimal(3,2)) AS cast_float_to_decimal,
    '1'::Int32 AS cast_string_to_int;
```

output:

```sql
┌─cast_int_to_uint─┬─cast_float_to_decimal─┬─cast_string_to_int─┐
│              255 │                  1.50 │                  1 │
└──────────────────┴───────────────────────┴────────────────────┘
```
