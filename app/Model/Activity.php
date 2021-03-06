<?php
App::uses('AppModel', 'Model');
/**
 * Activity Model
 *
 * @property Gift $Gift
 * @property GiveawayClaim $GiveawayClaim
 * @property Liquidation $Liquidation
 * @property Order $Order
 * @property PaypalOrder $PaypalOrder
 * @property Review $Review
 * @property Reward $Reward
 * @property Shipment $Shipment
 */
class Activity extends AppModel {

    public $findMethods = array('byUser' => true, 'byItem' => true);

    public $useTable = 'activity';
    public $primaryKey = 'activity_id';

    public $hasOne = array(
        'Gift', 'GiveawayClaim', 'Liquidation', 'Order', 'PaypalOrder',
        'Review', 'Reward', 'Shipment'
    );

    public $order = 'Activity.activity_id desc';

    /**
     * Inserts an Activity record with the specified $modelName and returns the new unique id.
     *
     * @param string $modelName the name of the model for which to create the Activity
     * @return int the generated id
     */
    public function getNewId($modelName) {
        return $this->query('select NewActivity(' . $this->getDataSource()->value($modelName) . ') as id', false)[0][0]['id'];
    }

    function _findCount($state, $query, $results = array()) {
        if ($state == 'before') {
            if (isset($query['type']) && $query['type'] != 'count') {
                $query = $this->{'_find' . ucfirst($query['type'])}($state, $query);
            }
            return parent::_findCount($state, $query);
        }
        if (isset($query['type']) && isset($this->findMethods[$query['type']]) && $this->findMethods[$query['type']]) {
            return $this->getDataSource()->fetchAll("select count(*) as count from ({$query['raw']}) as Activity")[0][0]['count'];
        }
        return parent::_findCount($state, $query, $results);
    }

    /*
     * Overriden findType for `findType => byItem`. Used for getting activity for Items.
     */
    public function _findByItem($state, $query, $results = array()) {

        if ($state == 'before' && (!isset($query['operation']) || $query['operation'] != 'count')) {
            return $query;
        }

        $item_id = $query['item_id'];

        $db = $this->getDataSource();

        $orderQuery = $db->buildStatement(array(
            'fields' => array(
                "Order.order_id as activity_id, 'Order' as model"
            ),
            'table' => $db->fullTableName($this->Order),
            'alias' => 'Order',
            'conditions' => array(
                'OrderDetail.item_id' => $item_id
            ),
            'joins' => array(
                array(
                    'table' => $db->fullTableName($this->Order->OrderDetail),
                    'alias' => 'OrderDetail',
                    'conditions' => array(
                        'Order.order_id = OrderDetail.order_id'
                    )
                )
            )
        ), $this->Order);

        $liquidationQuery = $db->buildStatement(array(
            'fields' => array(
                "Liquidation.liquidation_id as activity_id, 'Liquidation' as model"
            ),
            'table' => $db->fullTableName($this->Liquidation),
            'alias' => 'Liquidation',
            'conditions' => array(
                'LiquidationDetail.item_id' => $item_id
            ),
            'joins' => array(
                array(
                    'table' => $db->fullTableName($this->Liquidation->LiquidationDetail),
                    'alias' => 'LiquidationDetail',
                    'conditions' => array(
                        'Liquidation.liquidation_id = LiquidationDetail.liquidation_id'
                    )
                )
            )
        ), $this->Liquidation);

        $giftQuery = $db->buildStatement(array(
            'fields' => array(
                "Gift.gift_id as activity_id, 'Gift' as model"
            ),
            'table' => $db->fullTableName($this->Gift),
            'alias' => 'Gift',
            'conditions' => array(
                'GiftDetail.item_id' => $item_id
            ),
            'joins' => array(
                array(
                    'table' => $db->fullTableName($this->Gift->GiftDetail),
                    'alias' => 'GiftDetail',
                    'conditions' => array(
                        'Gift.gift_id = GiftDetail.gift_id'
                    )
                )
            )
        ), $this->Gift);

        $rewardQuery = $db->buildStatement(array(
            'fields' => array(
                "Reward.reward_id as activity_id, 'Reward' as model"
            ),
            'table' => $db->fullTableName($this->Reward),
            'alias' => 'Reward',
            'conditions' => array(
                'RewardDetail.item_id' => $item_id
            ),
            'joins' => array(
                array(
                    'table' => $db->fullTableName($this->Reward->RewardDetail),
                    'alias' => 'RewardDetail',
                    'conditions' => array(
                        'Reward.reward_id = RewardDetail.reward_id'
                    )
                )
            )
        ), $this->Reward);

        $reviewQuery = $db->buildStatement(array(
            'fields' => array(
                "review_id as activity_id, 'Review' as model"
            ),
            'table' => $db->fullTableName($this->Review),
            'alias' => 'Review',
            'conditions' => array(
                'Rating.item_id' => $item_id
            ),
            'joins' => array(
                array(
                    'table' => $db->fullTableName($this->Review->Rating),
                    'alias' => 'Rating',
                    'conditions' => array(
                        'Review.rating_id = Rating.rating_id'
                    )
                )
            ),
        ), $this->Review);

        $shipmentQuery = $db->buildStatement(array(
            'fields' => array(
                "Shipment.shipment_id as activity_id, 'Shipment' as model"
            ),
            'table' => $db->fullTableName($this->Shipment),
            'alias' => 'Shipment',
            'conditions' => array(
                'ShipmentDetail.item_id' => $item_id
            ),
            'joins' => array(
                array(
                    'table' => $db->fullTableName($this->Shipment->ShipmentDetail),
                    'alias' => 'ShipmentDetail',
                    'conditions' => array(
                        'Shipment.shipment_id = ShipmentDetail.shipment_id'
                    )
                )
            )
        ), $this->Shipment);

        $giveawayClaimQuery = $db->buildStatement(array(
            'fields' => array(
                "GiveawayClaim.giveaway_claim_id as activity_id, 'GiveawayClaim' as model"
            ),
            'table' => $db->fullTableName($this->GiveawayClaim),
            'alias' => 'GiveawayClaim',
            'conditions' => array(
                'GiveawayClaimDetail.item_id' => $item_id
            ),
            'joins' => array(
                array(
                    'table' => $db->fullTableName($this->GiveawayClaim->GiveawayClaimDetail),
                    'alias' => 'GiveawayClaimDetail',
                    'conditions' => array(
                        'GiveawayClaim.giveaway_claim_id = GiveawayClaimDetail.giveaway_claim_id'
                    )
                )
            )
        ), $this->GiveawayClaim);

        if (isset($query['limit'])) {
            $offset = isset($query['offset']) ? $query['offset'] : 0;
            $limit = "limit $offset,{$query['limit']}";
        } else {
            $limit = '';
        }

        $unions = implode(' union all ', array(
            $orderQuery,
            $liquidationQuery,
            $giftQuery,
            $rewardQuery,
            $reviewQuery,
            $shipmentQuery,
            $giveawayClaimQuery
        ));

        $rawQuery = "select * from ($unions) as Activity order by activity_id desc $limit";

        if ($state == 'before' && isset($query['operation']) && $query['operation'] == 'count') {
            $query['raw'] = $rawQuery;
            return $query;
        }

        return $db->fetchAll($rawQuery);
    }

