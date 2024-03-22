<?php
namespace GDO\DogSlapwarz;

use GDO\Core\Application;
use GDO\Core\GDO;
use GDO\Core\GDO_DBException;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_Int;
use GDO\Core\GDT_Object;
use GDO\Core\GDT_String;
use GDO\Core\GDT_UInt;
use GDO\Date\Time;
use GDO\Dog\DOG_User;
use GDO\User\GDO_User;
use GDO\User\GDT_User;
use GDO\Util\Random;

final class DOG_SlapHistory extends GDO
{

    public bool $fake = false;

    public function fake(bool $fake=true): self
    {
        $this->fake = $fake;
        return $this;
    }

    public float $remain = 0;

    public function remain(float $remain): self
    {
        $this->remain = $remain;
        return $this->fake();
    }

    public string $targetString = '';

    public function targetString(string $targetString): self
    {
        $this->targetString = $targetString;
        return $this;
    }


    /**
     * @throws GDO_DBException
     */
    public static function maySlapMore(GDO_User $user, ?GDO_User $target, float $timeout, int $maxSlaps): ?int
    {
        if (!$target)
        {
            return null;
        }
        $stimeout = Time::getDate( Application::$MICROTIME-$timeout);
        $data = self::table()->select('COUNT(*), MIN(slap_created)')->first()
            ->where("slap_user={$user->getID()} AND slap_target={$target->getID()} AND slap_created>'$stimeout'")
            ->exec()->fetchRow();
        list($oldSlaps, $time) = $data;
        if ($oldSlaps < $maxSlaps)
        {
            return null;
        }
        $time = Time::getTimestamp($time);
        return ceil($time + $timeout - Application::$MICROTIME);
    }

    /**
     * @throws GDO_DBException
     */
//    public static function maySlap(GDO_User $user, ?GDO_User $target, int $timeout): ?int
//    {
//        if (!$target)
//        {
//            return null;
//        }
//
//        $timeout = Time::getDate( Application::$MICROTIME-$timeout);
//        $oldSlap = self::table()->select()->first()
//            ->where("slap_user={$user->getID()} AND slap_target={$target->getID()} AND slap_created>'{$timeout}'")
//            ->order('slap_created ASC')->exec()->fetchObject();
//
//        if (!$oldSlap)
//        {
//            return null;
//        }
//
//        $last_date = $oldSlap->gdoVar('slap_created');
//        $time = Time::getTimestamp($last_date);
//        $remain = ceil($time + $timeout - Application::$MICROTIME);
//        if ($remain > 0)
//        {
//            return $remain;
//        }
//        return null;
//    }

    public function isFake(): bool
    {
        return $this->fake;
    }

    public function gdoColumns(): array
    {
        return [
            GDT_AutoInc::make('slap_id'),
            GDT_User::make('slap_user')->notNull(),
            GDT_User::make('slap_target')->notNull(),
            GDT_Int::make('slap_damage')->bytes(2)->notNull(),
            GDT_Object::make('slap_adverb')->table(DOG_SlapItem::table())->notNull(),
            GDT_Object::make('slap_verb')->table(DOG_SlapItem::table())->notNull(),
            GDT_Object::make('slap_adjective')->table(DOG_SlapItem::table())->notNull(),
            GDT_Object::make('slap_item')->table(DOG_SlapItem::table())->notNull(),
            GDT_CreatedAt::make('slap_created'),
        ];
    }

    public function getUser(): GDO_User
    {
        return $this->gdoValue('slap_user');
    }

    public function getTarget(): GDO_User
    {
        return $this->gdoValue('slap_target');
    }

    public function getDamage(): int
    {
        return $this->gdoValue('slap_damage');
    }

    public static function getSlaps(): array
    {
        static $slaps = null;
        if (!$slaps)
        {
            $path = Module_DogSlapwarz::instance()->filePath('slaps.php');
            $slaps = require $path;
        }
        return $slaps;
    }

    public static function generate(GDO_User $slapper, ?GDO_User $target, string $targetString): self
    {
        $fake = $target === null;

        $slaps = self::getSlaps();
        $damage = 10000;
        list($adverb, $dmg_adv) = Random::randomItem($slaps['adverbs']);
        $damage = self::applyDamage($damage, $dmg_adv);
        list($verb, $dmg_verb) = Random::randomItem($slaps['verbs']);
        $damage = self::applyDamage($damage, $dmg_verb);
        list($adjective, $dmg_adj) = Random::randomItem($slaps['adjectives']);
        $damage = self::applyDamage($damage, $dmg_adj);
        list($item, $dmg_item) = Random::randomItem($slaps['items']);
        $damage = self::applyDamage($damage, $dmg_item);
        $damage = round($damage);

        $slap = self::blank([
            'slap_user' => $slapper->getID(),
            'slap_target' => $target->getID()?:null,
            'slap_damage' => $damage,
            'slap_adverb' => DOG_SlapItem::getOrCreate('adverb', $adverb)->getID(),
            'slap_verb' => DOG_SlapItem::getOrCreate('verb', $verb)->getID(),
            'slap_adjective' => DOG_SlapItem::getOrCreate('adjective', $adjective)->getID(),
            'slap_item' => DOG_SlapItem::getOrCreate('item', $item)->getID(),
        ]);

        $slap->fake($fake);

        if ($fake)
        {
            $slap->targetString($targetString);
        }

        return $slap;
    }

    private static function applyDamage(float $damage, float $factor): float
    {
        return $damage * (($factor - 10.0) / 100.0);
    }

    /**
     * @return string    'msg_dog_slaps' => '%s %s %s %s with %s %s.',

     */
    public function renderHTML(): string
    {
        $msg = t('msg_dog_slaps', [
            $this->getUser()->renderUserName(),
            $this->gdoValue('slap_adverb')->renderName(),
            $this->gdoValue('slap_verb')->renderName(),
            $this->getTargetString(),
            $this->gdoValue('slap_adjective')->renderName(),
            $this->gdoValue('slap_item')->renderName(),
        ]);
        if ($this->remain > 0)
        {
            $msg .= ' ' . t('msg_dog_slap_remain', [$this->getDamage(), Time::humanDuration($this->remain)]);
        }

        if (!$this->isFake())
        {
            $msg .= ' ' . t('msg_dog_slap_score', [$this->getDamage()]);
        }
        return $msg;
    }

    public function renderCLI(): string
    {
        return $this->renderHTML();
    }

    private function getTargetString(): string
    {
        return $this->targetString?:$this->getTarget()->renderUserName();

    }

}
