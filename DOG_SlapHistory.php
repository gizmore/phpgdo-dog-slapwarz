<?php
namespace GDO\DogSlapwarz;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_Int;
use GDO\Core\GDT_String;
use GDO\Core\GDT_UInt;
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
            GDT_String::make('slap_adverb')->max(64)->notNull(),
            GDT_String::make('slap_verb')->max(64)->notNull(),
            GDT_String::make('slap_adjective')->max(64)->notNull(),
            GDT_String::make('slap_item')->max(64)->notNull(),
            GDT_CreatedAt::make('slap_created'),
        ];
    }

    public function getUser(): GDO_User
    {
        return $this->gdoValue('slap_user');
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
            'slap_adverb' => $adverb,
            'slap_verb' => $verb,
            'slap_adjective' => $adjective,
            'slap_item' => $item,
            'targetstring' => $targetString,
        ]);

        $slap->fake($fake);

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
            $this->gdoVar('targetstring'),
        ]);
        return $msg;
    }


}