    /*
     * Overriden findType for `findType => byUser`. Used for getting activity for Users.
     */
    public function _findByUser($state, $query, $results = array()) {

        if ($state == 'before' && (!isset($query['operation']) || $query['operation'] != 'count')) {
            return $query;
        }

        $user_id = $query['user_id'];

        $db = $this->getDataSource();

        $orderQuery = $db->buildStatement(array(
            'fields' => array(
                "order_id as activity_id, 'Order' as model"
            ),
            'table' => $db->fullTableName($this->Order),
            'alias' => 'Order',
            'conditions' => array(
                'user_id' => $user_id
            )
        ), $this->Order);

        $liquidationQuery = $db->buildStatement(array(
            'fields' => array(
                "liquidation_id as activity_id, 'Liquidation' as model"
            ),
            'table' => $db->fullTableName($this->Liquidation),
            'alias' => 'Liquidation',
            'conditions' => array(
                'user_id' => $user_id
            )
        ), $this->Liquidation);

        $paypalQuery = $db->buildStatement(array(
            'fields' => array(
                "paypal_order_id as activity_id, 'PaypalOrder' as model"
            ),
            'table' => $db->fullTableName($this->PaypalOrder),
            'alias' => 'PaypalOrder',
            'conditions' => array(
                'user_id' => $user_id
            )
        ), $this->PaypalOrder);

        $giftQuery = $db->buildStatement(array(
            'fields' => array(
                "gift_id as activity_id, 'Gift' as model"
            ),
            'table' => $db->fullTableName($this->Gift),
            'alias' => 'Gift',
            'conditions' => array(
                'OR' => array(
                    'AND' => array_merge(
                        array('sender_id' => $user_id),
                        $query['showAnonymousGiftSenders'] ? array() : array('anonymous = 0')
                    ),
                    'recipient_id' => $user_id
                )
            )
        ), $this->Gift);

        $rewardQuery = $db->buildStatement(array(
            'fields' => array(
                "reward_id as activity_id, 'Reward' as model"
            ),
            'table' => $db->fullTableName($this->Reward->RewardRecipient),
            'alias' => 'RewardRecipient',
            'conditions' => array(
                'RewardRecipient.recipient_id' => $user_id
            )
        ), $this->Reward->RewardRecipient);

        $reviewQuery = $db->buildStatement(array(
            'fields' => array(
                "review_id as activity_id, 'Review' as model"
            ),
            'table' => $db->fullTableName($this->Review),
            'alias' => 'Review',
            'conditions' => array(
                'Rating.user_id' => $user_id
            ),
            'joins' => array(
                array(
                    'table' => $db->fullTableName($this->Review->Rating),
                    'alias' => 'Rating',
                    'conditions' => array(
                        'Review.rating_id = Rating.rating_id'
                    )
                )
            )
        ), $this->Review);

        $giveawayClaimQuery = $db->buildStatement(array(
            'fields' => array(
                "GiveawayClaim.giveaway_claim_id as activity_id, 'GiveawayClaim' as model"
            ),
            'table' => $db->fullTableName($this->GiveawayClaim),
            'alias' => 'GiveawayClaim',
            'conditions' => array(
                'GiveawayClaim.user_id' => $user_id
            )
        ), $this->GiveawayClaim);

        if (isset($query['limit'])) {
            $offset = isset($query['offset']) ? $query['offset'] : 0;
            $limit = "limit $offset,{$query['limit']}";
        } else {
            $limit = '';
        }

        $unions = implode(' union all ', array(
            $orderQuery,
            $liquidationQuery,
            $paypalQuery,
            $giftQuery,
            $rewardQuery,
            $reviewQuery,
            $giveawayClaimQuery
        ));

        $rawQuery = "select * from ($unions) as Activity order by activity_id desc $limit";

        if ($state == 'before' && isset($query['operation']) && $query['operation'] == 'count') {
            $query['raw'] = $rawQuery;
            return $query;
        }

        return $db->fetchAll($rawQuery);
    }

