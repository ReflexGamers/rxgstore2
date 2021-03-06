<?php

/**
 * SteamPlayer Model
 *
 * @property SteamPlayerCache $SteamPlayerCache
 */
class SteamPlayer extends AppModel {
    public $useDbConfig = 'steam';
    public $hasOne = 'SteamPlayerCache';

    /**
     * Gets player data for all of the players in the provided list of steamids. If all of the players are saved in the
     * cache and have not expired according to Store.SteamCache.Duration, the data will be pulled from the cache. If
     * not, all players will be re-fetched with the Steam API and saved again. This aggressive approach ensures fewer
     * calls to the Steam API overall, but the calls that do happen may take longer.
     *
     * @param array $accounts list of signed 32-bit steamids (user_id)
     * @param int $timeout optional number of seconds for which to limit the request
     * @return array of player data in no particular order
     */
    public function getPlayers($accounts, $timeout = 30) {

        $cache = $this->SteamPlayerCache->getPlayers($accounts);

        $countDesired = count($accounts);
        $countFound = count($cache);

        if (!empty($cache) && $countFound == $countDesired) {

            CakeLog::write('steam', "Fetched $countDesired players from the Steam cache.");
            return Hash::extract($cache, '{n}.SteamPlayerCache');

        } else {

            $beginTime = microtime(true);
            $steamPlayers = $this->SteamPlayerCache->refreshPlayers($accounts, false, $timeout);
            $endTime = microtime(true);

            $timeTaken = number_format($endTime - $beginTime, 3);
            CakeLog::write('steam', "Tried to fetch $countDesired players from the Steam cache but found only $countFound. Steam API used. Took $timeTaken seconds.");

            if (empty($steamPlayers)) {
                CakeLog::write('steam', 'Steam API provided no response or did not respond in time. Cached players used.');
                return Hash::extract($cache, '{n}.SteamPlayerCache');
            }

            return $steamPlayers;
        }
    }

    /**
     * Gets a single player from the cache by steamid.
     *
     * @param int $user_id the signed 32-bit steamid (user_id) of the player
     * @return array of player data for the specified steamid
     */
    public function getPlayer($user_id) {
        $players = $this->find('all', array(
            'conditions' => array(
                'user_id' => array($user_id)
            )
        ))['SteamPlayers'];

        if (empty($players)) return array();

        $player = $players[0];
        $player['name'] = $player['personaname'];

        return $player;
    }

    /**
     * Sets and returns schema info for Steam API.
     *
     * @param bool $field
     * @return array
     */
    function schema($field = false) {
        $this->_schema = array(
            'steamid' => array('type' => 'String'),
            'personaname' => array('type' => 'String'),
            'profileurl' => array('type' => 'String'),
            'avatar' => array('type' => 'String'),
            'avatarmedium' => array('type' => 'String'),
            'avatarfull' => array('type' => 'String'),
            'personastate' => array('type' => 'integer'),
            'communityvisibilitystate' => array('type' => 'integer'),
            'profilestate' => array('type' => 'integer'),
            'lastlogoff' => array('type' => 'integer'),
            'commentpermission' => array('type' => 'integer'),
            'realname' => array('type' => 'String'),
            'primaryclanid' => array('type' => 'String'),
            'timecreated' => array('type' => 'integer'),
            'gameid' => array('type' => 'integer'),
            'gameserverip' => array('type' => 'String'),
            'gameextrainfo' => array('type' => 'String'),
            'cityid' => array('type' => 'String'),
            'loccountrycode' => array('type' => 'String'),
            'locstatecode' => array('type' => 'String'),
            'loccityid' => array('type' => 'String'),
        );
        return $this->_schema;
    }

}