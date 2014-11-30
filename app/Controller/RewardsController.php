<?php
App::uses('AppController', 'Controller');

/**
 * Rewards Controller
 *
 * @property Reward $Reward
 * @property ServerUtilityComponent $ServerUtility
 *
 * Magic Properties (for inspection):
 * @property Activity $Activity
 * @property UserItem $UserItem
 */
class RewardsController extends AppController {
    public $components = array('Paginator', 'RequestHandler', 'ServerUtility');
    public $helpers = array('Html', 'Form', 'Session', 'Js', 'Time');

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->deny();
    }

    /**
     * Accepts a reward and re-renders the inventory in response.
     *
     * Only users listed as recipients of a reward may accept it, and only once for each person. If a user tries to
     * accept it again, it will re-render their inventory but not update their items in the database.
     *
     * @param int $reward_id the id of the reward to accept
     * @broadcast reward contents
     */
    public function accept($reward_id) {

        $user_id = $this->Auth->user('user_id');

        // find the reward
        $reward = $this->Reward->RewardRecipient->find('first', array(
            'conditions' => array(
                'RewardRecipient.reward_id' => $reward_id,
                'recipient_id' => $user_id,
                'accepted = 0'
            ),
            'contain' => 'Reward.RewardDetail'
        ));

        $this->loadModel('UserItem');
        $this->loadModel('Item');

        // make sure valid reward exists
        if (!empty($reward)) {

            $this->UserItem->query('LOCK TABLES user_item WRITE, user_item as UserItem WRITE');

            $userItems = Hash::combine(
                $this->UserItem->findAllByUserId($user_id),
                '{n}.UserItem.item_id', '{n}.UserItem'
            );

            foreach ($reward['Reward']['RewardDetail'] as $detail) {

                $item_id = $detail['item_id'];
                $quantity = $detail['quantity'];

                if (empty($userItems[$item_id])) {
                    $userItems[$item_id] = array(
                        'user_id' => $user_id,
                        'item_id' => $item_id,
                        'quantity' => $quantity
                    );
                } else {
                    $userItems[$item_id]['quantity'] += $quantity;
                }
            }

            $this->UserItem->saveMany($userItems, array('atomic' => false));
            $this->UserItem->query('UNLOCK TABLES');

            $this->Reward->RewardRecipient->id = $reward['RewardRecipient']['reward_recipient_id'];
            $this->Reward->RewardRecipient->saveField('accepted', 1);

            $this->loadModel('User');
            $server = $this->User->getCurrentServer($user_id);

            // refresh user's inventory in-game
            if (!empty($server)) {
                $this->ServerUtility->broadcastRewardReceive($server, $user_id, $reward['Reward']);
            }
        }

        // re-render inventory as response
        $this->loadItems();

        $this->set(array(
            'quantity' => $this->UserItem->getByUser($user_id)
        ));

        $this->render('/Items/list.inc');
    }

    /**
     * Shows the compose reward page.
     */
    public function compose() {

        if (!$this->Access->check('Rewards')) {
            $this->redirect($this->referer());
            return;
        }

        $this->loadModel('Item');

        $this->set(array(
            'isReward' => true
        ));

        $this->activity(false);
        $this->render('/Gifts/compose');
    }

    /**
     * Shows the activity data for rewards. This is either included in the compose page or called via ajax for paging.
     *
     * @param bool $forceRender whether to force render. set to false if calling from another action
     */
    public function activity($forceRender = true) {

        $this->Paginator->settings = array(
            'Reward' => array(
                'contain' => array(
                    'RewardDetail',
                    'RewardRecipient'
                ),
                'limit' => 5
            )
        );

        $rewards = $this->Paginator->paginate('Reward');

        // organize results
        foreach ($rewards as &$reward) {

            $reward['RewardDetail'] = Hash::combine(
                $reward['RewardDetail'],
                '{n}.item_id', '{n}.quantity'
            );

            if (isset($reward['RewardRecipient'])) {
                $reward['RewardRecipient'] = Hash::extract(
                    $reward['RewardRecipient'], '{n}.recipient_id'
                );
            }
        }

        $this->addPlayers($rewards, '{n}.{s}.sender_id');
        $this->addPlayers($rewards, '{n}.RewardRecipient.{n}');

        $this->loadItems();

        $this->set(array(
            'pageModel' => 'Reward',
            'activities' => $rewards,
            'activityPageLocation' => array('controller' => 'Rewards', 'action' => 'activity')
        ));

        if ($forceRender) {
            $this->set('title', 'Reward Activity');
            $this->render('/Activity/list');
        }
    }

    /**
     * Packages the reward and shows the confirmation page.
     */
    public function package() {

        $this->request->allowMethod('post');

        if (!$this->Access->check('Rewards')) {
            $this->redirect($this->referer());
            return;
        }

        $rewardData = $this->request->data['Reward'];
        $recipientData = empty($rewardData['recipients']) ? '' : preg_split("/\s*\n\s*/", $rewardData['recipients']);

        if (empty($recipientData)) {
            $this->Session->setFlash('You did not specify any recipients.', 'default', array('class' => 'error'));
            $this->redirect(array('action' => 'compose'));
            return;
        }

        $recipients = $this->AccountUtility->resolveAccountIDs($recipientData);
        $this->addPlayers($recipients);

        $rewardDetails = $this->request->data['RewardDetail'];
        $message = empty($this->request->data['Reward']['message']) ? '' : $this->request->data['Reward']['message'];

        $user_id = $this->Auth->user('user_id');

        $this->loadModel('Item');
        $items = $this->loadItems();
        $totalValue = 0;

        foreach ($rewardDetails as $key => $detail) {

            $item_id = $detail['item_id'];
            $quantity = $detail['quantity'];

            if (empty($quantity) || $quantity < 1) {
                unset($rewardDetails[$key]);
                continue;
            }

            //Current price for estimated value
            $totalValue += $items[$item_id]['price'] * $quantity;
        }

        if(empty($rewardDetails)) {
            $this->redirect(array('action' => 'compose'));
            return;
        }

        $rewardRecipients = array();

        foreach ($recipients as $recipient) {
            $rewardRecipients[] = array(
                'recipient_id' => $recipient
            );
        }

        $this->Session->write('reward', array(
            'RewardRecipient' => $rewardRecipients,
            'Reward' => array(
                'sender_id' => $user_id,
                'message' => $message
            ),
            'RewardDetail' => $rewardDetails
        ));

        $this->set(array(
            'recipients' => $recipients,
            'data' => $rewardDetails,
            'message' => $message,
            'totalValue' => $totalValue,
            'isReward' => true
        ));

        $this->Session->setFlash('Please confirm the reward below and then click send.');
        $this->render('/Gifts/compose');
    }

    /**
     * Sends the reward to the recipients.
     */
    public function send()  {

        $this->request->allowMethod('post');

        if (!$this->Access->check('Rewards')) {
            $this->redirect($this->referer());
            return;
        }

        $reward = $this->Session->read('reward');

        if (empty($reward)) {
            $this->Session->setFlash('Oops! It appears you have not prepared a reward.', 'default', array('class' => 'error'));
            $this->redirect(array('action' => 'compose'));
            return;
        }

        $this->loadModel('Activity');

        $reward['Reward']['reward_id'] = $this->Activity->getNewId('Reward');
        $result = $this->Reward->saveAssociated($reward, array('atomic' => false));

        if (!$result['Reward'] || in_array(false, $result['RewardDetail']) || in_array(false, $result['RewardRecipient'])) {
            $this->Session->setFlash('There was an error sending the reward. Please contact an administrator', 'default', array('class' => 'error'));
        } else {
            $this->Session->setFlash("The reward has been sent! Reward number - #{$this->Reward->id}", 'default', array('class' => 'success'));
        }

        $this->Session->delete('reward');
        $this->redirect(array('action' => 'compose'));
    }
}