    /**
     * Given a list of Activity records, this extracts the ids and models and then runs a query on
     * each of those models to get their full records. The details of each model are then flattened
     * into the `item_id => quantity` format for easy consumption.
     *
     * @param array $data list of Activity records containing `activity_id` and `model`
     * @return array
     */
    public function getRecent($data) {

        $modelIds = Hash::reduce($data, '{n}.Activity', function($idMap, $activity) {
            $modelName = $activity['model'];
            $id = $activity['activity_id'];

            if (empty($idMap[$modelName])) {
                $idMap[$modelName] = array($id);
            } else {
                array_push($idMap[$modelName], $id);
            }

            return $idMap;
        }, array());

        $activities = array();

        if (!empty($modelIds['Order'])) {
            $activities = array_merge(
                $activities,
                $this->Order->find('all', array(
                    'conditions' => array(
                        'order_id' => $modelIds['Order']
                    ),
                    'contain' => 'OrderDetail'
                )
            ));
        }

        if (!empty($modelIds['Liquidation'])) {
            $activities = array_merge(
                $activities,
                $this->Liquidation->find('all', array(
                    'conditions' => array(
                        'liquidation_id' => $modelIds['Liquidation']
                    ),
                    'contain' => 'LiquidationDetail'
                ))
            );
        }

        if (!empty($modelIds['PaypalOrder'])) {
            $activities = array_merge(
                $activities,
                $this->PaypalOrder->find('all', array(
                    'conditions' => array(
                        'paypal_order_id' => $modelIds['PaypalOrder']
                    )
                )
            ));
        }

        if (!empty($modelIds['Gift'])) {
            $activities = array_merge(
                $activities,
                $this->Gift->find('all', array(
                    'conditions' => array(
                        'gift_id' => $modelIds['Gift']
                    ),
                    'contain' => 'GiftDetail'
                )
            ));
        }

        if (!empty($modelIds['Reward'])) {
            $activities = array_merge(
                $activities,
                $this->Reward->find('all', array(
                        'conditions' => array(
                            'reward_id' => $modelIds['Reward']
                        ),
                        'contain' => array(
                            'RewardDetail',
                            'RewardRecipient'
                        )
                    )
            ));
        }

        if (!empty($modelIds['Review'])) {
            $activities = array_merge($activities, $this->Review->find('all', array(
                'fields' => array(
                    'review_id', 'rating_id', 'created as date', 'created', 'modified', 'content'
                ),
                'conditions' => array(
                    'review_id' => $modelIds['Review']
                ),
                'contain' => array(
                    'Rating' => array(
                        'fields' => array(
                            'rating_id', 'item_id', 'user_id', 'rating'
                        )
                    )
                )
            )));
        }

        if (!empty($modelIds['Shipment'])) {
            $activities = array_merge(
                $activities,
                $this->Shipment->find('all', array(
                        'conditions' => array(
                            'shipment_id' => $modelIds['Shipment']
                        ),
                        'contain' => 'ShipmentDetail',
                    )
            ));
        }

        if (!empty($modelIds['GiveawayClaim'])) {
            $activities = array_merge(
                $activities,
                $this->GiveawayClaim->find('all', array(
                        'fields' => array(
                            'Giveaway.name', 'GiveawayClaim.user_id', 'GiveawayClaim.date'
                        ),
                        'conditions' => array(
                            'giveaway_claim_id' => $modelIds['GiveawayClaim']
                        ),
                        'contain' => array(
                            'Giveaway',
                            'GiveawayClaimDetail'
                        )
                    ))
            );
        }


        $activities = Hash::sort($activities, '{n}.{s}.date', 'desc');

        // squash all details to `item_id => quantity`
        foreach ($activities as &$activity) {

            if (isset($activity['Order'])) {

                $subTotal = 0;

                foreach ($activity['OrderDetail'] as $detail) {
                    $subTotal += $detail['price'] * $detail['quantity'];
                }

                $activity['Order']['subTotal'] = $subTotal;

                $activity['OrderDetail'] = Hash::combine(
                    $activity['OrderDetail'],
                    '{n}.item_id', '{n}.quantity'
                );

            } else if (isset($activity['Liquidation'])) {

                $total = 0;

                foreach ($activity['LiquidationDetail'] as $detail) {
                    $total += $detail['price'] * $detail['quantity'];
                }

                $activity['Liquidation']['total'] = $total;

                $activity['LiquidationDetail'] = Hash::combine(
                    $activity['LiquidationDetail'],
                    '{n}.item_id', '{n}.quantity'
                );

            } else if (isset($activity['GiftDetail'])) {

                $activity['GiftDetail'] = Hash::combine(
                    $activity['GiftDetail'],
                    '{n}.item_id', '{n}.quantity'
                );

            } else if (isset($activity['RewardDetail'])) {

                $activity['RewardDetail'] = Hash::combine(
                    $activity['RewardDetail'],
                    '{n}.item_id', '{n}.quantity'
                );

                // add cash as item_id 0
                $activity['RewardDetail'][0] = $activity['Reward']['credit'];

            } else if (isset($activity['ShipmentDetail'])) {

                $activity['ShipmentDetail'] = Hash::combine(
                    $activity['ShipmentDetail'],
                    '{n}.item_id', '{n}.quantity'
                );

            } else if (isset($activity['GiveawayClaimDetail'])) {

                $activity['GiveawayClaimDetail'] = Hash::combine(
                    $activity['GiveawayClaimDetail'],
                    '{n}.item_id', '{n}.quantity'
                );
            }
        }

        return $activities;
    }

