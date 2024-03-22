<?php
namespace GDO\DogSlapwarz;

use GDO\Core\GDO;
use GDO\Core\GDO_DBException;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_EnumNoI18n;
use GDO\Core\GDT_Name;
use GDO\Core\GDT_UInt;

final class DOG_SlapItem extends GDO
{

     public function gdoColumns(): array
    {
        return [
            GDT_AutoInc::make('si_id'),
            GDT_EnumNoI18n::make('si_type')->notNull()->enumValues('adverb', 'verb', 'adjective', 'item'),
            GDT_Name::make('si_name')->notNull(),
            GDT_UInt::make('si_uses')->notNull()->initial('0'),
            GDT_UInt::make('si_fake_uses')->notNull()->initial('0'),
        ];
    }

    public function renderName(): string
    {
        return $this->gdoVar('si_name');
    }

    /**
     * @throws GDO_DBException
     */
    public static function getOrCreate(string $type, string $name): self
    {
        $item = self::getByVars([
            'si_type' => $type,
            'si_name' => $name,
        ]);
        if (!$item)
        {
            $item = self::blank([
                'si_type' => $type,
                'si_name' => $name,
            ])->insert();
        }
        return $item;
    }

    /**
     * @throws GDO_DBException
     */
    public static function countSlap(DOG_SlapHistory $slap): void
    {
        $counter = $slap->isFake() ? 'si_fake_uses' : 'si_uses';
        $slap->gdoValue('slap_adverb')->increase($counter);
        $slap->gdoValue('slap_verb')->increase($counter);
        $slap->gdoValue('slap_adjective')->increase($counter);
        $slap->gdoValue('slap_item')->increase($counter);
    }

}
