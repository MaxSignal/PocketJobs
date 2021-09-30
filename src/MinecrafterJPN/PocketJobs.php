<?php

namespace MinecrafterJPN;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\block\BlockLegacyIds;
use pocketmine\Server;

class PocketJobs extends PluginBase implements Listener
{
    /** @var Config */
    private $users;
    /** @var Config */
    private $joblist;

    public function onLoad(): void
    {
    }

    public function onEnable(): void
    {
        if (!file_exists($this->getDataFolder())) @mkdir($this->getDataFolder(), 0755, true);
        $this->users = new Config($this->getDataFolder() . "users.yml", Config::YAML);
        $this->joblist = new Config($this->getDataFolder() . "joblist.yml", Config::YAML,
            array(
                'woodcutter' => array(
                    'break' => array(
                        array(
                            'ID' => BlockLegacyIds::LOG,
                            'meta' => 0,
                            'amount' => 25
                        ),
                        array(
                            'ID' => BlockLegacyIds::LOG,
                            'meta' => 1,
                            'amount' => 25
                        ),
                        array(
                            'ID' => BlockLegacyIds::LOG,
                            'meta' => 2,
                            'amount' => 25
                        ),
                        array(
                            'ID' => BlockLegacyIds::LOG,
                            'meta' => 3,
                            'amount' => 25
                        ),
                        array(
                            'ID' => BlockLegacyIds::LOG2,
                            'meta' => 0,
                            'amount' => 25
                        ),
                        array(
                            'ID' => BlockLegacyIds::LOG2,
                            'meta' => 1,
                            'amount' => 25
                        ),
                    ),
                    'place' => array(
                        array(
                            'ID' => BlockLegacyIds::SAPLING,
                            'meta' => 0,
                            'amount' => 1
                        ),
                        array(
                            'ID' => BlockLegacyIds::SAPLING,
                            'meta' => 1,
                            'amount' => 1
                        ),
                        array(
                            'ID' => BlockLegacyIds::SAPLING,
                            'meta' => 2,
                            'amount' => 1
                        ),
                        array(
                            'ID' => BlockLegacyIds::SAPLING,
                            'meta' => 3,
                            'amount' => 1
                        ),
                        array(
                            'ID' => BlockLegacyIds::SAPLING,
                            'meta' => 4,
                            'amount' => 1
                        ),
                        array(
                            'ID' => BlockLegacyIds::SAPLING,
                            'meta' => 5,
                            'amount' => 1
                        )
                    )
                ),

                'miner' => array(
                    'break' => array(
                        array(
                            'ID' => BlockLegacyIds::STONE,
                            'meta' => 0,
                            'amount' => 3
                        ),
                        array(
                            'ID' => BlockLegacyIds::GOLD_ORE,
                            'meta' => 0,
                            'amount' => 25
                        ),
                        array(
                            'ID' => BlockLegacyIds::IRON_ORE,
                            'meta' => 0,
                            'amount' => 20
                        ),
                        array(
                            'ID' => BlockLegacyIds::LAPIS_ORE,
                            'meta' => 0,
                            'amount' => 17
                        ),
                        array(
                            'ID' => BlockLegacyIds::OBSIDIAN,
                            'meta' => 0,
                            'amount' => 9
                        ),
                        array(
                            'ID' => BlockLegacyIds::DIAMOND_ORE,
                            'meta' => 0,
                            'amount' => 80
                        ),
                        array(
                            'ID' => BlockLegacyIds::REDSTONE_ORE,
                            'meta' => 0,
                            'amount' => 10
                        ),
                        array(
                            'ID' => BlockLegacyIds::EMERALD_ORE,
                            'meta' => 0,
                            'amount' => 200
                        )
                    )
                )

            ));
        $this->users->save();
        $this->joblist->save();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDisable(): void
    {
        $this->users->save();
        $this->joblist->save();
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args): bool
    {
        if (!($sender instanceof Player)) {
            $sender->sendMessage("Must be run in the world!");
            return true;
        }

        switch ($command->getName()) {
            case "jobs":
                $subCommand = strtolower(array_shift($args));
                switch ($subCommand) {
                    case "":
                        $sender->sendMessage("/jobs my");
                        $sender->sendMessage("/jobs browse");
                        $sender->sendMessage("/jobs join <jobname>");
                        $sender->sendMessage("/jobs leave <jobname>");
                        $sender->sendMessage("/jobs info <jobname>");
                        break;

                    case "my":
                        $slot1 = is_null($s = $this->users->get($sender->getName())['slot1']) ? "empty" : $s;
                        $slot2 = is_null($s = $this->users->get($sender->getName())['slot2']) ? "empty" : $s;
                        $sender->sendMessage("Slot1: $slot1, Slot2: $slot2");
                        break;

                    case "browse":
                        foreach ($this->joblist->getAll(true) as $job) {
                            $sender->sendMessage($job);
                        }
                        break;

                    case "join":
                        if (is_null($job = array_shift($args))) {
                            $sender->sendMessage("Usage: /jobs join <jobname>");
                            return true;
                        }
                        if ($this->joblist->exists($job)) {
                            $this->joinJob($sender->getName(), $job);
                        } else {
                            $sender->sendMessage("$job not found");
                        }
                        break;

                    case "leave":
                        if (is_null($job = array_shift($args))) {
                            $sender->sendMessage("Usage: /jobs leave <jobname>");
                            return true;
                        }
                        if ($this->joblist->exists($job)) {
                            $this->leaveJob($sender->getName(), $job);
                        } else {
                            $sender->sendMessage("$job not found");
                        }
                        break;

                    case "info":
                        if (is_null($job = array_shift($args))) {
                            $sender->sendMessage("Usage: /jobs info <jobname>");
                            return true;
                        }
                        if ($this->joblist->exists($job)) {
                            $this->infoJob($sender->getName(), $job);
                        } else {
                            $sender->sendMessage("$job not found");
                        }
                        break;
                }
                break;
        }
        return true;
    }

    public function onPlayerJoin(PlayerJoinEvent $event)
    {
        $name = $event->getPlayer()->getName();
        if (!$this->users->exists($name)) {
            $this->users->set($name, array('slot1' => null, 'slot2' => null));
            $this->users->save();
        }
    }

    public function onPlayerBreakBlock(BlockBreakEvent $event)
    {
        $this->workCheck("break", $event->getPlayer()->getName(), $event->getBlock()->getId(), $event->getBlock()->getMeta());
    }

    public function onPlayerPlaceBlock(BlockPlaceEvent $event)
    {
        $this->workCheck("place", $event->getPlayer()->getName(), $event->getBlock()->getId(), $event->getBlock()->getMeta());
    }

    private function workCheck($type, $username, $id, $meta)
    {
        print($id);
        foreach ($this->joblist->getAll() as $jobname => $jobinfo) {
            if (isset($jobinfo[$type])) {
                foreach ($jobinfo[$type] as $detail) {
                    if ($detail['ID'] === $id and $detail['meta'] === $meta) {
                        $amount = $detail['amount'];
                           $slots = $this->users->get($username);
                        if ($slots['slot1'] === $jobname || $slots['slot2'] === $jobname) {
                            $this->getServer()->getPluginManager()->getPlugin("PocketMoney")->grantMoney($username, $amount);
                        }
                    }
                }
            }
        }
    }

    private function joinJob($username, $job)
    {
        $slots = $this->users->get($username);
        if ($slots['slot1'] === $job || $slots['slot2'] === $job) {
            $this->getServer()->getPlayerByPrefix($username)->sendMessage("You have been already part of $job");
            return;
        }
        if (isset($slots['slot1'])) {
            if (isset($slots['slot2'])) {
                $this->getServer()->getPlayerByPrefix($username)->sendMessage("Your job slot is full");
            } else {
                $this->users->set($username, array(
                    'slot1' => $slots['slot1'],
                    'slot2' => $job
                ));
                $this->users->save();
                $this->getServer()->getPlayerByPrefix($username)->sendMessage("Set $job to your job slot2");
            }
        } else {
            $this->users->set($username, array(
                'slot1' => $job,
                'slot2' => $slots['slot2']
            ));
            $this->users->save();
            $this->getServer()->getPlayerByPrefix($username)->sendMessage("Set $job to your job slot1");
        }
    }

    private function leaveJob($username, $job)
    {
        $slots = $this->users->get($username);
        if ($slots['slot1'] === $job) {
            $this->users->set($username, array(
                'slot1' => null,
                'slot2' => $slots['slot2']
            ));
            $this->users->save();
            $this->getServer()->getPlayerByPrefix($username)->sendMessage("Remove $job from your job slot1");
        } elseif ($slots['slot2'] === $job) {
            $this->users->set($username, array(
                'slot1' => $slots['slot1'],
                'slot2' => null
            ));
            $this->users->save();
            $this->getServer()->getPlayerByPrefix($username)->sendMessage("Remove $job from your job slot2");
        } else {
            $this->getServer()->getPlayerByPrefix($username)->sendMessage("You are not part of $job");
        }
    }

    private function infoJob($username, $job)
    {
        foreach ($this->joblist->getAll(true) as $aJob) {
            if ($aJob === $job) {
                $info = $this->joblist->get($job);
                foreach ($info as $type => $detail) {
                    foreach ($detail as $value) {
                        $id = $value['ID'];
                        $meta = $value['meta'];
                        $amount = $value['amount'];
                        $this->getServer()->getPlayerByPrefix($username)->sendMessage("$type $id:$meta $amount");
                    }
                }
            }
        }
    }
}