    /**
     * Returns a query that can be used to fetch a page of activity for a specific item.
     *
     * Note: This does not return data; it simply returns the query as an array.
     *
     * @param int $item_id
     * @param int $limit optional limit for number of events to return
     * @return array
     */
    public function getItemPageQuery($item_id, $limit = 5) {

        return array(
            'Activity' => array(
                'findType' => 'byItem',
                'item_id' => $item_id,
                'limit' => $limit
            )
        );
    }

    /**
     * Returns a query that can be used to fetch a page of activity for a specific user.
     *
     * Note: This does not return data; it simply returns the query as an array.
     *
     * @param int $user_id
     * @param bool $showAnonymousGiftSenders whether the current user should see anonymous gifts
     * @param int $limit optional limit for number of events to return
     * @return array
     */
    public function getUserPageQuery($user_id, $showAnonymousGiftSenders = false, $limit = 5) {

        return array(
            'Activity' => array(
                'findType' => 'byUser',
                'user_id' => $user_id,
                'showAnonymousGiftSenders' => $showAnonymousGiftSenders,
                'limit' => $limit
            )
        );
    }

    /**
     * Returns a query that can be used to fetch a page of global activity.
     *
     * @param int $limit optional limit for number of events to return
     * @return array
     */
    public function getGlobalPageQuery($limit = 5) {

        return array(
            'Activity' => array(
                'limit' => $limit
            )
        );
    }
}
