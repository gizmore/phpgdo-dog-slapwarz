<?php
namespace GDO\DogSlapwarz;

use GDO\Core\GDO_Module;
use GDO\Core\GDT_Int;
use GDO\Core\GDT_UInt;

final class Module_DogSlapwarz extends GDO_Module
{

    public function onLoadLanguage(): void
    {
        $this->loadLanguage('lang/slapwarz');
    }

    public function getDependencies(): array
    {
        return [
            'Dog',
        ];
    }

    public function getClasses(): array
    {
        return [
            DOG_SlapHistory::class,
            DOG_SlapItem::class,
        ];
    }

    public function getUserConfig(): array
    {
        return [
            GDT_Int::make('slapwarz_score')->initial('0'),
            GDT_UInt::make('slapwarz_slaps')->initial('0'),
            GDT_UInt::make('slapwarz_remainslaps')->initial('0'),
            GDT_UInt::make('slapwarz_slapped')->initial('0'),
        ];
    }


}
