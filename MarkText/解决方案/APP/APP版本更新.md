# APP版本更新

## 参考文档

1. [App版本更新：后台实现策略梳理 | 人人都是产品经理](https://www.woshipm.com/pd/2272834.html)

2. [如何进行APP版本升级管理？ | 人人都是产品经理](https://www.woshipm.com/pd/4038557.html)

## 后台管理

#### 1. 方案一：以历史版本为更新依据的实现策略

1. 记录每一个版本信息，针对每次新版本发布，需维护所有历史版本，每个历史版本需对当前最新版本做差异化更新策略

2. 更新策略有：强制更新、弹窗提示更新、不弹窗提示
   
   ![image](https://image.woshipm.com/wp-files/2019/04/ZSnU6SWDX4wsprjihlay.png)
   
   ![image](https://image.woshipm.com/wp-files/2019/04/yNEcM5m6rhbkINSOvmMz.png)

###### 优点：可以细化到每一个历史版本，不会误伤历史版本

###### 缺点：需每次维护所有历史版本，此处可优化，增加批量修改来减少人工操作成本

#### 2. 方案二：以最新版本为更新依据的实现策略

1. 记录每一个版本信息，针对每次新版本发布，只需指明当前版本的更新策略即可，历史版本根据最新版的更新策略来实现更新

2. 更新策略有：强制更新、弹窗提示更新
   
   ![image](https://image.woshipm.com/wp-files/2019/04/3sIBJfvIvWus8BMSCF6H.png)
   
   ![image](https://image.woshipm.com/wp-files/2019/04/7xcGhG32tP7AbK925YSA.png)
   
   ###### 优点：维护简单，只需指明最新版本的更新策略即可
   
   ###### 缺点：历史版本若错过了中间某个强更版本，会导致漏了强更，此处可优化，若历史版本与最新版之间有一个强更版本，则该版本需强更到最新版
