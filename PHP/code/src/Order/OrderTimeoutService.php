<?php

namespace Lpp\Order;

/**
 * 订单超时处理
 *
 * // 一、用户侧
 * 1. 订单入库时，若未支付，则塞入队列，sorted set
 *
 * // 二、定时任务
 * 1. 获取超时订单
 * 2. 取消订单
 * 2.1 更新订单状态
 * 2.2 更新订单商品状态
 * 3. 从队列删除已处理订单
 * 4. 记录日志
 * 5. 发送通知，可以考虑异步，先批量塞入队列
 *
 * // 三、再开一个定时任务，处理由于异常导致的未处理订单，作为补偿
 * 1. 轮询昨天一天的订单，如果超时，则塞入队列
 *
 * // 四、在所有订单查询里，加上超时订单的判断，如果有，则塞入队列
 *
 * // 五、此处有用到发送消息异步队列，需要先启动消息队列
 *
 */
class OrderTimeoutService
{
    private $redis;
    private $db;
    private $logger;


    /**
     * 发送消息key
     * @var string
     */
    private string $sendMsgKey = 'time_wheel:order:msg';
    /**
     * 超时订单key
     * @var string
     */
    private string $timeWheelKey = 'time_wheel:order:timeout';
    /**
     * 超时时间
     * @var int
     */
    private int $timeout;

    /**
     * 超时开始时间
     * @var int
     */
    private int $timeoutStart;

    private array $timeoutOrderIds = [];

    public function __construct($timeout = 1800)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);

        $this->redis = $redis;
        $this->db = null;
        $this->logger = logger('order');

        $this->timeout = $timeout;
    }

    /**
     * 设置订单超时时间
     * @param $orderId
     * @return void
     * @throws \RedisException
     */
    public function setOrderTimeout($orderId)
    {
        $this->redis->zadd($this->timeWheelKey, time() + $this->timeout, $orderId);
    }

    // 处理超时订单
    public function dealOrderTimeout()
    {
        // 1. 获取超时订单
        $this->getTimeoutOrder();

        // 2. 取消订单
        $this->cancelOrder();
    }

    // 获取超时订单
    public function getTimeoutOrder()
    {
        $this->timeoutStart = time();
        $this->timeoutOrderIds = $this->redis->zrangebyscore($this->timeWheelKey, 0, $this->timeoutStart);
    }

    // 取消订单
    function cancelOrder()
    {
        if (empty($this->timeoutOrderIds)) return;

        $this->db->begin();
        try {
            // 1. 先判断当前订单状态是否可取消

            // 2. 更新订单状态

            // 3. 更新订单商品状态

            $this->db->execute("UPDATE `order` SET status = 3 WHERE id IN (".implode(',', $this->timeoutOrderIds).")");
            $this->db->execute("UPDATE `order_item` SET status = 3 WHERE order_id IN (".implode(',', $this->timeoutOrderIds).")");
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->logger->error($e->getMessage());

            return;
        }

        // 4. 记录日志
        $this->logger->info('订单超时取消', ['order_ids' => $this->timeoutOrderIds]);

        // 5. 从redis删除已处理订单
        $this->rmTimeoutOrder();

        // 6. 发送消息
        $this->sendMsg2Redis();
    }

    // 删除已处理的订单
    public function rmTimeoutOrder()
    {
        $this->redis->zremrangebyscore($this->timeWheelKey, 0, $this->timeoutStart);
    }

    // 异步发送消息，先塞入队列
    public function sendMsg2Redis()
    {
        if (empty($this->timeoutOrderIds)) return;

        $this->redis->Rpush($this->sendMsgKey, igbinary_serialize($this->timeoutOrderIds));
    }

    // 发送消息
    public function sendMsg()
    {
        if (empty($this->timeoutOrderIds)) return;

        $orderIds = implode(',', $this->timeoutOrderIds);
        $orderItems = $this->db->query("SELECT * FROM `order_item` WHERE order_id IN ({$orderIds})")->fetchAll();
        $orderItems = array_column($orderItems, null, 'order_id');

        $orderIds = implode(',', array_keys($orderItems));
        $orders = $this->db->query("SELECT * FROM `order` WHERE id IN ({$orderIds})")->fetchAll();
        $orders = array_column($orders, null, 'id');

        $userIds = array_column($orders, 'user_id');
        $userIds = implode(',', $userIds);
        $users = $this->db->query("SELECT * FROM `user` WHERE id IN ({$userIds})")->fetchAll();
        $users = array_column($users, null, 'id');

        $msg = [];
        foreach ($orderItems as $orderId => $orderItem) {
            $order = $orders[$orderId];
            $user = $users[$order['user_id']];

            $msg[] = [
                'user_id' => $user['id'],
                'title' => '订单超时取消',
                'content' => "您的订单{$order['order_no']}已超时取消",
                'type' => 1,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        $this->db->insert('message', $msg);

        // 记录日志
        $this->logger->info('订单超时取消发送消息', ['order_ids' => $this->timeoutOrderIds]);
    }
}