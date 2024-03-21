<?php
namespace GDO\DogSlapwarz\Method;

use GDO\Core\GDT;
use GDO\Dog\DOG_Command;
use GDO\Dog\DOG_Message;
use GDO\Dog\DOG_User;
use GDO\Dog\GDT_DogUser;
use GDO\DogSlapwarz\DOG_SlapHistory;
use GDO\DogSlapwarz\Module_DogSlapwarz;
use GDO\User\GDO_User;
use GDO\Util\Random;

final class Slap extends DOG_Command
{

    public function gdoParameters(): array
    {
        return [
            GDT_DogUser::make('target')->sameRoom()->positional(),
        ];
    }


    private function getRandomItem(array $items): array
    {
        return Random::randomItem($items);
    }

    public function dogExecute(DOG_Message $message, ?DOG_User $user): GDT
    {
        $fake = $user === null; # Invalid target


        DOG_SlapHistory::generate(GDO_User::current(), $user->getGDOUser(), $message->text);



    }

    private function applyDamage(float $damage, float $dmg): float
    {

    }


}
