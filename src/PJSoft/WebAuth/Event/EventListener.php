<?php
    
    namespace PJSoft\WebAuth\Event;
    
    use PJSoft\WebAuth\Main;
    use pocketmine\event\Listener;
    use pocketmine\event\player\PlayerCommandPreprocessEvent;
    use pocketmine\event\player\PlayerJoinEvent;
    use pocketmine\event\player\PlayerLoginEvent;
    use pocketmine\event\player\PlayerMoveEvent;
    use pocketmine\event\server\DataPacketReceiveEvent;
    use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
    
    class EventListener implements Listener
    {
        private $plugin;
        
        public function __construct(Main $plugin)
        {
            $this->plugin = $plugin;
        }
        
        public function onDataPacketReceive(DataPacketReceiveEvent $event): void
        {
            $player = $event->getPlayer();
            $name = $player->getName();
            $packet = $event->getPacket();
            if ( !$packet instanceof ModalFormResponsePacket ) {
                return;
            }
            $formId = $packet->formId;
            $formData = json_decode($packet->formData, true);
            //print_r($formId);
            //print_r($formData);
            //print_r($this->plugin->getFormId(0));
            switch ( $formId ) {
                case $this->plugin->getFormId(0):
                    //code
                    if ( $this->plugin->getDataConfig()->get($name) ) {
                        $this->plugin->sendTextForm($player, "すでに認証されています");
                        return;
                    }
                    if ( $formData === null ) {
                        return;
                    }
                    $auth_code = $formData[1];
                    if ( preg_match("/^[a-zA-Z0-9]+$/", $auth_code) !== 1 ) {
                        $content = array(
                            array(
                                "type" => "label",
                                "text" => "認証コードには 半角英数字 しか使用できません\n\n§l§c認証コード取得ページで取得した認証コードを入力してください",
                            ),
                            array(
                                "type" => "input",
                                "text" => "認証コード",
                                "placeholder" => "ここに入力",
                            ),
                        );
                        $this->plugin->sendCustomForm($player, $content, $this->plugin->getFormId(0));
                        return;
                    }
                    if ( $this->plugin->authCodeCheck($player, $auth_code) ) {
                        //ok
                        $this->plugin->getDataConfig()->set($name, true);
                        $player->setImmobile(false);
                        $this->plugin->sendTextForm($player, "認証が完了しました");
                    } else {
                        //ng
                        $content = array(
                            array(
                                "type" => "label",
                                "text" => "認証コードが違います\n\n§l§c認証コード取得ページで取得した認証コードを入力してください",
                            ),
                            array(
                                "type" => "input",
                                "text" => "認証コード",
                                "placeholder" => "ここに入力",
                            ),
                        );
                        $this->plugin->sendCustomForm($player, $content, $this->plugin->getFormId(0));
                        return;
                    }
                    break;
                case $this->plugin->getFormId(1):
                    //add
                    if ( $formData === null ) {
                        return;
                    }
                    $auth_name = $formData[1];
                    $this->plugin->getDataConfig()->set($auth_name, true);
                    foreach ( $this->plugin->getServer()->getOnlinePlayers() as $onlinePlayer ) {
                        $name = $player->getName();
                        if ( $this->plugin->getDataConfig()->get($name) ) {
                            $onlinePlayer->setImmobile(false);
                            $onlinePlayer->sendMessage(Main::SYSTEM_TAG . "強制的に認証されました");
                        }
                    }
                    $this->plugin->sendTextForm($player, "{$auth_name} を強制的に認証しました");
                    break;
            }
        }
        
        public function onCommandPreprocess(PlayerCommandPreprocessEvent $event): void
        {
            $player = $event->getPlayer();
            $name = $player->getName();
            $command = explode(" ", $event->getMessage());
            //print_r($command);
            if ( !$this->plugin->getDataConfig()->get($name) ) {
                if ( $command[0] === "/auth" ) {
                    return;
                }
                if ( !in_array($command[0], $this->plugin->getPluginConfig()->get("no-check-commands")) ) {
                    $player->sendMessage(Main::ERROR_TAG . "このコマンドは認証後に使用できます");
                    $event->setCancelled();
                }
            }
        }
        
        public function onLogin(PlayerLoginEvent $event): void
        {
            $player = $event->getPlayer();
            $name = $player->getName();
            if ( !$this->plugin->getDataConfig()->exists($name) ) {
                $this->plugin->getDataConfig()->set($name, false);
            }
        }
        
        public function onJoin(PlayerJoinEvent $event): void
        {
            $player = $event->getPlayer();
            $name = $player->getName();
            if ( !$this->plugin->getDataConfig()->get($name) ) {
                if ( $player->isOp() ) {
                    $this->plugin->getDataConfig()->set($name, true);
                    $player->sendMessage(Main::SYSTEM_TAG . "オペレーターのため認証を自動的に行いました");
                    return;
                }
                $player->sendMessage(Main::SYSTEM_TAG . "認証が必要です。/auth codeを実行して認証してください。");
                $player->setImmobile(true);
            } else {
                $player->setImmobile(false);
            }
        }
        
        public function onMove(PlayerMoveEvent $event): void
        {
            $player = $event->getPlayer();
            $name = $player->getName();
            if ( !$this->plugin->getDataConfig()->get($name) ) {
                $event->setCancelled();
            }
        }
    }