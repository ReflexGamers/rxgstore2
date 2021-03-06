<?php
App::uses('AppController', 'Controller');

/**
 * Users Controller
 *
 * @property User $User
 * @property PaginatorComponent $Paginator
 *
 * Magic Properties (for inspection):
 * @property Activity $Activity
 * @property Order $Order
 * @property UserItem $UserItem
 */
class UsersController extends AppController {
    public $components = array('Paginator', 'RequestHandler');
    public $helpers = array('Html', 'Form', 'Js', 'Time');

    /**
     * Shows a user's profile.
     *
     * @param int $steamid the user's 64-bit steamid
     */
    public function profile($steamid) {

        $user_id = $this->AccountUtility->AccountIDFromSteamID64($steamid);

        if ($user_id === false) {
            throw new NotFoundException(__('Invalid user'));
        }

        $itemData = $this->User->getCurrentAndPastItems($user_id);

        $profileUser = Hash::extract($this->User->findByUserId($user_id, array(
            'user_id', 'credit', 'last_activity', 'ingame', 'server'
        )), 'User');

        if (!empty($profileUser)) {
            $server_ip = $profileUser['server'];

            $currentlyIngame = (
                !empty($server_ip) &&
                $profileUser['ingame'] > $this->User->getIngameTime()
            );

            $canViewServer = $this->Access->check('AdminCP', 'read');

            $this->set(array(
                'profileUser' => $profileUser,
                'currentlyIngame' => $currentlyIngame,
                'canViewServer' => $canViewServer
            ));

            if (!empty($server_ip) && $canViewServer) {
                $this->loadModel('Server');

                $server = $this->Server->find('first', array(
                    'conditions' => array('server_ip' => $server_ip),
                    'fields' => 'name'
                ));

                $serverName = Hash::get($server, 'Server.name', $server_ip);
                $this->set('serverName', $serverName);
            }
        }

        $this->addPlayers($user_id);

        $this->set(array(
            'user_id' => $user_id,
            'userItems' => $itemData['current'],
            'pastItems' => $itemData['past'],
            'totalSpent' => $this->User->getTotalSpent($user_id),
            'canImpersonate' => $this->Auth->user('impersonating') || $this->Access->check('Debug')
        ));

        $this->loadShoutbox();
        $this->loadDivisions();

        $this->reviews($steamid, false);
        $this->activity($steamid, false);
    }

    /**
     * Shows a page of reviews written by a specific user. This is usually included in the profile action or called
     * via ajax directly for pagination.
     *
     * @param int $steamid the user's 64-bit steamid
     * @param bool $forceRender whether to force render. set to false if calling from another action
     */
    public function reviews($steamid, $forceRender = true) {

        $user_id = $this->AccountUtility->AccountIDFromSteamID64($steamid);

        $this->loadModel('Rating');
        $this->Paginator->settings = $this->User->getReviewPageQuery($user_id, 3);

        $reviews = Hash::map(
            $this->Paginator->paginate('Rating'),
            '{n}', function ($arr){
                return array_merge(
                    $arr['Rating'],
                    $arr['review'],
                    $arr[0]
                );
            }
        );

        $this->addPlayers($user_id);
        $this->loadItems();

        $this->set(array(
            'user_id' => $user_id,
            'reviews' => $reviews,
            'displayType' => 'user',
            'reviewPageLocation' => array('controller' => 'Users', 'action' => 'reviews', 'id' => $steamid)
        ));

        if ($forceRender) {
            $this->set('standalone', true);
            $this->render('/Reviews/list');
        }
    }

    /**
     * Shows activity data for a specific user. This is usually included in the profile action or called via ajax
     * directly for pagination.
     *
     * @param int $steamid the user's 64-bit steamid
     * @param bool $forceRender whether to force render. set to false if calling from another action
     */
    public function activity($steamid, $forceRender = true) {

        $user_id = $this->AccountUtility->AccountIDFromSteamID64($steamid);

        $this->loadModel('Activity');
        $this->Paginator->settings = $this->Activity->getUserPageQuery(
            $user_id,
            $this->Access->check('Rewards', 'create'),
            5
        );

        $activities = $this->Activity->getRecent(
            $this->Paginator->paginate('Activity')
        );

        $this->addPlayers($activities, '{n}.{s}.user_id');
        $this->addPlayers($activities, '{n}.{s}.sender_id');
        $this->addPlayers($activities, '{n}.{s}.recipient_id');
        $this->addPlayers($activities, '{n}.RewardRecipient.{n}.recipient_id');

        $this->loadItems();
        $this->loadCashData();

        $this->set(array(
            'user_id' => $user_id,
            'activities' => $activities,
            'activityPageLocation' => array('controller' => 'Users', 'action' => 'activity', 'id' => $steamid)
        ));

        if ($forceRender) {
            $this->set('standalone', true);
            $this->render('/Activity/list');
        }
    }

    /**
     * Allows a high-level admin to login as a specific user for debugging purposes.
     *
     * @param int $steamid the user's 64-bit steamid
     */
    public function impersonate($steamid) {

        if ($this->Auth->user('impersonating') || $this->Access->check('Debug')) {
            $this->AccountUtility->loginSteamid($steamid, AccountUtilityComponent::LOGIN_IMPERSONATE);
        }

        $this->redirect(array('controller' => 'Users', 'action' => 'profile', 'id' => $steamid));
    }

    /**
     * Redirects the user to Steam for authentication. This is also the return URL for when returning from Steam. If an
     * anonymous user tries to access a page that requires authentication, they will be redirected here as per the value
     * of Auth.loginAction in the components array within AppController.php.
     */
    public function login() {

        if ($this->Auth->user('user_id')) {
            $this->redirect($this->referer());
            return;
        }

        App::import('Vendor', 'Openid');

        try {

            $openid = new LightOpenID(Router::url('/', true));

            if (!$openid->mode) {

                // begin authentication
                $this->Session->write('rememberme', isset($this->request->data['rememberme']));

                $openid->identity = 'http://steamcommunity.com/openid';
                $this->Session->write('Auth.redirect', $this->referer());
                $this->redirect($openid->authUrl());
                return;

            } else if ($openid->mode == 'cancel') {

                // user cancelled authentication
                // redirect at end of function

            } else if ($openid->validate()) {

                // authentication successful
                $oid = $openid->identity;
                $steamid = substr($oid, strrpos($oid, "/") + 1);
                $loginFlags = ($this->Session->read('rememberme')) ? AccountUtilityComponent::LOGIN_SAVE : 0;
                $this->AccountUtility->loginSteamid($steamid, $loginFlags);
            }
        } catch (ErrorException $e) {
            echo 'auth error: ' . $e->getMessage();
        }

        $this->redirect($this->Auth->redirectUrl());
    }

    /**
     * Logs the user out and redirects them to the default logout page as specified by Auth.logoutRedirect in the
     * components array within AppController.php.
     */
    public function logout() {
        $this->Cookie->delete('saved_login');
        $this->redirect($this->Auth->logout());
        return;
    }
}
