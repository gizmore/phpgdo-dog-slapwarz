<?php
namespace GDO\DogSlapwarz\Method;

use GDO\Core\GDO_DBException;
use GDO\Core\GDT;
use GDO\Core\GDT_Response;
use GDO\Core\GDT_UInt;
use GDO\Date\GDT_Duration;
use GDO\Dog\DOG_Command;
use GDO\Dog\DOG_Message;
use GDO\Dog\DOG_User;
use GDO\Dog\GDT_DogUser;
use GDO\DogSlapwarz\DOG_SlapHistory;
use GDO\DogSlapwarz\DOG_SlapItem;
use GDO\User\GDO_User;
use GDO\Util\Random;

final class Slap extends DOG_Command
{

    public function getCLITrigger(): string
    {
        return 'slap';
    }

    public function isPrivateMethod(): bool
    {
        return false;
    }

    public function gdoParameters(): array
    {
        return [
            GDT_DogUser::make('target')->sameRoom()->positional(),
        ];
    }

    public function getConfigRoom(): array
    {
        return [
            GDT_Duration::make('timeout')->notNull()->initial('1d'), # one slap per day
            GDT_UInt::make('max_slaps')->notNull()->min(1)->initial('1'),  # one slap per user combi
            GDT_UInt::make('remainslap_score')->notNull()->initial('50'), # malus for remainslap
        ];
    }


    private function getRandomItem(array $items): array
    {
        return Random::randomItem($items);
    }

    /**
     * @throws GDO_DBException
     */
    public function dogExecute(DOG_Message $message, ?DOG_User $target): GDT
    {
        $user = GDO_User::current();

        $slap = DOG_SlapHistory::generate($user, $target->getGDOUser(), $message->text);

        # Check if a non record slap
        if (null !== ($remain = Dog_SlapHistory::maySlapMore($user, $target->getGDOUser(), $this->cfgTimeout($message), $this->cfgMaxSlaps($message))))
        {
            $slap->remain($remain);
            $slap->setVar('slap_damage', -$this->cfgRemainMalus($message));
            $user->increaseSetting('DogSlapwarz', 'slapwarz_score', -$this->cfgRemainMalus($message));
            $user->increaseSetting('DogSlapwarz', 'slapwarz_remainslaps', 1);
        }

        DOG_SlapItem::countSlap($slap);

        if (!$slap->isFake())
        {
            $slap->insert();
            $user->increaseSetting('DogSlapwarz', 'slapwarz_score', $slap->getDamage());
            $target->getGDOUser()->increaseSetting('DogSlapwarz', 'slapwarz_score', -$slap->getDamage());
            $user->increaseSetting('DogSlapwarz', 'slapwarz_slaps', 1);
            $target->getGDOUser()->increaseSetting('DogSlapwarz', 'slapwarz_slapped', 1);
        }

        $message->reply($slap->render());

        return GDT_Response::make();
    }


    private function cfgTimeout(DOG_Message $message): float
    {
        return $this->getConfigValueRoom($message->room, 'timeout');
    }

    private function cfgMaxSlaps(DOG_Message $message): int
    {
        return $this->getConfigValueRoom($message->room, 'max_slaps');
    }

    private function cfgRemainMalus(DOG_Message $message): int
    {
        return $this->getConfigValueRoom($message->room, 'remainslap_score');
    }

}
