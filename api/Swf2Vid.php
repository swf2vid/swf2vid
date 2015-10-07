<?php
/**
 * Created by PhpStorm.
 * User: san
 * Date: 9/11/2015
 * Time: 12:28 AM
 */

namespace Api {

    use Exception;
    use Guzzle\Http\Exception\ClientErrorResponseException;
    use GuzzleHttp\Client;
    use GuzzleHttp\Exception\ClientException;
    use Minute\App\App;
    use StdClass;

    class Swf2Vid
    {

        const API_ROOT = 'private/api_keys/swf2vid';
        protected $client;

        function __construct($login = '', $password = '')
        {
            if (empty($login)) {
                $config = App::getInstance()->config;
                $login = $config->getKey(self::API_ROOT . "/login");
                $password = $config->getKey(self::API_ROOT . "/password");
            }

            if (!empty($login) && !empty($password)) {
                $this->client = new Client(['base_url' => 'http://www.swf2vid.com/', 'defaults' => ['auth' => [$login, $password], 'headers' => ['Accept' => 'application/json']]]);
            } else {
                throw new Swf2VidError("Login and Password are required");
            }
        }

        /**
         * @param int $project_id
         * @param string $player_url
         * @param string $project_url
         * @param string $callback_url
         *
         * @return bool|int
         * @throws Swf2VidError
         */
        public function addVideo($project_id, $player_url = '', $project_url = '', $callback_url = '')
        {
            /** @var StdClass $yt_fallback */

            $host = App::getInstance()->getSelfHost();
            $player_url = $player_url ?: $this->getDefaultPlayerURL();
            $project_url = $project_url ?: $this->getDefaultProjectURL($project_id);
            $callback_url = $callback_url ?: sprintf('%s/callback/video/%s', $host, $project_id);

            try {
                $req = $this->client->post('members/add-video', ['body' => compact('project_id', 'player_url', 'project_url', 'callback_url')]);
                $res = $req->getBody();

                $result = json_decode($res);

                return @$result->video_id ?: false;
            } catch (Exception $e) {
                throw new Swf2VidError(sprintf("Add video: %s", $e instanceof ClientException ? $e->getResponse()->getReasonPhrase() : $e->getMessage()));
            }
        }

        /**
         * @param int $project_id
         * @param string $status
         *
         * @return bool|StdClass
         * @throws Swf2VidError
         */
        public function updateVideoStatus($project_id, $status)
        {
            try {
                $req = $this->client->post("members/update-video/$project_id", ['body' => ['status' => $status]]);
                $res = $req->getBody();

                return json_decode($res);
            } catch (Exception $e) {
                throw new Swf2VidError(sprintf("Update video: %s", $e instanceof ClientException ? $e->getResponse()->getReasonPhrase() : $e->getMessage()));
            }
        }

        /**
         * @param int $project_id
         *
         * @return bool|StdClass
         * @throws Swf2VidError
         */
        public function getVideoDetails($project_id)
        {
            try {
                $req = $this->client->get("members/get-video/$project_id");
                $result = json_decode($req->getBody());

                return @$result->video ?: false;
            } catch (Exception $e) {
                throw new Swf2VidError(sprintf("Get video: %s", $e instanceof ClientException ? $e->getResponse()->getReasonPhrase() : $e->getMessage()));
            }
        }

        /**
         * @param int $project_id
         * @param string $player_url (optional)
         * @param string $project_url (optional)
         *
         * @return string
         */
        public function getVideoPreviewURL($project_id, $player_url = '', $project_url = '')
        {
            $settings['player_url'] = $player_url ?: $this->getDefaultPlayerURL();
            $settings['project_url'] = $project_url ?: $this->getDefaultProjectURL($project_id);

            return sprintf("http://www.swf2vid.com/api/player?%s", http_build_query($settings));
        }

        private function getDefaultPlayerURL()
        {
            $app = App::getInstance();

            return $app->config->getKey(self::API_ROOT . '/default_player', sprintf('%s/static/swf/player/player.swf', $app->getSelfHost()));
        }

        private function getDefaultProjectURL($project_id)
        {
            return sprintf('%s/members/projects/data/%d', App::getInstance()->getSelfHost(), $project_id ?: 6);
        }
    }
}