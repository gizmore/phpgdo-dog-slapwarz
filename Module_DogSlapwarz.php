<?php
namespace GDO\DogSlapwarz;

use GDO\Core\GDO_Module;

final class Module_DogSlapwarz extends GDO_Module
{

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
        ];
    }

}
