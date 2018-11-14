<?php
    
    namespace PJSoft\WebAuth\Command;
    
    use PJSoft\WebAuth\Main;
    use pocketmine\command\Command;
    use pocketmine\command\CommandSender;
    use pocketmine\Player;
    
    class AuthCommand extends Command
    {
        private $plugin;
        
        public function __construct(Main $plugin)
        {
            $name = "auth";
            $description = "認証する";
            $usageMessage = "/auth [code:add]";
            $aliases = array();
            parent::__construct($name, $description, $usageMessage, $aliases);
            $this->setPermission("webauth.command.auth");
            $this->plugin = $plugin;
        }
        
        public function execute(CommandSender $sender, string $commandLabel, array $args): bool
        {
            if ( !$sender instanceof Player ) {
                $sender->sendMessage(Main::ERROR_TAG . "このコマンドはプレイヤーのみ実行できます");
                return true;
            } else {
                $player = $sender;
                $name = $player->getName();
            }
            if ( !isset($args[0]) ) {
                $sender->sendMessage(Main::ERROR_TAG . "使用方法：{$this->getUsage()}");
                return true;
            }
            switch ( $args[0] ) {
                case "code":
                    //code
                    if ($this->plugin->getDataConfig()->get($name)) {
                        $this->plugin->sendTextForm($player, "すでに認証されています");
                        return true;
                    }
                    $content = array(
                        array(
                            "type" => "label",
                            "text" => "§l§c認証コード取得ページで取得した認証コードを入力してください",
                        ),
                        array(
                            "type" => "input",
                            "text" => "認証コード",
                            "placeholder" => "ここに入力",
                        ),
                    );
                    $this->plugin->sendCustomForm($player, $content, $this->plugin->getFormId(0));
                    return true;
                case "add":
                    //add
                    if ( !$player->isOp() ) {
                        $this->plugin->sendTextForm($player, "このコマンドはオペレーターのみ実行できます");
                        return true;
                    }
                    $content = array(
                        array(
                            "type" => "label",
                            "text" => "§l§c強制的に認証したい名前を入力してください",
                        ),
                        array(
                            "type" => "input",
                            "text" => "認証したいプレイヤー名",
                            "placeholder" => "ここに入力",
                        ),
                    );
                    $this->plugin->sendCustomForm($player, $content, $this->plugin->getFormId(1));
                    return true;
                /*case "del":
                    //del
                    if ( !$player->isOp() ) {
                        $this->plugin->sendTextForm($player, "このコマンドはオペレーターのみ実行できます");
                        return true;
                    }
                    return true;*/
                default:
                    $player->sendMessage(Main::ERROR_TAG . "使用方法：{$this->getUsage()}");
                    return true;
            }
        }
        
    }