# ClickHouse时间函数

## 参考博文

1. [Clickhouse 时间日期函数实战（详细）_clickhouse 时间函数_基咯咯的博客-CSDN博客](https://blog.csdn.net/u010180815/article/details/105250864)

## 部分函数

#### 格式化时间：[toDate](https://clickhouse.com/docs/en/sql-reference/functions/type-conversion-functions#todate)

> toDate(timestamp)获取到天的完整date格式，比如toDate(now()) => 2023-07-03

类似的：toWeek/toMonth/toYear, toHour/toMinute/toSecond

可替换的方法：toYYMMDD/toYYMM/toYYMMDDhhmmss

通用时间格式化方法：formatDateTime

常用时间函数

```sql
now()                // 2020-04-01 17:25:40     取当前时间
toYear()             // 2020                    取日期中的年份
toMonth()            // 4                       取日期中的月份
today()              // 2020-04-01              今天的日期
yesterday()          // 2020-03-31              昨天的额日期
toDayOfYear()        // 92                      取一年中的第几天     
toDayOfWeek()        // 3                       取一周中的第几天
toHour()             //17                       取小时
toMinute()           //25                       取分钟
toSecond()           //40                       取秒
toStartOfYear()      //2020-01-01               取一年中的第一天
toStartOfMonth()     //2020-04-01               取当月的第一天

formatDateTime(now(),'%Y-%m-%d')        // 2020*04-01         指定时间格式
toYYYYMM()                              //202004              
toYYYYMMDD()                            //20200401
toYYYYMMDDhhmmss()                      //20200401172540
dateDiff()
......
```

```sql
SELECT
    toDateTime('2019-07-30 10:10:10') AS time,  

    -- 将DateTime转换成Unix时间戳
    toUnixTimestamp(time) as unixTimestamp,  

    -- 保留 时-分-秒
    toDate(time) as date_local,
    toTime(time) as date_time,   -- 将DateTime中的日期转换为一个固定的日期，同时保留时间部分。

    -- 获取年份，月份，季度，小时，分钟，秒钟
    toYear(time) as get_year,
    toMonth(time) as get_month,

    -- 一年分为四个季度。1（一季度:1-3）,2（二季度:4-6）,3（三季度:7-9）,4（四季度:10-12）
    toQuarter(time) as get_quarter,
    toHour(time) as get_hour,
    toMinute(time) as get_minute,
    toSecond(time) as get_second,

    -- 获取 DateTime中的当前日期是当前年份的第几天，当前月份的第几日，当前星期的周几
    toDayOfYear(time) as "当前年份中的第几天",
    toDayOfMonth(time) as "当前月份的第几天",
    toDayOfWeek(time) as "星期",
    toDate(time, 'Asia/Shanghai') AS date_shanghai,
    toDateTime(time, 'Asia/Shanghai') AS time_shanghai,

    -- 得到当前年份的第一天,当前月份的第一天，当前季度的第一天，当前日期的开始时刻
    toStartOfYear(time),
    toStartOfMonth(time),
    toStartOfQuarter(time),
    toStartOfDay(time) AS cur_start_daytime,
    toStartOfHour(time) as cur_start_hour,
    toStartOfMinute(time) AS cur_start_minute,

    -- 从过去的某个固定的时间开始，以此得到当前指定的日期的编号
    toRelativeYearNum(time),
    toRelativeQuarterNum(time);
```

#### 时间差：dateDiff

> dateDiff(type, datetime1, datetime2)，type为时间维度，day表示相差多少天，datetime2 - datetime1的差值
> 
> type：day/week/month/year/hour/minute/second

未来时间：addYears/addMonths/addWeeks/addDays/addHours/addMinutes/addSeconds

过去时间：把上面的add前缀替换为substract即可

```sql
With
    now() as today
SELECT 
    dateDiff('year', today, addYears(today, 1)) as diff_years,
    dateDiff('month', today, addMonths(today, 2)) as diff_months,
    dateDiff('week', today, addWeeks(today, 3)) as diff_weeks,
    dateDiff('day', today, addYears(today, 4)) as diff_days,
    dateDiff('hour', today, addHours(today, 5)) as diff_hours,
    dateDiff('minute', today, addMinutes(today, 6)) as diff_minutes,
    dateDiff('second', today, addSeconds(today, 7)) as diff_seconds;
```
