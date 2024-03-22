<?php
namespace GDO\DogSlapwarz\Method;

use GDO\Core\GDT;
use GDO\Core\GDT_Name;
use GDO\Core\Method;

final class Info extends Method
{

    public function gdoParameters(): array
    {
        return [
            GDT_Name::make('name')->notNull(),
        ];
    }

    public function execute(): GDT
    {
    }


}
