<?php
    
    namespace PJSoft\WebAuth;
    
    use PJSoft\WebAuth\Command\AuthCommand;
    use PJSoft\WebAuth\Event\EventListener;
    use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
    use pocketmine\Player;
    use pocketmine\plugin\PluginBase;
    use pocketmine\utils\Config;
    
    class Main extends PluginBase
    {
        const SYSTEM_TAG = "§l§bSYSTEM §8>> §r§6";
        const ERROR_TAG = "§l§4ERROR §8>> §r§4";
        const TITLE = "§l§6WEBAUTH";
        const NO_RET_FORM_ID = PHP_INT_MAX;
        
        private $formId;
        private $config;
        private $data;
        
        protected function onEnable(): void
        {
            $this->getLogger()->info("{$this->getDescription()->getName()} {$this->getDescription()->getVersion()}が有効になりました");
            $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
            $this->getServer()->getCommandMap()->register("auth", new AuthCommand($this));
            $this->initFormId(3);
            if ( file_exists($this->getDataFolder()) ) {
                @mkdir($this->getDataFolder(), 0777);
            }
            $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array(
                "auth-id" => "put-in-auth-id",
                "strength" => 5,
                "no-check-commands" => array(
                    "/register",
                    "/login",
                ),
            ));
            $this->data = new Config($this->getDataFolder() . "players.json", Config::JSON);
        }
        
        protected function onDisable(): void
        {
            $this->getDataConfig()->save();
            $this->getLogger()->info("{$this->getDescription()->getName()} {$this->getDescription()->getVersion()}が無効になりました");
        }
        
        private function initFormId(int $amount): void
        {
            for ( $i = 0; $i < $amount; $i++ ) {
                $this->formId[$i] = mt_rand(10000000, 1000000000);
            }
        }
        
        public function authCodeCheck(Player $player, string $auth_code): bool
        {
            $address = $player->getAddress();
            $config = $this->getPluginConfig();
            $ok_auth_code = substr(md5($address . $config->get("auth-id")), 0, $config->get("strength"));
            if ( $ok_auth_code === $auth_code ) {
                return true;
            } else {
                return false;
            }
        }
        
        public function getPluginConfig(): Config
        {
            return $this->config;
        }
        
        public function getDataConfig(): Config
        {
            return $this->data;
        }
        
        public function getFormId(int $number): ?int
        {
            if ( isset($this->formId[$number]) ) {
                return $this->formId[$number];
            } else {
                return null;
            }
        }
        
        public function sendTextForm(Player $player, string $text): void
        {
            $form = new ModalFormRequestPacket();
            $form->formId = self::NO_RET_FORM_ID;
            $form->formData = json_encode(array(
                "type" => "form",
                "title" => self::TITLE,
                "content" => $text,
                "buttons" => array(
                    array(
                        "text" => "閉じる",
                    ),
                ),
            ));
            $player->sendDataPacket($form);
        }
        
        public function sendCustomForm(Player $player, array $content, int $formId): void
        {
            $form = new ModalFormRequestPacket();
            $form->formId = $formId;
            $form->formData = json_encode(array(
                "type" => "custom_form",
                "title" => self::TITLE,
                "content" => $content,
            ));
            $player->sendDataPacket($form);
        }
    }